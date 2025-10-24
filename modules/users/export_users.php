<?php
/**
 * Export Users to CSV
 * Supports filtered results or selected users
 */

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';

start_secure_session();

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] === 'member') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate inputs
 $exportType = isset($_POST['export_type']) ? trim($_POST['export_type']) : 'filtered'; // 'filtered' or 'selected'
 $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
 $filters = isset($_POST['filters']) ? $_POST['filters'] : [];

// Sanitize user IDs if provided
if ($exportType === 'selected' && !empty($userIds)) {
    $userIds = array_map('intval', $userIds);
    $userIds = array_filter($userIds, function($id) { return $id > 0; });
}

try {
    // Build query based on export type
    $sql = "SELECT u.*, 
                   p.province_name,
                   d.diocese_name,
                   a.archdeaconry_name,
                   dn.deanery_name,
                   pr.parish_name
            FROM users u
            LEFT JOIN provinces p ON u.province_id = p.province_id
            LEFT JOIN dioceses d ON u.diocese_id = d.diocese_id
            LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN deaneries dn ON u.deanery_id = dn.deanery_id
            LEFT JOIN parishes pr ON u.parish_id = pr.parish_id
            WHERE 1=1";
    
    $params = [];
    $where_conditions = [];
    
    // Apply scope filtering
    $role_level = $_SESSION['role_level'];
    if ($role_level === 'parish_admin') {
        $where_conditions[] = "u.parish_id = ?";
        $params[] = $_SESSION['parish_id'];
    } elseif ($role_level === 'deanery_admin') {
        $where_conditions[] = "u.deanery_id = ?";
        $params[] = $_SESSION['deanery_id'];
    } elseif ($role_level === 'archdeaconry_admin') {
        $where_conditions[] = "u.archdeaconry_id = ?";
        $params[] = $_SESSION['archdeaconry_id'];
    } elseif ($role_level === 'diocese_admin') {
        $where_conditions[] = "u.diocese_id = ?";
        $params[] = $_SESSION['diocese_id'];
    }
    
    // Apply filters if exporting filtered results
    if ($exportType === 'filtered' && !empty($filters)) {
        // Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where_conditions[] = "u.account_status = ?";
            $params[] = $filters['status'];
        }
        
        // Role filter
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $where_conditions[] = "u.role_level = ?";
            $params[] = $filters['role'];
        }
        
        // Hierarchy filter
        if (!empty($filters['hierarchy_level']) && !empty($filters['hierarchy_id'])) {
            switch ($filters['hierarchy_level']) {
                case 'province':
                    $where_conditions[] = "u.province_id = ?";
                    $params[] = (int)$filters['hierarchy_id'];
                    break;
                case 'diocese':
                    $where_conditions[] = "u.diocese_id = ?";
                    $params[] = (int)$filters['hierarchy_id'];
                    break;
                case 'archdeaconry':
                    $where_conditions[] = "u.archdeaconry_id = ?";
                    $params[] = (int)$filters['hierarchy_id'];
                    break;
                case 'deanery':
                    $where_conditions[] = "u.deanery_id = ?";
                    $params[] = (int)$filters['hierarchy_id'];
                    break;
                case 'parish':
                    $where_conditions[] = "u.parish_id = ?";
                    $params[] = (int)$filters['hierarchy_id'];
                    break;
            }
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $search_term = '%' . $filters['search'] . '%';
            $where_conditions[] = "(
                LOWER(u.first_name) LIKE LOWER(?) OR 
                LOWER(u.last_name) LIKE LOWER(?) OR 
                LOWER(u.email) LIKE LOWER(?) OR 
                LOWER(u.username) LIKE LOWER(?)
            )";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
    }
    
    // Add WHERE conditions
    if (!empty($where_conditions)) {
        $sql .= " AND " . implode(" AND ", $where_conditions);
    }
    
    // Filter by specific user IDs if provided
    if ($exportType === 'selected' && !empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql .= " AND u.id IN ($placeholders)";
        $params = array_merge($params, $userIds);
    }
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No users found to export']);
        exit;
    }
    
    // Create CSV file
    $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID',
        'First Name',
        'Last Name',
        'Username',
        'Email',
        'Phone',
        'Role',
        'Status',
        'Province',
        'Diocese',
        'Archdeaconry',
        'Deanery',
        'Parish',
        'Created At',
        'Last Login'
    ]);
    
    // Add user data
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['first_name'],
            $user['last_name'],
            $user['username'],
            $user['email'],
            $user['phone'] ?? '',
            ucfirst(str_replace('_', ' ', $user['role_level'])),
            ucfirst($user['account_status']),
            $user['province_name'] ?? '',
            $user['diocese_name'] ?? '',
            $user['archdeaconry_name'] ?? '',
            $user['deanery_name'] ?? '',
            $user['parish_name'] ?? '',
            date('Y-m-d H:i:s', strtotime($user['created_at'])),
            $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'
        ]);
    }
    
    // Close output stream
    fclose($output);
    exit;
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred during export: ' . $e->getMessage()]);
    exit;
}
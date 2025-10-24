<?php
/**
 * ============================================
 * SCOPE HELPER FUNCTIONS
 * Role-Based Data Access Control
 * WITH SQL INJECTION PROTECTION
 * ============================================
 * 
 * NOTE: This file handles scope/access control only.
 * CSRF functions are in includes/security.php
 */

// ============================================
// INPUT VALIDATION & SANITIZATION
// ============================================

/**
 * Validate and sanitize role level
 * 
 * @param mixed $role_level Role to validate
 * @return string Valid role or 'member' as default
 */
function validate_role_level($role_level) {
    $valid_roles = [
        'super_admin',
        'national_admin', 
        'diocese_admin',
        'archdeaconry_admin',
        'deanery_admin',
        'parish_admin',
        'member'
    ];
    
    // Handle numeric role levels
    if (is_numeric($role_level)) {
        $role_map = [
            1 => 'super_admin',
            2 => 'national_admin',
            3 => 'diocese_admin',
            4 => 'archdeaconry_admin',
            5 => 'deanery_admin',
            6 => 'parish_admin',
            7 => 'member'
        ];
        $role_level = $role_map[(int)$role_level] ?? 'member';
    }
    
    return in_array($role_level, $valid_roles, true) ? $role_level : 'member';
}

/**
 * Validate integer ID
 * 
 * @param mixed $id ID to validate
 * @return int|null Valid positive integer or null
 */
function validate_id($id) {
    if ($id === null || $id === '') {
        return null;
    }
    
    $id = filter_var($id, FILTER_VALIDATE_INT);
    return ($id !== false && $id > 0) ? $id : null;
}

/**
 * Validate table alias for SQL queries
 * 
 * @param string $alias Alias to validate
 * @return string Valid alias or default 'u'
 */
function validate_table_alias($alias) {
    $allowed_aliases = [
        'u', 'users',
        'm', 'members',
        'p', 'parishes',
        'd', 'dioceses',
        'a', 'archdeaconries',
        'dn', 'deaneries',
        'prov', 'provinces'
    ];
    
    return in_array($alias, $allowed_aliases, true) ? $alias : 'u';
}

// ============================================
// SCOPE MANAGEMENT FUNCTIONS
// ============================================

/**
 * Get the user's scope based on their role
 * WITH INPUT VALIDATION
 * 
 * @return array Validated scope data
 */
function get_user_scope() {
    // Validate role level
    $role_level = validate_role_level($_SESSION['role_level'] ?? 'member');
    
    $scope = [
        'type' => $role_level,
        'province_id' => validate_id($_SESSION['province_id'] ?? null),
        'diocese_id' => validate_id($_SESSION['diocese_id'] ?? null),
        'archdeaconry_id' => validate_id($_SESSION['archdeaconry_id'] ?? null),
        'deanery_id' => validate_id($_SESSION['deanery_id'] ?? null),
        'parish_id' => validate_id($_SESSION['parish_id'] ?? null),
        'user_id' => validate_id($_SESSION['user_id'] ?? null),
    ];
    
    return $scope;
}

/**
 * Build SQL WHERE clause based on user scope
 * WITH SQL INJECTION PROTECTION
 * 
 * @param string $table_alias Alias for users table (e.g., 'u')
 * @return array ['where' => SQL string, 'params' => array of values]
 */
function build_scope_where($table_alias = 'u') {
    global $pdo;
    
    // CRITICAL: Validate table alias to prevent SQL injection
    $table_alias = validate_table_alias($table_alias);
    
    $scope = get_user_scope();
    
    $where = [];
    $params = [];
    
    switch ($scope['type']) {
        case 'super_admin':
            // Super admin sees everything - no restrictions
            return ['where' => '', 'params' => []];
            
        case 'diocese_admin':
            if ($scope['diocese_id']) {
                $where[] = "{$table_alias}.diocese_id = ?";
                $params[] = $scope['diocese_id'];
            }
            break;
            
        case 'archdeaconry_admin':
            if ($scope['archdeaconry_id']) {
                $where[] = "{$table_alias}.archdeaconry_id = ?";
                $params[] = $scope['archdeaconry_id'];
            }
            break;
            
        case 'deanery_admin':
            if ($scope['deanery_id']) {
                $where[] = "{$table_alias}.deanery_id = ?";
                $params[] = $scope['deanery_id'];
            }
            break;
            
        case 'parish_admin':
            if ($scope['parish_id']) {
                $where[] = "{$table_alias}.parish_id = ?";
                $params[] = $scope['parish_id'];
            }
            break;
            
        case 'member':
            // Members can only see themselves
            if ($scope['user_id']) {
                $where[] = "{$table_alias}.id = ?";
                $params[] = $scope['user_id'];
            }
            break;
    }
    
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    return ['where' => $where_sql, 'params' => $params];
}

// ============================================
// DATA ACCESS FUNCTIONS (WITH ERROR HANDLING)
// ============================================

/**
 * Get all provinces (Super Admin only)
 */
function get_accessible_provinces() {
    global $pdo;
    
    if ($_SESSION['role_level'] !== 'super_admin') {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT * FROM provinces ORDER BY province_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_accessible_provinces: " . $e->getMessage());
        return [];
    }
}

/**
 * Get dioceses accessible by current user
 */
function get_accessible_dioceses() {
    global $pdo;
    $scope = get_user_scope();
    
    try {
        if ($scope['type'] === 'super_admin') {
            $stmt = $pdo->query("
                SELECT d.*, p.province_name 
                FROM dioceses d
                LEFT JOIN provinces p ON d.province_id = p.province_id
                ORDER BY d.diocese_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'diocese_admin' && $scope['diocese_id']) {
            $stmt = $pdo->prepare("
                SELECT d.*, p.province_name 
                FROM dioceses d
                LEFT JOIN provinces p ON d.province_id = p.province_id
                WHERE d.diocese_id = ?
            ");
            $stmt->execute([$scope['diocese_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [];
    } catch (PDOException $e) {
        error_log("Database error in get_accessible_dioceses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get archdeaconries accessible by current user
 */
function get_accessible_archdeaconries() {
    global $pdo;
    $scope = get_user_scope();
    
    try {
        if ($scope['type'] === 'super_admin') {
            $stmt = $pdo->query("
                SELECT a.*, d.diocese_name 
                FROM archdeaconries a
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                ORDER BY a.archdeaconry_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'diocese_admin' && $scope['diocese_id']) {
            $stmt = $pdo->prepare("
                SELECT a.*, d.diocese_name 
                FROM archdeaconries a
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE a.diocese_id = ?
                ORDER BY a.archdeaconry_name
            ");
            $stmt->execute([$scope['diocese_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'archdeaconry_admin' && $scope['archdeaconry_id']) {
            $stmt = $pdo->prepare("
                SELECT a.*, d.diocese_name 
                FROM archdeaconries a
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE a.archdeaconry_id = ?
            ");
            $stmt->execute([$scope['archdeaconry_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [];
    } catch (PDOException $e) {
        error_log("Database error in get_accessible_archdeaconries: " . $e->getMessage());
        return [];
    }
}

/**
 * Get deaneries accessible by current user
 */
function get_accessible_deaneries() {
    global $pdo;
    $scope = get_user_scope();
    
    try {
        if ($scope['type'] === 'super_admin') {
            $stmt = $pdo->query("
                SELECT dn.*, a.archdeaconry_name, d.diocese_name 
                FROM deaneries dn
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                ORDER BY dn.deanery_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'diocese_admin' && $scope['diocese_id']) {
            $stmt = $pdo->prepare("
                SELECT dn.*, a.archdeaconry_name, d.diocese_name 
                FROM deaneries dn
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE d.diocese_id = ?
                ORDER BY dn.deanery_name
            ");
            $stmt->execute([$scope['diocese_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'archdeaconry_admin' && $scope['archdeaconry_id']) {
            $stmt = $pdo->prepare("
                SELECT dn.*, a.archdeaconry_name, d.diocese_name 
                FROM deaneries dn
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE a.archdeaconry_id = ?
                ORDER BY dn.deanery_name
            ");
            $stmt->execute([$scope['archdeaconry_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'deanery_admin' && $scope['deanery_id']) {
            $stmt = $pdo->prepare("
                SELECT dn.*, a.archdeaconry_name, d.diocese_name 
                FROM deaneries dn
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE dn.deanery_id = ?
            ");
            $stmt->execute([$scope['deanery_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [];
    } catch (PDOException $e) {
        error_log("Database error in get_accessible_deaneries: " . $e->getMessage());
        return [];
    }
}

/**
 * Get parishes accessible by current user
 */
function get_accessible_parishes() {
    global $pdo;
    $scope = get_user_scope();
    
    try {
        if ($scope['type'] === 'super_admin') {
            $stmt = $pdo->query("
                SELECT p.*, dn.deanery_name, a.archdeaconry_name, d.diocese_name 
                FROM parishes p
                LEFT JOIN deaneries dn ON p.deanery_id = dn.deanery_id
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                ORDER BY p.parish_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'diocese_admin' && $scope['diocese_id']) {
            $stmt = $pdo->prepare("
                SELECT p.*, dn.deanery_name, a.archdeaconry_name, d.diocese_name 
                FROM parishes p
                LEFT JOIN deaneries dn ON p.deanery_id = dn.deanery_id
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE d.diocese_id = ?
                ORDER BY p.parish_name
            ");
            $stmt->execute([$scope['diocese_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'archdeaconry_admin' && $scope['archdeaconry_id']) {
            $stmt = $pdo->prepare("
                SELECT p.*, dn.deanery_name, a.archdeaconry_name, d.diocese_name 
                FROM parishes p
                LEFT JOIN deaneries dn ON p.deanery_id = dn.deanery_id
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE a.archdeaconry_id = ?
                ORDER BY p.parish_name
            ");
            $stmt->execute([$scope['archdeaconry_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'deanery_admin' && $scope['deanery_id']) {
            $stmt = $pdo->prepare("
                SELECT p.*, dn.deanery_name, a.archdeaconry_name, d.diocese_name 
                FROM parishes p
                LEFT JOIN deaneries dn ON p.deanery_id = dn.deanery_id
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE dn.deanery_id = ?
                ORDER BY p.parish_name
            ");
            $stmt->execute([$scope['deanery_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($scope['type'] === 'parish_admin' && $scope['parish_id']) {
            $stmt = $pdo->prepare("
                SELECT p.*, dn.deanery_name, a.archdeaconry_name, d.diocese_name 
                FROM parishes p
                LEFT JOIN deaneries dn ON p.deanery_id = dn.deanery_id
                LEFT JOIN archdeaconries a ON dn.archdeaconry_id = a.archdeaconry_id
                LEFT JOIN dioceses d ON a.diocese_id = d.diocese_id
                WHERE p.parish_id = ?
            ");
            $stmt->execute([$scope['parish_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [];
    } catch (PDOException $e) {
        error_log("Database error in get_accessible_parishes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get users accessible by current user with proper hierarchy filtering
 * WITH INPUT VALIDATION
 */
function get_accessible_users($limit = null, $offset = 0) {
    global $pdo;
    
    // Validate pagination parameters
    if ($limit !== null) {
        $limit = validate_id($limit);
        if ($limit === null || $limit <= 0) {
            $limit = 50; // Default safe limit
        }
        // Cap maximum limit for performance
        if ($limit > 1000) {
            $limit = 1000;
        }
    }
    
    $offset = validate_id($offset);
    if ($offset === null || $offset < 0) {
        $offset = 0;
    }
    
    $scope = get_user_scope();
    
    $limit_sql = $limit ? "LIMIT ? OFFSET ?" : "";
    $where_conditions = [];
    $params = [];
    
    try {
        switch ($scope['type']) {
            case 'super_admin':
                // Super admin sees ALL users
                break;
                
            case 'diocese_admin':
                if ($scope['diocese_id']) {
                    $where_conditions[] = "u.diocese_id = ?";
                    $params[] = $scope['diocese_id'];
                }
                break;
                
            case 'archdeaconry_admin':
                if ($scope['archdeaconry_id']) {
                    $where_conditions[] = "u.archdeaconry_id = ?";
                    $params[] = $scope['archdeaconry_id'];
                }
                break;
                
            case 'deanery_admin':
                if ($scope['deanery_id']) {
                    $where_conditions[] = "u.deanery_id = ?";
                    $params[] = $scope['deanery_id'];
                }
                break;
                
            case 'parish_admin':
                if ($scope['parish_id']) {
                    $where_conditions[] = "u.parish_id = ?";
                    $params[] = $scope['parish_id'];
                }
                break;
                
            case 'member':
                if ($scope['user_id']) {
                    $where_conditions[] = "u.id = ?";
                    $params[] = $scope['user_id'];
                }
                break;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "
            SELECT 
                u.*,
                p.parish_name,
                dn.deanery_name,
                a.archdeaconry_name,
                d.diocese_name,
                prov.province_name
            FROM users u
            LEFT JOIN parishes p ON u.parish_id = p.parish_id
            LEFT JOIN deaneries dn ON u.deanery_id = dn.deanery_id
            LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN dioceses d ON u.diocese_id = d.diocese_id
            LEFT JOIN provinces prov ON u.province_id = prov.province_id
            {$where_sql}
            ORDER BY u.created_at DESC
            {$limit_sql}
        ";
        
        $stmt = $pdo->prepare($sql);
        
        if ($limit) {
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error in get_accessible_users: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has permission to access a specific record
 * WITH INPUT VALIDATION
 */
function can_access_user($target_user_id) {
    global $pdo;
    
    // Validate input
    $target_user_id = validate_id($target_user_id);
    if ($target_user_id === null) {
        return false;
    }
    
    $scope = get_user_scope();
    
    // Super admin can access anyone
    if ($scope['type'] === 'super_admin') {
        return true;
    }
    
    try {
        // Get target user's scope
        $stmt = $pdo->prepare("
            SELECT province_id, diocese_id, archdeaconry_id, deanery_id, parish_id 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$target_user_id]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target) {
            return false;
        }
        
        // Check based on role
        switch ($scope['type']) {
            case 'diocese_admin':
                return $target['diocese_id'] == $scope['diocese_id'];
                
            case 'archdeaconry_admin':
                return $target['archdeaconry_id'] == $scope['archdeaconry_id'];
                
            case 'deanery_admin':
                return $target['deanery_id'] == $scope['deanery_id'];
                
            case 'parish_admin':
                return $target['parish_id'] == $scope['parish_id'];
                
            case 'member':
                return $target_user_id == $scope['user_id'];
                
            default:
                return false;
        }
        
    } catch (PDOException $e) {
        error_log("Database error in can_access_user: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user can manage a specific level
 */
function can_manage_level($level) {
    $role_level = validate_role_level($_SESSION['role_level'] ?? 'member');
    
    $hierarchy = [
        'super_admin' => ['province', 'diocese', 'archdeaconry', 'deanery', 'parish', 'user'],
        'diocese_admin' => ['archdeaconry', 'deanery', 'parish', 'user'],
        'archdeaconry_admin' => ['deanery', 'parish', 'user'],
        'deanery_admin' => ['parish', 'user'],
        'parish_admin' => ['user'],
        'member' => []
    ];
    
    return in_array($level, $hierarchy[$role_level] ?? []);
}

/**
 * Get scope display based on user's role and hierarchy
 * Shows where the user's administrative scope applies
 * 
 * @param array $user User data array from database
 * @return string Display text for user's scope
 */
function get_scope_display($user) {
    $role_level = validate_role_level($user['role_level'] ?? 'member');
    
    // Build scope display based on role level
    switch ($role_level) {
        case 'super_admin':
            return 'National Level - All Access';
            
        case 'national_admin':
            return 'National Level';
            
        case 'diocese_admin':
            return !empty($user['diocese_name']) 
                ? htmlspecialchars($user['diocese_name'], ENT_QUOTES, 'UTF-8') . ' Diocese'
                : 'Diocese Level';
            
        case 'archdeaconry_admin':
            $scope = htmlspecialchars($user['archdeaconry_name'] ?? 'Archdeaconry', ENT_QUOTES, 'UTF-8');
            if (!empty($user['diocese_name'])) {
                $scope .= ', ' . htmlspecialchars($user['diocese_name'], ENT_QUOTES, 'UTF-8');
            }
            return $scope;
            
        case 'deanery_admin':
            $scope = htmlspecialchars($user['deanery_name'] ?? 'Deanery', ENT_QUOTES, 'UTF-8');
            if (!empty($user['archdeaconry_name'])) {
                $scope .= ', ' . htmlspecialchars($user['archdeaconry_name'], ENT_QUOTES, 'UTF-8');
            } elseif (!empty($user['diocese_name'])) {
                $scope .= ', ' . htmlspecialchars($user['diocese_name'], ENT_QUOTES, 'UTF-8');
            }
            return $scope;
            
        case 'parish_admin':
            $scope = htmlspecialchars($user['parish_name'] ?? 'Parish', ENT_QUOTES, 'UTF-8');
            if (!empty($user['deanery_name'])) {
                $scope .= ', ' . htmlspecialchars($user['deanery_name'], ENT_QUOTES, 'UTF-8');
            } elseif (!empty($user['diocese_name'])) {
                $scope .= ', ' . htmlspecialchars($user['diocese_name'], ENT_QUOTES, 'UTF-8');
            }
            return $scope;
            
        case 'member':
        default:
            return !empty($user['parish_name']) 
                ? htmlspecialchars($user['parish_name'], ENT_QUOTES, 'UTF-8') . ' Parish'
                : 'Member';
    }
}

/**
 * Get user statistics within the current user's scope
 * 
 * @return array Array of user statistics
 */
function get_user_statistics() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    $role = $_SESSION['role_level'] ?? '';
    $scope = get_user_scope();
    
    $stats = [
        'total' => 0,
        'by_status' => [],
        'by_role' => []
    ];
    
    // Base SQL for counting users
    $sql = "SELECT COUNT(*) as count FROM users u WHERE 1=1";
    $params = [];
    
    // Apply scope restrictions
    switch ($role) {
        case 'parish_admin':
            $sql .= " AND u.parish_id = ?";
            $params[] = $scope['parish_id'];
            break;
            
        case 'deanery_admin':
            $sql .= " AND u.deanery_id = ?";
            $params[] = $scope['deanery_id'];
            break;
            
        case 'archdeaconry_admin':
            $sql .= " AND u.archdeaconry_id = ?";
            $params[] = $scope['archdeaconry_id'];
            break;
            
        case 'diocese_admin':
            $sql .= " AND u.diocese_id = ?";
            $params[] = $scope['diocese_id'];
            break;
    }
    
    try {
        // Get total count
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['total'] = (int)$stmt->fetchColumn();
        
        // Get count by status
        $status_sql = str_replace("COUNT(*)", "account_status, COUNT(*)", $sql) . " GROUP BY account_status";
        $stmt = $pdo->prepare($status_sql);
        $stmt->execute($params);
        $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $stats['by_status'] = $status_counts;
        
        // Get count by role
        $role_sql = str_replace("account_status", "role_level", $status_sql);
        $stmt = $pdo->prepare($role_sql);
        $stmt->execute($params);
        $role_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $stats['by_role'] = $role_counts;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting user statistics: " . $e->getMessage());
        return $stats;
    }
}

/**
 * Get allowed parish IDs for a user (only if not already defined)
 */
if (!function_exists('allowedParishIds')) {
    function allowedParishIds($user) {
        global $pdo;
        $role = $user['role_level'];
        
        switch ($role) {
            case 'super_admin':
            case 'national_admin':
                $stmt = $pdo->query("SELECT parish_id FROM parishes");
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'diocese_admin':
                $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE diocese_id = ?");
                $stmt->execute([$user['diocese_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'archdeaconry_admin':
                $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE archdeaconry_id = ?");
                $stmt->execute([$user['archdeaconry_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'deanery_admin':
                $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE deanery_id = ?");
                $stmt->execute([$user['deanery_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'parish_admin':
                return [$user['parish_id']];
                
            default:
                return [];
        }
    }
}

/**
 * Get allowed user IDs for a user (only if not already defined)
 */
if (!function_exists('allowedUserIds')) {
    function allowedUserIds($user) {
        global $pdo;
        $role = $user['role_level'];
        
        switch ($role) {
            case 'super_admin':
            case 'national_admin':
                $stmt = $pdo->query("SELECT id FROM users");
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'diocese_admin':
                $stmt = $pdo->prepare("SELECT id FROM users WHERE diocese_id = ?");
                $stmt->execute([$user['diocese_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'archdeaconry_admin':
                $stmt = $pdo->prepare("SELECT id FROM users WHERE archdeaconry_id = ?");
                $stmt->execute([$user['archdeaconry_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'deanery_admin':
                $stmt = $pdo->prepare("SELECT id FROM users WHERE deanery_id = ?");
                $stmt->execute([$user['deanery_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            case 'parish_admin':
                $stmt = $pdo->prepare("SELECT id FROM users WHERE parish_id = ?");
                $stmt->execute([$user['parish_id']]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
                
            default:
                return [$user['id']];
        }
    }
}


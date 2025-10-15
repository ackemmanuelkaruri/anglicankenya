<?php
/**
 * ============================================
 * SCOPE HELPER FUNCTIONS
 * Role-Based Data Access Control
 * ============================================
 */

/**
 * Get the user's scope based on their role
 * Returns array with scope type and ID
 */
function get_user_scope() {
    $role_level = $_SESSION['role_level'] ?? 'member';
    
    $scope = [
        'type' => $role_level,
        'province_id' => $_SESSION['province_id'] ?? null,
        'diocese_id' => $_SESSION['diocese_id'] ?? null,
        'archdeaconry_id' => $_SESSION['archdeaconry_id'] ?? null,
        'deanery_id' => $_SESSION['deanery_id'] ?? null,
        'parish_id' => $_SESSION['parish_id'] ?? null,
    ];
    
    return $scope;
}

/**
 * Build SQL WHERE clause based on user scope
 * 
 * @param string $table_alias - Alias for users table (e.g., 'u')
 * @return array - ['where' => SQL string, 'params' => array of values]
 */
function build_scope_where($table_alias = 'u') {
    global $pdo;
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
            $where[] = "{$table_alias}.id = ?";
            $params[] = $_SESSION['user_id'];
            break;
    }
    
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    return ['where' => $where_sql, 'params' => $params];
}

/**
 * Get all provinces (Super Admin only)
 */
function get_accessible_provinces() {
    global $pdo;
    
    if ($_SESSION['role_level'] !== 'super_admin') {
        return [];
    }
    
    $stmt = $pdo->query("SELECT * FROM provinces ORDER BY province_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get dioceses accessible by current user
 */
function get_accessible_dioceses() {
    global $pdo;
    $scope = get_user_scope();
    
    if ($scope['type'] === 'super_admin') {
        // Super admin sees all dioceses
        $stmt = $pdo->query("
            SELECT d.*, p.province_name 
            FROM dioceses d
            LEFT JOIN provinces p ON d.province_id = p.province_id
            ORDER BY d.diocese_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($scope['type'] === 'diocese_admin' && $scope['diocese_id']) {
        // Diocese admin sees only their diocese
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
}

/**
 * Get archdeaconries accessible by current user
 */
function get_accessible_archdeaconries() {
    global $pdo;
    $scope = get_user_scope();
    
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
}

/**
 * Get deaneries accessible by current user
 */
function get_accessible_deaneries() {
    global $pdo;
    $scope = get_user_scope();
    
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
}

/**
 * Get parishes accessible by current user
 */
function get_accessible_parishes() {
    global $pdo;
    $scope = get_user_scope();
    
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
}

/**
 * Get users accessible by current user with proper hierarchy filtering
 */
function get_accessible_users($limit = null, $offset = 0) {
    global $pdo;
    $scope = get_user_scope();
    
    $limit_sql = $limit ? "LIMIT ? OFFSET ?" : "";
    $where_conditions = [];
    $params = [];
    
    switch ($scope['type']) {
        case 'super_admin':
            // Super admin sees ALL users across ALL 38 dioceses
            // No WHERE clause needed
            break;
            
        case 'diocese_admin':
            // Diocese admin sees ONLY users in their specific diocese (1 of 38)
            // This includes all archdeaconries, deaneries, and parishes under this diocese
            if ($scope['diocese_id']) {
                $where_conditions[] = "u.diocese_id = ?";
                $params[] = $scope['diocese_id'];
            }
            break;
            
        case 'archdeaconry_admin':
            // Archdeaconry admin sees ONLY users in their archdeaconry
            // This includes all deaneries and parishes under this archdeaconry
            if ($scope['archdeaconry_id']) {
                $where_conditions[] = "u.archdeaconry_id = ?";
                $params[] = $scope['archdeaconry_id'];
            }
            break;
            
        case 'deanery_admin':
            // Deanery admin sees ONLY users in their deanery
            // This includes all parishes under this deanery
            if ($scope['deanery_id']) {
                $where_conditions[] = "u.deanery_id = ?";
                $params[] = $scope['deanery_id'];
            }
            break;
            
        case 'parish_admin':
            // Parish admin sees ONLY users/members in their specific parish
            if ($scope['parish_id']) {
                $where_conditions[] = "u.parish_id = ?";
                $params[] = $scope['parish_id'];
            }
            break;
            
        case 'member':
            // Member sees ONLY their own account
            $where_conditions[] = "u.id = ?";
            $params[] = $_SESSION['user_id'];
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
}

/**
 * Check if user has permission to access a specific record
 */
function can_access_user($target_user_id) {
    global $pdo;
    $scope = get_user_scope();
    
    // Super admin can access anyone
    if ($scope['type'] === 'super_admin') {
        return true;
    }
    
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
            return $target_user_id == $_SESSION['user_id'];
            
        default:
            return false;
    }
}

/**
 * Check if user can manage a specific level
 */
function can_manage_level($level) {
    $role_level = $_SESSION['role_level'] ?? 'member';
    
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
    $role_level = $user['role_level'] ?? 'member';
    
    // Normalize role_level if numeric
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
        $role_level = $role_map[$role_level] ?? 'member';
    }
    
    // Build scope display based on role level
    switch (strtolower($role_level)) {
        case 'super_admin':
            return 'National Level - All Access';
            
        case 'national_admin':
            return 'National Level';
            
        case 'diocese_admin':
            return !empty($user['diocese_name']) 
                ? $user['diocese_name'] . ' Diocese'
                : 'Diocese Level';
            
        case 'archdeaconry_admin':
            $scope = $user['archdeaconry_name'] ?? 'Archdeaconry';
            if (!empty($user['diocese_name'])) {
                $scope .= ', ' . $user['diocese_name'];
            }
            return $scope;
            
        case 'deanery_admin':
            $scope = $user['deanery_name'] ?? 'Deanery';
            if (!empty($user['archdeaconry_name'])) {
                $scope .= ', ' . $user['archdeaconry_name'];
            } elseif (!empty($user['diocese_name'])) {
                $scope .= ', ' . $user['diocese_name'];
            }
            return $scope;
            
        case 'parish_admin':
            $scope = $user['parish_name'] ?? 'Parish';
            if (!empty($user['deanery_name'])) {
                $scope .= ', ' . $user['deanery_name'];
            } elseif (!empty($user['diocese_name'])) {
                $scope .= ', ' . $user['diocese_name'];
            }
            return $scope;
            
        case 'member':
        default:
            return !empty($user['parish_name']) 
                ? $user['parish_name'] . ' Parish'
                : 'Member';
    }
}
<?php
/**
 * ============================================
 * REQUEST MIDDLEWARE - RBAC ENFORCEMENT
 * Protects endpoints with permission checks
 * ============================================
 */

if (!defined('DB_INCLUDED')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/security.php';

/**
 * Require specific permission for resource/action
 * Redirects to access_denied.php if unauthorized
 * 
 * @param string $resource The resource type (user, parish, diocese, etc.)
 * @param string $action The action (view, create, edit, delete)
 * @param int|null $resource_id Optional resource ID for ownership checks
 */
function require_permission($resource, $action = 'view', $resource_id = null) {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['access_denied_reason'] = 'Your role does not have access to this page.';
        $_SESSION['access_denied_required_roles'] = $allowed_roles;
        
        header('Location: /anglicankenya/access_denied.php');
        exit;
    }
}

/**
 * Check if user can access a specific scope level
 * Used for hierarchy-based access control
 * 
 * @param string $scope_level The hierarchy level (province, diocese, etc.)
 * @param int $scope_id The ID of the specific entity
 * @return bool
 */
function require_scope_access($scope_level, $scope_id) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /anglicankenya/login.php');
        exit;
    }
    
    $user_role = $_SESSION['role_level'] ?? 'member';
    
    // Super admin and national admin have access to everything
    if (in_array($user_role, ['super_admin', 'national_admin'])) {
        return true;
    }
    
    // Check scope-specific access
    switch ($scope_level) {
        case 'province':
            if ($user_role === 'province_admin' && $_SESSION['province_id'] == $scope_id) {
                return true;
            }
            break;
            
        case 'diocese':
            if ($user_role === 'diocese_admin' && $_SESSION['diocese_id'] == $scope_id) {
                return true;
            }
            break;
            
        case 'archdeaconry':
            if ($user_role === 'archdeaconry_admin' && $_SESSION['archdeaconry_id'] == $scope_id) {
                return true;
            }
            break;
            
        case 'deanery':
            if ($user_role === 'deanery_admin' && $_SESSION['deanery_id'] == $scope_id) {
                return true;
            }
            break;
            
        case 'parish':
            if ($user_role === 'parish_admin' && $_SESSION['parish_id'] == $scope_id) {
                return true;
            }
            break;
    }
    
    // Access denied
    $_SESSION['access_denied_reason'] = "You don't have access to this {$scope_level}.";
    header('Location: /anglicankenya/access_denied.php');
    exit;
}

/**
 * Prevent members from accessing admin pages
 */
function require_admin_access() {
    require_role(['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin']);
}

/**
 * Check CSRF token for POST/PUT/DELETE requests
 * Should be called before processing form submissions
 * 
 * @return bool
 */
function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
        $_SERVER['REQUEST_METHOD'] === 'PUT' || 
        $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token) || !isset($_SESSION['csrf_token'])) {
            $_SESSION['access_denied_reason'] = 'Invalid security token. Please refresh the page and try again.';
            header('Location: /anglicankenya/access_denied.php');
            exit;
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            log_activity('CSRF_TOKEN_MISMATCH', null, null, [
                'url' => $_SERVER['REQUEST_URI']
            ]);
            
            $_SESSION['access_denied_reason'] = 'Security validation failed. Please try again.';
            header('Location: /anglicankenya/access_denied.php');
            exit;
        }
        
        return true;
    }
    
    return true;
}

/**
 * Rate limiting for sensitive operations
 * Prevents brute force and abuse
 * 
 * @param string $action The action being rate-limited
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 */
function rate_limit($action, $max_attempts = 5, $time_window = 300) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    try {
        // Count recent attempts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempt_count
            FROM activity_log
            WHERE user_id = ?
            AND action_type = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$user_id, $action, $time_window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['attempt_count'] >= $max_attempts) {
            $_SESSION['access_denied_reason'] = 'Too many requests. Please wait a few minutes and try again.';
            header('Location: /anglicankenya/access_denied.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Rate limiting error: " . $e->getMessage());
    }
}

/**
 * Check if user is impersonating another user
 * Adds restrictions on sensitive operations during impersonation
 * 
 * @return bool
 */
function is_impersonating() {
    return isset($_SESSION['impersonating']) && isset($_SESSION['original_user_id']);
}

/**
 * Prevent certain actions during impersonation
 * e.g., changing passwords, deleting accounts
 */
function prevent_during_impersonation() {
    if (is_impersonating()) {
        $_SESSION['access_denied_reason'] = 'This action cannot be performed while impersonating another user.';
        header('Location: /anglicankenya/access_denied.php');
        exit;
    }
}

/**
 * Get allowed resource IDs for current user's scope
 * Used to filter queries by scope
 * 
 * @param string $resource_type The resource type (user, parish, etc.)
 * @return array Array of allowed IDs
 */
function get_allowed_resource_ids($resource_type) {
    global $pdo;
    
    $user_role = $_SESSION['role_level'] ?? 'member';
    
    // Super admin and national admin can access all
    if (in_array($user_role, ['super_admin', 'national_admin'])) {
        return 'all'; // Special indicator for no restrictions
    }
    
    $allowed_ids = [];
    
    try {
        switch ($resource_type) {
            case 'user':
                // Build query based on user's scope
                $where_conditions = [];
                $params = [];
                
                if ($user_role === 'diocese_admin' && isset($_SESSION['diocese_id'])) {
                    $where_conditions[] = "diocese_id = ?";
                    $params[] = $_SESSION['diocese_id'];
                } elseif ($user_role === 'archdeaconry_admin' && isset($_SESSION['archdeaconry_id'])) {
                    $where_conditions[] = "archdeaconry_id = ?";
                    $params[] = $_SESSION['archdeaconry_id'];
                } elseif ($user_role === 'deanery_admin' && isset($_SESSION['deanery_id'])) {
                    $where_conditions[] = "deanery_id = ?";
                    $params[] = $_SESSION['deanery_id'];
                } elseif ($user_role === 'parish_admin' && isset($_SESSION['parish_id'])) {
                    $where_conditions[] = "parish_id = ?";
                    $params[] = $_SESSION['parish_id'];
                } else {
                    return [$_SESSION['user_id']]; // Members can only access themselves
                }
                
                if (!empty($where_conditions)) {
                    $sql = "SELECT id FROM users WHERE " . implode(' AND ', $where_conditions);
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $allowed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                break;
                
            case 'parish':
                if ($user_role === 'parish_admin' && isset($_SESSION['parish_id'])) {
                    $allowed_ids = [$_SESSION['parish_id']];
                } elseif ($user_role === 'deanery_admin' && isset($_SESSION['deanery_id'])) {
                    $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE deanery_id = ?");
                    $stmt->execute([$_SESSION['deanery_id']]);
                    $allowed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($user_role === 'archdeaconry_admin' && isset($_SESSION['archdeaconry_id'])) {
                    $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE archdeaconry_id = ?");
                    $stmt->execute([$_SESSION['archdeaconry_id']]);
                    $allowed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($user_role === 'diocese_admin' && isset($_SESSION['diocese_id'])) {
                    $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE diocese_id = ?");
                    $stmt->execute([$_SESSION['diocese_id']]);
                    $allowed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                break;
                
            // Add more resource types as needed
        }
    } catch (PDOException $e) {
        error_log("Error getting allowed resource IDs: " . $e->getMessage());
    }
    
    return $allowed_ids;
}

/**
 * Build WHERE clause for scoped queries
 * Automatically adds scope restrictions based on user role
 * 
 * @param string $table_alias The table alias to use
 * @return array [where_clause, params]
 */
function build_scope_where($table_alias = 'u') {
    $user_role = $_SESSION['role_level'] ?? 'member';
    
    // Super admin and national admin see everything
    if (in_array($user_role, ['super_admin', 'national_admin'])) {
        return ['', []];
    }
    
    $where_parts = [];
    $params = [];
    
    if ($user_role === 'diocese_admin' && isset($_SESSION['diocese_id'])) {
        $where_parts[] = "{$table_alias}.diocese_id = ?";
        $params[] = $_SESSION['diocese_id'];
    } elseif ($user_role === 'archdeaconry_admin' && isset($_SESSION['archdeaconry_id'])) {
        $where_parts[] = "{$table_alias}.archdeaconry_id = ?";
        $params[] = $_SESSION['archdeaconry_id'];
    } elseif ($user_role === 'deanery_admin' && isset($_SESSION['deanery_id'])) {
        $where_parts[] = "{$table_alias}.deanery_id = ?";
        $params[] = $_SESSION['deanery_id'];
    } elseif ($user_role === 'parish_admin' && isset($_SESSION['parish_id'])) {
        $where_parts[] = "{$table_alias}.parish_id = ?";
        $params[] = $_SESSION['parish_id'];
    } elseif ($user_role === 'member') {
        $where_parts[] = "{$table_alias}.id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $where_clause = !empty($where_parts) ? implode(' AND ', $where_parts) : '';
    
    return [$where_clause, $params];
}['access_denied_reason'] = 'You must be logged in to access this resource.';
        header('Location: /anglicankenya/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Build check function name
    $check_function = "can_{$action}";
    
    // Verify function exists
    if (!function_exists($check_function)) {
        error_log("RBAC: Unknown action '{$action}' for resource '{$resource}'");
        $_SESSION['access_denied_reason'] = 'Invalid permission check.';
        header('Location: /anglicankenya/access_denied.php');
        exit;
    }
    
    // Perform permission check
    $has_permission = $check_function($_SESSION, $resource, $resource_id);
    
    if (!$has_permission) {
        // Log unauthorized access attempt
        log_activity('UNAUTHORIZED_ACCESS_ATTEMPT', null, null, [
            'resource' => $resource,
            'action' => $action,
            'resource_id' => $resource_id,
            'url' => $_SERVER['REQUEST_URI']
        ]);
        
        $_SESSION['access_denied_reason'] = "You don't have permission to {$action} {$resource}.";
        $_SESSION['access_denied_resource'] = $resource;
        $_SESSION['access_denied_action'] = $action;
        
        header('Location: /anglicankenya/access_denied.php');
        exit;
    }
}

/**
 * Require one of multiple roles
 * More flexible than require_permission for role-based checks
 * 
 * @param array|string $allowed_roles Single role or array of allowed roles
 */
function require_role($allowed_roles) {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['access_denied_reason'] = 'You must be logged in to access this page.';
        header('Location: /anglicankenya/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $user_role = $_SESSION['role_level'] ?? 'member';
    $allowed_roles = (array)$allowed_roles; // Convert to array if string
    
    if (!in_array($user_role, $allowed_roles)) {
        log_activity('UNAUTHORIZED_ACCESS_ATTEMPT', null, null, [
            'required_roles' => implode(', ', $allowed_roles),
            'user_role' => $user_role,
            'url' => $_SERVER['REQUEST_URI']
        ]);
        
        $_SESSION
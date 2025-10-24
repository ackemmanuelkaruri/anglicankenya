<?php
/**
 * ============================================
 * ROLE-BASED ACCESS CONTROL (RBAC) - COMPLETE
 * Comprehensive permission system with impersonation
 * ============================================
 */

if (!defined('DB_INCLUDED')) {
    require_once __DIR__ . '/../db.php';
}

// ============================================
// CORE PERMISSION FUNCTIONS
// ============================================

/**
 * Check if user can view a resource
 */
function can_view($user, $resource, $resource_id = null) {
    $role = $user['role_level'] ?? 'member';
    
    // Super admins can view everything
    if ($role === 'super_admin') return true;
    
    switch ($resource) {
        case 'user':
            // Users can view their own profile
            if ($resource_id && $resource_id == $user['user_id']) return true;
            
            // Admins can view users in their scope
            if (in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin'])) {
                return is_user_in_scope($user, $resource_id);
            }
            break;
            
        case 'parish':
            // Members can view their own parish
            if ($resource_id && $resource_id == $user['parish_id']) return true;
            
            // Parish admins can view their parish
            if ($role === 'parish_admin' && $resource_id == $user['parish_id']) return true;
            
            // Higher admins can view parishes in scope
            if (in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin'])) {
                return is_parish_in_scope($user, $resource_id);
            }
            break;
            
        case 'diocese':
        case 'archdeaconry':
        case 'deanery':
            // National admin can view all
            if ($role === 'national_admin') return true;
            
            // Diocese admin can view dioceses, archdeaconries, deaneries in their scope
            if ($role === 'diocese_admin') {
                return is_resource_in_diocese($resource, $resource_id, $user['diocese_id']);
            }
            
            // Similar logic for archdeaconry and deanery admins
            if ($role === 'archdeaconry_admin' && $resource === 'deanery') {
                return is_deanery_in_archdeaconry($resource_id, $user['archdeaconry_id']);
            }
            break;
            
        case 'province':
            // Only super_admin and national_admin can view provinces
            return in_array($role, ['super_admin', 'national_admin']);
            
        case 'reports':
            // All admins can view reports
            return in_array($role, ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin']);
            
        case 'settings':
            // All admins can view settings
            return in_array($role, ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin']);
            
        case 'ministry':
            // All roles can view ministries
            return true;
    }
    
    return false;
}

/**
 * Check if user can create a resource
 */
function can_create($user, $resource) {
    $role = $user['role_level'] ?? 'member';
    
    // Super admins can create everything
    if ($role === 'super_admin') return true;
    
    switch ($resource) {
        case 'user':
            // All admins can create users
            return in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin']);
            
        case 'parish':
            // Deanery admin and above can create parishes
            return in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin']);
            
        case 'deanery':
            // Archdeaconry admin and above can create deaneries
            return in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin']);
            
        case 'archdeaconry':
            // Diocese admin and above can create archdeaconries
            return in_array($role, ['national_admin', 'diocese_admin']);
            
        case 'diocese':
            // Only national admin and super admin can create dioceses
            return in_array($role, ['national_admin']);
            
        case 'province':
            // Only super admin can create provinces
            return $role === 'super_admin';
            
        case 'ministry':
        case 'event':
            // All admins can create ministries and events
            return in_array($role, ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin']);
    }
    
    return false;
}

/**
 * Check if user can edit a resource
 */
function can_edit($user, $resource, $resource_id = null) {
    $role = $user['role_level'] ?? 'member';
    
    // Super admins can edit everything
    if ($role === 'super_admin') return true;
    
    switch ($resource) {
        case 'user':
            // Users can edit their own profile
            if ($resource_id && $resource_id == $user['user_id']) return true;
            
            // Admins can edit users in scope with lower roles
            if (in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin'])) {
                if (!$resource_id) {
                    // General permission check (e.g., can they edit ANY user)
                    return true;
                }
                
                if (!is_user_in_scope($user, $resource_id)) return false;
                
                $target_user = get_user_by_id($resource_id);
                return $target_user && is_role_lower($target_user['role_level'], $role);
            }
            break;
            
        case 'parish':
            // Parish admins can edit their parish
            if ($role === 'parish_admin' && $resource_id == $user['parish_id']) return true;
            
            // Higher admins can edit parishes in scope
            if (in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin'])) {
                return is_parish_in_scope($user, $resource_id);
            }
            break;
            
        case 'diocese':
        case 'archdeaconry':
        case 'deanery':
            // Similar scope-based editing
            return can_view($user, $resource, $resource_id);
            
        case 'ministry':
        case 'event':
            // Admins can edit ministries and events
            return in_array($role, ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin']);
    }
    
    return false;
}

/**
 * Check if user can delete a resource
 */
function can_delete($user, $resource, $resource_id = null) {
    $role = $user['role_level'] ?? 'member';
    
    // Super admins can delete everything
    if ($role === 'super_admin') return true;
    
    // Prevent self-deletion
    if ($resource === 'user' && $resource_id == $user['user_id']) {
        return false;
    }
    
    // For users, same rules as editing but stricter
    if ($resource === 'user') {
        if (!in_array($role, ['national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin'])) {
            return false;
        }
        
        if ($resource_id) {
            if (!is_user_in_scope($user, $resource_id)) return false;
            
            $target_user = get_user_by_id($resource_id);
            if (!$target_user) return false;
            
            // Cannot delete users of equal or higher role
            return is_role_lower($target_user['role_level'], $role);
        }
    }
    
    // For other resources, same as edit permission
    return can_edit($user, $resource, $resource_id);
}

/**
 * Check if user can approve pending users
 */
function can_approve($user, $target_user_id) {
    return can_edit($user, 'user', $target_user_id);
}

/**
 * Check if user can impersonate another user
 */
function can_impersonate($user, $target_user_id) {
    // Only super admins can impersonate
    if ($user['role_level'] !== 'super_admin') return false;
    
    // Cannot impersonate yourself
    if ($target_user_id == $user['user_id']) return false;
    
    // Get target user
    $target_user = get_user_by_id($target_user_id);
    if (!$target_user) return false;
    
    // Cannot impersonate another super admin
    if ($target_user['role_level'] === 'super_admin') return false;
    
    return true;
}

// ============================================
// SCOPE CHECKING FUNCTIONS
// ============================================

/**
 * Check if user is in scope of another user
 */
function is_user_in_scope($user, $target_user_id) {
    global $pdo;
    
    if (!$target_user_id) return false;
    
    $stmt = $pdo->prepare("
        SELECT diocese_id, archdeaconry_id, deanery_id, parish_id 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$target_user_id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target) return false;
    
    $role = $user['role_level'] ?? 'member';
    
    switch ($role) {
        case 'national_admin':
            return true;
        case 'diocese_admin':
            return $target['diocese_id'] == $user['diocese_id'];
        case 'archdeaconry_admin':
            return $target['archdeaconry_id'] == $user['archdeaconry_id'];
        case 'deanery_admin':
            return $target['deanery_id'] == $user['deanery_id'];
        case 'parish_admin':
            return $target['parish_id'] == $user['parish_id'];
    }
    
    return false;
}

/**
 * Check if parish is in scope of user
 */
function is_parish_in_scope($user, $parish_id) {
    global $pdo;
    
    if (!$parish_id) return false;
    
    $stmt = $pdo->prepare("
        SELECT diocese_id, archdeaconry_id, deanery_id 
        FROM parishes WHERE parish_id = ?
    ");
    $stmt->execute([$parish_id]);
    $parish = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parish) return false;
    
    $role = $user['role_level'] ?? 'member';
    
    switch ($role) {
        case 'national_admin':
            return true;
        case 'diocese_admin':
            return $parish['diocese_id'] == $user['diocese_id'];
        case 'archdeaconry_admin':
            return $parish['archdeaconry_id'] == $user['archdeaconry_id'];
        case 'deanery_admin':
            return $parish['deanery_id'] == $user['deanery_id'];
        case 'parish_admin':
            return $parish_id == $user['parish_id'];
    }
    
    return false;
}

/**
 * Check if resource is in diocese scope
 */
function is_resource_in_diocese($resource_type, $resource_id, $diocese_id) {
    global $pdo;
    
    $table_map = [
        'archdeaconry' => 'archdeaconries',
        'deanery' => 'deaneries',
        'parish' => 'parishes'
    ];
    
    if (!isset($table_map[$resource_type])) return false;
    
    $table = $table_map[$resource_type];
    $stmt = $pdo->prepare("SELECT diocese_id FROM $table WHERE {$resource_type}_id = ?");
    $stmt->execute([$resource_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['diocese_id'] == $diocese_id;
}

/**
 * Check if deanery is in archdeaconry scope
 */
function is_deanery_in_archdeaconry($deanery_id, $archdeaconry_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT archdeaconry_id FROM deaneries WHERE deanery_id = ?");
    $stmt->execute([$deanery_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['archdeaconry_id'] == $archdeaconry_id;
}

// ============================================
// ROLE HIERARCHY FUNCTIONS
// ============================================

/**
 * Check if role1 is lower than role2
 */
function is_role_lower($role1, $role2) {
    $hierarchy = [
        'super_admin' => 7,
        'national_admin' => 6,
        'diocese_admin' => 5,
        'archdeaconry_admin' => 4,
        'deanery_admin' => 3,
        'parish_admin' => 2,
        'member' => 1
    ];
    
    $level1 = $hierarchy[$role1] ?? 0;
    $level2 = $hierarchy[$role2] ?? 0;
    
    return $level1 < $level2;
}

/**
 * Check if current user can assign a specific role
 */
function can_assign_role($current_role, $target_role) {
    $hierarchy = [
        'super_admin' => 7,
        'national_admin' => 6,
        'diocese_admin' => 5,
        'archdeaconry_admin' => 4,
        'deanery_admin' => 3,
        'parish_admin' => 2,
        'member' => 1
    ];
    
    // Super admin can assign any role
    if ($current_role === 'super_admin') return true;
    
    // Other admins can only assign roles lower than themselves
    $current_level = $hierarchy[$current_role] ?? 0;
    $target_level = $hierarchy[$target_role] ?? 0;
    
    return $current_level > $target_level;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Get user by ID
 */
function get_user_by_id($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Get allowed parish IDs for current user (only if not already defined)
 */
if (!function_exists('allowedParishIds')) {
    function allowedParishIds($user) {
        global $pdo;
        
        $role = $user['role_level'] ?? 'member';
        
        // Super admin and national admin can access all parishes
        if (in_array($role, ['super_admin', 'national_admin'])) {
            $stmt = $pdo->query("SELECT parish_id FROM parishes");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Diocese admin - all parishes in diocese
        if ($role === 'diocese_admin' && isset($user['diocese_id'])) {
            $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE diocese_id = ?");
            $stmt->execute([$user['diocese_id']]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Archdeaconry admin - all parishes in archdeaconry
        if ($role === 'archdeaconry_admin' && isset($user['archdeaconry_id'])) {
            $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE archdeaconry_id = ?");
            $stmt->execute([$user['archdeaconry_id']]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Deanery admin - all parishes in deanery
        if ($role === 'deanery_admin' && isset($user['deanery_id'])) {
            $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE deanery_id = ?");
            $stmt->execute([$user['deanery_id']]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Parish admin - only their parish
        if ($role === 'parish_admin' && isset($user['parish_id'])) {
            return [$user['parish_id']];
        }
        
        // Members - their own parish
        if (isset($user['parish_id'])) {
            return [$user['parish_id']];
        }
        
        return [];
    }
}

// ============================================
// IMPERSONATION FUNCTIONS
// ============================================

/**
 * Start impersonating another user (only if not already defined in security.php)
 */
if (!function_exists('start_impersonation')) {
    function start_impersonation($target_user_id) {
        global $pdo;
        
        // Check if already impersonating
        if (isset($_SESSION['impersonating'])) {
            return false;
        }
        
        // Get target user
        $target_user = get_user_by_id($target_user_id);
        if (!$target_user) {
            return false;
        }
        
        // Store original user ID
        $_SESSION['original_user_id'] = $_SESSION['user_id'];
        $_SESSION['original_role_level'] = $_SESSION['role_level'];
        $_SESSION['original_first_name'] = $_SESSION['first_name'];
        $_SESSION['original_last_name'] = $_SESSION['last_name'];
        
        // Set impersonation flag
        $_SESSION['impersonating'] = true;
        $_SESSION['impersonation_start_time'] = time();
        
        // Replace session with target user data
        $_SESSION['user_id'] = $target_user['id'];
        $_SESSION['username'] = $target_user['username'];
        $_SESSION['email'] = $target_user['email'];
        $_SESSION['first_name'] = $target_user['first_name'];
        $_SESSION['last_name'] = $target_user['last_name'];
        $_SESSION['role_level'] = $target_user['role_level'];
        $_SESSION['parish_id'] = $target_user['parish_id'];
        $_SESSION['deanery_id'] = $target_user['deanery_id'];
        $_SESSION['archdeaconry_id'] = $target_user['archdeaconry_id'];
        $_SESSION['diocese_id'] = $target_user['diocese_id'];
        $_SESSION['province_id'] = $target_user['province_id'];
        
        // Log impersonation start
        if (function_exists('log_activity')) {
            log_activity('IMPERSONATION_STARTED', null, null, [
                'original_user_id' => $_SESSION['original_user_id'],
                'target_user_id' => $target_user_id,
                'target_user_name' => $target_user['first_name'] . ' ' . $target_user['last_name']
            ]);
        }
        
        return true;
    }
}

/**
 * Stop impersonating and restore original user (only if not already defined in security.php)
 */
if (!function_exists('stop_impersonation')) {
    function stop_impersonation() {
        if (!isset($_SESSION['impersonating']) || !isset($_SESSION['original_user_id'])) {
            return false;
        }
        
        // Log impersonation end
        $duration = time() - ($_SESSION['impersonation_start_time'] ?? time());
        if (function_exists('log_activity')) {
            log_activity('IMPERSONATION_ENDED', null, null, [
                'original_user_id' => $_SESSION['original_user_id'],
                'impersonated_user_id' => $_SESSION['user_id'],
                'duration_seconds' => $duration
            ]);
        }
        
        // Get original user data
        $original_user = get_user_by_id($_SESSION['original_user_id']);
        if (!$original_user) {
            return false;
        }
        
        // Remove impersonation flags
        unset($_SESSION['impersonating']);
        unset($_SESSION['impersonation_start_time']);
        unset($_SESSION['original_user_id']);
        unset($_SESSION['original_role_level']);
        unset($_SESSION['original_first_name']);
        unset($_SESSION['original_last_name']);
        
        // Restore original session data
        $_SESSION['user_id'] = $original_user['id'];
        $_SESSION['username'] = $original_user['username'];
        $_SESSION['email'] = $original_user['email'];
        $_SESSION['first_name'] = $original_user['first_name'];
        $_SESSION['last_name'] = $original_user['last_name'];
        $_SESSION['role_level'] = $original_user['role_level'];
        $_SESSION['parish_id'] = $original_user['parish_id'];
        $_SESSION['deanery_id'] = $original_user['deanery_id'];
        $_SESSION['archdeaconry_id'] = $original_user['archdeaconry_id'];
        $_SESSION['diocese_id'] = $original_user['diocese_id'];
        $_SESSION['province_id'] = $original_user['province_id'];
        
        return true;
    }
}

/**
 * Check if currently impersonating
 */
if (!function_exists('is_impersonating')) {
    function is_impersonating() {
        return isset($_SESSION['impersonating']) && isset($_SESSION['original_user_id']);
    }
}

// just an example to add inside includes/rbac.php (DO NOT overwrite: add if missing)
$ROLE_PERMISSIONS = [
  'super_admin' => ['edit_member','manage_leadership','manage_families','manage_ministries','view_reports'],
  'national_admin' => ['manage_leadership','manage_ministries','view_reports'],
  'diocese_admin' => ['edit_member','view_reports'],
  'parish_admin' => ['edit_member','view_families'],
  'member' => ['view_profile','manage_family']
];

function checkPermission($perm){
  global $ROLE_PERMISSIONS;
  $role = $_SESSION['role_level'] ?? 'member';
  if (!isset($ROLE_PERMISSIONS[$role])) return false;
  return in_array($perm, $ROLE_PERMISSIONS[$role]);
}

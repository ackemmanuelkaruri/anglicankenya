<?php
/**
 * Dashboard Statistics Functions
 * Provides statistics data for different user roles
 */

require_once 'db.php';

/**
 * Get dashboard statistics based on user role
 * @return array Statistics data
 */
function get_dashboard_stats() {
    global $pdo, $user; // Use $user from dashboard.php instead of session
    
    $stats = [];
    $role_level = $_SESSION['role_level'] ?? 'member';
    $user_id = $_SESSION['user_id'];
    
    switch ($role_level) {
        case 'super_admin':
            // Get counts for all administrative levels
            $stats['total_provinces'] = get_table_count('provinces');
            $stats['total_dioceses'] = get_table_count('dioceses');
            $stats['total_archdeaconries'] = get_table_count('archdeaconries');
            $stats['total_deaneries'] = get_table_count('deaneries');
            $stats['total_parishes'] = get_table_count('parishes');
            $stats['total_users'] = get_table_count('users');
            break;
            
        case 'diocese_admin':
            // Get diocese_id from $user array instead of session
            $diocese_id = $user['diocese_id'] ?? 0;
            
            if (!$diocese_id) {
                // Return zeros if no diocese assigned
                $stats['total_archdeaconries'] = 0;
                $stats['total_deaneries'] = 0;
                $stats['total_parishes'] = 0;
                $stats['total_users'] = 0;
            } else {
                $stats['total_archdeaconries'] = get_count_by_field('archdeaconries', 'diocese_id', $diocese_id);
                $stats['total_deaneries'] = get_deaneries_count_by_diocese($diocese_id);
                $stats['total_parishes'] = get_parishes_count_by_diocese($diocese_id);
                $stats['total_users'] = get_users_count_by_diocese($diocese_id);
            }
            break;
            
        case 'archdeaconry_admin':
            // Get archdeaconry_id from $user array
            $archdeaconry_id = $user['archdeaconry_id'] ?? 0;
            
            if (!$archdeaconry_id) {
                $stats['total_deaneries'] = 0;
                $stats['total_parishes'] = 0;
                $stats['total_users'] = 0;
            } else {
                $stats['total_deaneries'] = get_count_by_field('deaneries', 'archdeaconry_id', $archdeaconry_id);
                $stats['total_parishes'] = get_parishes_count_by_archdeaconry($archdeaconry_id);
                $stats['total_users'] = get_users_count_by_archdeaconry($archdeaconry_id);
            }
            break;
            
        case 'deanery_admin':
            // Get deanery_id from $user array
            $deanery_id = $user['deanery_id'] ?? 0;
            
            if (!$deanery_id) {
                $stats['total_parishes'] = 0;
                $stats['total_users'] = 0;
            } else {
                $stats['total_parishes'] = get_count_by_field('parishes', 'deanery_id', $deanery_id);
                $stats['total_users'] = get_users_count_by_deanery($deanery_id);
            }
            break;
            
        case 'parish_admin':
            // Get parish_id from $user array
            $parish_id = $user['parish_id'] ?? 0;
            
            if (!$parish_id) {
                $stats['total_members'] = 0;
                $stats['total_families'] = 0;
                $stats['total_ministries'] = 0;
            } else {
                $stats['total_members'] = get_members_count_by_parish($parish_id);
                $stats['total_families'] = get_families_count_by_parish($parish_id);
                $stats['total_ministries'] = get_ministries_count_by_parish($parish_id);
            }
            break;
            
        case 'member':
            // Get personal statistics
            $stats['family_members'] = get_family_member_count($user_id);
            $stats['my_ministries'] = get_user_ministries_count($user_id);
            break;
    }
    
    return $stats;
}

/**
 * Helper function to get count of records in a table
 */
function get_table_count($table) {
    global $pdo;
    
    try {
        // Use backticks to safely quote table names
        $allowed_tables = ['provinces', 'dioceses', 'archdeaconries', 'deaneries', 'parishes', 'users'];
        if (!in_array($table, $allowed_tables)) {
            return 0;
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_table_count($table): " . $e->getMessage());
        return 0;
    }
}

/**
 * Helper function to get count by field value
 */
function get_count_by_field($table, $field, $value) {
    global $pdo;
    
    try {
        // Whitelist table and field names to prevent SQL injection
        $allowed_combinations = [
            'archdeaconries.diocese_id',
            'deaneries.archdeaconry_id',
            'parishes.deanery_id'
        ];
        
        $combination = "$table.$field";
        if (!in_array($combination, $allowed_combinations)) {
            error_log("Invalid table.field combination: $combination");
            return 0;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$field` = ?");
        $stmt->execute([$value]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_count_by_field($table, $field): " . $e->getMessage());
        return 0;
    }
}

/**
 * Get deaneries count by diocese
 */
function get_deaneries_count_by_diocese($diocese_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM deaneries d
            JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
            WHERE a.diocese_id = ?
        ");
        $stmt->execute([$diocese_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_deaneries_count_by_diocese: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get parishes count by diocese
 */
function get_parishes_count_by_diocese($diocese_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM parishes p
            JOIN deaneries d ON p.deanery_id = d.deanery_id
            JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
            WHERE a.diocese_id = ?
        ");
        $stmt->execute([$diocese_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_parishes_count_by_diocese: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get users count by diocese
 */
function get_users_count_by_diocese($diocese_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM users u
            WHERE u.diocese_id = ?
        ");
        $stmt->execute([$diocese_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_users_count_by_diocese: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get parishes count by archdeaconry
 */
function get_parishes_count_by_archdeaconry($archdeaconry_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM parishes p
            JOIN deaneries d ON p.deanery_id = d.deanery_id
            WHERE d.archdeaconry_id = ?
        ");
        $stmt->execute([$archdeaconry_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_parishes_count_by_archdeaconry: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get users count by archdeaconry
 */
function get_users_count_by_archdeaconry($archdeaconry_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM users u
            WHERE u.archdeaconry_id = ?
        ");
        $stmt->execute([$archdeaconry_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_users_count_by_archdeaconry: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get users count by deanery
 */
function get_users_count_by_deanery($deanery_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM users u
            WHERE u.deanery_id = ?
        ");
        $stmt->execute([$deanery_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_users_count_by_deanery: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get members count by parish
 */
function get_members_count_by_parish($parish_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM users u
            WHERE u.parish_id = ? AND u.role_level = 'member'
        ");
        $stmt->execute([$parish_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_members_count_by_parish: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get families count by parish
 */
function get_families_count_by_parish($parish_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM families
            WHERE parish_id = ?
        ");
        $stmt->execute([$parish_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_families_count_by_parish: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get ministries count by parish
 */
function get_ministries_count_by_parish($parish_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM ministries
            WHERE parish_id = ?
        ");
        $stmt->execute([$parish_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_ministries_count_by_parish: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get family member count
 */
function get_family_member_count($user_id) {
    global $pdo;
    
    try {
        // Count family members through family_members table
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM family_members fm
            JOIN families f ON fm.family_id = f.family_id
            WHERE f.head_user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_family_member_count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get user ministries count
 */
function get_user_ministries_count($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM ministry_members
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error in get_user_ministries_count: " . $e->getMessage());
        return 0;
    }
}

// REMOVED DUPLICATE FUNCTIONS:
// - get_role_display_name() - Already defined in security.php
// - get_scope_display() - Already defined in security.php
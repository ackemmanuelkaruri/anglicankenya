<?php
/**
 * ============================================
 * SERVICE SCHEDULE HELPER FUNCTIONS
 * Anglican Church Management System
 * ============================================
 */

if (!defined('DB_INCLUDED')) {
    die('Direct access not permitted');
}

/**
 * Create or update annual theme
 */
function save_annual_theme($year, $month, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO annual_themes (
                year, month, theme_name, theme_description, 
                scripture_reference, parish_id, diocese_id, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                theme_name = VALUES(theme_name),
                theme_description = VALUES(theme_description),
                scripture_reference = VALUES(scripture_reference),
                updated_at = NOW()
        ");
        
        return $stmt->execute([
            $year, $month, $data['theme_name'], 
            $data['theme_description'] ?? null,
            $data['scripture_reference'] ?? null,
            $data['parish_id'] ?? null,
            $data['diocese_id'] ?? null,
            $_SESSION['user_id']
        ]);
        
    } catch (PDOException $e) {
        error_log("Error saving theme: " . $e->getMessage());
        return false;
    }
}

/**
 * Get annual themes
 */
function get_annual_themes($year, $parish_id = null) {
    global $pdo;
    
    try {
        $where = "year = ?";
        $params = [$year];
        
        if ($parish_id) {
            $where .= " AND (parish_id = ? OR parish_id IS NULL)";
            $params[] = $parish_id;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM annual_themes WHERE $where ORDER BY month ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching themes: " . $e->getMessage());
        return [];
    }
}

/**
 * Create service schedule
 */
function create_service_schedule($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO service_schedule (
                service_date, service_time, parish_id, theme_id,
                topic, scripture_reference, leading_group,
                service_leader_id, preacher_id, preacher_name,
                special_activities, notes, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['service_date'],
            $data['service_time'] ?? '10:00:00',
            $data['parish_id'],
            $data['theme_id'] ?? null,
            $data['topic'],
            $data['scripture_reference'] ?? null,
            $data['leading_group'] ?? null,
            $data['service_leader_id'] ?? null,
            $data['preacher_id'] ?? null,
            $data['preacher_name'] ?? null,
            $data['special_activities'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'scheduled',
            $_SESSION['user_id']
        ]);
        
        $schedule_id = $pdo->lastInsertId();
        
        log_activity('SERVICE_SCHEDULED', 'service_schedule', $schedule_id);
        
        return $schedule_id;
        
    } catch (PDOException $e) {
        error_log("Error creating service schedule: " . $e->getMessage());
        return false;
    }
}

/**
 * Get service schedules
 */
function get_service_schedules($filters = []) {
    global $pdo;
    
    $where_conditions = ['1=1'];
    $params = [];
    
    $scope = get_user_scope();
    
    switch ($scope['type']) {
        case 'parish_admin':
        case 'member':
            $where_conditions[] = "s.parish_id = ?";
            $params[] = $scope['parish_id'];
            break;
        case 'deanery_admin':
            $where_conditions[] = "p.deanery_id = ?";
            $params[] = $scope['deanery_id'];
            break;
        case 'archdeaconry_admin':
            $where_conditions[] = "d.archdeaconry_id = ?";
            $params[] = $scope['archdeaconry_id'];
            break;
        case 'diocese_admin':
            $where_conditions[] = "a.diocese_id = ?";
            $params[] = $scope['diocese_id'];
            break;
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "s.service_date >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "s.service_date <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['parish_id'])) {
        $where_conditions[] = "s.parish_id = ?";
        $params[] = $filters['parish_id'];
    }
    
    $where_sql = implode(' AND ', $where_conditions);
    
    try {
        $sql = "
            SELECT 
                s.*,
                p.parish_name,
                t.theme_name,
                CONCAT(sl.first_name, ' ', sl.last_name) AS service_leader_name,
                CONCAT(pr.first_name, ' ', pr.last_name) AS preacher_db_name
            FROM service_schedule s
            LEFT JOIN parishes p ON s.parish_id = p.parish_id
            LEFT JOIN deaneries d ON p.deanery_id = d.deanery_id
            LEFT JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN annual_themes t ON s.theme_id = t.theme_id
            LEFT JOIN users sl ON s.service_leader_id = sl.id
            LEFT JOIN users pr ON s.preacher_id = pr.id
            WHERE {$where_sql}
            ORDER BY s.service_date DESC, s.service_time DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching schedules: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single service schedule
 */
function get_service_schedule($schedule_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                p.parish_name,
                t.theme_name,
                t.theme_description,
                CONCAT(sl.first_name, ' ', sl.last_name) AS service_leader_name,
                sl.email AS service_leader_email,
                CONCAT(pr.first_name, ' ', pr.last_name) AS preacher_db_name,
                pr.email AS preacher_email
            FROM service_schedule s
            LEFT JOIN parishes p ON s.parish_id = p.parish_id
            LEFT JOIN annual_themes t ON s.theme_id = t.theme_id
            LEFT JOIN users sl ON s.service_leader_id = sl.id
            LEFT JOIN users pr ON s.preacher_id = pr.id
            WHERE s.schedule_id = ?
        ");
        
        $stmt->execute([$schedule_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching schedule: " . $e->getMessage());
        return false;
    }
}

/**
 * Update service schedule
 */
function update_service_schedule($schedule_id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE service_schedule SET
                service_date = ?,
                service_time = ?,
                topic = ?,
                scripture_reference = ?,
                leading_group = ?,
                service_leader_id = ?,
                preacher_id = ?,
                preacher_name = ?,
                special_activities = ?,
                notes = ?,
                status = ?,
                updated_at = NOW()
            WHERE schedule_id = ?
        ");
        
        $result = $stmt->execute([
            $data['service_date'],
            $data['service_time'],
            $data['topic'],
            $data['scripture_reference'] ?? null,
            $data['leading_group'] ?? null,
            $data['service_leader_id'] ?? null,
            $data['preacher_id'] ?? null,
            $data['preacher_name'] ?? null,
            $data['special_activities'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'scheduled',
            $schedule_id
        ]);
        
        if ($result) {
            log_activity('SERVICE_UPDATED', 'service_schedule', $schedule_id);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Error updating schedule: " . $e->getMessage());
        return false;
    }
}

/**
 * Get next Sunday's service
 */
function get_next_sunday_service($parish_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                p.parish_name,
                t.theme_name,
                CONCAT(sl.first_name, ' ', sl.last_name) AS service_leader_name,
                COALESCE(CONCAT(pr.first_name, ' ', pr.last_name), s.preacher_name) AS preacher_name
            FROM service_schedule s
            LEFT JOIN parishes p ON s.parish_id = p.parish_id
            LEFT JOIN annual_themes t ON s.theme_id = t.theme_id
            LEFT JOIN users sl ON s.service_leader_id = sl.id
            LEFT JOIN users pr ON s.preacher_id = pr.id
            WHERE s.parish_id = ?
            AND s.service_date >= CURDATE()
            AND DAYOFWEEK(s.service_date) = 1
            AND s.status = 'scheduled'
            ORDER BY s.service_date ASC
            LIMIT 1
        ");
        
        $stmt->execute([$parish_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching next Sunday service: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark service as completed
 */
function complete_service($schedule_id, $actual_attendance = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE service_schedule SET
                status = 'completed',
                actual_attendance = ?,
                updated_at = NOW()
            WHERE schedule_id = ?
        ");
        
        return $stmt->execute([$actual_attendance, $schedule_id]);
        
    } catch (PDOException $e) {
        error_log("Error completing service: " . $e->getMessage());
        return false;
    }
}
<?php
/**
 * ============================================
 * EVENTS MANAGEMENT HELPER FUNCTIONS
 * Anglican Church Management System
 * ============================================
 */

if (!defined('DB_INCLUDED')) {
    die('Direct access not permitted');
}

/**
 * Create a new event
 * 
 * @param array $data Event data
 * @return int|false Event ID or false on failure
 */
function create_event($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO events (
                event_name, event_description, event_type,
                event_date, start_time, end_time,
                is_recurring, recurrence_pattern, recurrence_end_date,
                parish_id, location_name, location_address,
                has_streaming, streaming_platform, streaming_link,
                capacity, requires_rsvp, rsvp_deadline,
                leader_id, speaker_name,
                scope_type, scope_id, visibility,
                send_notification, send_reminder, reminder_hours_before,
                status, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $data['event_name'],
            $data['event_description'] ?? null,
            $data['event_type'],
            $data['event_date'],
            $data['start_time'],
            $data['end_time'] ?? null,
            $data['is_recurring'] ?? 0,
            $data['recurrence_pattern'] ?? null,
            $data['recurrence_end_date'] ?? null,
            $data['parish_id'] ?? null,
            $data['location_name'],
            $data['location_address'] ?? null,
            $data['has_streaming'] ?? 0,
            $data['streaming_platform'] ?? null,
            $data['streaming_link'] ?? null,
            $data['capacity'] ?? null,
            $data['requires_rsvp'] ?? 0,
            $data['rsvp_deadline'] ?? null,
            $data['leader_id'] ?? null,
            $data['speaker_name'] ?? null,
            $data['scope_type'] ?? 'parish',
            $data['scope_id'] ?? $data['parish_id'],
            $data['visibility'] ?? 'members_only',
            $data['send_notification'] ?? 1,
            $data['send_reminder'] ?? 1,
            $data['reminder_hours_before'] ?? 24,
            $data['status'] ?? 'draft',
            $_SESSION['user_id']
        ]);
        
        $event_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity('EVENT_CREATED', 'events', $event_id, [
            'event_name' => $data['event_name'],
            'event_date' => $data['event_date']
        ]);
        
        return $event_id;
        
    } catch (PDOException $e) {
        error_log("Error creating event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get events based on scope and filters
 * 
 * @param array $filters Optional filters
 * @return array List of events
 */
function get_events($filters = []) {
    global $pdo;
    
    $where_conditions = ['1=1'];
    $params = [];
    
    // Apply scope-based filtering
    $scope = get_user_scope();
    
    switch ($scope['type']) {
        case 'parish_admin':
        case 'member':
            $where_conditions[] = "(e.parish_id = ? OR e.scope_type = 'diocese' OR e.scope_type = 'all')";
            $params[] = $scope['parish_id'];
            break;
            
        case 'deanery_admin':
            $where_conditions[] = "(p.deanery_id = ? OR e.scope_type = 'diocese' OR e.scope_type = 'all')";
            $params[] = $scope['deanery_id'];
            break;
            
        case 'archdeaconry_admin':
            $where_conditions[] = "(d.archdeaconry_id = ? OR e.scope_type = 'diocese' OR e.scope_type = 'all')";
            $params[] = $scope['archdeaconry_id'];
            break;
            
        case 'diocese_admin':
            $where_conditions[] = "(a.diocese_id = ? OR e.scope_type = 'all')";
            $params[] = $scope['diocese_id'];
            break;
    }
    
    // Status filter
    if (!empty($filters['status'])) {
        $where_conditions[] = "e.status = ?";
        $params[] = $filters['status'];
    } else {
        $where_conditions[] = "e.status != 'cancelled'";
    }
    
    // Date filters
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "e.event_date >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "e.event_date <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Event type filter
    if (!empty($filters['event_type'])) {
        $where_conditions[] = "e.event_type = ?";
        $params[] = $filters['event_type'];
    }
    
    // Parish filter
    if (!empty($filters['parish_id'])) {
        $where_conditions[] = "e.parish_id = ?";
        $params[] = $filters['parish_id'];
    }
    
    $where_sql = implode(' AND ', $where_conditions);
    
    try {
        $sql = "
            SELECT 
                e.*,
                p.parish_name,
                d.deanery_name,
                a.archdeaconry_name,
                dio.diocese_name,
                CONCAT(u.first_name, ' ', u.last_name) AS leader_name,
                (SELECT COUNT(*) FROM event_rsvp WHERE event_id = e.event_id AND rsvp_status = 'attending') AS attendee_count
            FROM events e
            LEFT JOIN parishes p ON e.parish_id = p.parish_id
            LEFT JOIN deaneries d ON p.deanery_id = d.deanery_id
            LEFT JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN dioceses dio ON a.diocese_id = dio.diocese_id
            LEFT JOIN users u ON e.leader_id = u.id
            WHERE {$where_sql}
            ORDER BY e.event_date ASC, e.start_time ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching events: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single event by ID
 * 
 * @param int $event_id
 * @return array|false Event data or false
 */
function get_event($event_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.*,
                p.parish_name,
                d.deanery_name,
                a.archdeaconry_name,
                dio.diocese_name,
                CONCAT(u.first_name, ' ', u.last_name) AS leader_name,
                u.email AS leader_email,
                (SELECT COUNT(*) FROM event_rsvp WHERE event_id = e.event_id AND rsvp_status = 'attending') AS attendee_count,
                (SELECT COUNT(*) FROM event_rsvp WHERE event_id = e.event_id AND did_attend = 1) AS actual_attendee_count
            FROM events e
            LEFT JOIN parishes p ON e.parish_id = p.parish_id
            LEFT JOIN deaneries d ON p.deanery_id = d.deanery_id
            LEFT JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN dioceses dio ON a.diocese_id = dio.diocese_id
            LEFT JOIN users u ON e.leader_id = u.id
            WHERE e.event_id = ?
        ");
        
        $stmt->execute([$event_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching event: " . $e->getMessage());
        return false;
    }
}

/**
 * Update event
 * 
 * @param int $event_id
 * @param array $data Updated data
 * @return bool Success status
 */
function update_event($event_id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE events SET
                event_name = ?,
                event_description = ?,
                event_type = ?,
                event_date = ?,
                start_time = ?,
                end_time = ?,
                location_name = ?,
                location_address = ?,
                has_streaming = ?,
                streaming_platform = ?,
                streaming_link = ?,
                capacity = ?,
                requires_rsvp = ?,
                rsvp_deadline = ?,
                leader_id = ?,
                speaker_name = ?,
                visibility = ?,
                status = ?,
                updated_at = NOW()
            WHERE event_id = ?
        ");
        
        $result = $stmt->execute([
            $data['event_name'],
            $data['event_description'] ?? null,
            $data['event_type'],
            $data['event_date'],
            $data['start_time'],
            $data['end_time'] ?? null,
            $data['location_name'],
            $data['location_address'] ?? null,
            $data['has_streaming'] ?? 0,
            $data['streaming_platform'] ?? null,
            $data['streaming_link'] ?? null,
            $data['capacity'] ?? null,
            $data['requires_rsvp'] ?? 0,
            $data['rsvp_deadline'] ?? null,
            $data['leader_id'] ?? null,
            $data['speaker_name'] ?? null,
            $data['visibility'] ?? 'members_only',
            $data['status'] ?? 'published',
            $event_id
        ]);
        
        if ($result) {
            log_activity('EVENT_UPDATED', 'events', $event_id);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Error updating event: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete event
 * 
 * @param int $event_id
 * @return bool Success status
 */
function delete_event($event_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $result = $stmt->execute([$event_id]);
        
        if ($result) {
            log_activity('EVENT_DELETED', 'events', $event_id);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Error deleting event: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancel event
 * 
 * @param int $event_id
 * @param bool $notify Send notification
 * @return bool Success status
 */
function cancel_event($event_id, $notify = true) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE events SET status = 'cancelled', updated_at = NOW() WHERE event_id = ?");
        $result = $stmt->execute([$event_id]);
        
        if ($result) {
            log_activity('EVENT_CANCELLED', 'events', $event_id);
            
            // TODO: Send cancellation notification if $notify = true
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Error cancelling event: " . $e->getMessage());
        return false;
    }
}

/**
 * Submit RSVP for event
 * 
 * @param int $event_id
 * @param int $user_id
 * @param string $status attending|not_attending|maybe
 * @param array $additional_data Optional additional data
 * @return bool Success status
 */
function submit_rsvp($event_id, $user_id, $status, $additional_data = []) {
    global $pdo;
    
    try {
        // Check if RSVP already exists
        $stmt = $pdo->prepare("SELECT rsvp_id FROM event_rsvp WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing RSVP
            $stmt = $pdo->prepare("
                UPDATE event_rsvp SET
                    rsvp_status = ?,
                    attendance_type = ?,
                    number_of_guests = ?,
                    special_requirements = ?,
                    rsvp_date = NOW()
                WHERE rsvp_id = ?
            ");
            
            $result = $stmt->execute([
                $status,
                $additional_data['attendance_type'] ?? 'physical',
                $additional_data['number_of_guests'] ?? 0,
                $additional_data['special_requirements'] ?? null,
                $existing['rsvp_id']
            ]);
        } else {
            // Insert new RSVP
            $stmt = $pdo->prepare("
                INSERT INTO event_rsvp (
                    event_id, user_id, rsvp_status,
                    attendance_type, number_of_guests, special_requirements
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $event_id,
                $user_id,
                $status,
                $additional_data['attendance_type'] ?? 'physical',
                $additional_data['number_of_guests'] ?? 0,
                $additional_data['special_requirements'] ?? null
            ]);
        }
        
        if ($result) {
            log_activity('EVENT_RSVP', 'event_rsvp', $event_id, [
                'user_id' => $user_id,
                'status' => $status
            ]);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Error submitting RSVP: " . $e->getMessage());
        return false;
    }
}

/**
 * Get RSVP list for event
 * 
 * @param int $event_id
 * @param string $status Optional status filter
 * @return array List of RSVPs
 */
function get_event_rsvps($event_id, $status = null) {
    global $pdo;
    
    try {
        $where = "r.event_id = ?";
        $params = [$event_id];
        
        if ($status) {
            $where .= " AND r.rsvp_status = ?";
            $params[] = $status;
        }
        
        $sql = "
            SELECT 
                r.*,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                u.email,
                u.phone_number
            FROM event_rsvp r
            JOIN users u ON r.user_id = u.id
            WHERE {$where}
            ORDER BY r.rsvp_date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching RSVPs: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has RSVP'd to event
 * 
 * @param int $event_id
 * @param int $user_id
 * @return array|false RSVP data or false
 */
function get_user_rsvp($event_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM event_rsvp WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching user RSVP: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark attendance for RSVP
 * 
 * @param int $rsvp_id
 * @param bool $attended
 * @return bool Success status
 */
function mark_attendance($rsvp_id, $attended) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE event_rsvp SET
                did_attend = ?,
                check_in_time = NOW()
            WHERE rsvp_id = ?
        ");
        
        return $stmt->execute([$attended ? 1 : 0, $rsvp_id]);
        
    } catch (PDOException $e) {
        error_log("Error marking attendance: " . $e->getMessage());
        return false;
    }
}

/**
 * Get upcoming events for a user
 * 
 * @param int $user_id
 * @param int $limit
 * @return array List of events
 */
function get_user_upcoming_events($user_id, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.*,
                p.parish_name,
                r.rsvp_status,
                r.attendance_type
            FROM events e
            LEFT JOIN parishes p ON e.parish_id = p.parish_id
            LEFT JOIN event_rsvp r ON e.event_id = r.event_id AND r.user_id = ?
            WHERE e.event_date >= CURDATE()
            AND e.status = 'published'
            ORDER BY e.event_date ASC, e.start_time ASC
            LIMIT ?
        ");
        
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching upcoming events: " . $e->getMessage());
        return [];
    }
}

/**
 * Check event capacity
 * 
 * @param int $event_id
 * @return array Capacity info
 */
function check_event_capacity($event_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.capacity,
                COUNT(r.rsvp_id) AS registered_count
            FROM events e
            LEFT JOIN event_rsvp r ON e.event_id = r.event_id AND r.rsvp_status = 'attending'
            WHERE e.event_id = ?
            GROUP BY e.event_id
        ");
        
        $stmt->execute([$event_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $capacity = $result['capacity'];
            $registered = $result['registered_count'];
            
            return [
                'capacity' => $capacity,
                'registered' => $registered,
                'available' => $capacity ? ($capacity - $registered) : null,
                'is_full' => $capacity ? ($registered >= $capacity) : false
            ];
        }
        
        return ['capacity' => null, 'registered' => 0, 'available' => null, 'is_full' => false];
        
    } catch (PDOException $e) {
        error_log("Error checking capacity: " . $e->getMessage());
        return ['capacity' => null, 'registered' => 0, 'available' => null, 'is_full' => false];
    }
}
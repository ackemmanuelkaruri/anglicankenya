<?php
// modules/events/send_event_reminders.php

/**
 * Send event reminders to members based on their preferences
 * This should be called via cron job daily
 */

// Include necessary files
require_once '../../config/database.php';
require_once 'includes/email_communication_helper.php';
require_once 'includes/events_helper.php';

function send_event_reminders() {
    global $pdo;
    
    try {
        // Get events happening in the next 24 hours that need reminders
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   p.parish_name,
                   CONCAT(u.first_name, ' ', u.last_name) AS leader_name
            FROM events e
            LEFT JOIN parishes p ON e.parish_id = p.parish_id
            LEFT JOIN users u ON e.leader_id = u.id
            WHERE e.status = 'published'
            AND e.event_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
            AND e.send_reminder = 1
            AND (e.reminder_sent = 0 OR e.reminder_sent IS NULL)
        ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reminder_count = 0;
        
        foreach ($events as $event) {
            // Get users who want reminders for this event
            $reminder_users = get_users_who_want_reminders($event);
            
            if (!empty($reminder_users)) {
                foreach ($reminder_users as $user) {
                    if (send_event_reminder_email($event, $user)) {
                        $reminder_count++;
                    }
                }
                
                // Mark event as reminder sent
                $stmt = $pdo->prepare("
                    UPDATE events 
                    SET reminder_sent = 1, 
                        reminder_sent_at = NOW() 
                    WHERE event_id = ?
                ");
                $stmt->execute([$event['event_id']]);
            }
        }
        
        echo "Sent {$reminder_count} event reminders at " . date('Y-m-d H:i:s') . "\n";
        return $reminder_count;
        
    } catch (Exception $e) {
        error_log("Error sending event reminders: " . $e->getMessage());
        echo "Error sending event reminders: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Get users who want reminders for a specific event
 */
function get_users_who_want_reminders($event) {
    global $pdo;
    
    try {
        // Get users in the event's scope who want reminders
        $sql = "
            SELECT u.id, u.email, u.first_name, u.last_name, u.parish_id
            FROM users u
            WHERE u.event_reminders = 1
            AND u.email_opt_in = 1
            AND u.email IS NOT NULL 
            AND u.email != ''
        ";
        
        // Add scope filtering
        switch ($event['scope_type']) {
            case 'parish':
                $sql .= " AND u.parish_id = " . (int)$event['scope_id'];
                break;
            case 'deanery':
                $sql .= " AND u.parish_id IN (SELECT parish_id FROM parishes WHERE deanery_id = " . (int)$event['scope_id'] . ")";
                break;
            case 'archdeaconry':
                $sql .= " AND u.parish_id IN (
                    SELECT p.parish_id FROM parishes p 
                    JOIN deaneries d ON p.deanery_id = d.deanery_id 
                    WHERE d.archdeaconry_id = " . (int)$event['scope_id'] . "
                )";
                break;
            case 'diocese':
                $sql .= " AND u.parish_id IN (
                    SELECT p.parish_id FROM parishes p 
                    JOIN deaneries d ON p.deanery_id = d.deanery_id 
                    JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id 
                    WHERE a.diocese_id = " . (int)$event['scope_id'] . "
                )";
                break;
        }
        
        // Exclude users who already RSVP'd "not_attending"
        $sql .= " AND u.id NOT IN (
            SELECT user_id FROM event_rsvp 
            WHERE event_id = " . (int)$event['event_id'] . " 
            AND rsvp_status = 'not_attending'
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting reminder users: " . $e->getMessage());
        return [];
    }
}

/**
 * Send reminder email to a single user
 */
function send_event_reminder_email($event, $user) {
    // Get reminder template
    $template = get_email_template('event_reminder');
    
    if (!$template) {
        error_log("No event reminder template found");
        return false;
    }
    
    // Prepare email content
    $subject = str_replace('{{event_name}}', $event['event_name'], $template['subject']);
    $body = prepare_reminder_email_body($template['body_html'], $event, $user);
    
    // Send email
    return send_email_smtp($user['email'], $subject, $body);
}

/**
 * Prepare reminder email body with variables
 */
function prepare_reminder_email_body($template, $event, $user) {
    $variables = [
        '{{first_name}}' => $user['first_name'],
        '{{last_name}}' => $user['last_name'],
        '{{event_name}}' => $event['event_name'],
        '{{event_description}}' => $event['event_description'] ?? '',
        '{{event_date}}' => date('l, F j, Y', strtotime($event['event_date'])),
        '{{event_time}}' => date('g:i A', strtotime($event['start_time'])),
        '{{location}}' => $event['location_name'],
        '{{streaming_link}}' => $event['streaming_link'] ?? '',
        '{{parish_name}}' => $event['parish_name'] ?? '',
        '{{rsvp_link}}' => get_base_url() . '/modules/events/rsvp.php?event_id=' . $event['event_id']
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $template);
}

/**
 * Get base URL for the application
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

// Send reminders
send_event_reminders();
?>
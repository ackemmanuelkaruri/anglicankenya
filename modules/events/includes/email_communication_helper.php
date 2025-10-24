<?php
// modules/events/includes/email_communication_helper.php (additions)

/**
 * Get recipients based on admin scope
 */
function get_recipients_by_admin_scope($recipient_type, $scope_id = null) {
    global $pdo;
    
    $recipients = [];
    $user_scope = get_user_scope();
    
    try {
        switch ($recipient_type) {
            case 'parish':
                // Parish admin can only send to their parish
                if ($user_scope['type'] == 'parish_admin') {
                    $scope_id = $user_scope['parish_id'];
                }
                // Higher admins can send to any parish
                elseif (in_array($user_scope['type'], ['deanery_admin', 'archdeaconry_admin', 'diocese_admin'])) {
                    if (!$scope_id) {
                        // Get all parishes in their scope
                        $recipients = get_parishes_in_scope($user_scope);
                        return $recipients;
                    }
                }
                
                if ($scope_id) {
                    $stmt = $pdo->prepare("
                        SELECT id, email, first_name, last_name 
                        FROM users 
                        WHERE parish_id = ? AND email_opt_in = 1 AND email IS NOT NULL AND email != ''
                    ");
                    $stmt->execute([$scope_id]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'deanery':
                // Deanery admin can only send to their deanery
                if ($user_scope['type'] == 'deanery_admin') {
                    $scope_id = $user_scope['deanery_id'];
                }
                // Higher admins can send to any deanery
                elseif (in_array($user_scope['type'], ['archdeaconry_admin', 'diocese_admin'])) {
                    if (!$scope_id) {
                        // Get all deaneries in their scope
                        $recipients = get_deaneries_in_scope($user_scope);
                        return $recipients;
                    }
                }
                
                if ($scope_id) {
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.email, u.first_name, u.last_name 
                        FROM users u
                        JOIN parishes p ON u.parish_id = p.parish_id
                        WHERE p.deanery_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                    ");
                    $stmt->execute([$scope_id]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'archdeaconry':
                // Archdeaconry admin can only send to their archdeaconry
                if ($user_scope['type'] == 'archdeaconry_admin') {
                    $scope_id = $user_scope['archdeaconry_id'];
                }
                // Diocese admin can send to any archdeaconry
                elseif ($user_scope['type'] == 'diocese_admin') {
                    if (!$scope_id) {
                        // Get all archdeaconries in their scope
                        $recipients = get_archdeaconries_in_scope($user_scope);
                        return $recipients;
                    }
                }
                
                if ($scope_id) {
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.email, u.first_name, u.last_name 
                        FROM users u
                        JOIN parishes p ON u.parish_id = p.parish_id
                        JOIN deaneries d ON p.deanery_id = d.deanery_id
                        WHERE d.archdeaconry_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                    ");
                    $stmt->execute([$scope_id]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'diocese':
                // Only diocese admin can send to diocese
                if ($user_scope['type'] == 'diocese_admin') {
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.email, u.first_name, u.last_name 
                        FROM users u
                        JOIN parishes p ON u.parish_id = p.parish_id
                        JOIN deaneries d ON p.deanery_id = d.deanery_id
                        JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
                        WHERE a.diocese_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                    ");
                    $stmt->execute([$user_scope['diocese_id']]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                break;
        }
        
    } catch (PDOException $e) {
        error_log("Error getting recipients by scope: " . $e->getMessage());
    }
    
    return $recipients;
}

/**
 * Get all parishes in admin's scope
 */
function get_parishes_in_scope($user_scope) {
    global $pdo;
    $recipients = [];
    
    try {
        switch ($user_scope['type']) {
            case 'parish_admin':
                $stmt = $pdo->prepare("
                    SELECT id, email, first_name, last_name 
                    FROM users 
                    WHERE parish_id = ? AND email_opt_in = 1 AND email IS NOT NULL AND email != ''
                ");
                $stmt->execute([$user_scope['parish_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'deanery_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    WHERE p.deanery_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['deanery_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'archdeaconry_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    JOIN deaneries d ON p.deanery_id = d.deanery_id
                    WHERE d.archdeaconry_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['archdeaconry_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'diocese_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    JOIN deaneries d ON p.deanery_id = d.deaneryry_id
                    JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
                    WHERE a.diocese_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['diocese_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    } catch (PDOException $e) {
        error_log("Error getting parishes in scope: " . $e->getMessage());
    }
    
    return $recipients;
}

/**
 * Get all deaneries in admin's scope
 */
function get_deaneries_in_scope($user_scope) {
    global $pdo;
    $recipients = [];
    
    try {
        switch ($user_scope['type']) {
            case 'deanery_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    WHERE p.deanery_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['deanery_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'archdeaconry_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    JOIN deaneries d ON p.deanery_id = d.deanery_id
                    WHERE d.archdeaconry_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['archdeaconry_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'diocese_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    JOIN deaneries d ON p.deanery_id = d.deanery_id
                    JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
                    WHERE a.diocese_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['diocese_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    } catch (PDOException $e) {
        error_log("Error getting deaneries in scope: " . $e->getMessage());
    }
    
    return $recipients;
}

/**
 * Get all archdeaconries in admin's scope
 */
function get_archdeaconries_in_scope($user_scope) {
    global $pdo;
    $recipients = [];
    
    try {
        switch ($user_scope['type']) {
            case 'archdeaconry_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    JOIN deaneries d ON p.deanery_id = d.deanery_id
                    WHERE d.archdeaconry_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['archdeaconry_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'diocese_admin':
                $stmt = $pdo->prepare("
                    SELECT u.id, u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN parishes p ON u.parish_id = p.parish_id
                    JOIN deaneries d ON p.deanery_id = d.deanery_id
                    JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
                    WHERE a.diocese_id = ? AND u.email_opt_in = 1 AND u.email IS NOT NULL AND u.email != ''
                ");
                $stmt->execute([$user_scope['diocese_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    } catch (PDOException $e) {
        error_log("Error getting archdeaconries in scope: " . $e->getMessage());
    }
    
    return $recipients;
}

/**
 * Update create_email_campaign to use scope-based recipients
 */
function create_email_campaign($data) {
    global $pdo;
    
    try {
        // Get recipients based on admin scope
        $recipients = get_recipients_by_admin_scope($data['recipient_type'], $data['recipient_scope_id']);
        
        if (empty($recipients)) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO email_campaigns (
                campaign_name, campaign_type, template_id,
                subject, body_html, body_plain,
                recipient_type, recipient_scope_id, custom_recipient_list,
                related_event_id, related_service_id,
                send_immediately, scheduled_send_time,
                status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['campaign_name'],
            $data['campaign_type'],
            $data['template_id'] ?? null,
            $data['subject'],
            $data['body_html'],
            $data['body_plain'] ?? strip_tags($data['body_html']),
            $data['recipient_type'],
            $data['recipient_scope_id'] ?? null,
            isset($data['custom_recipient_list']) ? json_encode($data['custom_recipient_list']) : null,
            $data['related_event_id'] ?? null,
            $data['related_service_id'] ?? null,
            $data['send_immediately'] ?? 1,
            $data['scheduled_send_time'] ?? null,
            $data['send_immediately'] ? 'scheduled' : 'draft',
            $_SESSION['user_id']
        ]);
        
        $campaign_id = $pdo->lastInsertId();
        
        // Update total recipients count
        $stmt = $pdo->prepare("UPDATE email_campaigns SET total_recipients = ? WHERE campaign_id = ?");
        $stmt->execute([count($recipients), $campaign_id]);
        
        // Queue emails
        foreach ($recipients as $recipient) {
            queue_email($campaign_id, $recipient);
        }
        
        log_activity('EMAIL_CAMPAIGN_CREATED', 'email_campaigns', $campaign_id);
        
        return $campaign_id;
        
    } catch (PDOException $e) {
        error_log("Error creating campaign: " . $e->getMessage());
        return false;
    }
}
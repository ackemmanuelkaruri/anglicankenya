<?php
// This file tracks streaming link clicks for analytics

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Get and validate data
 $platform = $_POST['platform'] ?? '';
 $url = $_POST['url'] ?? '';
 $event_id = $_POST['event_id'] ?? null;
 $user_id = $_SESSION['user_id'] ?? null;

if (empty($platform) || empty($url)) {
    http_response_code(400);
    exit('Bad Request');
}

try {
    global $pdo;
    
    // Insert tracking record
    $stmt = $pdo->prepare("
        INSERT INTO streaming_clicks (
            event_id, user_id, platform, url, 
            ip_address, user_agent, click_time
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $event_id,
        $user_id,
        $platform,
        $url,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Update event streaming click count
    if ($event_id) {
        $stmt = $pdo->prepare("
            UPDATE events 
            SET streaming_clicks = COALESCE(streaming_clicks, 0) + 1
            WHERE event_id = ?
        ");
        $stmt->execute([$event_id]);
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Log error
    error_log("Error tracking streaming click: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
<?php
// Get event ID
 $event_id = $_GET['id'] ?? 0;

if (!$event_id) {
    header('Location: index.php');
    exit;
}

// Get event details
 $event = get_event($event_id);

if (!$event) {
    header('Location: index.php');
    exit;
}

// Check if user has permission to edit this event
 $user_scope = get_user_scope();
if ($user_scope['type'] == 'member' && $event['created_by'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

 $page_title = 'Edit Event';
 $breadcrumb = 'Edit Event';
 $form_title = 'Edit Event: ' . htmlspecialchars($event['event_name']);
 $form_action = 'edit_event.php?id=' . $event_id;
 $submit_text = 'Update Event';

// Include header
require_once 'templates/header.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'event_name' => $_POST['event_name'],
        'event_description' => $_POST['event_description'],
        'event_type' => $_POST['event_type'],
        'event_date' => $_POST['event_date'],
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time'] ?? null,
        'location_name' => $_POST['location_name'],
        'location_address' => $_POST['location_address'],
        'has_streaming' => isset($_POST['has_streaming']) ? 1 : 0,
        'streaming_platform' => $_POST['streaming_platform'] ?? null,
        'streaming_link' => $_POST['streaming_link'] ?? null,
        'capacity' => $_POST['capacity'] ?? null,
        'requires_rsvp' => isset($_POST['requires_rsvp']) ? 1 : 0,
        'rsvp_deadline' => $_POST['rsvp_deadline'] ?? null,
        'leader_id' => $_POST['leader_id'] ?? null,
        'speaker_name' => $_POST['speaker_name'] ?? null,
        'visibility' => $_POST['visibility'] ?? 'members_only',
        'status' => $_POST['status'] ?? 'published'
    ];
    
    $success = update_event($event_id, $data);
    
    if ($success) {
        // Send notification if status changed to published and notification is enabled
        if ($data['status'] == 'published' && $event['status'] != 'published' && isset($_POST['send_notification'])) {
            send_event_notification($event_id);
        }
        
        // Redirect to event view
        header('Location: view_event.php?id=' . $event_id);
        exit;
    } else {
        echo '<div class="alert alert-danger">Error updating event. Please try again.</div>';
    }
}

// Show form
include 'templates/event_form.php';

// Include footer
require_once 'templates/footer.php';
?>
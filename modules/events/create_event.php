<?php
 $page_title = 'Create Event';
 $breadcrumb = 'Create Event';
 $form_title = 'Create New Event';
 $form_action = 'create_event.php';
 $submit_text = 'Create Event';

// Include header
require_once __DIR__ . '/../../includes/dashboard_header.php'; // ✅ USE THIS

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
        'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
        'recurrence_pattern' => $_POST['recurrence_pattern'] ?? null,
        'recurrence_end_date' => $_POST['recurrence_end_date'] ?? null,
        'has_streaming' => isset($_POST['has_streaming']) ? 1 : 0,
        'streaming_platform' => $_POST['streaming_platform'] ?? null,
        'streaming_link' => $_POST['streaming_link'] ?? null,
        'capacity' => $_POST['capacity'] ?? null,
        'requires_rsvp' => isset($_POST['requires_rsvp']) ? 1 : 0,
        'rsvp_deadline' => $_POST['rsvp_deadline'] ?? null,
        'leader_id' => $_POST['leader_id'] ?? null,
        'speaker_name' => $_POST['speaker_name'] ?? null,
        'scope_type' => $_POST['scope_type'] ?? 'parish',
        'scope_id' => $_SESSION['parish_id'] ?? null,
        'visibility' => $_POST['visibility'] ?? 'members_only',
        'send_notification' => isset($_POST['send_notification']) ? 1 : 0,
        'send_reminder' => isset($_POST['send_reminder']) ? 1 : 0,
        'reminder_hours_before' => $_POST['reminder_hours_before'] ?? 24,
        'status' => $_POST['status'] ?? 'draft'
    ];
    
    $event_id = create_event($data);
    
    if ($event_id) {
        // Send notification if enabled
        if ($data['send_notification'] && $data['status'] == 'published') {
            send_event_notification($event_id);
        }
        
        // Redirect to event view
        header('Location: view_event.php?id=' . $event_id);
        exit;
    } else {
        echo '<div class="alert alert-danger">Error creating event. Please try again.</div>';
    }
}

// Show form
include 'templates/event_form.php';

// Include footer
require_once 'templates/footer.php';
?>
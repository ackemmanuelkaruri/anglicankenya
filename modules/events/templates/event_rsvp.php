<?php
// Get event ID
 $event_id = $_POST['event_id'] ?? $_GET['event_id'] ?? 0;

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

// Get user RSVP if exists
 $user_rsvp = get_user_rsvp($event_id, $_SESSION['user_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rsvp_status = $_POST['rsvp_status'];
    $attendance_type = $_POST['attendance_type'] ?? 'physical';
    $number_of_guests = $_POST['number_of_guests'] ?? 0;
    $special_requirements = $_POST['special_requirements'] ?? '';
    
    $success = submit_rsvp($event_id, $_SESSION['user_id'], $rsvp_status, [
        'attendance_type' => $attendance_type,
        'number_of_guests' => $number_of_guests,
        'special_requirements' => $special_requirements
    ]);
    
    if ($success) {
        // Return JSON response if AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Redirect to event view
        header('Location: view_event.php?id=' . $event_id);
        exit;
    } else {
        // Return JSON response if AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Error submitting RSVP']);
            exit;
        }
        
        echo '<div class="alert alert-danger">Error submitting RSVP. Please try again.</div>';
    }
}

 $page_title = 'Event RSVP';
 $breadcrumb = 'Event RSVP';

// Include header
require_once 'templates/header.php';

// Show RSVP form
include 'templates/event_rsvp.php';

// Include footer
require_once 'templates/footer.php';
?>
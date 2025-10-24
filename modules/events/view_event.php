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

// Get user RSVP if exists
 $user_rsvp = get_user_rsvp($event_id, $_SESSION['user_id']);

// Get RSVP list if admin
 $rsvps = [];
if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'pastor') {
    $rsvps = get_event_rsvps($event_id);
}

 $page_title = 'View Event';
 $breadcrumb = 'View Event';

// Include header
require_once 'templates/header.php';

// Show event details
include 'templates/event_view.php';

// Include footer
require_once 'templates/footer.php';
?>

<?php if ($event['has_streaming'] == 1): ?>
    <div class="streaming-section">
        <h3><i class="fas fa-video"></i> Online Streaming</h3>
        <p>This event will be streamed online.</p>
        <?php if (!empty($event['streaming_link'])): ?>
            <a href="<?php echo htmlspecialchars($event['streaming_link']); ?>" 
               class="streaming-link" 
               target="_blank"
               data-platform="<?php echo $event['streaming_platform']; ?>"
               data-event-id="<?php echo $event['event_id']; ?>">
                <i class="fab fa-<?php echo $event['streaming_platform']; ?>"></i> Join on <?php echo ucfirst($event['streaming_platform']); ?>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>
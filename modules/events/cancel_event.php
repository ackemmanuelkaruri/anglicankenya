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

// Check if user has permission to cancel this event
 $user_scope = get_user_scope();
if ($user_scope['type'] == 'member') {
    header('Location: index.php');
    exit;
}

 $page_title = 'Cancel Event';
 $breadcrumb = 'Cancel Event';

// Include header
require_once 'templates/header.php';

// Process cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notify_members = isset($_POST['notify_members']) ? 1 : 0;
    $cancellation_reason = $_POST['cancellation_reason'] ?? '';
    
    // Cancel the event
    $success = cancel_event($event_id, $notify_members);
    
    if ($success) {
        // Send cancellation notification if requested
        if ($notify_members) {
            // Create cancellation email campaign
            $campaign_data = [
                'campaign_name' => 'Event Cancellation: ' . $event['event_name'],
                'campaign_type' => 'event_notification',
                'subject' => 'Event Cancelled: ' . $event['event_name'],
                'body_html' => '
                    <h2>Event Cancelled</h2>
                    <p>Dear {{first_name}},</p>
                    <p>The following event has been cancelled:</p>
                    <p><strong>Event:</strong> ' . htmlspecialchars($event['event_name']) . '<br>
                    <strong>Date:</strong> ' . date('l, F j, Y', strtotime($event['event_date'])) . '<br>
                    <strong>Time:</strong> ' . date('g:i A', strtotime($event['start_time'])) . '</p>
                    ' . (!empty($cancellation_reason) ? '<p><strong>Reason:</strong> ' . htmlspecialchars($cancellation_reason) . '</p>' : '') . '
                    <p>We apologize for any inconvenience this may cause.</p>
                ',
                'recipient_type' => $event['scope_type'],
                'recipient_scope_id' => $event['scope_id'],
                'related_event_id' => $event_id,
                'send_immediately' => 1
            ];
            
            create_email_campaign($campaign_data);
        }
        
        // Redirect to events list
        header('Location: index.php?cancelled=1');
        exit;
    } else {
        echo '<div class="alert alert-danger">Error cancelling event. Please try again.</div>';
    }
}
?>

<div class="events-container">
    <div class="events-header">
        <h1>Cancel Event</h1>
        <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn"><i class="fas fa-arrow-left"></i> Back to Event</a>
    </div>
    
    <div class="event-details">
        <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
        
        <div class="detail-item">
            <span class="detail-label">Date:</span>
            <span class="detail-value"><?php echo date('l, F j, Y', strtotime($event['event_date'])); ?></span>
        </div>
        
        <div class="detail-item">
            <span class="detail-label">Time:</span>
            <span class="detail-value">
                <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                <?php if (!empty($event['end_time'])): ?>
                    - <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                <?php endif; ?>
            </span>
        </div>
        
        <div class="detail-item">
            <span class="detail-label">Location:</span>
            <span class="detail-value"><?php echo htmlspecialchars($event['location_name']); ?></span>
        </div>
        
        <div class="detail-item">
            <span class="detail-label">Type:</span>
            <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?></span>
        </div>
        
        <?php if (!empty($event['attendee_count'])): ?>
            <div class="detail-item">
                <span class="detail-label">People Attending:</span>
                <span class="detail-value"><?php echo $event['attendee_count']; ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="event-form">
        <form method="post" action="cancel_event.php?id=<?php echo $event_id; ?>">
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle"></i> Confirm Cancellation</h4>
                <p>Are you sure you want to cancel this event? This action cannot be undone.</p>
            </div>
            
            <div class="form-group">
                <label for="cancellation_reason">Cancellation Reason (Optional)</label>
                <textarea id="cancellation_reason" name="cancellation_reason" class="form-control" rows="3" placeholder="Please provide a reason for cancelling this event..."></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="notify_members" name="notify_members" class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="notify_members">
                        Notify all members who RSVP'd or were invited to this event
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-danger">Cancel Event</button>
                <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Back to Event</a>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
require_once 'templates/footer.php';
?>
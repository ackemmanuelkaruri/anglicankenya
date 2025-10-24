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

// Check if user has permission to send notifications
 $user_scope = get_user_scope();
if ($user_scope['type'] == 'member') {
    header('Location: index.php');
    exit;
}

 $page_title = 'Send Event Notification';
 $breadcrumb = 'Send Notification';

// Include header
require_once 'templates/header.php';

// Process notification sending
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_type = $_POST['notification_type'] ?? 'reminder';
    $custom_message = $_POST['custom_message'] ?? '';
    $recipient_type = $_POST['recipient_type'] ?? $event['scope_type'];
    $recipient_scope_id = $_POST['recipient_scope_id'] ?? $event['scope_id'];
    
    // Get email template
    $template = get_email_template('event_notification');
    
    if (!$template) {
        echo '<div class="alert alert-danger">No email template found. Please contact administrator.</div>';
    } else {
        // Prepare email content
        $subject = $template['subject'];
        $body_html = $template['body_html'];
        
        // Add custom message if provided
        if (!empty($custom_message)) {
            $body_html = '<p>' . nl2br(htmlspecialchars($custom_message)) . '</p><hr>' . $body_html;
        }
        
        // Adjust subject based on notification type
        if ($notification_type == 'reminder') {
            $subject = 'Reminder: ' . $subject;
        } elseif ($notification_type == 'update') {
            $subject = 'Event Update: ' . $subject;
        }
        
        // Create email campaign
        $campaign_data = [
            'campaign_name' => $notification_type . ' for ' . $event['event_name'],
            'campaign_type' => 'event_notification',
            'template_id' => $template['template_id'],
            'subject' => $subject,
            'body_html' => prepare_event_notification_body($body_html, $event),
            'recipient_type' => $recipient_type,
            'recipient_scope_id' => $recipient_scope_id,
            'related_event_id' => $event_id,
            'send_immediately' => 1
        ];
        
        $campaign_id = create_email_campaign($campaign_data);
        
        if ($campaign_id) {
            // Redirect to event view with success message
            header('Location: view_event.php?id=' . $event_id . '&sent=1');
            exit;
        } else {
            echo '<div class="alert alert-danger">Error sending notification. Please try again.</div>';
        }
    }
}
?>

<div class="events-container">
    <div class="events-header">
        <h1>Send Event Notification</h1>
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
        
        <?php if (!empty($event['attendee_count'])): ?>
            <div class="detail-item">
                <span class="detail-label">People Attending:</span>
                <span class="detail-value"><?php echo $event['attendee_count']; ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="event-form">
        <form method="post" action="send_notification.php?id=<?php echo $event_id; ?>">
            <div class="form-group">
                <label for="notification_type">Notification Type</label>
                <select id="notification_type" name="notification_type" class="form-control">
                    <option value="reminder">Event Reminder</option>
                    <option value="update">Event Update</option>
                    <option value="custom">Custom Message</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="custom_message">Custom Message (Optional)</label>
                <textarea id="custom_message" name="custom_message" class="form-control" rows="4" placeholder="Add a custom message to the notification..."></textarea>
                <small class="form-text text-muted">This message will appear at the top of the email notification.</small>
            </div>
            
            <div class="form-group">
                <label for="recipient_type">Send To</label>
                <select id="recipient_type" name="recipient_type" class="form-control">
                    <option value="<?php echo $event['scope_type']; ?>" selected>
                        <?php echo ucfirst($event['scope_type']); ?> Members
                    </option>
                    <option value="all_members">All Members</option>
                    <option value="parish">Specific Parish</option>
                    <option value="diocese">Specific Diocese</option>
                </select>
            </div>
            
            <div class="form-group" id="scope_id_group" style="display: none;">
                <label for="recipient_scope_id">Select <?php echo ucfirst($event['scope_type']); ?></label>
                <select id="recipient_scope_id" name="recipient_scope_id" class="form-control">
                    <?php
                    // Get parishes/dioceses based on selection
                    if ($event['scope_type'] == 'parish') {
                        $parishes = get_parishes();
                        foreach ($parishes as $parish) {
                            echo '<option value="' . $parish['parish_id'] . '">' . htmlspecialchars($parish['parish_name']) . '</option>';
                        }
                    } elseif ($event['scope_type'] == 'diocese') {
                        $dioceses = get_dioceses();
                        foreach ($dioceses as $diocese) {
                            echo '<option value="' . $diocese['diocese_id'] . '">' . htmlspecialchars($diocese['diocese_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> Preview</h4>
                    <p>This notification will include:</p>
                    <ul>
                        <li>Event name, date, time, and location</li>
                        <li>Streaming link (if available)</li>
                        <li>RSVP status (if applicable)</li>
                        <li>Your custom message (if provided)</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Send Notification</button>
                <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recipientType = document.getElementById('recipient_type');
    const scopeIdGroup = document.getElementById('scope_id_group');
    
    recipientType.addEventListener('change', function() {
        if (this.value === 'parish' || this.value === 'diocese') {
            scopeIdGroup.style.display = 'block';
        } else {
            scopeIdGroup.style.display = 'none';
        }
    });
});
</script>

<?php
// Include footer
require_once 'templates/footer.php';
?>
<div class="events-container">
    <div class="events-header">
        <h1>RSVP for Event</h1>
        <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="btn"><i class="fas fa-arrow-left"></i> Back to Event</a>
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
        
        <?php if ($event['has_streaming'] == 1): ?>
            <div class="detail-item">
                <span class="detail-label">Streaming:</span>
                <span class="detail-value">
                    <a href="<?php echo htmlspecialchars($event['streaming_link']); ?>" target="_blank">
                        <?php echo ucfirst($event['streaming_platform']); ?> Link
                    </a>
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="rsvp-section">
        <h2>RSVP for this Event</h2>
        
        <?php if ($user_rsvp): ?>
            <div class="alert alert-info">
                <p>You have already submitted an RSVP for this event.</p>
                <p><strong>Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $user_rsvp['rsvp_status'])); ?></p>
                <p><strong>Attendance Type:</strong> <?php echo ucfirst($user_rsvp['attendance_type']); ?></p>
                <?php if ($user_rsvp['number_of_guests'] > 0): ?>
                    <p><strong>Number of Guests:</strong> <?php echo $user_rsvp['number_of_guests']; ?></p>
                <?php endif; ?>
                <?php if (!empty($user_rsvp['special_requirements'])): ?>
                    <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($user_rsvp['special_requirements']); ?></p>
                <?php endif; ?>
                
                <form method="post" action="rsvp.php">
                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                    <input type="hidden" name="update_rsvp" value="1">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update RSVP</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <p>Will you be attending this event?</p>
            
            <form id="rsvp-form" method="post" action="rsvp.php">
                <input type="hidden" id="event_id" name="event_id" value="<?php echo $event['event_id']; ?>">
                <input type="hidden" id="rsvp_status" name="rsvp_status" value="">
                
                <div class="rsvp-options">
                    <div class="rsvp-option" data-status="attending">
                        <i class="fas fa-check-circle"></i>
                        <div>Attending</div>
                    </div>
                    <div class="rsvp-option" data-status="not_attending">
                        <i class="fas fa-times-circle"></i>
                        <div>Not Attending</div>
                    </div>
                    <div class="rsvp-option" data-status="maybe">
                        <i class="fas fa-question-circle"></i>
                        <div>Maybe</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="attendance_type">Attendance Type</label>
                    <select id="attendance_type" name="attendance_type" class="form-control">
                        <option value="physical">Physical</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="number_of_guests">Number of Guests</label>
                    <input type="number" id="number_of_guests" name="number_of_guests" class="form-control" value="0" min="0">
                </div>
                
                <div class="form-group">
                    <label for="special_requirements">Special Requirements</label>
                    <textarea id="special_requirements" name="special_requirements" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Submit RSVP</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
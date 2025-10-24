<div class="events-container">
    <div class="events-header">
        <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>
        <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Events</a>
    </div>
    
    <div class="event-details">
        <h2>Event Details</h2>
        
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
            <span class="detail-value">
                <?php echo htmlspecialchars($event['location_name']); ?>
                <?php if (!empty($event['location_address'])): ?>
                    <br><?php echo nl2br(htmlspecialchars($event['location_address'])); ?>
                <?php endif; ?>
            </span>
        </div>
        
        <div class="detail-item">
            <span class="detail-label">Type:</span>
            <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?></span>
        </div>
        
        <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
                <span class="badge badge-<?php echo $event['status'] == 'published' ? 'success' : ($event['status'] == 'cancelled' ? 'danger' : ($event['status'] == 'completed' ? 'info' : 'warning')); ?>">
                    <?php echo ucfirst($event['status']); ?>
                </span>
            </span>
        </div>
        
        <?php if (!empty($event['event_description'])): ?>
            <div class="detail-item">
                <span class="detail-label">Description:</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($event['leader_name'])): ?>
            <div class="detail-item">
                <span class="detail-label">Leader:</span>
                <span class="detail-value"><?php echo htmlspecialchars($event['leader_name']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($event['speaker_name'])): ?>
            <div class="detail-item">
                <span class="detail-label">Speaker:</span>
                <span class="detail-value"><?php echo htmlspecialchars($event['speaker_name']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($event['capacity'])): ?>
            <div class="detail-item">
                <span class="detail-label">Capacity:</span>
                <span class="detail-value"><?php echo $event['capacity']; ?> people</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($event['attendee_count'])): ?>
            <div class="detail-item">
                <span class="detail-label">Attending:</span>
                <span class="detail-value"><?php echo $event['attendee_count']; ?> people</span>
            </div>
        <?php endif; ?>
        
        <?php if ($event['is_recurring'] == 1): ?>
            <div class="detail-item">
                <span class="detail-label">Recurrence:</span>
                <span class="detail-value">
                    <?php echo ucfirst($event['recurrence_pattern']); ?>
                    <?php if (!empty($event['recurrence_end_date'])): ?>
                        until <?php echo date('F j, Y', strtotime($event['recurrence_end_date'])); ?>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
        
        <?php if ($event['has_streaming'] == 1): ?>
            <div class="streaming-section">
                <h3><i class="fas fa-video"></i> Online Streaming</h3>
                <p>This event will be streamed online.</p>
                <?php if (!empty($event['streaming_link'])): ?>
                    <a href="<?php echo htmlspecialchars($event['streaming_link']); ?>" class="streaming-link" target="_blank">
                        <i class="fab fa-<?php echo $event['streaming_platform']; ?>"></i> Join on <?php echo ucfirst($event['streaming_platform']); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($event['requires_rsvp'] == 1): ?>
        <div class="rsvp-section">
            <h2>RSVP for this Event</h2>
            
            <?php if ($user_rsvp): ?>
                <div class="alert alert-info">
                    <p id="current-rsvp-status" class="rsvp-status rsvp-<?php echo $user_rsvp['rsvp_status']; ?>">
                        Your RSVP: <?php echo ucfirst(str_replace('_', ' ', $user_rsvp['rsvp_status'])); ?>
                    </p>
                    <p>Attendance Type: <?php echo ucfirst($user_rsvp['attendance_type']); ?></p>
                    <?php if ($user_rsvp['number_of_guests'] > 0): ?>
                        <p>Number of Guests: <?php echo $user_rsvp['number_of_guests']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user_rsvp['special_requirements'])): ?>
                        <p>Special Requirements: <?php echo htmlspecialchars($user_rsvp['special_requirements']); ?></p>
                    <?php endif; ?>
                    
                    <a href="rsvp.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-primary">Change RSVP</a>
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
    <?php endif; ?>
    
    <?php if ($user_role == 'admin' || $user_role == 'pastor'): ?>
        <div class="event-admin-actions">
            <h2>Admin Actions</h2>
            <div class="event-actions">
                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-warning">Edit Event</a>
                <?php if ($event['status'] != 'cancelled'): ?>
                    <a href="cancel_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this event?');">Cancel Event</a>
                <?php endif; ?>
                <a href="send_notification.php?id=<?php echo $event['event_id']; ?>" class="btn btn-info">Send Notification</a>
            </div>
        </div>
        
        <?php if (!empty($rsvps)): ?>
            <div class="rsvp-list">
                <h2>RSVP List</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Attendance Type</th>
                            <th>Guests</th>
                            <th>Special Requirements</th>
                            <th>RSVP Date</th>
                            <th>Attended</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rsvps as $rsvp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rsvp['user_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $rsvp['rsvp_status'] == 'attending' ? 'success' : ($rsvp['rsvp_status'] == 'not_attending' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $rsvp['rsvp_status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($rsvp['attendance_type']); ?></td>
                                <td><?php echo $rsvp['number_of_guests']; ?></td>
                                <td><?php echo htmlspecialchars($rsvp['special_requirements'] ?: 'None'); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($rsvp['rsvp_date'])); ?></td>
                                <td>
                                    <?php if ($rsvp['did_attend'] === null): ?>
                                        <span class="text-muted">Not recorded</span>
                                    <?php elseif ($rsvp['did_attend'] == 1): ?>
                                        <span class="text-success">Yes</span>
                                    <?php else: ?>
                                        <span class="text-danger">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
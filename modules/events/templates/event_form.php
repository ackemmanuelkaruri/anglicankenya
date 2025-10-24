<div class="events-container">
    <div class="events-header">
        <h1><?php echo $form_title ?? 'Create Event'; ?></h1>
        <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Events</a>
    </div>
    
    <div class="event-form">
        <form id="event-form" method="post" action="<?php echo $form_action ?? 'create_event.php'; ?>">
            <?php if (isset($event_id)): ?>
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="event_name">Event Name *</label>
                <input type="text" id="event_name" name="event_name" class="form-control" value="<?php echo $event['event_name'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="event_description">Event Description</label>
                <textarea id="event_description" name="event_description" class="form-control" rows="4"><?php echo $event['event_description'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="event_type">Event Type *</label>
                    <select id="event_type" name="event_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="service" <?php echo (isset($event['event_type']) && $event['event_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                        <option value="bible_study" <?php echo (isset($event['event_type']) && $event['event_type'] == 'bible_study') ? 'selected' : ''; ?>>Bible Study</option>
                        <option value="prayer_meeting" <?php echo (isset($event['event_type']) && $event['event_type'] == 'prayer_meeting') ? 'selected' : ''; ?>>Prayer Meeting</option>
                        <option value="youth_fellowship" <?php echo (isset($event['event_type']) && $event['event_type'] == 'youth_fellowship') ? 'selected' : ''; ?>>Youth Fellowship</option>
                        <option value="conference" <?php echo (isset($event['event_type']) && $event['event_type'] == 'conference') ? 'selected' : ''; ?>>Conference</option>
                        <option value="crusade" <?php echo (isset($event['event_type']) && $event['event_type'] == 'crusade') ? 'selected' : ''; ?>>Crusade</option>
                        <option value="wedding" <?php echo (isset($event['event_type']) && $event['event_type'] == 'wedding') ? 'selected' : ''; ?>>Wedding</option>
                        <option value="funeral" <?php echo (isset($event['event_type']) && $event['event_type'] == 'funeral') ? 'selected' : ''; ?>>Funeral</option>
                        <option value="other" <?php echo (isset($event['event_type']) && $event['event_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="visibility">Visibility</label>
                    <select id="visibility" name="visibility" class="form-control">
                        <option value="public" <?php echo (isset($event['visibility']) && $event['visibility'] == 'public') ? 'selected' : ''; ?>>Public</option>
                        <option value="members_only" <?php echo (!isset($event['visibility']) || $event['visibility'] == 'members_only') ? 'selected' : ''; ?>>Members Only</option>
                        <option value="private" <?php echo (isset($event['visibility']) && $event['visibility'] == 'private') ? 'selected' : ''; ?>>Private</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input type="date" id="event_date" name="event_date" class="form-control" value="<?php echo $event['event_date'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" class="form-control" value="<?php echo $event['start_time'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" class="form-control" value="<?php echo $event['end_time'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="location_name">Location *</label>
                <input type="text" id="location_name" name="location_name" class="form-control" value="<?php echo $event['location_name'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="location_address">Location Address</label>
                <textarea id="location_address" name="location_address" class="form-control" rows="2"><?php echo $event['location_address'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="is_recurring" name="is_recurring" class="form-check-input" value="1" <?php echo (isset($event['is_recurring']) && $event['is_recurring'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_recurring">This is a recurring event</label>
                </div>
            </div>
            
            <div id="recurrence-options" style="display: <?php echo (isset($event['is_recurring']) && $event['is_recurring'] == 1) ? 'block' : 'none'; ?>;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="recurrence_pattern">Recurrence Pattern</label>
                        <select id="recurrence_pattern" name="recurrence_pattern" class="form-control">
                            <option value="">Select Pattern</option>
                            <option value="daily" <?php echo (isset($event['recurrence_pattern']) && $event['recurrence_pattern'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo (isset($event['recurrence_pattern']) && $event['recurrence_pattern'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo (isset($event['recurrence_pattern']) && $event['recurrence_pattern'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="recurrence_end_date">Recurrence End Date</label>
                        <input type="date" id="recurrence_end_date" name="recurrence_end_date" class="form-control" value="<?php echo $event['recurrence_end_date'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="has_streaming" name="has_streaming" class="form-check-input" value="1" <?php echo (isset($event['has_streaming']) && $event['has_streaming'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="has_streaming">This event will be streamed online</label>
                </div>
            </div>
            
            <div id="streaming-options" style="display: <?php echo (isset($event['has_streaming']) && $event['has_streaming'] == 1) ? 'block' : 'none'; ?>;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="streaming_platform">Streaming Platform</label>
                        <select id="streaming_platform" name="streaming_platform" class="form-control">
                            <option value="">Select Platform</option>
                            <option value="youtube" <?php echo (isset($event['streaming_platform']) && $event['streaming_platform'] == 'youtube') ? 'selected' : ''; ?>>YouTube</option>
                            <option value="facebook" <?php echo (isset($event['streaming_platform']) && $event['streaming_platform'] == 'facebook') ? 'selected' : ''; ?>>Facebook</option>
                            <option value="zoom" <?php echo (isset($event['streaming_platform']) && $event['streaming_platform'] == 'zoom') ? 'selected' : ''; ?>>Zoom</option>
                            <option value="custom" <?php echo (isset($event['streaming_platform']) && $event['streaming_platform'] == 'custom') ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="streaming_link">Streaming Link</label>
                        <input type="url" id="streaming_link" name="streaming_link" class="form-control" value="<?php echo $event['streaming_link'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="capacity">Capacity (leave blank for unlimited)</label>
                    <input type="number" id="capacity" name="capacity" class="form-control" value="<?php echo $event['capacity'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="requires_rsvp" name="requires_rsvp" class="form-check-input" value="1" <?php echo (isset($event['requires_rsvp']) && $event['requires_rsvp'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="requires_rsvp">Requires RSVP</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="rsvp_deadline">RSVP Deadline</label>
                <input type="datetime-local" id="rsvp_deadline" name="rsvp_deadline" class="form-control" value="<?php echo $event['rsvp_deadline'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="leader_id">Event Leader</label>
                <select id="leader_id" name="leader_id" class="form-control">
                    <option value="">Select Leader</option>
                    <?php
                    // Get list of users who can be leaders
                    $leaders = get_users_by_role(['admin', 'pastor', 'deacon', 'elder']);
                    foreach ($leaders as $leader) {
                        $selected = (isset($event['leader_id']) && $event['leader_id'] == $leader['id']) ? 'selected' : '';
                        echo "<option value=\"{$leader['id']}\" {$selected}>{$leader['first_name']} {$leader['last_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="speaker_name">Speaker Name (if not in system)</label>
                <input type="text" id="speaker_name" name="speaker_name" class="form-control" value="<?php echo $event['speaker_name'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="scope_type">Scope</label>
                <select id="scope_type" name="scope_type" class="form-control">
                    <option value="parish" <?php echo (!isset($event['scope_type']) || $event['scope_type'] == 'parish') ? 'selected' : ''; ?>>Parish</option>
                    <option value="deanery" <?php echo (isset($event['scope_type']) && $event['scope_type'] == 'deanery') ? 'selected' : ''; ?>>Deanery</option>
                    <option value="archdeaconry" <?php echo (isset($event['scope_type']) && $event['scope_type'] == 'archdeaconry') ? 'selected' : ''; ?>>Archdeaconry</option>
                    <option value="diocese" <?php echo (isset($event['scope_type']) && $event['scope_type'] == 'diocese') ? 'selected' : ''; ?>>Diocese</option>
                    <option value="all" <?php echo (isset($event['scope_type']) && $event['scope_type'] == 'all') ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="send_notification" name="send_notification" class="form-check-input" value="1" <?php echo (!isset($event['send_notification']) || $event['send_notification'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="send_notification">Send notification to members</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="send_reminder" name="send_reminder" class="form-check-input" value="1" <?php echo (!isset($event['send_reminder']) || $event['send_reminder'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="send_reminder">Send reminder before event</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reminder_hours_before">Reminder Hours Before</label>
                <input type="number" id="reminder_hours_before" name="reminder_hours_before" class="form-control" value="<?php echo $event['reminder_hours_before'] ?? 24; ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="draft" <?php echo (isset($event['status']) && $event['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo (!isset($event['status']) || $event['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                    <option value="cancelled" <?php echo (isset($event['status']) && $event['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="completed" <?php echo (isset($event['status']) && $event['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><?php echo $submit_text ?? 'Create Event'; ?></button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
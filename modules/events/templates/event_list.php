<div class="events-container">
    <div class="events-header">
        <h1>Events</h1>
        <a href="create_event.php" class="btn"><i class="fas fa-plus"></i> Create Event</a>
    </div>
    
    <div class="events-filters">
        <form method="get" action="index.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="filter_date_from">Date From</label>
                    <input type="date" id="filter_date_from" name="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="filter_date_to">Date To</label>
                    <input type="date" id="filter_date_to" name="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="filter_event_type">Event Type</label>
                    <select id="filter_event_type" name="event_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="service" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                        <option value="bible_study" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'bible_study') ? 'selected' : ''; ?>>Bible Study</option>
                        <option value="prayer_meeting" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'prayer_meeting') ? 'selected' : ''; ?>>Prayer Meeting</option>
                        <option value="youth_fellowship" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'youth_fellowship') ? 'selected' : ''; ?>>Youth Fellowship</option>
                        <option value="conference" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'conference') ? 'selected' : ''; ?>>Conference</option>
                        <option value="crusade" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'crusade') ? 'selected' : ''; ?>>Crusade</option>
                        <option value="wedding" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'wedding') ? 'selected' : ''; ?>>Wedding</option>
                        <option value="funeral" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'funeral') ? 'selected' : ''; ?>>Funeral</option>
                        <option value="other" <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filter_status">Status</label>
                    <select id="filter_status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="published" <?php echo (isset($_GET['status']) && $_GET['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo (isset($_GET['status']) && $_GET['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="events-view-toggle">
        <div class="btn-group">
            <a href="index.php?view=list" class="btn <?php echo (!isset($_GET['view']) || $_GET['view'] == 'list') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-list"></i> List View</a>
            <a href="index.php?view=calendar" class="btn <?php echo (isset($_GET['view']) && $_GET['view'] == 'calendar') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-calendar-alt"></i> Calendar View</a>
        </div>
    </div>
    
    <?php if (empty($events)): ?>
        <div class="alert alert-info">No events found. <a href="create_event.php">Create a new event</a>.</div>
    <?php else: ?>
        <div class="events-list">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></div>
                    
                    <div class="event-meta">
                        <div class="event-meta-item">
                            <i class="fas fa-calendar-day"></i> 
                            <?php echo date('D, M j, Y', strtotime($event['event_date'])); ?>
                        </div>
                        <div class="event-meta-item">
                            <i class="fas fa-clock"></i> 
                            <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                            <?php if (!empty($event['end_time'])): ?>
                                - <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="event-meta-item">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($event['location_name']); ?>
                        </div>
                        <div class="event-meta-item">
                            <i class="fas fa-tag"></i> 
                            <?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?>
                        </div>
                        <div class="event-meta-item">
                            <span class="badge badge-<?php echo $event['status'] == 'published' ? 'success' : ($event['status'] == 'cancelled' ? 'danger' : ($event['status'] == 'completed' ? 'info' : 'warning')); ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($event['event_description'])): ?>
                        <div class="event-description">
                            <?php echo nl2br(htmlspecialchars(substr($event['event_description'], 0, 200) . (strlen($event['event_description']) > 200 ? '...' : ''))); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['has_streaming']) && $event['has_streaming'] == 1): ?>
                        <div class="streaming-section">
                            <i class="fas fa-video"></i> This event will be streamed online
                        </div>
                    <?php endif; ?>
                    
                    <div class="event-actions">
                        <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary">View</a>
                        <?php if ($user_role == 'admin' || $user_role == 'pastor'): ?>
                            <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-warning">Edit</a>
                            <?php if ($event['status'] != 'cancelled'): ?>
                                <a href="cancel_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this event?');">Cancel</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($event['requires_rsvp'] == 1): ?>
                            <a href="rsvp.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-success">RSVP</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
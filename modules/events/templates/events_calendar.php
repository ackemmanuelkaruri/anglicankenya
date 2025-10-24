<?php
// Get current month and year
 $current_month = $_GET['month'] ?? date('m');
 $current_year = $_GET['year'] ?? date('Y');

// Get events for the month
 $filters = [
    'date_from' => "$current_year-$current_month-01",
    'date_to' => date("$current_year-$current_month-t")
];
 $events = get_events($filters);

// Group events by date
 $events_by_date = [];
foreach ($events as $event) {
    $event_date = date('Y-m-d', strtotime($event['event_date']));
    if (!isset($events_by_date[$event_date])) {
        $events_by_date[$event_date] = [];
    }
    $events_by_date[$event_date][] = $event;
}

// Create calendar
 $first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
 $days_in_month = date('t', $first_day);
 $day_of_week = date('w', $first_day);

// Get month name
 $month_name = date('F Y', $first_day);
?>

<div class="calendar-container">
    <div class="calendar-header">
        <h2><?php echo $month_name; ?></h2>
        <div class="calendar-nav">
            <a href="index.php?view=calendar&month=<?php echo date('m', strtotime('-1 month', $first_day)); ?>&year=<?php echo date('Y', strtotime('-1 month', $first_day)); ?>" class="btn btn-sm btn-secondary">Previous</a>
            <a href="index.php?view=calendar&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-sm btn-primary">Current</a>
            <a href="index.php?view=calendar&month=<?php echo date('m', strtotime('+1 month', $first_day)); ?>&year=<?php echo date('Y', strtotime('+1 month', $first_day)); ?>" class="btn btn-sm btn-secondary">Next</a>
        </div>
    </div>
    
    <div class="calendar-grid">
        <!-- Day headers -->
        <div class="calendar-day-header">Sunday</div>
        <div class="calendar-day-header">Monday</div>
        <div class="calendar-day-header">Tuesday</div>
        <div class="calendar-day-header">Wednesday</div>
        <div class="calendar-day-header">Thursday</div>
        <div class="calendar-day-header">Friday</div>
        <div class="calendar-day-header">Saturday</div>
        
        <!-- Empty cells before first day of month -->
        <?php for ($i = 0; $i < $day_of_week; $i++): ?>
            <div class="calendar-day empty"></div>
        <?php endfor; ?>
        
        <!-- Days of the month -->
        <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
            <?php
            $date = "$current_year-$current_month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            $day_events = $events_by_date[$date] ?? [];
            $is_today = (date('Y-m-d') == $date);
            ?>
            <div class="calendar-day <?php echo !empty($day_events) ? 'has-events' : ''; ?> <?php echo $is_today ? 'today' : ''; ?>" data-date="<?php echo $date; ?>">
                <div class="calendar-day-number"><?php echo $day; ?></div>
                <?php foreach ($day_events as $event): ?>
                    <div class="calendar-event" title="<?php echo htmlspecialchars($event['event_name']); ?>">
                        <?php echo htmlspecialchars(substr($event['event_name'], 0, 15) . (strlen($event['event_name']) > 15 ? '...' : '')); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>
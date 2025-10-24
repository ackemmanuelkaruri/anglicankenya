// Events Module JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize event handlers
    initEventForm();
    initRsvpForm();
    initCalendar();
    initStreamingLinks();
});

// Event form handling
function initEventForm() {
    const eventForm = document.getElementById('event-form');
    if (eventForm) {
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateEventForm()) {
                return;
            }
            
            // Submit form via AJAX
            submitEventForm();
        });
        
        // Handle recurrence options
        const isRecurring = document.getElementById('is_recurring');
        const recurrenceOptions = document.getElementById('recurrence-options');
        
        if (isRecurring && recurrenceOptions) {
            isRecurring.addEventListener('change', function() {
                recurrenceOptions.style.display = this.checked ? 'block' : 'none';
            });
        }
        
        // Handle streaming options
        const hasStreaming = document.getElementById('has_streaming');
        const streamingOptions = document.getElementById('streaming-options');
        
        if (hasStreaming && streamingOptions) {
            hasStreaming.addEventListener('change', function() {
                streamingOptions.style.display = this.checked ? 'block' : 'none';
            });
        }
    }
}

// RSVP form handling
function initRsvpForm() {
    const rsvpOptions = document.querySelectorAll('.rsvp-option');
    const rsvpForm = document.getElementById('rsvp-form');
    
    if (rsvpOptions.length > 0) {
        rsvpOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                rsvpOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Set the hidden input value
                const rsvpStatus = this.getAttribute('data-status');
                document.getElementById('rsvp_status').value = rsvpStatus;
            });
        });
    }
    
    if (rsvpForm) {
        rsvpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitRsvpForm();
        });
    }
}

// Calendar initialization
function initCalendar() {
    const calendarNav = document.querySelector('.calendar-nav');
    const calendarGrid = document.querySelector('.calendar-grid');
    
    if (calendarNav && calendarGrid) {
        // Previous month button
        const prevBtn = calendarNav.querySelector('.prev-month');
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                loadCalendarMonth('prev');
            });
        }
        
        // Next month button
        const nextBtn = calendarNav.querySelector('.next-month');
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                loadCalendarMonth('next');
            });
        }
        
        // Day click handler
        calendarGrid.addEventListener('click', function(e) {
            if (e.target.classList.contains('calendar-day')) {
                const date = e.target.getAttribute('data-date');
                if (date) {
                    loadDayEvents(date);
                }
            }
        });
    }
}

// Streaming links handling
function initStreamingLinks() {
    const streamingLinks = document.querySelectorAll('.streaming-link');
    
    streamingLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('href');
            const platform = this.getAttribute('data-platform');
            
            // Track streaming link click
            trackStreamingClick(platform, url);
            
            // Open link in new window
            window.open(url, '_blank');
        });
    });
}

// Validate event form
function validateEventForm() {
    const eventName = document.getElementById('event_name').value.trim();
    const eventDate = document.getElementById('event_date').value;
    const startTime = document.getElementById('start_time').value;
    const location = document.getElementById('location_name').value.trim();
    
    if (!eventName) {
        showAlert('Please enter event name', 'danger');
        return false;
    }
    
    if (!eventDate) {
        showAlert('Please select event date', 'danger');
        return false;
    }
    
    if (!startTime) {
        showAlert('Please select start time', 'danger');
        return false;
    }
    
    if (!location) {
        showAlert('Please enter location', 'danger');
        return false;
    }
    
    return true;
}

// Submit event form via AJAX
function submitEventForm() {
    const formData = new FormData(document.getElementById('event-form'));
    
    fetch('create_event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Event created successfully', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        } else {
            showAlert(data.message || 'Error creating event', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    });
}

// Submit RSVP form via AJAX
function submitRsvpForm() {
    const eventId = document.getElementById('event_id').value;
    const rsvpStatus = document.getElementById('rsvp_status').value;
    const attendanceType = document.getElementById('attendance_type').value;
    const numberOfGuests = document.getElementById('number_of_guests').value;
    const specialRequirements = document.getElementById('special_requirements').value;
    
    fetch('rsvp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            event_id: eventId,
            rsvp_status: rsvpStatus,
            attendance_type: attendanceType,
            number_of_guests: numberOfGuests,
            special_requirements: specialRequirements
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Your RSVP has been submitted', 'success');
            // Update UI to show RSVP status
            updateRsvpStatus(rsvpStatus);
        } else {
            showAlert(data.message || 'Error submitting RSVP', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    });
}

// Load calendar month
function loadCalendarMonth(direction) {
    const currentMonth = document.getElementById('current-month').value;
    
    fetch('events_calendar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'load_month',
            direction: direction,
            current_month: currentMonth
        })
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('calendar-container').innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error loading calendar', 'danger');
    });
}

// Load events for a specific day
function loadDayEvents(date) {
    fetch('events_calendar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'load_day',
            date: date
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.events && data.events.length > 0) {
            showDayEventsModal(date, data.events);
        } else {
            showAlert('No events for this day', 'info');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error loading events', 'danger');
    });
}

// Track streaming link click
function trackStreamingClick(platform, url) {
    fetch('track_streaming.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            platform: platform,
            url: url
        })
    })
    .catch(error => {
        console.error('Error tracking streaming click:', error);
    });
}

// Show alert message
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    
    if (alertContainer) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alert);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 300);
        }, 5000);
    }
}

// Update RSVP status in UI
function updateRsvpStatus(status) {
    const rsvpStatusElement = document.getElementById('current-rsvp-status');
    if (rsvpStatusElement) {
        rsvpStatusElement.textContent = `Your RSVP: ${status.charAt(0).toUpperCase() + status.slice(1)}`;
        rsvpStatusElement.className = `rsvp-status rsvp-${status}`;
    }
}

// Show day events modal
function showDayEventsModal(date, events) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('day-events-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'day-events-modal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    // Build modal content
    let eventsHtml = `<h3>Events for ${date}</h3><ul>`;
    
    events.forEach(event => {
        eventsHtml += `
            <li>
                <strong>${event.event_name}</strong><br>
                ${event.start_time} - ${event.end_time || 'TBA'}<br>
                ${event.location_name}
            </li>
        `;
    });
    
    eventsHtml += '</ul>';
    
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close">&times;</span>
            ${eventsHtml}
        </div>
    `;
    
    // Show modal
    modal.style.display = 'block';
    
    // Close modal when clicking on X
    const closeBtn = modal.querySelector('.close');
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Add this function to the events.js file

// Track streaming link click
function trackStreamingClick(platform, url, eventId = null) {
    fetch('track_streaming.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            platform: platform,
            url: url,
            event_id: eventId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Streaming click tracked successfully');
        } else {
            console.error('Error tracking streaming click:', data.error);
        }
    })
    .catch(error => {
        console.error('Error tracking streaming click:', error);
    });
}

// Update the initStreamingLinks function to pass event ID
function initStreamingLinks() {
    const streamingLinks = document.querySelectorAll('.streaming-link');
    
    streamingLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('href');
            const platform = this.getAttribute('data-platform');
            const eventId = this.getAttribute('data-event-id');
            
            // Track streaming link click
            trackStreamingClick(platform, url, eventId);
            
            // Open link in new window
            window.open(url, '_blank');
        });
    });
}
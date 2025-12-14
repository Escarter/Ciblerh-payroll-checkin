<div class="d-flex align-items-start">
    <svg class="icon icon-sm me-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
    </svg>
    <div>
        <div class="fw-bold text-primary fs-5" id="live-clock-time">--:--:--</div>
        <small class="text-muted" id="live-clock-date">Loading...</small>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateClock() {
        const now = new Date();
        const timeElement = document.getElementById('live-clock-time');
        const dateElement = document.getElementById('live-clock-date');

        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString('en-GB', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        if (dateElement) {
            dateElement.textContent = now.toLocaleDateString('en-GB', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }

    // Update immediately
    updateClock();

    // Update every second
    setInterval(updateClock, 1000);
});
</script>
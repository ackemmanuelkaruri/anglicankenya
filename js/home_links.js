document.addEventListener('DOMContentLoaded', function() {
    // Find all home links and update them to point to dashboard
    const homeLinks = document.querySelectorAll('a[href*="index.php"], a[href*="/user/"], a[href="/"]');
    homeLinks.forEach(link => {
        link.href = 'dashboard.php';
    });
});
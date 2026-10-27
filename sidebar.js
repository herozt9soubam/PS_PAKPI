// Highlight active menu item on page load
document.addEventListener('DOMContentLoaded', function() {
    // Remove 'active' class from all menu items
    document.querySelectorAll('.menu-service-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Get current service ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const serviceId = urlParams.get('id');
    
    if (serviceId) {
        // Find and activate the current service link
        const currentLink = document.querySelector(`a[href*="id=${serviceId}"]`);
        if (currentLink) {
            currentLink.classList.add('active');
        }
    }
});
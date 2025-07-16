// assets/js/dashboard_script.js
document.addEventListener('DOMContentLoaded', function() {
    const menuToggleIcon = document.querySelector('.menu-toggle-icon');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggleIcon && sidebar) {
        menuToggleIcon.addEventListener('click', () => {
            sidebar.classList.toggle('visible');
        });
    }
});
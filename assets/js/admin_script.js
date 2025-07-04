document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const menuToggleIcon = document.querySelector('.menu-toggle-icon');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggleIcon && sidebar) {
        menuToggleIcon.addEventListener('click', () => {
            sidebar.classList.toggle('visible');
            menuToggleIcon.classList.toggle('fa-bars');
            menuToggleIcon.classList.toggle('fa-times');
        });
    }

    // Dropdown menus
    const dropdownToggles = document.querySelectorAll('.menu-toggle-btn');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const parent = toggle.parentElement;
            parent.classList.toggle('open');
            
            // Close other open dropdowns
            dropdownToggles.forEach(otherToggle => {
                if (otherToggle !== toggle) {
                    otherToggle.parentElement.classList.remove('open');
                }
            });
        });
    });

    // Active menu item highlight
    const navItems = document.querySelectorAll('.nav-item');
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'accueil';
    
    navItems.forEach(item => {
        const link = item.querySelector('a');
        if (link) {
            const linkPage = new URL(link.href).searchParams.get('page') || 'accueil';
            
            if (linkPage === currentPage) {
                item.classList.add('active');
                
                // Open parent dropdown if exists
                const dropdownParent = item.closest('.submenu')?.parentElement;
                if (dropdownParent) {
                    dropdownParent.classList.add('open');
                }
            }
        }
    });

    // Card hover effect enhancement
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });

    // Form input animations
    const formInputs = document.querySelectorAll('input, select, textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('focused');
        });
    });
});
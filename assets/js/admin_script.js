  document.addEventListener('DOMContentLoaded', function() {
            const menuToggleIcon = document.querySelector('.menu-toggle-icon');
            const sidebar = document.querySelector('.sidebar');
            menuToggleIcon.addEventListener('click', () => sidebar.classList.toggle('visible'));

            const dropdownToggles = document.querySelectorAll('.menu-toggle-btn');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    toggle.parentElement.classList.toggle('open');
                });
            });
          });
          
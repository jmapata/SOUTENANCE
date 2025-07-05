/**
 * Script principal pour le dashboard administrateur
 * Gestion des interactions et animations
 */

class AdminDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupAnimations();
        this.setupTooltips();
        this.setupFormValidation();
    }

    setupEventListeners() {
        // Toggle du menu mobile
        const menuToggle = document.querySelector('.menu-toggle-icon');
        const sidebar = document.querySelector('.sidebar');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('visible');
                this.handleBodyOverflow(sidebar.classList.contains('visible'));
            });
        }

        // Fermeture du menu mobile en cliquant à l'extérieur
        document.addEventListener('click', (e) => {
            if (sidebar && sidebar.classList.contains('visible')) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('visible');
                    this.handleBodyOverflow(false);
                }
            }
        });

        // Gestion des sous-menus
        const dropdownToggles = document.querySelectorAll('.menu-toggle-btn');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const navItem = toggle.parentElement;
                const isOpen = navItem.classList.contains('open');
                
                // Fermer tous les autres sous-menus
                document.querySelectorAll('.nav-item.open').forEach(item => {
                    if (item !== navItem) {
                        item.classList.remove('open');
                    }
                });
                
                // Toggle du sous-menu actuel
                navItem.classList.toggle('open');
                
                // Animation smooth
                const submenu = navItem.querySelector('.submenu');
                if (submenu) {
                    if (navItem.classList.contains('open')) {
                        submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    } else {
                        submenu.style.maxHeight = '0';
                    }
                }
            });
        });

        // Gestion des modales
        this.setupModalHandlers();

        // Gestion des tableaux
        this.setupTableHandlers();

        // Gestion des formulaires
        this.setupFormHandlers();
    }

    setupModalHandlers() {
        // Ouverture des modales
        document.addEventListener('click', (e) => {
            const modalTrigger = e.target.closest('[data-modal-target]');
            if (modalTrigger) {
                e.preventDefault();
                const targetModal = modalTrigger.getAttribute('data-modal-target');
                this.openModal(targetModal);
            }
        });

        // Fermeture des modales
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay') || 
                e.target.classList.contains('close-modal')) {
                this.closeModal(e.target.closest('.modal-overlay'));
            }
        });

        // Fermeture avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    this.closeModal(activeModal);
                }
            }
        });
    }

    setupTableHandlers() {
        // Tri des colonnes
        const sortableHeaders = document.querySelectorAll('[data-sortable]');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });

        // Actions sur les lignes
        const tableActions = document.querySelectorAll('[data-action]');
        tableActions.forEach(action => {
            action.addEventListener('click', (e) => {
                e.preventDefault();
                const actionType = action.getAttribute('data-action');
                const itemId = action.getAttribute('data-id');
                this.handleTableAction(actionType, itemId, action);
            });
        });
    }

    setupFormHandlers() {
        // Validation en temps réel
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        });

        // Soumission des formulaires
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.getAttribute('data-validate') === 'true') {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            }
        });
    }

    setupAnimations() {
        // Observer pour les animations d'entrée
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // Observer les cartes et éléments animables
        const animatableElements = document.querySelectorAll('.card, .table-container, .form-section');
        animatableElements.forEach(el => {
            observer.observe(el);
        });
    }

    setupTooltips() {
        // Tooltips simples
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }

    // Gestion des modales
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focus sur le premier élément focusable
            const firstFocusable = modal.querySelector('input, button, textarea, select');
            if (firstFocusable) {
                setTimeout(() => firstFocusable.focus(), 100);
            }
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Gestion des tableaux
    sortTable(header) {
        const table = header.closest('table');
        const columnIndex = Array.from(header.parentElement.children).indexOf(header);
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const currentOrder = header.getAttribute('data-order') || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        // Réinitialiser tous les headers
        table.querySelectorAll('[data-sortable]').forEach(h => {
            h.removeAttribute('data-order');
            h.classList.remove('sorted-asc', 'sorted-desc');
        });
        
        // Marquer le header actuel
        header.setAttribute('data-order', newOrder);
        header.classList.add(`sorted-${newOrder}`);
        
        // Trier les lignes
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            let comparison = 0;
            if (aValue < bValue) comparison = -1;
            if (aValue > bValue) comparison = 1;
            
            return newOrder === 'asc' ? comparison : -comparison;
        });
        
        // Réorganiser le tableau
        rows.forEach(row => tbody.appendChild(row));
        
        // Animation
        tbody.style.opacity = '0.7';
        setTimeout(() => {
            tbody.style.opacity = '1';
        }, 150);
    }

    handleTableAction(action, itemId, element) {
        switch (action) {
            case 'edit':
                this.editItem(itemId, element);
                break;
            case 'delete':
                this.deleteItem(itemId, element);
                break;
            case 'view':
                this.viewItem(itemId, element)
                break;
        }
    }
}
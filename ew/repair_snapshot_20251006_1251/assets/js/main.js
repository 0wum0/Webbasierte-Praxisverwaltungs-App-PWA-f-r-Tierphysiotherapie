/**
 * Tierphysio Manager - Main JavaScript
 * Unified functionality for theme switching, navigation, and interactions
 * 
 * @author Florian Engelhardt
 * @version 3.0
 * @created 2025
 */

(function() {
    'use strict';

    // ============================================
    // 1. THEME MANAGER
    // ============================================
    
    const ThemeManager = {
        STORAGE_KEY: 'tierphysio-theme',
        THEME_LIGHT: 'light',
        THEME_DARK: 'dark',
        
        init() {
            this.currentTheme = this.loadTheme();
            this.applyTheme(this.currentTheme);
            this.setupThemeToggle();
            this.listenForThemeChanges();
        },
        
        loadTheme() {
            const savedTheme = localStorage.getItem(this.STORAGE_KEY);
            if (savedTheme) {
                return savedTheme;
            }
            
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return this.THEME_DARK;
            }
            
            return this.THEME_LIGHT;
        },
        
        saveTheme(theme) {
            try {
                localStorage.setItem(this.STORAGE_KEY, theme);
            } catch (e) {
                console.warn('Could not save theme preference:', e);
            }
        },
        
        applyTheme(theme) {
            // Remove existing theme classes
            document.body.classList.remove('dark-mode', 'light-mode');
            document.documentElement.removeAttribute('data-theme');
            
            // Apply new theme
            if (theme === this.THEME_DARK) {
                document.body.classList.add('dark-mode');
                document.body.dataset.theme = 'dark';
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.body.classList.add('light-mode');
                document.body.dataset.theme = 'light';
                document.documentElement.setAttribute('data-theme', 'light');
            }
            
            // Update meta theme-color
            const metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (metaThemeColor) {
                metaThemeColor.content = theme === this.THEME_DARK ? '#121212' : '#ffffff';
            }
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('themechange', { 
                detail: { theme: theme } 
            }));
            
            this.updateThemeButtons(theme);
        },
        
        toggleTheme() {
            this.currentTheme = this.currentTheme === this.THEME_DARK ? this.THEME_LIGHT : this.THEME_DARK;
            this.applyTheme(this.currentTheme);
            this.saveTheme(this.currentTheme);
        },
        
        setupThemeToggle() {
            // Find all theme toggle buttons
            const toggleButtons = document.querySelectorAll(
                '#themeToggle, #theme-toggle, .theme-toggle, .theme-toggle-btn, [data-theme-toggle]'
            );
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleTheme();
                });
            });
            
            // Keyboard shortcut: Ctrl/Cmd + Shift + D
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                    e.preventDefault();
                    this.toggleTheme();
                }
            });
        },
        
        updateThemeButtons(theme) {
            const isDark = theme === this.THEME_DARK;
            const icon = isDark ? '☀️' : '🌙';
            const label = isDark ? 'Helles Design' : 'Dunkles Design';
            
            // Update all theme toggle buttons
            const toggleButtons = document.querySelectorAll(
                '#themeToggle, #theme-toggle, .theme-toggle, .theme-toggle-btn, [data-theme-toggle]'
            );
            
            toggleButtons.forEach(button => {
                // Update icon
                if (button.querySelector('i')) {
                    button.querySelector('i').className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
                } else {
                    button.innerHTML = icon;
                }
                
                // Update accessibility
                button.setAttribute('aria-label', label);
                button.title = label;
            });
        },
        
        listenForThemeChanges() {
            // Listen for theme changes in other tabs
            window.addEventListener('storage', (e) => {
                if (e.key === this.STORAGE_KEY && e.newValue) {
                    this.currentTheme = e.newValue;
                    this.applyTheme(this.currentTheme);
                }
            });
        }
    };

    // ============================================
    // 2. NAVIGATION MANAGER
    // ============================================
    
    const NavigationManager = {
        init() {
            this.setupSidebarToggle();
            this.setupActiveNavigation();
            this.setupMobileMenu();
            this.setupDropdowns();
        },
        
        setupSidebarToggle() {
            const toggleBtn = document.querySelector('.mobile-toggle-menu, .toggle-icon');
            const sidebar = document.querySelector('.sidebar-wrapper');
            const overlay = document.querySelector('.sidebar-overlay');
            const wrapper = document.querySelector('.wrapper');
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const isMobile = window.innerWidth < 992;
                    
                    if (isMobile) {
                        sidebar?.classList.toggle('active');
                        overlay?.classList.toggle('active');
                    } else {
                        wrapper?.classList.toggle('toggled');
                    }
                });
            }
            
            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', () => {
                    sidebar?.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', () => {
                const isMobile = window.innerWidth < 992;
                if (!isMobile) {
                    sidebar?.classList.remove('active');
                    overlay?.classList.remove('active');
                }
            });
        },
        
        setupActiveNavigation() {
            const currentPath = window.location.pathname;
            const currentFile = currentPath.split('/').pop() || 'dashboard.php';
            
            // Find and activate current menu item
            const menuItems = document.querySelectorAll('.metismenu a, .navbar-nav a');
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href.includes(currentFile)) {
                    item.classList.add('active');
                    const parent = item.closest('li');
                    if (parent) {
                        parent.classList.add('active');
                    }
                }
            });
        },
        
        setupMobileMenu() {
            const mobileToggle = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            
            if (mobileToggle && navbarCollapse) {
                mobileToggle.addEventListener('click', () => {
                    navbarCollapse.classList.toggle('show');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (!mobileToggle.contains(e.target) && !navbarCollapse.contains(e.target)) {
                        navbarCollapse.classList.remove('show');
                    }
                });
            }
        },
        
        setupDropdowns() {
            const dropdowns = document.querySelectorAll('.dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (toggle && menu) {
                    toggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Close other dropdowns
                        document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
                            if (otherMenu !== menu) {
                                otherMenu.classList.remove('show');
                            }
                        });
                        
                        menu.classList.toggle('show');
                    });
                }
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', () => {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            });
        }
    };

    // ============================================
    // 3. MODAL MANAGER
    // ============================================
    
    const ModalManager = {
        init() {
            this.setupModalTriggers();
            this.fixModalCentering();
        },
        
        setupModalTriggers() {
            // Setup all modal triggers
            document.querySelectorAll('[data-bs-toggle="modal"]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = trigger.getAttribute('data-bs-target');
                    if (targetId) {
                        this.openModal(targetId);
                    }
                });
            });
            
            // Setup modal close buttons
            document.querySelectorAll('.modal .btn-close, [data-bs-dismiss="modal"]').forEach(closeBtn => {
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modal = closeBtn.closest('.modal');
                    if (modal) {
                        this.closeModal(modal);
                    }
                });
            });
            
            // Close modal on backdrop click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closeModal(modal);
                    }
                });
            });
            
            // Close modal on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        this.closeModal(openModal);
                    }
                }
            });
        },
        
        openModal(selector) {
            const modal = document.querySelector(selector);
            if (modal) {
                // Add classes for showing
                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
                
                // Create backdrop if not exists
                if (!document.querySelector('.modal-backdrop')) {
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
                
                // Center the modal
                this.centerModal(modal);
                
                // Focus first input in modal
                setTimeout(() => {
                    const firstInput = modal.querySelector('input, textarea, select');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            }
        },
        
        closeModal(modal) {
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
                
                document.body.classList.remove('modal-open');
                
                // Remove backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        },
        
        centerModal(modal) {
            const dialog = modal.querySelector('.modal-dialog');
            if (dialog) {
                // Ensure centered class is present
                if (!dialog.classList.contains('modal-dialog-centered')) {
                    dialog.classList.add('modal-dialog-centered');
                }
            }
        },
        
        fixModalCentering() {
            // Fix all modals to be centered by default
            document.querySelectorAll('.modal-dialog').forEach(dialog => {
                if (!dialog.classList.contains('modal-dialog-centered')) {
                    dialog.classList.add('modal-dialog-centered');
                }
            });
        }
    };

    // ============================================
    // 4. SEARCH FUNCTIONALITY
    // ============================================
    
    const SearchManager = {
        init() {
            this.setupGlobalSearch();
        },
        
        setupGlobalSearch() {
            const searchInput = document.getElementById('globalSearch');
            const searchResults = document.getElementById('searchResults');
            
            if (!searchInput || !searchResults) return;
            
            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    this.performSearch(query, searchResults);
                }, 300);
            });
            
            // Close search results when clicking outside
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        },
        
        async performSearch(query, resultsContainer) {
            try {
                const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (!response.ok) throw new Error('Search failed');
                
                const results = await response.json();
                this.displaySearchResults(results, resultsContainer);
            } catch (error) {
                console.error('Search error:', error);
                resultsContainer.innerHTML = '<div class="no-results">Fehler bei der Suche</div>';
                resultsContainer.style.display = 'block';
            }
        },
        
        displaySearchResults(results, container) {
            if (!results || results.length === 0) {
                container.innerHTML = '<div class="no-results">Keine Treffer</div>';
                container.style.display = 'block';
                return;
            }
            
            let html = '';
            results.forEach(result => {
                const badge = result.type === 'patient' ? 'Patient' : 'Besitzer';
                html += `
                    <div class="result-item" data-type="${result.type}" data-id="${result.id}">
                        <span class="badge bg-primary">${badge}</span>
                        <span>${this.escapeHtml(result.label)}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            container.style.display = 'block';
            
            // Add click handlers
            container.querySelectorAll('.result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const type = item.dataset.type;
                    const id = item.dataset.id;
                    this.openSearchDetail(type, id);
                });
            });
        },
        
        async openSearchDetail(type, id) {
            try {
                const response = await fetch(`/api/search_detail.php?type=${type}&id=${id}`);
                const html = await response.text();
                
                // Open modal with details
                const modal = document.getElementById('searchDetailModal');
                if (modal) {
                    const content = modal.querySelector('#searchDetailContent');
                    if (content) {
                        content.innerHTML = html;
                    }
                    ModalManager.openModal('#searchDetailModal');
                }
            } catch (error) {
                console.error('Error loading detail:', error);
            }
        },
        
        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // ============================================
    // 5. FORM UTILITIES
    // ============================================
    
    const FormUtils = {
        init() {
            this.setupFormValidation();
            this.setupAutoResize();
        },
        
        setupFormValidation() {
            // Bootstrap-style validation
            const forms = document.querySelectorAll('.needs-validation');
            
            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        },
        
        setupAutoResize() {
            // Auto-resize textareas
            const textareas = document.querySelectorAll('textarea[data-autoresize]');
            
            textareas.forEach(textarea => {
                const resize = () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                };
                
                textarea.addEventListener('input', resize);
                resize(); // Initial resize
            });
        }
    };

    // ============================================
    // 6. UTILITIES
    // ============================================
    
    const Utils = {
        init() {
            this.setupBackToTop();
            this.setupTooltips();
            this.setupSmoothScroll();
        },
        
        setupBackToTop() {
            const backToTop = document.querySelector('.back-to-top');
            
            if (backToTop) {
                // Show/hide based on scroll position
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        backToTop.classList.add('show');
                    } else {
                        backToTop.classList.remove('show');
                    }
                });
                
                // Scroll to top on click
                backToTop.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        },
        
        setupTooltips() {
            // Simple tooltip implementation
            const elements = document.querySelectorAll('[title]');
            
            elements.forEach(element => {
                const title = element.getAttribute('title');
                if (title) {
                    element.setAttribute('data-tooltip', title);
                    element.removeAttribute('title');
                    
                    element.addEventListener('mouseenter', () => {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'tooltip-custom';
                        tooltip.textContent = title;
                        document.body.appendChild(tooltip);
                        
                        const rect = element.getBoundingClientRect();
                        tooltip.style.position = 'fixed';
                        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                        tooltip.style.zIndex = '9999';
                    });
                    
                    element.addEventListener('mouseleave', () => {
                        const tooltip = document.querySelector('.tooltip-custom');
                        if (tooltip) {
                            tooltip.remove();
                        }
                    });
                }
            });
        },
        
        setupSmoothScroll() {
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href === '#') return;
                    
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
    };

    // ============================================
    // 7. INITIALIZATION
    // ============================================
    
    const App = {
        init() {
            // Initialize all modules
            ThemeManager.init();
            NavigationManager.init();
            ModalManager.init();
            SearchManager.init();
            FormUtils.init();
            Utils.init();
            
            // Add loaded class to body
            document.body.classList.add('loaded');
            
            // Remove loading overlay if exists
            const loadingOverlay = document.querySelector('.loading-overlay');
            if (loadingOverlay) {
                setTimeout(() => {
                    loadingOverlay.classList.remove('active');
                }, 500);
            }
        }
    };

    // ============================================
    // 8. DOM READY
    // ============================================
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => App.init());
    } else {
        App.init();
    }

    // Expose to global scope for external access
    window.TierphysioApp = {
        ThemeManager,
        NavigationManager,
        ModalManager,
        SearchManager,
        FormUtils,
        Utils,
        App
    };

})();

// ============================================
// ADDITIONAL STYLES FOR TOOLTIPS
// ============================================

const tooltipStyles = document.createElement('style');
tooltipStyles.textContent = `
    .tooltip-custom {
        position: fixed;
        background: var(--text-primary);
        color: var(--bg-primary);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        white-space: nowrap;
        pointer-events: none;
        z-index: 9999;
        animation: fadeIn 0.2s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(tooltipStyles);
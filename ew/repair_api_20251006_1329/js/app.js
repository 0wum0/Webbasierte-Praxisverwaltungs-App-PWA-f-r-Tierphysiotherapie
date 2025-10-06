/**
 * Tierphysio Manager - Unified App JavaScript
 * Single source of truth for all interactive elements
 * @version 3.0.0
 */

(function() {
    'use strict';

    // Global state management
    const AppState = {
        sidebar: {
            isOpen: false,
            element: null,
            overlay: null
        },
        search: {
            isOpen: false,
            element: null,
            input: null,
            results: null
        },
        initialized: false
    };

    // Prevent multiple initializations
    if (window.TierphysioApp && window.TierphysioApp.initialized) {
        console.warn('App already initialized, skipping...');
        return;
    }

    window.TierphysioApp = AppState;

    /**
     * Initialize sidebar functionality
     */
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');
        
        if (!sidebar) return;

        AppState.sidebar.element = sidebar;
        AppState.sidebar.overlay = overlay;

        // Menu toggle handler
        if (menuToggle) {
            menuToggle.removeEventListener('click', toggleSidebar);
            menuToggle.addEventListener('click', toggleSidebar);
        }

        // Desktop sidebar toggle
        if (sidebarToggle) {
            sidebarToggle.removeEventListener('click', toggleSidebar);
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        // Close on overlay click
        if (overlay) {
            overlay.removeEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);
        }

        // Handle window resize
        window.addEventListener('resize', handleResize);
    }

    function toggleSidebar(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        const sidebar = AppState.sidebar.element;
        const overlay = AppState.sidebar.overlay;
        const isDesktop = window.innerWidth >= 992;

        if (!sidebar) return;

        if (isDesktop) {
            // Desktop: Toggle wrapper class
            const wrapper = document.querySelector('.wrapper');
            if (wrapper) {
                wrapper.classList.toggle('toggled');
            }
        } else {
            // Mobile: Toggle sidebar classes
            const isOpen = sidebar.classList.contains('open');
            
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }
    }

    function openSidebar() {
        const sidebar = AppState.sidebar.element;
        const overlay = AppState.sidebar.overlay;

        if (sidebar) {
            sidebar.classList.add('open', 'active');
            AppState.sidebar.isOpen = true;
        }
        
        if (overlay) {
            overlay.classList.add('active', 'show');
        }
    }

    function closeSidebar() {
        const sidebar = AppState.sidebar.element;
        const overlay = AppState.sidebar.overlay;

        if (sidebar) {
            sidebar.classList.remove('open', 'active');
            AppState.sidebar.isOpen = false;
        }
        
        if (overlay) {
            overlay.classList.remove('active', 'show');
        }
    }

    /**
     * Initialize search functionality
     */
    function initSearch() {
        const searchBtn = document.getElementById('globalSearchBtn');
        const searchBar = document.getElementById('globalSearchBar');
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const closeBtn = document.getElementById('closeSearchBtn');

        if (!searchBar) return;

        AppState.search.element = searchBar;
        AppState.search.input = searchInput;
        AppState.search.results = searchResults;

        // Search button handler
        if (searchBtn) {
            searchBtn.removeEventListener('click', toggleSearch);
            searchBtn.addEventListener('click', toggleSearch);
        }

        // Close button handler
        if (closeBtn) {
            closeBtn.removeEventListener('click', closeSearch);
            closeBtn.addEventListener('click', closeSearch);
        }

        // Search input handler
        if (searchInput) {
            searchInput.removeEventListener('input', handleSearch);
            searchInput.addEventListener('input', handleSearch);
        }

        // Click outside to close
        if (searchBar) {
            searchBar.addEventListener('click', function(e) {
                if (e.target === searchBar) {
                    closeSearch();
                }
            });
        }
    }

    function toggleSearch(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        const isOpen = AppState.search.isOpen;
        
        if (isOpen) {
            closeSearch();
        } else {
            openSearch();
        }
    }

    function openSearch() {
        const searchBar = AppState.search.element;
        const searchInput = AppState.search.input;

        if (searchBar) {
            searchBar.style.display = 'flex';
            searchBar.classList.add('active');
            AppState.search.isOpen = true;

            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        }
    }

    function closeSearch() {
        const searchBar = AppState.search.element;

        if (searchBar) {
            searchBar.style.display = 'none';
            searchBar.classList.remove('active');
            AppState.search.isOpen = false;
        }
    }

    let searchTimeout;
    function handleSearch() {
        clearTimeout(searchTimeout);
        
        const searchInput = AppState.search.input;
        const searchResults = AppState.search.results;
        
        if (!searchInput || !searchResults) return;
        
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
            searchResults.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`/api/search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            const badge = item.type === 'patient' ? 
                                '<span class="badge bg-primary">Patient</span>' : 
                                '<span class="badge bg-success">Besitzer</span>';
                            const url = item.type === 'patient' ? 
                                `/patient.php?id=${item.id}` : 
                                `/owner.php?id=${item.id}`;
                            
                            html += `
                                <div class="result-item" onclick="window.location.href='${url}'">
                                    ${badge}
                                    <span>${item.label}</span>
                                </div>
                            `;
                        });
                        searchResults.innerHTML = html;
                    } else {
                        searchResults.innerHTML = '<div class="no-results">Keine Ergebnisse gefunden</div>';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="no-results">Fehler bei der Suche</div>';
                });
        }, 300);
    }

    /**
     * Initialize user dropdown
     */
    function initUserDropdown() {
        const userToggle = document.getElementById('userMenuToggle');
        const userMenu = document.getElementById('userMenu');

        if (!userToggle || !userMenu) return;

        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
                const dropdown = bootstrap.Dropdown.getInstance(userToggle);
                if (dropdown) {
                    dropdown.hide();
                }
            }
        });
    }

    /**
     * Initialize active navigation highlighting
     */
    function initActiveNavigation() {
        const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
        const navLinks = document.querySelectorAll('.nav-link[data-route]');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            const route = link.getAttribute('data-route');
            
            // Remove any existing active classes
            link.classList.remove('active');
            const parent = link.closest('li');
            if (parent) {
                parent.classList.remove('active');
            }
            
            // Check if this is the current page
            if (href && (href.includes(currentPath) || currentPath.includes(route))) {
                link.classList.add('active');
                if (parent) {
                    parent.classList.add('active');
                }
            }
        });
    }

    /**
     * Fix modal z-index issues
     */
    function initModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                // Ensure proper z-index
                setTimeout(() => {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.style.zIndex = '1040';
                    }
                    modal.style.zIndex = '2000';
                }, 10);
            });
        });
    }

    /**
     * Initialize Bootstrap components
     */
    function initBootstrapComponents() {
        // Tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // Popovers
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(el => {
            new bootstrap.Popover(el);
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    /**
     * Handle window resize
     */
    function handleResize() {
        const isDesktop = window.innerWidth >= 992;
        
        if (isDesktop && AppState.sidebar.element) {
            // Close mobile sidebar on desktop resize
            closeSidebar();
        }
    }

    /**
     * Remove problematic elements and animations
     */
    function cleanupProblematicElements() {
        // Remove duplicate headers
        const headers = document.querySelectorAll('.app-header, .topbar');
        if (headers.length > 1) {
            for (let i = 1; i < headers.length; i++) {
                headers[i].remove();
            }
        }

        // Remove overlay elements that might block interactions
        const overlays = document.querySelectorAll('.decor-overlay, .gradient-banner, .header-overlay');
        overlays.forEach(el => el.remove());

        // Ensure header is clickable
        const header = document.querySelector('.app-header, .topbar');
        if (header) {
            header.style.pointerEvents = 'auto';
            header.style.zIndex = '1030';
        }
    }

    /**
     * Main initialization
     */
    function init() {
        // Prevent double initialization
        if (AppState.initialized) return;

        // Clean up first
        cleanupProblematicElements();

        // Initialize all components
        initSidebar();
        initSearch();
        initUserDropdown();
        initActiveNavigation();
        initModals();
        initBootstrapComponents();
        initFormValidation();

        // Mark as initialized
        AppState.initialized = true;
        console.log('Tierphysio Manager initialized successfully');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM is already loaded
        init();
    }

    // Export functions for global use if needed
    window.openSearch = openSearch;
    window.closeSearch = closeSearch;
    window.toggleSidebar = toggleSidebar;
    window.sidebarToggle = toggleSidebar; // Legacy alias

})();
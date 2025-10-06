/**
 * Tierphysio Manager - Main Application JavaScript
 * Fixed version without UI glitches, animations, and overlays
 * @version 3.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== KILL ALL PROBLEMATIC ANIMATIONS & OVERLAYS =====
    // Stop any rogue intervals/animations that might move header controls
    (function killProblematicAnimations() {
        // Override setInterval to prevent header animations
        const origSetInterval = window.setInterval;
        window.setInterval = function(fn, t) {
            const fnStr = fn && fn.toString();
            // Block any intervals that might animate header elements
            if (fnStr && /move|slide|animate|translateX|header|topbar|search-bar|top-menu/i.test(fnStr)) {
                console.warn('Blocked problematic animation interval');
                return 0;
            }
            return origSetInterval(fn, t);
        };

        // Clear any existing intervals that might be problematic
        const highestId = window.setTimeout(function() {
            for (let i = highestId; i >= 0; i--) {
                window.clearInterval(i);
            }
        }, 0);

        // Remove all overlays that might block clicks
        document.querySelectorAll('.decor-overlay, .gradient-banner, [data-overlay], .header-overlay, .hero-banner').forEach(el => {
            el.style.pointerEvents = 'none';
            el.style.zIndex = '0';
            el.remove(); // Remove them entirely
        });

        // Ensure header is always on top and clickable
        const headers = document.querySelectorAll('.app-header, .topbar, header');
        headers.forEach((header, index) => {
            if (index === 0) {
                // Keep only first header
                header.style.zIndex = '1030';
                header.style.pointerEvents = 'auto';
                header.style.position = 'fixed';
            } else {
                // Remove duplicate headers
                header.remove();
            }
        });

        // Remove transform/animation styles from header elements
        document.querySelectorAll('.search-bar, .top-menu, .theme-toggle-btn, .actions').forEach(el => {
            el.style.transform = 'none';
            el.style.animation = 'none';
            el.style.position = 'static';
        });
    })();

    // ===== Sidebar Toggle Functionality =====
    const sidebar = document.querySelector('.sidebar-wrapper');
    const overlay = document.querySelector('.sidebar-overlay');
    const burgerBtn = document.getElementById('burgerBtn');
    const menuToggle = document.getElementById('menuToggle');
    const toggleBtn = document.querySelector('.mobile-toggle-menu');
    const collapseBtn = document.querySelector('.toggle-icon');
    const wrapper = document.querySelector('.wrapper');
    
    // Menu toggle handler (new elegant header)
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isDesktop = window.innerWidth >= 992;
            
            if (isDesktop) {
                // Desktop: Toggle wrapper class
                if (wrapper) {
                    wrapper.classList.toggle('toggled');
                }
            } else {
                // Mobile: Toggle sidebar and overlay
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            }
        });
    }
    
    // Legacy burger button handler (for compatibility)
    if (burgerBtn) {
        burgerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isDesktop = window.innerWidth >= 992;
            
            if (isDesktop) {
                // Desktop: Toggle wrapper class
                if (wrapper) {
                    wrapper.classList.toggle('toggled');
                }
            } else {
                // Mobile: Toggle sidebar and overlay
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            }
        });
    }
    
    // Mobile toggle
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isDesktop = window.innerWidth >= 992;
            
            if (isDesktop) {
                // Desktop: Toggle wrapper class
                if (wrapper) {
                    wrapper.classList.toggle('toggled');
                }
            } else {
                // Mobile: Toggle sidebar and overlay
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            }
        });
    }
    
    // Desktop collapse button
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (wrapper) {
                wrapper.classList.toggle('toggled');
            }
        });
    }
    
    // Close on overlay click (mobile)
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) {
                sidebar.classList.remove('active');
            }
            overlay.classList.remove('active');
        });
    }

    // ===== Search Functionality =====
    const searchBtn = document.getElementById('searchBtn');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (searchBtn && searchOverlay) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            searchOverlay.style.display = 'flex';
            searchOverlay.classList.add('active');
            if (searchInput) {
                searchInput.focus();
            }
        });
    }

    // Open search
    window.openSearch = function() {
        const searchOverlay = document.getElementById('searchOverlay');
        const searchInput = document.getElementById('searchInput');
        if (searchOverlay) {
            searchOverlay.style.display = 'flex';
            searchOverlay.classList.add('active');
            if (searchInput) {
                searchInput.focus();
            }
        }
    };

    // Close search
    window.closeSearch = function() {
        const searchOverlay = document.getElementById('searchOverlay');
        if (searchOverlay) {
            searchOverlay.style.display = 'none';
            searchOverlay.classList.remove('active');
        }
    };

    // Search functionality
    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
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
                                html += `
                                    <div class="result-item" onclick="window.location.href='${item.type === 'patient' ? 'patient' : 'owner'}.php?id=${item.id}'">
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
        });
    }

    // ===== Back to Top Button =====
    const backToTopBtn = document.querySelector('.back-to-top');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ===== Fix Modal Z-Index Issues =====
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function() {
            // Ensure modal and backdrop have correct z-index
            setTimeout(function() {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.zIndex = '1040';
                }
                modal.style.zIndex = '1050';
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog) {
                    dialog.style.zIndex = '1060';
                }
            }, 10);
        });
    });

    // ===== Initialize Bootstrap Components =====
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });

    // ===== Form Validation =====
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ===== Auto-hide Alerts =====
    const autoAlerts = document.querySelectorAll('.alert.auto-dismiss');
    autoAlerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // ===== Active Navigation Highlighting =====
    const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
    const navLinks = document.querySelectorAll('.metismenu li a, .main-nav a');
    
    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href === currentPath) {
            link.classList.add('active');
            const parent = link.closest('li');
            if (parent) {
                parent.classList.add('active');
            }
        }
    });

    // ===== Initialize MetisMenu if available =====
    if (typeof $ !== 'undefined' && $.fn.metisMenu) {
        $('#menu').metisMenu();
    }

    // ===== Initialize DataTables if available =====
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.datatable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/de-DE.json"
            },
            "pageLength": 25,
            "responsive": true,
            "order": [[0, "desc"]]
        });
    }

    // ===== File Input Preview =====
    const fileInputs = document.querySelectorAll('.custom-file-input');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Datei auswählen';
            const label = this.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = fileName;
            }
        });
    });

    // ===== Handle window resize =====
    window.addEventListener('resize', function() {
        const isDesktop = window.innerWidth >= 992;
        
        if (isDesktop && sidebar) {
            // Remove mobile classes on desktop
            sidebar.classList.remove('active');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
});

// ===== Mobile Search Toggle =====
window.toggleMobileSearch = function() {
    const overlay = document.getElementById('mobileSearchOverlay');
    if (overlay) {
        overlay.classList.toggle('active');
        if (overlay.classList.contains('active')) {
            const input = document.getElementById('mobileSearchInput');
            if (input) {
                input.focus();
            }
        }
    }
};
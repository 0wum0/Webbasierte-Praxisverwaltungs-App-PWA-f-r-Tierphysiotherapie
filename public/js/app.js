/**
 * Tierphysio Manager - Main Application JavaScript
 * Fixed version without UI glitches
 * @version 3.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== Sidebar Toggle Functionality =====
    const sidebar = document.querySelector('.sidebar-wrapper');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggleBtn = document.querySelector('.mobile-toggle-menu');
    const collapseBtn = document.querySelector('.toggle-icon');
    const wrapper = document.querySelector('.wrapper');
    
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
    
    // Handle window resize
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

    // ===== Initialize Tooltips =====
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ===== Initialize Popovers =====
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
    const navLinks = document.querySelectorAll('.metismenu li a');
    
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

    // ===== Password Strength Indicator =====
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('keyup', function() {
            const strength = checkPasswordStrength(this.value);
            const indicator = document.getElementById('password-strength');
            
            if (indicator) {
                indicator.className = 'password-strength';
                indicator.classList.add(strength);
                
                const labels = {
                    'weak': 'Schwach',
                    'medium': 'Mittel',
                    'strong': 'Stark'
                };
                indicator.textContent = labels[strength] || '';
            }
        });
    }
    
    function checkPasswordStrength(password) {
        let strength = 'weak';
        
        if (password.length >= 8) {
            strength = 'medium';
        }
        
        if (password.length >= 12 && 
            /[A-Z]/.test(password) && 
            /[a-z]/.test(password) && 
            /[0-9]/.test(password) && 
            /[^A-Za-z0-9]/.test(password)) {
            strength = 'strong';
        }
        
        return strength;
    }

    // ===== Smooth Scroll for Anchor Links =====
    const anchorLinks = document.querySelectorAll('a[href^="#"]:not([data-bs-toggle])');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId !== '#') {
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    const offset = 100;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // ===== Fix Modal Z-Index Issues =====
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function() {
            // Ensure modal backdrop is at correct z-index
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

    // ===== Prevent Menu/Search Animation Glitches =====
    // Remove any periodic animations
    const topMenu = document.querySelector('.top-menu');
    const searchBar = document.querySelector('.search-bar');
    
    if (topMenu) {
        // Remove any transform or animation styles
        topMenu.style.transform = 'none';
        topMenu.style.animation = 'none';
    }
    
    if (searchBar) {
        // Remove any transform or animation styles
        searchBar.style.transform = 'none';
        searchBar.style.animation = 'none';
    }
    
    // Clear any animation intervals that might exist
    const highestId = window.setTimeout(function() {
        for (let i = highestId; i >= 0; i--) {
            window.clearInterval(i);
        }
    }, 0);

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

});

// ===== Global Search Functionality =====
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    const mobileSearchInput = document.getElementById('mobileSearchInput');
    const mobileSearchResults = document.getElementById('mobileSearchResults');
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Search function
    function performSearch(query, resultsContainer) {
        if (!resultsContainer) return;
        
        if (query.length < 2) {
            resultsContainer.style.display = 'none';
            resultsContainer.innerHTML = '';
            return;
        }
        
        fetch(`api/search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        const badge = item.type === 'patient' ? 
                            '<span class="badge bg-primary">Patient</span>' : 
                            '<span class="badge bg-success">Besitzer</span>';
                        html += `
                            <div class="result-item" data-type="${item.type}" data-id="${item.id}">
                                ${badge}
                                <span>${item.label}</span>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                    resultsContainer.style.display = 'block';
                    
                    // Add click handlers
                    resultsContainer.querySelectorAll('.result-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const type = this.dataset.type;
                            const id = this.dataset.id;
                            if (type === 'patient') {
                                window.location.href = `patient.php?id=${id}`;
                            } else {
                                window.location.href = `owner.php?id=${id}`;
                            }
                        });
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="no-results">Keine Ergebnisse gefunden</div>';
                    resultsContainer.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                resultsContainer.innerHTML = '<div class="no-results">Fehler bei der Suche</div>';
                resultsContainer.style.display = 'block';
            });
    }
    
    // Debounced search
    const debouncedSearch = debounce(performSearch, 300);
    
    // Desktop search
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value.trim(), searchResults);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
    
    // Mobile search
    if (mobileSearchInput && mobileSearchResults) {
        mobileSearchInput.addEventListener('input', function() {
            debouncedSearch(this.value.trim(), mobileSearchResults);
        });
    }
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
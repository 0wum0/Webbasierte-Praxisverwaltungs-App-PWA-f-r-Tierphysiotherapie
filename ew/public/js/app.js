/**
 * Tierphysio Manager - Enhanced App JavaScript with Robust API Handling
 * @version 4.0.0
 */

(function() {
    'use strict';

    // ============================================
    // API HELPER - Robust JSON Fetch
    // ============================================
    
    async function apiJson(url, opts = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...opts.headers
                },
                credentials: 'same-origin',
                ...opts
            });
            
            const text = await response.text();
            
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                console.error('Invalid JSON response:', text.substring(0, 500));
                throw new Error('Server lieferte kein gültiges JSON');
            }
            
            if (!response.ok || data.success === false) {
                const msg = data?.error?.message || `HTTP ${response.status}`;
                throw new Error(msg);
            }
            
            return data.data ?? data;
            
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // ============================================
    // MODAL FORM HANDLERS
    // ============================================
    
    function initModalForms() {
        // Owner & Patient Create Modal
        const ownerPatientForm = document.querySelector('#addOwnerPatientModal form, #createOwnerPatientModal form');
        if (ownerPatientForm) {
            ownerPatientForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Speichert...';
                }
                
                try {
                    const formData = new FormData(this);
                    const payload = {
                        owner: {
                            first_name: formData.get('owner_first_name') || formData.get('first_name'),
                            last_name: formData.get('owner_last_name') || formData.get('last_name'),
                            email: formData.get('owner_email') || formData.get('email'),
                            phone: formData.get('owner_phone') || formData.get('phone'),
                            address: formData.get('owner_address') || formData.get('address')
                        },
                        patient: {
                            name: formData.get('patient_name') || formData.get('name'),
                            species: formData.get('patient_species') || formData.get('species'),
                            breed: formData.get('patient_breed') || formData.get('breed'),
                            birthdate: formData.get('patient_birthdate') || formData.get('birthdate'),
                            notes: formData.get('patient_notes') || formData.get('notes')
                        }
                    };
                    
                    const result = await apiJson('/api/index.php?action=owner_patient_create', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    
                    showToast('Besitzer und Patient erfolgreich erstellt', 'success');
                    
                    // Close modal
                    const modalEl = this.closest('.modal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    
                    // Refresh page or update list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                } catch (error) {
                    showToast(error.message || 'Fehler beim Speichern', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                }
            });
        }
        
        // Single Owner Create Modal
        const ownerForm = document.querySelector('#addOwnerModal form, #createOwnerModal form');
        if (ownerForm && !ownerForm.hasAttribute('data-initialized')) {
            ownerForm.setAttribute('data-initialized', 'true');
            
            ownerForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Speichert...';
                }
                
                try {
                    const formData = new FormData(this);
                    const payload = {
                        first_name: formData.get('first_name'),
                        last_name: formData.get('last_name'),
                        email: formData.get('email'),
                        phone: formData.get('phone'),
                        address: formData.get('address')
                    };
                    
                    const result = await apiJson('/api/index.php?action=owner_create', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    
                    showToast('Besitzer erfolgreich erstellt', 'success');
                    
                    // Close modal
                    const modalEl = this.closest('.modal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    
                    // Refresh page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                } catch (error) {
                    showToast(error.message || 'Fehler beim Speichern', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                }
            });
        }
        
        // Patient Create Modal
        const patientForm = document.querySelector('#addPatientModal form, #createPatientModal form');
        if (patientForm && !patientForm.hasAttribute('data-initialized')) {
            patientForm.setAttribute('data-initialized', 'true');
            
            patientForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Speichert...';
                }
                
                try {
                    const formData = new FormData(this);
                    const payload = {
                        owner_id: formData.get('owner_id'),
                        name: formData.get('name'),
                        species: formData.get('species'),
                        breed: formData.get('breed'),
                        birthdate: formData.get('birthdate'),
                        notes: formData.get('notes')
                    };
                    
                    const result = await apiJson('/api/index.php?action=patient_create', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    
                    showToast('Patient erfolgreich erstellt', 'success');
                    
                    // Close modal
                    const modalEl = this.closest('.modal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    
                    // Refresh page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                } catch (error) {
                    showToast(error.message || 'Fehler beim Speichern', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                }
            });
        }
    }

    // ============================================
    // BURGER MENU & MOBILE NAVIGATION
    // ============================================
    
    function initMobileMenu() {
        // Mobile menu toggle
        const mobileToggle = document.querySelector('.mobile-toggle-menu, .mobile-menu-toggle, #menuToggle');
        const wrapper = document.querySelector('.wrapper');
        
        if (mobileToggle && wrapper) {
            mobileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                wrapper.classList.toggle('toggled');
                
                // Also toggle sidebar for mobile
                const sidebar = document.querySelector('#sidebar, .sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                    sidebar.classList.toggle('open');
                }
            });
        }
        
        // Desktop sidebar toggle
        const sidebarToggle = document.querySelector('#sidebarToggle, .sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                if (wrapper) {
                    wrapper.classList.toggle('toggled');
                }
            });
        }
        
        // Close mobile menu on overlay click
        const overlay = document.querySelector('#sidebarOverlay, .sidebar-overlay');
        if (overlay) {
            overlay.addEventListener('click', function() {
                if (wrapper) {
                    wrapper.classList.remove('toggled');
                }
                const sidebar = document.querySelector('#sidebar, .sidebar');
                if (sidebar) {
                    sidebar.classList.remove('active');
                    sidebar.classList.remove('open');
                }
            });
        }
    }

    // ============================================
    // GLOBAL SEARCH
    // ============================================
    
    function initGlobalSearch() {
        const searchInput = document.querySelector('#globalSearch, #searchInput, .global-search-input');
        const searchResults = document.querySelector('#searchResults, .search-results');
        
        if (!searchInput || !searchResults) return;
        
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(async () => {
                try {
                    const results = await apiJson(`/api/index.php?action=search&q=${encodeURIComponent(query)}`);
                    
                    if (results && results.length > 0) {
                        let html = '<div class="search-results-dropdown">';
                        
                        results.forEach(item => {
                            const badge = item.type === 'patient' ? 
                                '<span class="badge bg-primary ms-2">Patient</span>' : 
                                '<span class="badge bg-success ms-2">Besitzer</span>';
                            const url = item.type === 'patient' ? 
                                `/patient.php?id=${item.id}` : 
                                `/owner.php?id=${item.id}`;
                            
                            html += `
                                <a href="${url}" class="search-result-item d-block p-2 text-decoration-none">
                                    <span>${item.label}</span>
                                    ${badge}
                                </a>
                            `;
                        });
                        
                        html += '</div>';
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="p-3 text-muted">Keine Ergebnisse gefunden</div>';
                        searchResults.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="p-3 text-danger">Fehler bei der Suche</div>';
                    searchResults.style.display = 'block';
                }
            }, 300);
        });
        
        // Hide results on click outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // ============================================
    // USER DROPDOWN
    // ============================================
    
    function initUserDropdown() {
        // Bootstrap dropdown initialization
        const dropdownElements = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        dropdownElements.forEach(element => {
            new bootstrap.Dropdown(element);
        });
    }

    // ============================================
    // DASHBOARD SPECIFIC
    // ============================================
    
    async function initDashboard() {
        if (!window.location.pathname.includes('dashboard')) return;
        
        try {
            const metrics = await apiJson('/api/index.php?action=dashboard_metrics');
            
            // Update metric cards
            if (metrics.income) {
                updateElement('#income-today', formatCurrency(metrics.income.today));
                updateElement('#income-month', formatCurrency(metrics.income.month));
                updateElement('#income-year', formatCurrency(metrics.income.year));
            }
            
            if (metrics.counts) {
                updateElement('#appointments-today', metrics.counts.appointments_today);
                updateElement('#active-patients', metrics.counts.active_patients);
                updateElement('#new-patients', metrics.counts.new_patients_week);
            }
            
            if (metrics.invoices) {
                updateElement('#invoices-paid', metrics.invoices.paid);
                updateElement('#invoices-unpaid', metrics.invoices.unpaid);
            }
            
            // Update charts if available
            if (window.Chart && metrics.income && metrics.income.series) {
                updateIncomeChart(metrics.income.series);
            }
            
        } catch (error) {
            console.error('Dashboard metrics error:', error);
        }
    }
    
    function updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        }
    }
    
    function formatCurrency(value) {
        return new Intl.NumberFormat('de-DE', { 
            style: 'currency', 
            currency: 'EUR' 
        }).format(value);
    }
    
    function updateIncomeChart(series) {
        const canvas = document.querySelector('#incomeChart, #income-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: series.map(s => s.label),
                datasets: [{
                    label: 'Einnahmen',
                    data: series.map(s => s.value),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // FIX INTERACTIVE ELEMENTS
    // ============================================
    
    function fixInteractiveElements() {
        // Remove any pointer-events: none from header elements
        const headers = document.querySelectorAll('.header, .topbar, .app-header, .navbar');
        headers.forEach(header => {
            header.style.pointerEvents = 'auto';
            header.style.position = 'relative';
            header.style.zIndex = '1030';
        });
        
        // Remove overlays that block interactions
        const overlays = document.querySelectorAll('.decor-overlay, .gradient-banner, .header-overlay');
        overlays.forEach(overlay => {
            if (!overlay.classList.contains('sidebar-overlay') && !overlay.id?.includes('sidebar')) {
                overlay.remove();
            }
        });
        
        // Ensure all buttons and links are clickable
        const interactiveElements = document.querySelectorAll('button, a, input, select, textarea, [role="button"]');
        interactiveElements.forEach(element => {
            element.style.pointerEvents = 'auto';
        });
    }

    // ============================================
    // MAIN INITIALIZATION
    // ============================================
    
    function init() {
        console.log('Initializing Tierphysio Manager App...');
        
        // Fix interactive elements first
        fixInteractiveElements();
        
        // Initialize all components
        initModalForms();
        initMobileMenu();
        initGlobalSearch();
        initUserDropdown();
        initDashboard();
        
        // Initialize Bootstrap components
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
        
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(el => new bootstrap.Popover(el));
        
        console.log('Tierphysio Manager App initialized successfully');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export utilities for global use
    window.apiJson = apiJson;
    window.showToast = showToast;

})();
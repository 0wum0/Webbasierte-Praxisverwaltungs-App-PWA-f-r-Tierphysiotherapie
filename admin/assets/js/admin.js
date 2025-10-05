/**
 * Admin Panel Custom JavaScript
 */

// Utility: AJAX Helper
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaults, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        });
}

// Utility: Show Toast Notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    
    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }
    
    const iconMap = {
        success: 'check-circle-fill',
        error: 'x-circle-fill',
        warning: 'exclamation-triangle-fill',
        info: 'info-circle-fill'
    };
    
    const bgMap = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-primary'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white ${bgMap[type] || bgMap.info} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${iconMap[type] || iconMap.info} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Entfernen nach dem Ausblenden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Utility: Confirm Dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Utility: Show Loading Overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'spinner-overlay';
    overlay.id = 'loadingOverlay';
    overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Laden...</span></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// DataTable Initialisierung (falls jQuery DataTables verwendet wird)
document.addEventListener('DOMContentLoaded', function() {
    // Auto-Initialisierung für .data-table Klasse
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
            jQuery(table).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/de-DE.json'
                },
                pageLength: 25,
                responsive: true
            });
        }
    });
    
    // Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K: Fokus auf Suche
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // ESC: Modale schließen
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }
});

// Export functions for use in other scripts
window.adminUtils = {
    ajaxRequest,
    showToast,
    confirmAction,
    showLoading,
    hideLoading
};

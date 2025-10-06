/**
 * API Client for Tierphysio Manager
 * Handles all API communications with proper error handling
 * @version 2.0.0
 */

(function(window) {
    'use strict';

    // API configuration
    const API_BASE = '/api';
    const API_TIMEOUT = 30000; // 30 seconds

    /**
     * API Client class
     */
    class ApiClient {
        constructor() {
            this.baseUrl = API_BASE;
            this.defaultHeaders = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
        }

        /**
         * Make API request
         * @param {string} endpoint - API endpoint
         * @param {object} options - Request options
         * @returns {Promise}
         */
        async request(endpoint, options = {}) {
            const url = endpoint.startsWith('http') ? endpoint : `${this.baseUrl}/${endpoint}`;
            
            const config = {
                method: options.method || 'GET',
                headers: {
                    ...this.defaultHeaders,
                    ...options.headers
                },
                credentials: 'same-origin'
            };

            // Add body for POST/PUT requests
            if (options.data && ['POST', 'PUT', 'PATCH'].includes(config.method)) {
                if (options.json !== false) {
                    config.headers['Content-Type'] = 'application/json';
                    config.body = JSON.stringify(options.data);
                } else if (options.data instanceof FormData) {
                    config.body = options.data;
                    // Let browser set content-type for FormData
                    delete config.headers['Content-Type'];
                } else {
                    // URL encoded
                    config.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    config.body = this.encodeParams(options.data);
                }
            }

            // Add query parameters for GET requests
            let finalUrl = url;
            if (options.params && config.method === 'GET') {
                const queryString = this.encodeParams(options.params);
                finalUrl = url + (url.includes('?') ? '&' : '?') + queryString;
            }

            try {
                // Create abort controller for timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), options.timeout || API_TIMEOUT);

                const response = await fetch(finalUrl, {
                    ...config,
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                const isJson = contentType && contentType.includes('application/json');

                // Get response data
                let data;
                if (isJson) {
                    const text = await response.text();
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (e) {
                        console.error('JSON parse error:', e, 'Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                } else {
                    // Non-JSON response (error page, etc.)
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned non-JSON response');
                }

                // Handle HTTP errors
                if (!response.ok) {
                    const error = new Error(data.error?.message || `HTTP ${response.status}`);
                    error.status = response.status;
                    error.data = data;
                    throw error;
                }

                // Handle API-level errors
                if (data.success === false) {
                    const error = new Error(data.error?.message || 'API Error');
                    error.status = response.status;
                    error.data = data;
                    throw error;
                }

                return data;

            } catch (error) {
                if (error.name === 'AbortError') {
                    throw new Error('Request timeout');
                }
                throw error;
            }
        }

        /**
         * Encode parameters for URL
         */
        encodeParams(params) {
            return Object.keys(params)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
                .join('&');
        }

        /**
         * GET request
         */
        get(endpoint, params = {}, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'GET',
                params
            });
        }

        /**
         * POST request
         */
        post(endpoint, data = {}, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'POST',
                data
            });
        }

        /**
         * PUT request
         */
        put(endpoint, data = {}, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'PUT',
                data
            });
        }

        /**
         * DELETE request
         */
        delete(endpoint, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'DELETE'
            });
        }
    }

    /**
     * API methods for specific features
     */
    class TierphysioAPI extends ApiClient {
        // ========== OWNERS ==========
        async createOwner(data) {
            return this.post('index.php', { action: 'owner_create', ...data });
        }

        async updateOwner(id, data) {
            return this.post('index.php', { action: 'owner_update', id, ...data });
        }

        async getOwner(id) {
            return this.get('index.php', { action: 'owner_get', id });
        }

        async listOwners(params = {}) {
            return this.get('index.php', { action: 'owner_list', ...params });
        }

        // ========== PATIENTS ==========
        async createPatient(data) {
            return this.post('index.php', { action: 'patient_create', ...data });
        }

        async updatePatient(id, data) {
            return this.post('index.php', { action: 'patient_update', id, ...data });
        }

        async getPatient(id) {
            return this.get('index.php', { action: 'patient_get', id });
        }

        async listPatients(params = {}) {
            return this.get('index.php', { action: 'patient_list', ...params });
        }

        // ========== COMBINED ==========
        async createOwnerWithPatient(data) {
            return this.post('index.php', { action: 'owner_patient_create', ...data });
        }

        // ========== APPOINTMENTS ==========
        async createAppointment(data) {
            return this.post('index.php', { action: 'appointment_create', ...data });
        }

        async updateAppointment(id, data) {
            return this.post('index.php', { action: 'appointment_update', id, ...data });
        }

        async listAppointments(date = null, patientId = null) {
            const params = { action: 'appointment_list' };
            if (date) params.date = date;
            if (patientId) params.patient_id = patientId;
            return this.get('index.php', params);
        }

        async deleteAppointment(id) {
            return this.post('index.php', { action: 'appointment_delete', id });
        }

        // ========== INVOICES ==========
        async createInvoice(data) {
            return this.post('index.php', { action: 'invoice_create', ...data });
        }

        async updateInvoice(id, data) {
            return this.post('index.php', { action: 'invoice_update', id, ...data });
        }

        async listInvoices(params = {}) {
            return this.get('index.php', { action: 'invoice_list', ...params });
        }

        async updateInvoiceStatus(id, status) {
            return this.post('index.php', { action: 'invoice_status', id, status });
        }

        // ========== SEARCH & METRICS ==========
        async search(query) {
            // Use the dedicated search.php endpoint for compatibility
            return this.get('search.php', { q: query });
        }

        async getDashboardMetrics() {
            // Use the dedicated dashboard_metrics.php endpoint
            return this.get('dashboard_metrics.php');
        }

        async getAdminStats() {
            return this.get('index.php', { action: 'admin_stats' });
        }

        // ========== HEALTH CHECK ==========
        async healthCheck() {
            return this.get('health.php');
        }
    }

    // Create global API instance
    const api = new TierphysioAPI();

    // Export to window
    window.TierphysioAPI = api;

    // Helper function for showing notifications
    window.showApiError = function(error, title = 'Fehler') {
        console.error('API Error:', error);
        
        const message = error.data?.error?.message || error.message || 'Ein unerwarteter Fehler ist aufgetreten';
        
        // Use Bootstrap toast if available
        if (window.bootstrap && window.bootstrap.Toast) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-danger border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}:</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            const container = document.querySelector('.toast-container') || createToastContainer();
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = container.lastElementChild;
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            
            // Remove after hidden
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        } else {
            // Fallback to alert
            alert(`${title}: ${message}`);
        }
    };

    window.showApiSuccess = function(message, title = 'Erfolg') {
        // Use Bootstrap toast if available
        if (window.bootstrap && window.bootstrap.Toast) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}:</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            const container = document.querySelector('.toast-container') || createToastContainer();
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = container.lastElementChild;
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            
            // Remove after hidden
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        } else {
            // Fallback to alert
            alert(`${title}: ${message}`);
        }
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

})(window);
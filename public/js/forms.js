/**
 * Form Handler for Tierphysio Manager
 * Handles AJAX form submissions and modal forms
 * @version 2.0.0
 */

(function(window) {
    'use strict';

    /**
     * Initialize AJAX forms
     */
    function initAjaxForms() {
        // Find all forms with data-ajax attribute
        document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
        });

        // Initialize modal forms
        initModalForms();
    }

    /**
     * Handle form submission via AJAX
     */
    async function handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('[type="submit"]');
        const action = form.dataset.action || form.action;
        const method = form.method || 'POST';
        
        // Disable submit button
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Wird gespeichert...';
        }

        try {
            // Get form data
            const formData = new FormData(form);
            
            // Add action if specified
            if (form.dataset.apiAction) {
                formData.append('action', form.dataset.apiAction);
            }

            // Convert FormData to object for JSON
            const data = {};
            for (let [key, value] of formData.entries()) {
                // Handle multiple values (like checkboxes)
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }

            // Make API call
            let response;
            if (window.TierphysioAPI && form.dataset.apiAction) {
                // Use API client
                response = await window.TierphysioAPI.post('index.php', data);
            } else {
                // Direct fetch
                const fetchResponse = await fetch(action, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data),
                    credentials: 'same-origin'
                });

                const contentType = fetchResponse.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned non-JSON response');
                }

                response = await fetchResponse.json();
                
                if (!fetchResponse.ok || response.success === false) {
                    throw new Error(response.error?.message || 'Fehler beim Speichern');
                }
            }

            // Handle success
            handleFormSuccess(form, response);

        } catch (error) {
            console.error('Form submission error:', error);
            handleFormError(form, error);
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                if (submitBtn.dataset.originalText) {
                    submitBtn.innerHTML = submitBtn.dataset.originalText;
                }
            }
        }
    }

    /**
     * Handle successful form submission
     */
    function handleFormSuccess(form, response) {
        // Show success message
        const message = response.data?.message || response.message || 'Erfolgreich gespeichert';
        
        if (window.showApiSuccess) {
            window.showApiSuccess(message);
        } else {
            alert('Erfolg: ' + message);
        }

        // Handle specific actions
        const onSuccess = form.dataset.onSuccess;
        if (onSuccess) {
            switch (onSuccess) {
                case 'reload':
                    setTimeout(() => window.location.reload(), 1000);
                    break;
                    
                case 'redirect':
                    if (form.dataset.redirectUrl) {
                        setTimeout(() => {
                            window.location.href = form.dataset.redirectUrl;
                        }, 1000);
                    }
                    break;
                    
                case 'close-modal':
                    const modal = form.closest('.modal');
                    if (modal && window.bootstrap) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                            // Reload after modal is hidden
                            modal.addEventListener('hidden.bs.modal', () => {
                                window.location.reload();
                            }, { once: true });
                        }
                    }
                    break;
                    
                case 'reset':
                    form.reset();
                    break;
            }
        }

        // Trigger custom event
        form.dispatchEvent(new CustomEvent('ajax:success', { detail: response }));
    }

    /**
     * Handle form submission error
     */
    function handleFormError(form, error) {
        const message = error.message || 'Ein Fehler ist aufgetreten';
        
        if (window.showApiError) {
            window.showApiError(error);
        } else {
            alert('Fehler: ' + message);
        }

        // Display validation errors
        if (error.data?.error?.missing_fields) {
            error.data.error.missing_fields.forEach(field => {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add('is-invalid');
                    
                    // Add error message if not exists
                    if (!input.nextElementSibling?.classList.contains('invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = 'Dieses Feld ist erforderlich';
                        input.parentNode.insertBefore(feedback, input.nextSibling);
                    }
                }
            });
        }

        // Trigger custom event
        form.dispatchEvent(new CustomEvent('ajax:error', { detail: error }));
    }

    /**
     * Initialize modal forms
     */
    function initModalForms() {
        // Owner/Patient creation modal
        const ownerPatientModal = document.getElementById('createOwnerPatientModal');
        if (ownerPatientModal) {
            const form = ownerPatientModal.querySelector('form');
            if (form) {
                form.dataset.ajax = 'true';
                form.dataset.apiAction = 'owner_patient_create';
                form.dataset.onSuccess = 'close-modal';
                
                if (!form.hasAttribute('data-initialized')) {
                    form.addEventListener('submit', handleFormSubmit);
                    form.setAttribute('data-initialized', 'true');
                }
            }
        }

        // Appointment creation modal
        const appointmentModal = document.getElementById('createAppointmentModal');
        if (appointmentModal) {
            const form = appointmentModal.querySelector('form');
            if (form) {
                form.dataset.ajax = 'true';
                form.dataset.apiAction = 'appointment_create';
                form.dataset.onSuccess = 'close-modal';
                
                if (!form.hasAttribute('data-initialized')) {
                    form.addEventListener('submit', handleFormSubmit);
                    form.setAttribute('data-initialized', 'true');
                }
            }
        }

        // Invoice creation modal
        const invoiceModal = document.getElementById('createInvoiceModal');
        if (invoiceModal) {
            const form = invoiceModal.querySelector('form');
            if (form) {
                form.dataset.ajax = 'true';
                form.dataset.apiAction = 'invoice_create';
                form.dataset.onSuccess = 'close-modal';
                
                if (!form.hasAttribute('data-initialized')) {
                    form.addEventListener('submit', handleFormSubmit);
                    form.setAttribute('data-initialized', 'true');
                    
                    // Initialize invoice items handling
                    initInvoiceItems(form);
                }
            }
        }
    }

    /**
     * Initialize invoice items dynamic handling
     */
    function initInvoiceItems(form) {
        const itemsContainer = form.querySelector('#invoice-items');
        const addItemBtn = form.querySelector('#add-invoice-item');
        
        if (!itemsContainer || !addItemBtn) return;

        addItemBtn.addEventListener('click', () => {
            const itemCount = itemsContainer.querySelectorAll('.invoice-item').length;
            const newItem = document.createElement('div');
            newItem.className = 'invoice-item row mb-2';
            newItem.innerHTML = `
                <div class="col-md-6">
                    <input type="text" class="form-control" name="items[${itemCount}][description]" placeholder="Beschreibung" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="items[${itemCount}][quantity]" placeholder="Menge" value="1" min="1" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" name="items[${itemCount}][price]" placeholder="Preis" min="0" step="0.01" required>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            `;
            
            itemsContainer.appendChild(newItem);
            
            // Add remove handler
            newItem.querySelector('.remove-item').addEventListener('click', () => {
                newItem.remove();
                updateInvoiceTotal();
            });
            
            // Add change handlers for calculation
            newItem.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', updateInvoiceTotal);
            });
        });

        // Update invoice total
        function updateInvoiceTotal() {
            let subtotal = 0;
            itemsContainer.querySelectorAll('.invoice-item').forEach(item => {
                const quantity = parseFloat(item.querySelector('[name*="quantity"]').value) || 0;
                const price = parseFloat(item.querySelector('[name*="price"]').value) || 0;
                subtotal += quantity * price;
            });
            
            const taxRate = parseFloat(form.querySelector('[name="tax_rate"]')?.value || 19);
            const tax = subtotal * (taxRate / 100);
            const total = subtotal + tax;
            
            // Update display
            const totalDisplay = form.querySelector('#invoice-total-display');
            if (totalDisplay) {
                totalDisplay.textContent = `Gesamt: €${total.toFixed(2)} (Netto: €${subtotal.toFixed(2)}, MwSt: €${tax.toFixed(2)})`;
            }
        }
    }

    /**
     * Quick action buttons
     */
    window.quickCreate = {
        owner: async function() {
            if (!window.TierphysioAPI) {
                alert('API nicht verfügbar');
                return;
            }

            const firstName = prompt('Vorname:');
            if (!firstName) return;
            
            const lastName = prompt('Nachname:');
            if (!lastName) return;

            try {
                const response = await window.TierphysioAPI.createOwner({
                    first_name: firstName,
                    last_name: lastName
                });
                
                window.showApiSuccess('Besitzer wurde angelegt');
                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                window.showApiError(error);
            }
        },

        patient: async function(ownerId) {
            if (!window.TierphysioAPI) {
                alert('API nicht verfügbar');
                return;
            }

            if (!ownerId) {
                ownerId = prompt('Besitzer-ID:');
                if (!ownerId) return;
            }

            const name = prompt('Tier-Name:');
            if (!name) return;
            
            const species = prompt('Tierart (z.B. Hund, Katze):', 'Hund');

            try {
                const response = await window.TierphysioAPI.createPatient({
                    owner_id: ownerId,
                    name: name,
                    species: species
                });
                
                window.showApiSuccess('Patient wurde angelegt');
                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                window.showApiError(error);
            }
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAjaxForms);
    } else {
        initAjaxForms();
    }

    // Re-initialize after dynamic content is loaded
    document.addEventListener('ajax:loaded', initAjaxForms);

})(window);
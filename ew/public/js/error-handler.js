/**
 * Global Error Handler for Tierphysio Manager
 * Provides user-friendly error messages and logging
 */

(function() {
    'use strict';

    // Error message translations
    const errorMessages = {
        'network': 'Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.',
        'timeout': 'Die Anfrage hat zu lange gedauert. Bitte versuchen Sie es erneut.',
        '400': 'Ungültige Anfrage. Bitte überprüfen Sie Ihre Eingaben.',
        '401': 'Sie sind nicht angemeldet. Bitte melden Sie sich an.',
        '403': 'Sie haben keine Berechtigung für diese Aktion.',
        '404': 'Die angeforderte Ressource wurde nicht gefunden.',
        '422': 'Die eingegebenen Daten sind ungültig.',
        '429': 'Zu viele Anfragen. Bitte warten Sie einen Moment.',
        '500': 'Serverfehler. Bitte versuchen Sie es später erneut.',
        '502': 'Der Server ist momentan nicht erreichbar.',
        '503': 'Der Service ist momentan nicht verfügbar.',
        'default': 'Ein unerwarteter Fehler ist aufgetreten.'
    };

    // Error logger
    class ErrorLogger {
        constructor() {
            this.errors = [];
            this.maxErrors = 50;
        }

        log(error, context = {}) {
            const errorEntry = {
                timestamp: new Date().toISOString(),
                message: error.message || error,
                stack: error.stack,
                context: context,
                userAgent: navigator.userAgent,
                url: window.location.href
            };

            this.errors.unshift(errorEntry);
            if (this.errors.length > this.maxErrors) {
                this.errors.pop();
            }

            // Log to console in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.error('Error logged:', errorEntry);
            }

            // Send to server if critical
            if (context.critical) {
                this.sendToServer(errorEntry);
            }

            return errorEntry;
        }

        async sendToServer(errorEntry) {
            try {
                await fetch('/api/log-error', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(errorEntry)
                });
            } catch (e) {
                // Silently fail - don't create error loop
                console.warn('Could not send error to server:', e);
            }
        }

        getErrors() {
            return this.errors;
        }

        clearErrors() {
            this.errors = [];
        }
    }

    // Global error logger instance
    window.ErrorLogger = new ErrorLogger();

    // Enhanced error handler
    window.handleApiError = function(error, showToast = true) {
        // Determine error type and message
        let userMessage = errorMessages.default;
        let errorCode = null;

        if (error.message) {
            if (error.message.includes('network') || error.message.includes('fetch')) {
                userMessage = errorMessages.network;
            } else if (error.message.includes('timeout')) {
                userMessage = errorMessages.timeout;
            } else if (error.status) {
                errorCode = error.status.toString();
                userMessage = errorMessages[errorCode] || errorMessages.default;
            }
        }

        // Log the error
        window.ErrorLogger.log(error, {
            type: 'api_error',
            code: errorCode,
            critical: errorCode && errorCode.startsWith('5')
        });

        // Show user-friendly message
        if (showToast && window.showToast) {
            window.showToast(userMessage, 'error');
        }

        return {
            userMessage: userMessage,
            technicalError: error,
            code: errorCode
        };
    };

    // Global unhandled error catcher
    window.addEventListener('error', function(event) {
        window.ErrorLogger.log(event.error || event.message, {
            type: 'unhandled_error',
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno
        });
    });

    // Unhandled promise rejection catcher
    window.addEventListener('unhandledrejection', function(event) {
        window.ErrorLogger.log(event.reason, {
            type: 'unhandled_rejection',
            promise: event.promise
        });
        
        // Prevent default browser behavior
        event.preventDefault();
    });

    // Network status monitor
    let isOnline = navigator.onLine;
    
    window.addEventListener('online', function() {
        if (!isOnline) {
            isOnline = true;
            if (window.showToast) {
                window.showToast('Verbindung wiederhergestellt', 'success');
            }
        }
    });

    window.addEventListener('offline', function() {
        if (isOnline) {
            isOnline = false;
            if (window.showToast) {
                window.showToast('Keine Internetverbindung', 'error');
            }
        }
    });

    // Performance monitor
    if (window.PerformanceObserver) {
        const perfObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.duration > 3000) {
                    window.ErrorLogger.log(`Slow resource: ${entry.name} (${Math.round(entry.duration)}ms)`, {
                        type: 'performance',
                        entryType: entry.entryType,
                        duration: entry.duration
                    });
                }
            }
        });

        try {
            perfObserver.observe({ entryTypes: ['resource', 'navigation'] });
        } catch (e) {
            // Some browsers don't support all entry types
        }
    }

    // Retry mechanism for failed requests
    window.retryableRequest = async function(fn, maxRetries = 3, delay = 1000) {
        let lastError;
        
        for (let i = 0; i < maxRetries; i++) {
            try {
                return await fn();
            } catch (error) {
                lastError = error;
                
                if (i < maxRetries - 1) {
                    // Wait before retry with exponential backoff
                    await new Promise(resolve => setTimeout(resolve, delay * Math.pow(2, i)));
                }
            }
        }
        
        throw lastError;
    };

    // Debug helper for development
    window.debugInfo = function() {
        const errors = window.ErrorLogger.getErrors();
        const info = {
            errors: errors,
            errorCount: errors.length,
            online: navigator.onLine,
            userAgent: navigator.userAgent,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            memory: performance.memory ? {
                used: Math.round(performance.memory.usedJSHeapSize / 1048576) + ' MB',
                total: Math.round(performance.memory.totalJSHeapSize / 1048576) + ' MB',
                limit: Math.round(performance.memory.jsHeapSizeLimit / 1048576) + ' MB'
            } : 'Not available'
        };
        
        console.table(info);
        return info;
    };

})();
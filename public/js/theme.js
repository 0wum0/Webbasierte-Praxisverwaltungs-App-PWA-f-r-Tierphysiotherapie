/**
 * Tierphysio Manager - Theme Management
 * Single icon display, no gray button
 * @version 3.0.0
 */

(function() {
    'use strict';

    const STORAGE_KEY = 'tierphysio-theme';
    const DEFAULT_THEME = 'light';
    
    // Prevent multiple initializations
    if (window.ThemeManager && window.ThemeManager.initialized) {
        console.warn('Theme manager already initialized');
        return;
    }

    window.ThemeManager = {
        initialized: false,
        currentTheme: DEFAULT_THEME
    };

    /**
     * Get the current theme from localStorage or default
     */
    function getCurrentTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        } catch (e) {
            console.warn('LocalStorage not available:', e);
            return DEFAULT_THEME;
        }
    }

    /**
     * Save theme preference to localStorage
     */
    function saveTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
            window.ThemeManager.currentTheme = theme;
        } catch (e) {
            console.warn('Could not save theme:', e);
        }
    }

    /**
     * Apply theme to document
     */
    function applyTheme(theme) {
        // Validate theme
        if (theme !== 'light' && theme !== 'dark') {
            theme = DEFAULT_THEME;
        }

        // Apply to document
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.dataset.theme = theme;
        
        // Update body class for compatibility
        document.body.classList.remove('theme-light', 'theme-dark');
        document.body.classList.add(`theme-${theme}`);

        // Save preference
        saveTheme(theme);

        // Update all theme toggle buttons
        updateThemeButtons(theme);

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    /**
     * Update all theme toggle button states
     */
    function updateThemeButtons(theme) {
        const buttons = document.querySelectorAll('#themeToggle, .theme-toggle');
        
        buttons.forEach(button => {
            // Remove any inline styles that might make it gray
            button.style.removeProperty('background');
            button.style.removeProperty('background-color');
            button.style.removeProperty('border');
            
            // Ensure button stays transparent/white
            button.style.color = 'white';
            
            // Update aria-label for accessibility
            button.setAttribute('aria-label', theme === 'light' ? 'Zu Dunkelmodus wechseln' : 'Zu Hellmodus wechseln');
            button.setAttribute('title', theme === 'light' ? 'Zu Dunkelmodus wechseln' : 'Zu Hellmodus wechseln');
        });
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    }

    /**
     * Initialize theme toggle buttons
     */
    function initThemeButtons() {
        const buttons = document.querySelectorAll('#themeToggle, .theme-toggle');
        
        buttons.forEach(button => {
            // Remove old event listeners by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add click handler
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleTheme();
            });

            // Ensure button is clickable
            newButton.style.pointerEvents = 'auto';
            newButton.style.cursor = 'pointer';
            newButton.style.position = 'relative';
            newButton.style.zIndex = '1050';
        });
    }

    /**
     * Initialize theme system
     */
    function init() {
        // Prevent double initialization
        if (window.ThemeManager.initialized) return;

        // Get and apply saved theme
        const savedTheme = getCurrentTheme();
        applyTheme(savedTheme);

        // Initialize buttons
        initThemeButtons();

        // Listen for storage changes (sync across tabs)
        window.addEventListener('storage', function(e) {
            if (e.key === STORAGE_KEY && e.newValue) {
                applyTheme(e.newValue);
            }
        });

        // Mark as initialized
        window.ThemeManager.initialized = true;
        console.log(`Theme manager initialized with ${savedTheme} theme`);
    }

    // Initialize immediately to prevent FOUC
    if (document.readyState === 'loading') {
        // Apply theme immediately even before DOM is ready
        const savedTheme = getCurrentTheme();
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Full init when DOM is ready
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM is already loaded
        init();
    }

    // Export functions for global use
    window.toggleTheme = toggleTheme;
    window.setTheme = applyTheme;

})();
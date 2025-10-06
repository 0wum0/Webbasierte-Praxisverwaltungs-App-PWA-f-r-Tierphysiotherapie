/**
 * Theme Manager for Tierphysio Manager
 * Handles light/dark mode switching with localStorage persistence
 * Prevents FOUC and animation glitches - FIXED VERSION
 * @version 3.0.0
 */

(function() {
    'use strict';

    // Configuration
    const STORAGE_KEY = 'tierphysio-theme';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    const DEFAULT_THEME = THEME_LIGHT;
    
    /**
     * Initialize theme immediately (prevent FOUC)
     */
    function initThemeEarly() {
        const savedTheme = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
    
    // Apply theme as early as possible
    initThemeEarly();
    
    /**
     * Theme Manager
     */
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        // Get current theme
        function getCurrentTheme() {
            return html.getAttribute('data-theme') || DEFAULT_THEME;
        }
        
        // Update icon based on theme
        function updateIcon() {
            const isDark = getCurrentTheme() === THEME_DARK;
            if (themeIcon) {
                themeIcon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
            if (themeToggle) {
                themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            }
        }
        
        // Apply theme
        function applyTheme(theme) {
            // Remove transition during theme switch to prevent flash
            html.style.transition = 'none';
            
            // Apply theme
            html.setAttribute('data-theme', theme);
            
            // Save to localStorage
            try {
                localStorage.setItem(STORAGE_KEY, theme);
            } catch (e) {
                console.warn('Could not save theme preference:', e);
            }
            
            // Update icon
            updateIcon();
            
            // Update meta theme-color
            let metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (!metaThemeColor) {
                metaThemeColor = document.createElement('meta');
                metaThemeColor.name = 'theme-color';
                document.head.appendChild(metaThemeColor);
            }
            metaThemeColor.content = theme === THEME_DARK ? '#121212' : '#7C4DFF';
            
            // Re-enable transitions after a brief delay
            setTimeout(function() {
                html.style.transition = '';
            }, 50);
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('themechange', {
                detail: { theme: theme }
            }));
        }
        
        // Toggle theme
        function toggleTheme() {
            const currentTheme = getCurrentTheme();
            const newTheme = currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
            applyTheme(newTheme);
        }
        
        // Set up theme toggle button
        if (themeToggle) {
            // Remove any existing listeners (prevents duplicate handlers)
            const newToggle = themeToggle.cloneNode(true);
            themeToggle.parentNode.replaceChild(newToggle, themeToggle);
            
            // Add click handler
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleTheme();
            });
        }
        
        // Initialize icon
        updateIcon();
        
        // Listen for theme changes in other tabs
        window.addEventListener('storage', function(e) {
            if (e.key === STORAGE_KEY) {
                const newTheme = e.newValue || DEFAULT_THEME;
                applyTheme(newTheme);
            }
        });
        
        // Keyboard shortcut (Ctrl/Cmd + Shift + T)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                toggleTheme();
            }
        });
        
        // Apply initial theme with all features
        applyTheme(getCurrentTheme());
    });
    
})();
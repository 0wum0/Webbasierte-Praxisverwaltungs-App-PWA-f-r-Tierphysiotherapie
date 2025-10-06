/**
 * Theme Manager for Tierphysio Manager
 * Handles light/dark mode switching with localStorage persistence
 * Prevents FOUC and animation glitches
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
     * Theme Manager Class
     */
    class ThemeManager {
        constructor() {
            this.currentTheme = this.loadTheme();
            this.toggleButtons = [];
            this.init();
        }
        
        /**
         * Initialize theme manager
         */
        init() {
            // Apply saved theme immediately
            this.applyTheme(this.currentTheme);
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupToggleButtons());
            } else {
                this.setupToggleButtons();
            }
            
            // Listen for theme changes in other tabs
            window.addEventListener('storage', (e) => {
                if (e.key === STORAGE_KEY) {
                    this.currentTheme = e.newValue || DEFAULT_THEME;
                    this.applyTheme(this.currentTheme);
                    this.updateToggleButtons();
                }
            });
            
            // Add keyboard shortcut (Ctrl/Cmd + Shift + T)
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                    e.preventDefault();
                    this.toggleTheme();
                }
            });
        }
        
        /**
         * Load theme from localStorage or system preference
         */
        loadTheme() {
            // Check localStorage first
            const savedTheme = localStorage.getItem(STORAGE_KEY);
            if (savedTheme === THEME_LIGHT || savedTheme === THEME_DARK) {
                return savedTheme;
            }
            
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return THEME_DARK;
            }
            
            return DEFAULT_THEME;
        }
        
        /**
         * Save theme to localStorage
         */
        saveTheme(theme) {
            try {
                localStorage.setItem(STORAGE_KEY, theme);
            } catch (e) {
                console.warn('Could not save theme preference:', e);
            }
        }
        
        /**
         * Apply theme to document
         */
        applyTheme(theme) {
            const root = document.documentElement;
            
            // Remove transition during initial load to prevent flash
            const isInitialLoad = !root.hasAttribute('data-theme-applied');
            if (isInitialLoad) {
                root.style.transition = 'none';
                root.setAttribute('data-theme-applied', 'true');
            }
            
            // Apply theme
            root.setAttribute('data-theme', theme);
            
            // Update meta theme-color for mobile browsers
            let metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (!metaThemeColor) {
                metaThemeColor = document.createElement('meta');
                metaThemeColor.name = 'theme-color';
                document.head.appendChild(metaThemeColor);
            }
            metaThemeColor.content = theme === THEME_DARK ? '#1a1d23' : '#7C4DFF';
            
            // Update color-scheme meta tag
            let metaColorScheme = document.querySelector('meta[name="color-scheme"]');
            if (!metaColorScheme) {
                metaColorScheme = document.createElement('meta');
                metaColorScheme.name = 'color-scheme';
                document.head.appendChild(metaColorScheme);
            }
            metaColorScheme.content = theme === THEME_DARK ? 'dark' : 'light';
            
            // Re-enable transitions after initial load
            if (isInitialLoad) {
                setTimeout(() => {
                    root.style.transition = '';
                }, 0);
            }
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('themechange', {
                detail: { theme: theme }
            }));
        }
        
        /**
         * Toggle between light and dark theme
         */
        toggleTheme() {
            this.currentTheme = this.currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
            this.applyTheme(this.currentTheme);
            this.saveTheme(this.currentTheme);
            this.updateToggleButtons();
            
            // Add a small animation to the toggle button
            this.toggleButtons.forEach(btn => {
                btn.style.transform = 'rotate(180deg)';
                setTimeout(() => {
                    btn.style.transform = '';
                }, 300);
            });
        }
        
        /**
         * Setup toggle buttons
         */
        setupToggleButtons() {
            // Find all theme toggle buttons
            this.toggleButtons = document.querySelectorAll('#themeToggle, .theme-toggle-btn, [data-theme-toggle]');
            
            // Add click handlers
            this.toggleButtons.forEach(btn => {
                // Remove any existing listeners to prevent duplicates
                btn.replaceWith(btn.cloneNode(true));
            });
            
            // Re-query after replacing
            this.toggleButtons = document.querySelectorAll('#themeToggle, .theme-toggle-btn, [data-theme-toggle]');
            
            this.toggleButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleTheme();
                });
            });
            
            // Update button states
            this.updateToggleButtons();
        }
        
        /**
         * Update toggle button states
         */
        updateToggleButtons() {
            const isDark = this.currentTheme === THEME_DARK;
            
            this.toggleButtons.forEach(btn => {
                // Update icon
                const iconHtml = isDark 
                    ? '<i class="bi bi-sun-fill"></i>' 
                    : '<i class="bi bi-moon-fill"></i>';
                
                // Only update if content has changed to prevent flicker
                if (btn.innerHTML !== iconHtml) {
                    btn.innerHTML = iconHtml;
                }
                
                // Update accessibility
                btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
                btn.title = isDark ? 'Helles Design aktivieren' : 'Dunkles Design aktivieren';
            });
        }
        
        /**
         * Get current theme
         */
        getTheme() {
            return this.currentTheme;
        }
        
        /**
         * Set theme programmatically
         */
        setTheme(theme) {
            if (theme === THEME_LIGHT || theme === THEME_DARK) {
                this.currentTheme = theme;
                this.applyTheme(theme);
                this.saveTheme(theme);
                this.updateToggleButtons();
            }
        }
    }
    
    // Initialize theme manager and expose to global scope
    window.themeManager = new ThemeManager();
    
})();
/**
 * Theme Manager for Tierphysio Manager
 * Handles light/dark mode switching with localStorage persistence
 * 
 * @author Florian Engelhardt
 * @version 2.0
 */

(function() {
  'use strict';

  // Configuration
  const STORAGE_KEY = 'tierphysio-theme';
  const THEME_LIGHT = 'light';
  const THEME_DARK = 'dark';
  const DEFAULT_THEME = THEME_LIGHT;
  
  // Icons for theme toggle button (using Bootstrap Icons)
  const ICON_LIGHT = '<i class="bi bi-sun-fill"></i>';
  const ICON_DARK = '<i class="bi bi-moon-fill"></i>';
  
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
      const isInitialLoad = !root.hasAttribute('data-theme');
      if (isInitialLoad) {
        root.style.transition = 'none';
      }
      
      // Apply theme
      root.setAttribute('data-theme', theme);
      
      // Update meta theme-color for mobile browsers
      const metaThemeColor = document.querySelector('meta[name="theme-color"]');
      if (metaThemeColor) {
        metaThemeColor.content = theme === THEME_DARK ? '#1E1B24' : '#ffffff';
      } else {
        const meta = document.createElement('meta');
        meta.name = 'theme-color';
        meta.content = theme === THEME_DARK ? '#1E1B24' : '#ffffff';
        document.head.appendChild(meta);
      }
      
      // Update color-scheme meta tag
      const metaColorScheme = document.querySelector('meta[name="color-scheme"]');
      if (metaColorScheme) {
        metaColorScheme.content = theme === THEME_DARK ? 'dark' : 'light';
      } else {
        const meta = document.createElement('meta');
        meta.name = 'color-scheme';
        meta.content = theme === THEME_DARK ? 'dark' : 'light';
        document.head.appendChild(meta);
      }
      
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
        btn.classList.add('theme-toggle-animated');
        setTimeout(() => btn.classList.remove('theme-toggle-animated'), 300);
      });
    }
    
    /**
     * Setup toggle buttons
     */
    setupToggleButtons() {
      // Find all theme toggle buttons
      this.toggleButtons = document.querySelectorAll('[data-theme-toggle], #theme-toggle, .theme-toggle-btn');
      
      // If no buttons found, create a default one
      if (this.toggleButtons.length === 0) {
        this.createDefaultToggleButton();
      }
      
      // Add click handlers
      this.toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          this.toggleTheme();
        });
      });
      
      // Update button states
      this.updateToggleButtons();
    }
    
    /**
     * Create a default toggle button if none exists
     */
    createDefaultToggleButton() {
      // Check if we're in a header or navbar
      const header = document.querySelector('header, .header, .navbar, nav');
      if (!header) return;
      
      // Find a suitable container for the button
      const rightSection = header.querySelector('.header-right, .navbar-nav:last-child, .top-menu');
      if (!rightSection) return;
      
      // Create the toggle button
      const toggleBtn = document.createElement('button');
      toggleBtn.id = 'theme-toggle';
      toggleBtn.className = 'theme-toggle-btn';
      toggleBtn.setAttribute('data-theme-toggle', '');
      toggleBtn.setAttribute('aria-label', 'Toggle theme');
      toggleBtn.style.cssText = `
        background: transparent;
        border: 2px solid var(--color-primary);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 18px;
        margin-left: 10px;
      `;
      
      // Add hover effect
      toggleBtn.addEventListener('mouseenter', () => {
        toggleBtn.style.background = 'var(--color-primary)';
        toggleBtn.style.transform = 'scale(1.1)';
      });
      
      toggleBtn.addEventListener('mouseleave', () => {
        toggleBtn.style.background = 'transparent';
        toggleBtn.style.transform = 'scale(1)';
      });
      
      // Insert the button
      rightSection.appendChild(toggleBtn);
      
      // Add to toggle buttons array
      this.toggleButtons = [toggleBtn];
    }
    
    /**
     * Update toggle button states
     */
    updateToggleButtons() {
      const isDark = this.currentTheme === THEME_DARK;
      
      this.toggleButtons.forEach(btn => {
        // Update icon
        btn.innerHTML = isDark ? ICON_LIGHT : ICON_DARK;
        
        // Update accessibility label
        btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        
        // Update title
        btn.title = isDark ? 'Helles Design aktivieren' : 'Dunkles Design aktivieren';
        
        // Add/remove dark class
        if (isDark) {
          btn.classList.add('dark');
        } else {
          btn.classList.remove('dark');
        }
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
  
  // Add CSS for theme toggle animation
  const style = document.createElement('style');
  style.textContent = `
    .theme-toggle-animated {
      animation: themeToggleRotate 0.3s ease;
    }
    
    @keyframes themeToggleRotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Smooth transition for theme changes */
    [data-theme] {
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    /* Theme toggle button styles */
    .theme-toggle-btn:hover {
      box-shadow: 0 0 10px rgba(123, 91, 190, 0.5);
    }
    
    .theme-toggle-btn:active {
      transform: scale(0.95);
    }
  `;
  document.head.appendChild(style);
  
})();
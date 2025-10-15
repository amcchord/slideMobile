/**
 * Theme Management System
 * Handles light/dark theme switching with localStorage persistence
 */

(function() {
    'use strict';

    // Get saved theme preference or default to 'dark'
    function getSavedTheme() {
        return localStorage.getItem('theme') || 'dark';
    }

    // Apply theme to the document
    function applyTheme(theme) {
        const html = document.documentElement;
        html.setAttribute('data-bs-theme', theme);
        
        // Update PWA theme-color meta tag
        const themeColorMeta = document.querySelector('meta[name="theme-color"]');
        if (themeColorMeta) {
            if (theme === 'light') {
                themeColorMeta.setAttribute('content', '#f5f7fa');
            } else {
                themeColorMeta.setAttribute('content', '#212529');
            }
        }
        
        // Save preference
        localStorage.setItem('theme', theme);
        
        // Dispatch custom event for other components to react
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    // Toggle between light and dark themes
    function toggleTheme() {
        const currentTheme = getSavedTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
        return newTheme;
    }

    // Get current theme
    function getCurrentTheme() {
        return getSavedTheme();
    }

    // Initialize theme on page load
    function initTheme() {
        const savedTheme = getSavedTheme();
        applyTheme(savedTheme);
    }

    // Run initialization immediately
    initTheme();

    // Expose API globally
    window.themeManager = {
        toggle: toggleTheme,
        apply: applyTheme,
        getCurrent: getCurrentTheme
    };
})();


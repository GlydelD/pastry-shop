/**
 * Theme Toggle Functionality
 * Handles switching between light and dark modes and ensures only navigation toggles are active.
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('Theme Toggle: Script initializing...');

    // 1. Initialize Theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    console.log(`Theme Toggle: Initial theme is '${savedTheme}'`);

    // 2. Cleanup Duplicates
    const allToggles = document.querySelectorAll('.theme-toggle');
    console.log(`Theme Toggle: Found ${allToggles.length} .theme-toggle elements.`);

    allToggles.forEach(toggle => {
        // Strictly keep only toggles inside a <nav> element
        if (!toggle.closest('nav')) {
            console.warn('Theme Toggle: Removing invalid duplicate button:', toggle);
            toggle.remove();
        }
    });

    // 3. Setup Active Toggles
    const navToggles = document.querySelectorAll('nav .theme-toggle');
    if (navToggles.length === 0) {
        console.error('Theme Toggle: No navigation theme toggle found!');
        return;
    }

    navToggles.forEach(toggle => {
        console.log('Theme Toggle: Configuring button:', toggle);

        const iconSpan = toggle.querySelector('.icon') || (() => {
            const s = document.createElement('span');
            s.className = 'icon';
            toggle.appendChild(s);
            return s;
        })();

        // Function to update visual state
        const updateVisuals = (theme) => {
            const icon = theme === 'dark' ? '☀️' : '🌙';
            iconSpan.textContent = icon;
            // Ensure button doesn't look pressed/stuck
            toggle.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        };

        // Initialize visuals
        updateVisuals(savedTheme);

        // Click Handler
        toggle.onclick = function (e) {
            e.preventDefault();
            e.stopPropagation(); // Stop bubbling just in case
            console.log('Theme Toggle: Clicked!');

            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            console.log(`Theme Toggle: Switching to '${newTheme}'`);

            // Apply new theme
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update all active toggles on the page
            document.querySelectorAll('nav .theme-toggle').forEach(t => {
                const i = t.querySelector('.icon');
                if (i) {
                    i.textContent = newTheme === 'dark' ? '☀️' : '🌙';
                }
                t.setAttribute('aria-label', newTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            });
        };
    });
});

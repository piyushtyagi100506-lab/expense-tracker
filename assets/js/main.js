/**
 * Personal Expense Tracker - Main Application JavaScript & Interactive Components
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Theme Switcher (Dark/Light mode) using localStorage persistence.
 * - Mobile sidebar navigation drawer and backdrop listeners.
 * - Modern Floating Toast Notification System (replaces static intrusive alerts).
 * - Keyboard shortcuts (Alt+N: New Record, Alt+T: Toggle Theme, Alt+H: History, Alt+B: Budget).
 * - Floating Action Button (FAB) expandable quick-menu.
 * - Real-time client-side multi-currency converter calculator preview.
 */

document.addEventListener('DOMContentLoaded', function () {
    // =========================================================================
    // 1. Theme Switcher (Dark Mode / Light Mode)
    // =========================================================================
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeToggleIcon');

    // Check localStorage for preferred theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (themeIcon) {
            themeIcon.className = (savedTheme === 'dark') ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        if (themeIcon) {
            themeIcon.className = (newTheme === 'dark') ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }

        if (window.showToast) {
            window.showToast(`Switched interface to ${newTheme === 'dark' ? 'Dark' : 'Light'} theme mode.`, 'info', 'Theme Changed');
        }
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleTheme);
    }

    // =========================================================================
    // 2. Mobile Sidebar Navigation Drawer Toggle
    // =========================================================================
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // =========================================================================
    // 3. Password Visibility Toggle (Show / Hide Password)
    // =========================================================================
    const togglePasswordBtn = document.getElementById('togglePassword');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const passwordField = document.getElementById('password') || document.getElementById('new_password');
            const icon = this.querySelector('i');
            if (passwordField) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    if (icon) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                } else {
                    passwordField.type = 'password';
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            }
        });
    }

    // =========================================================================
    // 4. Modern Interactive Toast Notification System
    // =========================================================================
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    window.showToast = function (message, type = 'info', title = null, duration = 4500) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const iconMap = {
            success: 'fa-solid fa-circle-check',
            danger: 'fa-solid fa-circle-exclamation',
            warning: 'fa-solid fa-triangle-exclamation',
            info: 'fa-solid fa-circle-info'
        };

        const defaultTitles = {
            success: 'Success',
            danger: 'Attention Required',
            warning: 'Warning',
            info: 'Notice'
        };

        const displayTitle = title || defaultTitles[type] || 'Notification';

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${iconMap[type] || iconMap.info}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${displayTitle}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button type="button" class="toast-close">&times;</button>
            <div class="toast-progress">
                <div class="toast-progress-bar"></div>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Slide in animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
            const progressBar = toast.querySelector('.toast-progress-bar');
            if (progressBar) {
                progressBar.style.transition = `width ${duration}ms linear`;
                progressBar.style.width = '0%';
            }
        });

        // Close handlers
        const closeBtn = toast.querySelector('.toast-close');
        function removeToast() {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 350);
        }

        if (closeBtn) closeBtn.addEventListener('click', removeToast);
        const timer = setTimeout(removeToast, duration);

        toast.addEventListener('mouseenter', () => clearTimeout(timer));
    };

    // Automatically convert any existing server-side PHP flash banners into sleek toasts
    const staticFlash = document.querySelector('.alert-dismissible');
    if (staticFlash) {
        const text = staticFlash.querySelector('.alert-text')?.textContent.trim() || staticFlash.textContent.trim();
        let type = 'info';
        if (staticFlash.classList.contains('alert-success')) type = 'success';
        if (staticFlash.classList.contains('alert-danger')) type = 'danger';
        if (staticFlash.classList.contains('alert-warning')) type = 'warning';

        // Display as modern toast alongside the static banner
        window.showToast(text, type);
    }

    // =========================================================================
    // 5. Floating Action Button (FAB) Speed Dial
    // =========================================================================
    const fabContainer = document.querySelector('.fab-container');
    const fabMainBtn = document.querySelector('.fab-main-btn');

    if (fabContainer && fabMainBtn) {
        fabMainBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fabContainer.classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
            if (!fabContainer.contains(e.target)) {
                fabContainer.classList.remove('active');
            }
        });
    }

    // =========================================================================
    // 6. Keyboard Shortcuts for Power Users
    // =========================================================================
    document.addEventListener('keydown', function (e) {
        // Ignore shortcuts when inside an active input or textarea
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select') {
            if (e.key === 'Escape') {
                document.activeElement.blur();
                const modals = document.querySelectorAll('.modal.active');
                modals.forEach(m => m.classList.remove('active'));
            }
            return;
        }

        if (e.altKey && (e.key === 't' || e.key === 'T')) {
            e.preventDefault();
            toggleTheme();
        } else if (e.altKey && (e.key === 'n' || e.key === 'N')) {
            e.preventDefault();
            window.location.href = '/pages/add_transaction.php';
        } else if (e.altKey && (e.key === 'h' || e.key === 'H')) {
            e.preventDefault();
            window.location.href = '/pages/history.php';
        } else if (e.altKey && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            window.location.href = '/pages/budget.php';
        } else if (e.key === 'Escape') {
            closeSidebar();
            const modals = document.querySelectorAll('.modal.active');
            modals.forEach(m => m.classList.remove('active'));
        }
    });

    // =========================================================================
    // 7. Client-Side Multi-Currency Live Converter Preview
    // =========================================================================
    const amountInput = document.getElementById('amount');
    const currencyWidget = document.getElementById('currencyLiveWidget');

    if (amountInput && currencyWidget) {
        // Standard baseline exchange rates (relative to USD)
        const rates = {
            USD: 1.00,
            EUR: 0.92,
            GBP: 0.79,
            INR: 84.40,
            JPY: 154.50
        };

        function updateCurrencyConversion() {
            const rawVal = parseFloat(amountInput.value) || 0;
            const usdVal = rawVal; // assuming base currency is USD

            document.querySelectorAll('.currency-conv-chip').forEach(chip => {
                const code = chip.getAttribute('data-curr');
                const rate = rates[code] || 1.0;
                const converted = (usdVal * rate).toFixed(2);
                const amtEl = chip.querySelector('.curr-amount');
                if (amtEl) {
                    amtEl.textContent = converted;
                }
            });
        }

        amountInput.addEventListener('input', updateCurrencyConversion);
        updateCurrencyConversion();
    }
});

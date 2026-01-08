/**
 * Accessibility (a11y) Utilities
 * WCAG 2.1 AA compliance helpers for Cobra Shop
 */

class A11yManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupSkipLinks();
        this.setupFocusManagement();
        this.setupKeyboardNavigation();
        this.setupReducedMotion();
        this.setupAnnouncer();
        this.injectStyles();
    }

    /**
     * Inject accessibility styles
     */
    injectStyles() {
        if (document.getElementById('a11y-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'a11y-styles';
        styles.textContent = `
            /* Skip Link */
            .skip-link {
                position: absolute;
                top: -100%;
                left: 50%;
                transform: translateX(-50%);
                background: #1a1a2e;
                color: white;
                padding: 12px 24px;
                border-radius: 0 0 8px 8px;
                z-index: 10001;
                text-decoration: none;
                font-weight: 600;
                transition: top 0.3s;
            }

            .skip-link:focus {
                top: 0;
                outline: 3px solid #4361ee;
                outline-offset: 2px;
            }

            /* Focus Styles */
            *:focus {
                outline: 2px solid #4361ee;
                outline-offset: 2px;
            }

            *:focus:not(:focus-visible) {
                outline: none;
            }

            *:focus-visible {
                outline: 2px solid #4361ee;
                outline-offset: 2px;
            }

            /* Focus within for cards */
            .card:focus-within,
            .product-card:focus-within {
                box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.4);
            }

            /* Screen reader only content */
            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .sr-only-focusable:focus,
            .sr-only-focusable:active {
                position: static;
                width: auto;
                height: auto;
                overflow: visible;
                clip: auto;
                white-space: normal;
            }

            /* Live Announcer */
            .a11y-announcer {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            /* High Contrast Mode Support */
            @media (prefers-contrast: high) {
                button, .btn {
                    border: 2px solid currentColor !important;
                }

                a {
                    text-decoration: underline !important;
                }

                .card, .product-card {
                    border: 2px solid #000 !important;
                }
            }

            /* Reduced Motion */
            @media (prefers-reduced-motion: reduce) {
                *, *::before, *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            }

            /* Focus Trap Modal Backdrop */
            .focus-trap-active {
                overflow: hidden;
            }

            /* Keyboard-only focus ring */
            body.using-mouse *:focus {
                outline: none;
            }

            body.using-keyboard *:focus {
                outline: 2px solid #4361ee;
                outline-offset: 2px;
            }

            /* Error state for form fields */
            .field-error {
                border-color: #e63946 !important;
            }

            .field-error:focus {
                outline-color: #e63946;
                box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.25);
            }

            .error-message {
                color: #e63946;
                font-size: 0.875rem;
                margin-top: 4px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .error-message::before {
                content: '⚠';
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * Setup skip links for keyboard navigation
     */
    setupSkipLinks() {
        // Check if skip link already exists
        if (document.querySelector('.skip-link')) return;

        // Find main content
        const main = document.querySelector('main, [role="main"], #main-content, .main-content');
        if (main && !main.id) {
            main.id = 'main-content';
        }

        if (main) {
            const skipLink = document.createElement('a');
            skipLink.href = '#' + main.id;
            skipLink.className = 'skip-link';
            skipLink.textContent = 'Skip to main content';
            document.body.insertBefore(skipLink, document.body.firstChild);
        }
    }

    /**
     * Setup focus management
     */
    setupFocusManagement() {
        // Track input method (mouse vs keyboard)
        document.body.addEventListener('mousedown', () => {
            document.body.classList.add('using-mouse');
            document.body.classList.remove('using-keyboard');
        });

        document.body.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('using-keyboard');
                document.body.classList.remove('using-mouse');
            }
        });
    }

    /**
     * Setup keyboard navigation helpers
     */
    setupKeyboardNavigation() {
        // Escape key to close modals/dropdowns
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Close any open modals
                const modal = document.querySelector('.modal.show');
                if (modal) {
                    const closeBtn = modal.querySelector('[data-bs-dismiss="modal"], .btn-close');
                    if (closeBtn) closeBtn.click();
                }

                // Close any open dropdowns
                const dropdown = document.querySelector('.dropdown-menu.show');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // Arrow key navigation for lists
        document.addEventListener('keydown', (e) => {
            const list = e.target.closest('[role="listbox"], [role="menu"]');
            if (!list) return;

            const items = Array.from(list.querySelectorAll('[role="option"], [role="menuitem"]'));
            const currentIndex = items.indexOf(document.activeElement);

            if (e.key === 'ArrowDown' && currentIndex < items.length - 1) {
                e.preventDefault();
                items[currentIndex + 1].focus();
            } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                e.preventDefault();
                items[currentIndex - 1].focus();
            }
        });
    }

    /**
     * Setup reduced motion preference
     */
    setupReducedMotion() {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        
        const handleMotionPreference = (e) => {
            document.body.classList.toggle('reduced-motion', e.matches);
        };

        prefersReducedMotion.addEventListener('change', handleMotionPreference);
        handleMotionPreference(prefersReducedMotion);
    }

    /**
     * Setup live announcer for screen readers
     */
    setupAnnouncer() {
        if (document.getElementById('a11y-announcer')) return;

        const announcer = document.createElement('div');
        announcer.id = 'a11y-announcer';
        announcer.className = 'a11y-announcer';
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        document.body.appendChild(announcer);
    }

    /**
     * Announce a message to screen readers
     * @param {string} message
     * @param {string} priority - 'polite' | 'assertive'
     */
    announce(message, priority = 'polite') {
        const announcer = document.getElementById('a11y-announcer');
        if (!announcer) return;

        announcer.setAttribute('aria-live', priority);
        announcer.textContent = '';
        
        // Small delay for screen readers to pick up the change
        setTimeout(() => {
            announcer.textContent = message;
        }, 100);
    }

    /**
     * Create a focus trap for modals
     * @param {HTMLElement} element
     * @returns {Object} - Methods to activate/deactivate trap
     */
    createFocusTrap(element) {
        const focusableSelectors = [
            'button:not([disabled])',
            'a[href]',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');

        let firstFocusable, lastFocusable, previouslyFocused;

        const updateFocusableElements = () => {
            const focusables = element.querySelectorAll(focusableSelectors);
            firstFocusable = focusables[0];
            lastFocusable = focusables[focusables.length - 1];
        };

        const handleKeyDown = (e) => {
            if (e.key !== 'Tab') return;

            updateFocusableElements();

            if (e.shiftKey && document.activeElement === firstFocusable) {
                e.preventDefault();
                lastFocusable.focus();
            } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                e.preventDefault();
                firstFocusable.focus();
            }
        };

        return {
            activate: () => {
                previouslyFocused = document.activeElement;
                document.body.classList.add('focus-trap-active');
                element.addEventListener('keydown', handleKeyDown);
                updateFocusableElements();
                if (firstFocusable) firstFocusable.focus();
            },
            deactivate: () => {
                document.body.classList.remove('focus-trap-active');
                element.removeEventListener('keydown', handleKeyDown);
                if (previouslyFocused) previouslyFocused.focus();
            }
        };
    }

    /**
     * Add ARIA labels to form fields
     * @param {HTMLFormElement} form
     */
    labelFormFields(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Skip if already has accessible name
            if (input.getAttribute('aria-label') || input.getAttribute('aria-labelledby')) {
                return;
            }

            // Try to find associated label
            let label = form.querySelector(`label[for="${input.id}"]`);
            
            if (!label && input.id) {
                // Check for wrapping label
                label = input.closest('label');
            }

            if (label) {
                if (!input.id) {
                    input.id = 'field-' + Math.random().toString(36).substr(2, 9);
                    label.setAttribute('for', input.id);
                }
            } else if (input.placeholder) {
                // Use placeholder as fallback label
                input.setAttribute('aria-label', input.placeholder);
            }

            // Add required indicator
            if (input.required) {
                input.setAttribute('aria-required', 'true');
            }
        });
    }

    /**
     * Set error state on a form field
     * @param {HTMLElement} field
     * @param {string} message
     */
    setFieldError(field, message) {
        const errorId = field.id + '-error';
        
        // Remove existing error
        this.clearFieldError(field);

        // Add error class
        field.classList.add('field-error');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', errorId);

        // Create error message
        const errorEl = document.createElement('div');
        errorEl.id = errorId;
        errorEl.className = 'error-message';
        errorEl.setAttribute('role', 'alert');
        errorEl.textContent = message;
        
        field.parentNode.insertBefore(errorEl, field.nextSibling);

        // Announce error
        this.announce(message, 'assertive');
    }

    /**
     * Clear error state from a form field
     * @param {HTMLElement} field
     */
    clearFieldError(field) {
        field.classList.remove('field-error');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');

        const errorEl = document.getElementById(field.id + '-error');
        if (errorEl) errorEl.remove();
    }

    /**
     * Check if reduced motion is preferred
     * @returns {boolean}
     */
    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /**
     * Check if high contrast is preferred
     * @returns {boolean}
     */
    prefersHighContrast() {
        return window.matchMedia('(prefers-contrast: high)').matches;
    }
}

// Create global instance
window.a11y = new A11yManager();

// Also export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = A11yManager;
}






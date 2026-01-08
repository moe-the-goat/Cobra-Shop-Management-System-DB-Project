/**
 * Toast Notification System
 * Modern, accessible notifications for Cobra Shop
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.defaultDuration = 4000;
        this.init();
    }

    init() {
        // Create container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-label', 'Notifications');
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }

        // Inject styles if not already present
        if (!document.getElementById('toast-styles')) {
            this.injectStyles();
        }
    }

    injectStyles() {
        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 400px;
                pointer-events: none;
            }

            .toast {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 16px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                pointer-events: auto;
                transform: translateX(120%);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                border-left: 4px solid #6c757d;
                min-width: 300px;
            }

            .toast.show {
                transform: translateX(0);
                opacity: 1;
            }

            .toast.hide {
                transform: translateX(120%);
                opacity: 0;
            }

            .toast.success { border-left-color: #2ec4b6; }
            .toast.error { border-left-color: #e63946; }
            .toast.warning { border-left-color: #ff9f1c; }
            .toast.info { border-left-color: #4361ee; }

            .toast-icon {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 14px;
            }

            .toast.success .toast-icon { background: rgba(46, 196, 182, 0.15); color: #2ec4b6; }
            .toast.error .toast-icon { background: rgba(230, 57, 70, 0.15); color: #e63946; }
            .toast.warning .toast-icon { background: rgba(255, 159, 28, 0.15); color: #ff9f1c; }
            .toast.info .toast-icon { background: rgba(67, 97, 238, 0.15); color: #4361ee; }

            .toast-content {
                flex: 1;
                min-width: 0;
            }

            .toast-title {
                font-weight: 600;
                color: #1a1a2e;
                margin-bottom: 4px;
                font-size: 14px;
            }

            .toast-message {
                color: #6c757d;
                font-size: 13px;
                line-height: 1.4;
                word-wrap: break-word;
            }

            .toast-close {
                background: none;
                border: none;
                color: #adb5bd;
                cursor: pointer;
                padding: 4px;
                font-size: 18px;
                line-height: 1;
                transition: color 0.2s;
                flex-shrink: 0;
            }

            .toast-close:hover {
                color: #495057;
            }

            .toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: currentColor;
                opacity: 0.3;
                border-radius: 0 0 0 12px;
                transition: width linear;
            }

            .toast.success .toast-progress { background: #2ec4b6; }
            .toast.error .toast-progress { background: #e63946; }
            .toast.warning .toast-progress { background: #ff9f1c; }
            .toast.info .toast-progress { background: #4361ee; }

            /* Mobile responsive */
            @media (max-width: 480px) {
                .toast-container {
                    left: 10px;
                    right: 10px;
                    max-width: none;
                }

                .toast {
                    min-width: auto;
                }
            }

            /* Reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .toast {
                    transition: opacity 0.2s;
                    transform: none !important;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * Show a toast notification
     * @param {Object} options
     * @param {string} options.type - 'success' | 'error' | 'warning' | 'info'
     * @param {string} options.title - Toast title
     * @param {string} options.message - Toast message
     * @param {number} options.duration - Duration in ms (0 for persistent)
     * @param {boolean} options.showProgress - Show progress bar
     * @returns {HTMLElement} The toast element
     */
    show(options = {}) {
        const {
            type = 'info',
            title = '',
            message = '',
            duration = this.defaultDuration,
            showProgress = true
        } = options;

        const icons = {
            success: '<i class="fas fa-check"></i>',
            error: '<i class="fas fa-times"></i>',
            warning: '<i class="fas fa-exclamation"></i>',
            info: '<i class="fas fa-info"></i>'
        };

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.position = 'relative';
        toast.style.overflow = 'hidden';

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${this.escapeHtml(title)}</div>` : ''}
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
            <button class="toast-close" aria-label="Close notification">&times;</button>
            ${showProgress && duration > 0 ? '<div class="toast-progress"></div>' : ''}
        `;

        // Add to container
        this.container.appendChild(toast);
        this.toasts.push(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Progress bar animation
        if (showProgress && duration > 0) {
            const progress = toast.querySelector('.toast-progress');
            if (progress) {
                progress.style.width = '100%';
                requestAnimationFrame(() => {
                    progress.style.transitionDuration = `${duration}ms`;
                    progress.style.width = '0%';
                });
            }
        }

        // Close button
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => this.dismiss(toast));

        // Auto dismiss
        if (duration > 0) {
            toast.timeoutId = setTimeout(() => this.dismiss(toast), duration);
        }

        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            if (toast.timeoutId) {
                clearTimeout(toast.timeoutId);
                const progress = toast.querySelector('.toast-progress');
                if (progress) {
                    progress.style.transitionDuration = '0ms';
                    const remaining = progress.getBoundingClientRect().width;
                    progress.style.width = `${remaining}px`;
                }
            }
        });

        toast.addEventListener('mouseleave', () => {
            if (duration > 0) {
                const progress = toast.querySelector('.toast-progress');
                const remainingDuration = progress 
                    ? (parseFloat(progress.style.width) / toast.offsetWidth) * duration 
                    : duration / 2;
                
                if (progress) {
                    progress.style.transitionDuration = `${remainingDuration}ms`;
                    progress.style.width = '0%';
                }
                
                toast.timeoutId = setTimeout(() => this.dismiss(toast), remainingDuration);
            }
        });

        return toast;
    }

    /**
     * Dismiss a toast
     * @param {HTMLElement} toast
     */
    dismiss(toast) {
        if (!toast || !this.container.contains(toast)) return;

        toast.classList.remove('show');
        toast.classList.add('hide');

        if (toast.timeoutId) {
            clearTimeout(toast.timeoutId);
        }

        setTimeout(() => {
            if (this.container.contains(toast)) {
                this.container.removeChild(toast);
                this.toasts = this.toasts.filter(t => t !== toast);
            }
        }, 300);
    }

    /**
     * Dismiss all toasts
     */
    dismissAll() {
        [...this.toasts].forEach(toast => this.dismiss(toast));
    }

    // Convenience methods
    success(message, title = 'Success') {
        return this.show({ type: 'success', title, message });
    }

    error(message, title = 'Error') {
        return this.show({ type: 'error', title, message, duration: 6000 });
    }

    warning(message, title = 'Warning') {
        return this.show({ type: 'warning', title, message });
    }

    info(message, title = 'Info') {
        return this.show({ type: 'info', title, message });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global instance
window.toast = new ToastManager();

// Also export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastManager;
}






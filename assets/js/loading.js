/**
 * Loading Animation System
 * Skeleton loaders and spinners for Cobra Shop
 */

class LoadingManager {
    constructor() {
        this.activeOverlays = new Map();
        this.init();
    }

    init() {
        if (!document.getElementById('loading-styles')) {
            this.injectStyles();
        }
    }

    injectStyles() {
        const styles = document.createElement('style');
        styles.id = 'loading-styles';
        styles.textContent = `
            /* Full Page Loader */
            .page-loader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.95);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 1;
                transition: opacity 0.3s ease;
            }

            .page-loader.fade-out {
                opacity: 0;
                pointer-events: none;
            }

            .page-loader .loader-content {
                text-align: center;
            }

            .page-loader .loader-text {
                margin-top: 20px;
                color: #6c757d;
                font-size: 14px;
            }

            /* Spinner Variants */
            .spinner {
                display: inline-block;
            }

            /* Circular Spinner */
            .spinner-circle {
                width: 48px;
                height: 48px;
                border: 4px solid #e9ecef;
                border-top-color: #4361ee;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }

            .spinner-circle.sm { width: 24px; height: 24px; border-width: 2px; }
            .spinner-circle.lg { width: 64px; height: 64px; border-width: 5px; }

            /* Dots Spinner */
            .spinner-dots {
                display: flex;
                gap: 8px;
            }

            .spinner-dots span {
                width: 12px;
                height: 12px;
                background: #4361ee;
                border-radius: 50%;
                animation: bounce 1.4s infinite ease-in-out both;
            }

            .spinner-dots span:nth-child(1) { animation-delay: -0.32s; }
            .spinner-dots span:nth-child(2) { animation-delay: -0.16s; }
            .spinner-dots span:nth-child(3) { animation-delay: 0s; }

            /* Pulse Spinner */
            .spinner-pulse {
                width: 48px;
                height: 48px;
                background: #4361ee;
                border-radius: 50%;
                animation: pulse 1.5s infinite;
            }

            /* Bar Spinner */
            .spinner-bars {
                display: flex;
                gap: 4px;
                height: 32px;
                align-items: flex-end;
            }

            .spinner-bars span {
                width: 6px;
                background: #4361ee;
                border-radius: 3px;
                animation: bars 1.2s infinite ease-in-out;
            }

            .spinner-bars span:nth-child(1) { animation-delay: 0s; }
            .spinner-bars span:nth-child(2) { animation-delay: 0.1s; }
            .spinner-bars span:nth-child(3) { animation-delay: 0.2s; }
            .spinner-bars span:nth-child(4) { animation-delay: 0.3s; }

            /* Skeleton Loaders */
            .skeleton {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: shimmer 1.5s infinite;
                border-radius: 4px;
            }

            .skeleton-text {
                height: 16px;
                margin-bottom: 8px;
            }

            .skeleton-text.sm { height: 12px; }
            .skeleton-text.lg { height: 24px; }

            .skeleton-title {
                height: 28px;
                width: 60%;
                margin-bottom: 16px;
            }

            .skeleton-avatar {
                width: 48px;
                height: 48px;
                border-radius: 50%;
            }

            .skeleton-image {
                width: 100%;
                padding-top: 75%;
                border-radius: 8px;
            }

            .skeleton-button {
                height: 40px;
                width: 120px;
                border-radius: 8px;
            }

            .skeleton-card {
                background: white;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            /* Product Card Skeleton */
            .skeleton-product {
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            .skeleton-product .skeleton-image {
                border-radius: 0;
                padding-top: 100%;
            }

            .skeleton-product .skeleton-content {
                padding: 16px;
            }

            /* Table Row Skeleton */
            .skeleton-table-row {
                display: flex;
                gap: 16px;
                padding: 12px 0;
                border-bottom: 1px solid #f0f0f0;
            }

            .skeleton-table-row .skeleton-cell {
                height: 20px;
                flex: 1;
            }

            /* Overlay Loader */
            .overlay-loader {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 100;
                border-radius: inherit;
            }

            /* Button Loading State */
            .btn-loading {
                position: relative;
                color: transparent !important;
                pointer-events: none;
            }

            .btn-loading::after {
                content: '';
                position: absolute;
                width: 20px;
                height: 20px;
                top: 50%;
                left: 50%;
                margin: -10px 0 0 -10px;
                border: 2px solid rgba(255,255,255,0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: spin 0.6s linear infinite;
            }

            .btn-loading.btn-outline-primary::after,
            .btn-loading.btn-light::after {
                border-color: rgba(67, 97, 238, 0.3);
                border-top-color: #4361ee;
            }

            /* Animations */
            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            @keyframes bounce {
                0%, 80%, 100% { transform: scale(0); }
                40% { transform: scale(1); }
            }

            @keyframes pulse {
                0%, 100% { transform: scale(0.8); opacity: 0.5; }
                50% { transform: scale(1); opacity: 1; }
            }

            @keyframes bars {
                0%, 100% { height: 40%; }
                50% { height: 100%; }
            }

            @keyframes shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }

            /* Reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .spinner-circle,
                .spinner-dots span,
                .spinner-pulse,
                .spinner-bars span,
                .skeleton,
                .btn-loading::after {
                    animation: none;
                }

                .skeleton {
                    background: #e0e0e0;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * Show full page loader
     * @param {string} text - Loading text
     * @param {string} type - Spinner type: 'circle' | 'dots' | 'pulse' | 'bars'
     */
    showPageLoader(text = 'Loading...', type = 'circle') {
        const existing = document.querySelector('.page-loader');
        if (existing) existing.remove();

        const loader = document.createElement('div');
        loader.className = 'page-loader';
        loader.innerHTML = `
            <div class="loader-content">
                ${this.getSpinner(type)}
                <div class="loader-text">${this.escapeHtml(text)}</div>
            </div>
        `;
        document.body.appendChild(loader);
        return loader;
    }

    /**
     * Hide full page loader
     */
    hidePageLoader() {
        const loader = document.querySelector('.page-loader');
        if (loader) {
            loader.classList.add('fade-out');
            setTimeout(() => loader.remove(), 300);
        }
    }

    /**
     * Show overlay loader on an element
     * @param {HTMLElement|string} element - Element or selector
     * @param {string} type - Spinner type
     */
    showOverlay(element, type = 'circle') {
        const el = typeof element === 'string' ? document.querySelector(element) : element;
        if (!el) return;

        // Ensure element has position
        const position = getComputedStyle(el).position;
        if (position === 'static') {
            el.style.position = 'relative';
        }

        const overlay = document.createElement('div');
        overlay.className = 'overlay-loader';
        overlay.innerHTML = this.getSpinner(type, 'sm');
        el.appendChild(overlay);
        this.activeOverlays.set(el, overlay);
        return overlay;
    }

    /**
     * Hide overlay loader from an element
     * @param {HTMLElement|string} element
     */
    hideOverlay(element) {
        const el = typeof element === 'string' ? document.querySelector(element) : element;
        if (!el) return;

        const overlay = this.activeOverlays.get(el);
        if (overlay) {
            overlay.remove();
            this.activeOverlays.delete(el);
        }
    }

    /**
     * Set button loading state
     * @param {HTMLElement|string} button
     * @param {boolean} loading
     */
    setButtonLoading(button, loading = true) {
        const btn = typeof button === 'string' ? document.querySelector(button) : button;
        if (!btn) return;

        if (loading) {
            btn.classList.add('btn-loading');
            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
        } else {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        }
    }

    /**
     * Get spinner HTML
     * @param {string} type
     * @param {string} size - 'sm' | '' | 'lg'
     */
    getSpinner(type = 'circle', size = '') {
        const sizeClass = size ? ` ${size}` : '';
        
        switch (type) {
            case 'dots':
                return `<div class="spinner spinner-dots${sizeClass}"><span></span><span></span><span></span></div>`;
            case 'pulse':
                return `<div class="spinner spinner-pulse${sizeClass}"></div>`;
            case 'bars':
                return `<div class="spinner spinner-bars${sizeClass}"><span></span><span></span><span></span><span></span></div>`;
            default:
                return `<div class="spinner spinner-circle${sizeClass}"></div>`;
        }
    }

    /**
     * Create skeleton loader HTML
     * @param {string} type - 'text' | 'title' | 'avatar' | 'image' | 'button' | 'product' | 'table-row'
     * @param {Object} options
     */
    skeleton(type, options = {}) {
        const { count = 1, width = '100%' } = options;
        
        const skeletons = {
            text: `<div class="skeleton skeleton-text" style="width: ${width}"></div>`,
            title: `<div class="skeleton skeleton-title" style="width: ${width}"></div>`,
            avatar: `<div class="skeleton skeleton-avatar"></div>`,
            image: `<div class="skeleton skeleton-image"></div>`,
            button: `<div class="skeleton skeleton-button"></div>`,
            product: `
                <div class="skeleton-product">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton skeleton-text" style="width: 70%"></div>
                        <div class="skeleton skeleton-text sm" style="width: 50%"></div>
                        <div class="skeleton skeleton-text lg" style="width: 40%"></div>
                    </div>
                </div>
            `,
            'table-row': `
                <div class="skeleton-table-row">
                    <div class="skeleton skeleton-cell" style="flex: 0.5"></div>
                    <div class="skeleton skeleton-cell" style="flex: 2"></div>
                    <div class="skeleton skeleton-cell" style="flex: 1"></div>
                    <div class="skeleton skeleton-cell" style="flex: 1"></div>
                </div>
            `
        };

        const skeleton = skeletons[type] || skeletons.text;
        return Array(count).fill(skeleton).join('');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global instance
window.loading = new LoadingManager();

// Also export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoadingManager;
}






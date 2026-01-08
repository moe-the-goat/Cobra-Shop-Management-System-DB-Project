/**
 * Order Tracking JavaScript
 * Handles order lookup and timeline display
 */

class OrderTracker {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkUrlParams();
    }

    bindEvents() {
        document.getElementById('trackingForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const orderId = document.getElementById('orderIdInput').value.trim();
            if (orderId) {
                this.trackOrder(orderId);
            }
        });
    }

    checkUrlParams() {
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('order_id');
        if (orderId) {
            document.getElementById('orderIdInput').value = orderId;
            this.trackOrder(orderId);
        }
    }

    showState(state) {
        const states = ['loadingState', 'errorState', 'orderDetails'];
        states.forEach(s => {
            document.getElementById(s).classList.toggle('d-none', s !== state);
        });
    }

    async trackOrder(orderId) {
        this.showState('loadingState');
        
        // Show overlay loader if available
        const trackBtn = document.querySelector('#trackingForm button[type="submit"]');
        if (window.loading && trackBtn) {
            window.loading.setButtonLoading(trackBtn, true);
        }

        try {
            const response = await fetch(`/Database_Project/src/api/order_tracking.php?order_id=${encodeURIComponent(orderId)}`);
            const result = await response.json();

            if (result.success) {
                this.displayOrder(result.data);
                this.showState('orderDetails');
                
                // Show success toast
                if (window.toast) {
                    window.toast.success('Order found!');
                }
                
                // Update URL without reload
                const newUrl = `${window.location.pathname}?order_id=${orderId}`;
                window.history.pushState({ orderId }, '', newUrl);
            } else {
                document.getElementById('errorMessage').textContent = result.message || 'Order not found';
                this.showState('errorState');
                
                // Show error toast
                if (window.toast) {
                    window.toast.error(result.message || 'Order not found');
                }
            }
        } catch (error) {
            console.error('Error tracking order:', error);
            document.getElementById('errorMessage').textContent = 'Failed to connect to server';
            this.showState('errorState');
            
            if (window.toast) {
                window.toast.error('Failed to connect to server');
            }
        } finally {
            if (window.loading && trackBtn) {
                window.loading.setButtonLoading(trackBtn, false);
            }
        }
    }

    displayOrder(data) {
        const { order, customer, items, payment, timeline } = data;

        // Order Summary
        document.getElementById('orderId').textContent = order.id;
        document.getElementById('orderDate').textContent = this.formatDate(order.date);
        document.getElementById('orderTotal').textContent = this.formatCurrency(order.total);

        // Status Badge
        const statusBadge = document.getElementById('orderStatusBadge');
        statusBadge.textContent = order.status;
        statusBadge.className = `status-badge ${order.status.toLowerCase()}`;

        // Customer Info
        document.getElementById('customerName').textContent = customer.name;
        document.getElementById('customerAddress').textContent = customer.address || 'No address provided';
        document.getElementById('customerEmail').textContent = customer.email;
        document.getElementById('customerPhone').textContent = customer.phone || 'N/A';

        // Timeline
        this.renderTimeline(timeline);

        // Order Items
        this.renderItems(items);

        // Payment Info
        this.renderPayment(payment, order.total);
    }

    renderTimeline(timeline) {
        const container = document.getElementById('orderTimeline');
        
        // Calculate progress percentage
        const completedCount = timeline.filter(t => t.completed).length;
        const progressPercent = ((completedCount - 1) / (timeline.length - 1)) * 100;

        container.innerHTML = `
            <div class="timeline-progress" style="width: ${Math.max(0, progressPercent)}%"></div>
            ${timeline.map(item => `
                <div class="timeline-item ${item.completed ? 'completed' : ''} ${item.active ? 'active' : ''} ${item.type || ''}">
                    <div class="timeline-icon">
                        <i class="fas ${item.icon}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-status">${item.status}</div>
                        <div class="timeline-description">${item.description}</div>
                        ${item.date ? `<div class="timeline-date">${this.formatDateTime(item.date)}</div>` : ''}
                    </div>
                </div>
            `).join('')}
        `;
    }

    renderItems(items) {
        const container = document.getElementById('orderItems');

        if (!items || items.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No items found</p>';
            return;
        }

        container.innerHTML = items.map(item => `
            <div class="item-row">
                <img src="${item.product_image || 'images/products/thumbnails/default.jpg'}" 
                     alt="${this.escapeHtml(item.product_name)}" 
                     class="item-image"
                     onerror="this.src='https://via.placeholder.com/70x70?text=No+Image'">
                <div class="item-details">
                    <div class="item-name">${this.escapeHtml(item.product_name)}</div>
                    <div class="item-quantity">Qty: ${item.Quantity} × ${this.formatCurrency(item.Unit_Price)}</div>
                </div>
                <div class="item-price">${this.formatCurrency(item.subtotal)}</div>
            </div>
        `).join('');
    }

    renderPayment(payment, total) {
        const container = document.getElementById('paymentInfo');

        if (!payment) {
            container.innerHTML = `
                <div class="payment-row">
                    <span class="payment-label">Status</span>
                    <span class="payment-value">Pending Payment</span>
                </div>
                <div class="payment-row">
                    <span class="payment-label">Total Due</span>
                    <span class="payment-value">${this.formatCurrency(total)}</span>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="payment-row">
                <span class="payment-label">Method</span>
                <span class="payment-value">${this.escapeHtml(payment.Payment_Method)}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Status</span>
                <span class="payment-value ${payment.Payment_Status === 'Completed' ? 'success' : ''}">
                    ${payment.Payment_Status}
                </span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Amount</span>
                <span class="payment-value success">${this.formatCurrency(payment.Amount)}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Date</span>
                <span class="payment-value">${this.formatDate(payment.Payment_Date)}</span>
            </div>
        `;
    }

    // Utility functions
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    formatDateTime(dateString) {
        if (!dateString) return '';
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.orderTracker = new OrderTracker();
});






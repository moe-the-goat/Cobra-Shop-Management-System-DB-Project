// Staff Management JavaScript
class StaffManagement {
    constructor() {
        this.selectedStaffId = null;
        this.currentOrders = [];
        this.currentOrderId = null;
        this.init();
    }

    init() {
        this.loadStaff();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Status filter change
        document.getElementById('statusFilter').addEventListener('change', () => {
            this.filterOrders();
        });

        // Modal close events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeStatusModal();
            }
        });

        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeStatusModal();
            }
        });
    }

    async loadStaff() {
        try {
            this.showLoading(true);
            const response = await fetch('get_staff.php');
            const data = await response.json();

            if (data.success) {
                this.renderStaffGrid(data.staff);
            } else {
                this.showToast('Error loading staff: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading staff:', error);
            this.showToast('Error loading staff data', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    renderStaffGrid(staff) {
        const staffGrid = document.getElementById('staffGrid');
        
        if (staff.length === 0) {
            staffGrid.innerHTML = `
                <div class="no-staff-message">
                    <i class="fas fa-users"></i>
                    <h3>No Sales Management Staff Found</h3>
                    <p>No staff members with 'Sales Management' position were found.</p>
                </div>
            `;
            return;
        }

        staffGrid.innerHTML = staff.map(member => `
            <div class="staff-card" onclick="staffManagement.selectStaff(${member.Staff_ID})">
                <div class="staff-info">
                    <div class="staff-avatar">
                        ${this.getInitials(member.Name)}
                    </div>
                    <div class="staff-details">
                        <h3>${this.escapeHtml(member.Name)}</h3>
                        <p>${this.escapeHtml(member.Position)}</p>
                    </div>
                </div>
                <div class="staff-stats">
                    <div class="stat-item">
                        <div class="stat-value">${member.assignment_count || 0}</div>
                        <div class="stat-label">Assigned Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${member.Staff_ID}</div>
                        <div class="stat-label">Staff ID</div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async selectStaff(staffId) {
        try {
            this.selectedStaffId = staffId;
            
            // Update UI to show selected staff
            document.querySelectorAll('.staff-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Show loading and fetch orders
            this.showLoading(true);
            await this.loadStaffOrders(staffId);
            
            // Show orders section
            document.getElementById('salesOrdersSection').style.display = 'block';
            document.getElementById('salesOrdersSection').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });

        } catch (error) {
            console.error('Error selecting staff:', error);
            this.showToast('Error loading staff orders', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadStaffOrders(staffId) {
        try {
            const response = await fetch(`get_staff_orders.php?staff_id=${staffId}`);
            const data = await response.json();

            if (data.success) {
                this.currentOrders = data.orders;
                this.renderStaffInfo(data.staff);
                this.renderOrdersTable(data.orders);
                this.updateOrdersStats(data.orders);
            } else {
                this.showToast('Error loading orders: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading staff orders:', error);
            this.showToast('Error loading staff orders', 'error');
        }
    }

    renderStaffInfo(staff) {
        const staffInfo = document.getElementById('selectedStaffInfo');
        staffInfo.innerHTML = `
            <div class="selected-staff-card">
                <div class="staff-avatar">
                    ${this.getInitials(staff.Name)}
                </div>
                <div class="staff-details">
                    <h3>${this.escapeHtml(staff.Name)}</h3>
                    <p>Staff ID: ${staff.Staff_ID} | Position: ${this.escapeHtml(staff.Position)}</p>
                </div>
            </div>
        `;
    }

    renderOrdersTable(orders) {
        const tableBody = document.getElementById('ordersTableBody');
        
        if (orders.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="no-orders-message">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-shopping-cart" style="font-size: 2rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                            <p>No orders assigned to this staff member yet.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = orders.map(order => `
            <tr>
                <td><strong>#${order.SO_ID}</strong></td>
                <td>${this.escapeHtml(order.Customer_Name)}</td>
                <td>${this.formatDate(order.Order_Date)}</td>
                <td>$${parseFloat(order.Total_Amount).toFixed(2)}</td>
                <td>
                    <span class="status-badge status-${order.Status.toLowerCase()}">
                        ${order.Status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="staffManagement.openStatusModal(${order.SO_ID}, '${order.Status}')">
                        <i class="fas fa-edit"></i>
                        Update Status
                    </button>
                </td>
            </tr>
        `).join('');
    }

    updateOrdersStats(orders) {
        const stats = {
            total: orders.length,
            pending: orders.filter(o => o.Status === 'Pending').length,
            processing: orders.filter(o => o.Status === 'Processing').length,
            completed: orders.filter(o => ['Delivered', 'Shipped'].includes(o.Status)).length
        };

        const statsContainer = document.getElementById('ordersStats');
        statsContainer.innerHTML = `
            <div class="stat-item">
                <div class="stat-value">${stats.total}</div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${stats.pending}</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${stats.processing}</div>
                <div class="stat-label">Processing</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${stats.completed}</div>
                <div class="stat-label">Completed</div>
            </div>
        `;
    }

    filterOrders() {
        const statusFilter = document.getElementById('statusFilter').value;
        let filteredOrders = this.currentOrders;

        if (statusFilter) {
            filteredOrders = this.currentOrders.filter(order => order.Status === statusFilter);
        }

        this.renderOrdersTable(filteredOrders);
    }

    openStatusModal(orderId, currentStatus) {
        this.currentOrderId = orderId;
        document.getElementById('newStatus').value = currentStatus;
        document.getElementById('statusNote').value = '';
        
        const modal = document.getElementById('statusModal');
        modal.classList.add('show');
    }

    closeStatusModal() {
        const modal = document.getElementById('statusModal');
        modal.classList.remove('show');
        this.currentOrderId = null;
    }

    async updateOrderStatus() {
        if (!this.currentOrderId) return;

        const newStatus = document.getElementById('newStatus').value;
        const note = document.getElementById('statusNote').value;

        try {
            this.showLoading(true);
            
            const response = await fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: this.currentOrderId,
                    status: newStatus,
                    note: note
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Order status updated successfully', 'success');
                this.closeStatusModal();
                // Reload orders for the selected staff
                await this.loadStaffOrders(this.selectedStaffId);
            } else {
                this.showToast('Error updating status: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            this.showToast('Error updating order status', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (show) {
            overlay.classList.add('show');
        } else {
            overlay.classList.remove('show');
        }
    }

    showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? 'check-circle' : 
                    type === 'error' ? 'exclamation-circle' : 
                    'exclamation-triangle';
        
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${icon} toast-icon"></i>
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    getInitials(name) {
        return name.split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .slice(0, 2)
            .join('');
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the staff management system when the page loads
let staffManagement;
document.addEventListener('DOMContentLoaded', () => {
    staffManagement = new StaffManagement();
});

// Global functions for onclick handlers
window.staffManagement = {
    selectStaff: (staffId) => staffManagement.selectStaff(staffId),
    openStatusModal: (orderId, currentStatus) => staffManagement.openStatusModal(orderId, currentStatus),
    closeStatusModal: () => staffManagement.closeStatusModal(),
    updateOrderStatus: () => staffManagement.updateOrderStatus()
};


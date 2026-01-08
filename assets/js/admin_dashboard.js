/**
 * Admin Dashboard JavaScript
 * Handles data fetching and chart rendering
 */

class AdminDashboard {
    constructor() {
        this.charts = {};
        this.data = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardData();
    }

    bindEvents() {
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Date range filter
        document.getElementById('dateRange').addEventListener('change', () => {
            this.loadDashboardData();
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', () => {
            this.loadDashboardData();
        });

        // Mobile sidebar
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    }

    showLoading(show = true) {
        // Use global loading manager if available
        if (window.loading) {
            if (show) {
                window.loading.showPageLoader('Loading dashboard...', 'circle');
            } else {
                window.loading.hidePageLoader();
            }
        } else {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }
    }

    async loadDashboardData() {
        const days = document.getElementById('dateRange').value;
        this.showLoading(true);

        try {
            const response = await fetch(`/Database_Project/src/api/dashboard_stats.php?days=${days}`);
            const result = await response.json();

            if (result.success) {
                this.data = result.data;
                this.updateDashboard();
            } else {
                console.error('Failed to load dashboard data:', result.message);
                this.showError('Failed to load dashboard data');
            }
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            this.showError('Error connecting to server');
        } finally {
            this.showLoading(false);
        }
    }

    showError(message) {
        // Use global toast system if available
        if (window.toast) {
            window.toast.error(message);
        } else {
            alert(message);
        }
    }

    showSuccess(message) {
        if (window.toast) {
            window.toast.success(message);
        }
    }

    updateDashboard() {
        this.updateStatCards();
        this.updateSalesChart();
        this.updateStatusChart();
        this.updateCategoryChart();
        this.updateTopProducts();
        this.updateRecentOrders();
        this.updateStaffPerformance();
        this.updateLowStockAlert();
    }

    updateStatCards() {
        const { sales_overview, customer_stats } = this.data;
        
        document.getElementById('totalRevenue').textContent = 
            this.formatCurrency(sales_overview.total_revenue);
        document.getElementById('totalOrders').textContent = 
            sales_overview.total_orders.toLocaleString();
        document.getElementById('totalCustomers').textContent = 
            customer_stats.total_customers.toLocaleString();
        document.getElementById('avgOrderValue').textContent = 
            this.formatCurrency(sales_overview.avg_order_value);
    }

    updateSalesChart() {
        const { daily_sales } = this.data;
        const ctx = document.getElementById('salesChart').getContext('2d');

        // Destroy existing chart if it exists
        if (this.charts.sales) {
            this.charts.sales.destroy();
        }

        const labels = daily_sales.map(d => this.formatDate(d.date));
        const revenueData = daily_sales.map(d => parseFloat(d.revenue));
        const ordersData = daily_sales.map(d => parseInt(d.orders));

        this.charts.sales = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: revenueData,
                        borderColor: '#2ec4b6',
                        backgroundColor: 'rgba(46, 196, 182, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: ordersData,
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    updateStatusChart() {
        const { orders_by_status } = this.data;
        const ctx = document.getElementById('statusChart').getContext('2d');

        if (this.charts.status) {
            this.charts.status.destroy();
        }

        const statusColors = {
            'Pending': '#ffc107',
            'Processing': '#17a2b8',
            'Shipped': '#28a745',
            'Delivered': '#20c997',
            'Cancelled': '#dc3545',
            'Returned': '#6c757d'
        };

        const labels = orders_by_status.map(s => s.Status);
        const data = orders_by_status.map(s => parseInt(s.count));
        const colors = labels.map(l => statusColors[l] || '#6c757d');

        this.charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    updateCategoryChart() {
        const { category_performance } = this.data;
        const ctx = document.getElementById('categoryChart').getContext('2d');

        if (this.charts.category) {
            this.charts.category.destroy();
        }

        const labels = category_performance.map(c => c.category_name);
        const revenueData = category_performance.map(c => parseFloat(c.revenue));

        this.charts.category = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: revenueData,
                    backgroundColor: [
                        'rgba(67, 97, 238, 0.8)',
                        'rgba(46, 196, 182, 0.8)',
                        'rgba(255, 159, 28, 0.8)',
                        'rgba(230, 57, 70, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    updateTopProducts() {
        const { top_products } = this.data;
        const tbody = document.getElementById('topProductsTable');

        if (top_products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">No data available</td></tr>';
            return;
        }

        tbody.innerHTML = top_products.map(product => `
            <tr>
                <td>
                    <strong>${this.escapeHtml(product.Name)}</strong>
                </td>
                <td>${parseInt(product.total_sold).toLocaleString()}</td>
                <td>${this.formatCurrency(product.total_revenue)}</td>
            </tr>
        `).join('');
    }

    updateRecentOrders() {
        const { recent_orders } = this.data;
        const tbody = document.getElementById('recentOrdersTable');

        if (recent_orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No orders found</td></tr>';
            return;
        }

        tbody.innerHTML = recent_orders.map(order => `
            <tr>
                <td><strong>#${order.SO_ID}</strong></td>
                <td>${this.escapeHtml(order.customer_name)}</td>
                <td>${this.formatCurrency(order.Total_Amount)}</td>
                <td>
                    <span class="status-badge ${order.Status.toLowerCase()}">
                        ${order.Status}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    updateStaffPerformance() {
        const { staff_performance } = this.data;
        const container = document.getElementById('staffPerformance');

        if (staff_performance.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No staff data available</p>';
            return;
        }

        container.innerHTML = staff_performance.map(staff => `
            <div class="staff-item">
                <div class="staff-avatar">${staff.Name.charAt(0)}</div>
                <div class="staff-details">
                    <div class="staff-name">${this.escapeHtml(staff.Name)}</div>
                    <div class="staff-stats">${staff.orders_handled} orders handled</div>
                </div>
                <div class="staff-revenue">${this.formatCurrency(staff.revenue_generated)}</div>
            </div>
        `).join('');
    }

    updateLowStockAlert() {
        const { low_stock_products } = this.data;
        const container = document.getElementById('lowStockList');

        if (low_stock_products.length === 0) {
            container.innerHTML = `
                <div class="text-center text-success p-3">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <p>All products are well-stocked!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = low_stock_products.map(product => `
            <div class="low-stock-item ${parseInt(product.Stock) <= 3 ? 'critical' : ''}">
                <div class="product-name">${this.escapeHtml(product.Name)}</div>
                <div class="stock-count">
                    <span>${product.Stock} left</span>
                </div>
            </div>
        `).join('');
    }

    // Utility functions
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new AdminDashboard();
});






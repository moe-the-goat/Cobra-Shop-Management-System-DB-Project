// Global variable to store order data
let customerOrders = [];

document.addEventListener('DOMContentLoaded', function() {
    const userData = JSON.parse(localStorage.getItem('userData'));

    if (!userData || !userData.Customer_ID) {
        window.location.href = 'login_page.html';
        return;
    }

    const customerId = userData.Customer_ID;

    // Setup Event Listeners
    document.getElementById('logoutBtn').addEventListener('click', logoutUser);
    document.getElementById('statsCategoryFilter').addEventListener('change', (e) => fetchCategoryStats(customerId, e.target.value));

    // Initial Load
    loadUserProfile(userData);
    loadDashboardData(customerId);
});

function loadUserProfile(user) {
    // Populate header and personal info card
    document.getElementById('userWelcome').textContent = `Welcome, ${user.name}`;
    document.getElementById('profile-header-name').textContent = user.name || 'User Profile';
    document.getElementById('profile-header-email').textContent = user.email || 'No email provided';
    document.getElementById('profilePhone').textContent = user.Phone || 'Not provided';
    document.getElementById('profileBirthday').textContent = user.Birth_Date ? new Date(user.Birth_Date).toLocaleDateString() : 'Not provided';
}

async function loadDashboardData(customerId) {
    try {
        const [statsResponse, ordersResponse] = await Promise.all([
            fetch(`/Database_Project/src/api/get_customer_stats.php?customer_id=${customerId}`),
            fetch(`/Database_Project/src/api/get_customer_orders.php?customer_id=${customerId}`)
        ]);

        const statsResult = await statsResponse.json();
        const ordersResult = await ordersResponse.json();

        if (statsResult.success) {
            renderSummaryStats(statsResult.stats.summary);
            renderMonthlySpendingChart(statsResult.stats.by_month);
            renderCategoryPieChart(statsResult.stats.by_category);
            // FIX: Populate category filter from stats data
            populateCategoryFilter(statsResult.stats.by_category);
        }

        if (ordersResult.success) {
            // FIX: Store orders globally for the modal to use
            customerOrders = ordersResult.orders;
            renderOrderHistory(customerOrders);
        }

    } catch (error) {
        console.error("Failed to load dashboard data:", error);
    }
}

// FIX: This function now populates the dropdown from fetched stats
function populateCategoryFilter(categoryData) {
    const filterSelect = document.getElementById('statsCategoryFilter');
    filterSelect.innerHTML = '<option value="">-- Select Category --</option>'; // Reset
    if (categoryData && categoryData.length > 0) {
        categoryData.forEach(category => {
            const option = document.createElement('option');
            option.value = category.Category_ID;
            option.textContent = category.category_name;
            filterSelect.appendChild(option);
        });
    }
}


async function fetchCategoryStats(customerId, categoryId) {
    const resultDiv = document.getElementById('categoryStatsResult');
    if (!categoryId) {
        resultDiv.innerHTML = '<p>Select a category to see detailed statistics.</p>';
        return;
    }

    resultDiv.innerHTML = '<p>Loading stats...</p>';

    try {
        const response = await fetch(`/Database_Project/src/api/get_customer_stats.php?customer_id=${customerId}&category_id=${categoryId}`);
        const result = await response.json();

        if (result.success && result.stats.category_specific) {
            const stats = result.stats.category_specific;
            const totalSpent = parseFloat(stats.total_spent).toFixed(2);
            const mostBought = stats.most_bought_product ? 
                `"${stats.most_bought_product.Title}" (bought ${stats.most_bought_product.total_quantity} times)` : 
                'No products purchased yet in this category.';

            resultDiv.innerHTML = `
                <p><strong>Total Spent in this Category:</strong> $${totalSpent}</p>
                <p><strong>Most Purchased Product:</strong> ${mostBought}</p>
            `;
        } else {
            resultDiv.innerHTML = `<p class="text-danger">Could not load stats for this category.</p>`;
        }
    } catch (error) {
        console.error("Failed to load category stats:", error);
        resultDiv.innerHTML = `<p class="text-danger">An error occurred while fetching stats.</p>`;
    }
}

function renderSummaryStats(summary) {
    document.getElementById('totalSpent').textContent = `$${parseFloat(summary.total_spent).toFixed(2)}`;
    document.getElementById('totalOrders').textContent = summary.total_orders;
    document.getElementById('favoriteCategory').textContent = summary.favorite_category;
}

function renderOrderHistory(orders) {
    const tbody = document.getElementById('orderHistoryBody');
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No orders found.</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>#${order.SO_ID}</td>
            <td>${new Date(order.Order_Date).toLocaleDateString()}</td>
            <td>$${parseFloat(order.Total_Amount).toFixed(2)}</td>
            <td><span class="status-badge status-${order.Status.toLowerCase()}">${order.Status}</span></td>
            <td><button class="btn btn-sm btn-outline-primary" onclick="showOrderDetails(${order.SO_ID})">View</button></td>
        </tr>
    `).join('');
}

// Chart Configurations
let monthlyChart, categoryChart;

function renderMonthlySpendingChart(monthlyData) {
    const ctx = document.getElementById('monthlySpendingChart').getContext('2d');
    const labels = monthlyData.map(d => d.month);
    const data = monthlyData.map(d => d.monthly_total);

    if (monthlyChart) {
        monthlyChart.destroy();
    }
    monthlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Spending',
                data: data,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function renderCategoryPieChart(categoryData) {
    const ctx = document.getElementById('spendingByCategoryChart').getContext('2d');
    const labels = categoryData.map(d => d.category_name);
    const data = categoryData.map(d => d.category_total);
    const backgroundColors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#8b5cf6'];
    
    if (categoryChart) {
        categoryChart.destroy();
    }
    categoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                label: 'Spending',
                data: data,
                backgroundColor: backgroundColors
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function logoutUser() {
    localStorage.removeItem('userData');
    window.location.href = 'index.html';
}

function showOrderDetails(orderId) {
    const order = customerOrders.find(o => o.SO_ID == orderId);
    if (!order) {
        console.error("Order not found!");
        return;
    }

    const modalTitle = document.getElementById('orderDetailsModalLabel');
    const modalBody = document.getElementById('orderDetailsModalBody');
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));

    modalTitle.textContent = `Details for Order #${order.SO_ID}`;

    let itemsHtml = '';
    if (order.details && order.details.length > 0) {
        order.details.forEach(item => {
            const itemTotal = item.Quantity * item.Unit_Price;
            itemsHtml += `
                <tr>
                    <td>${item.Title}</td>
                    <td class="text-end">$${parseFloat(item.Unit_Price).toFixed(2)}</td>
                    <td class="text-center">${item.Quantity}</td>
                    <td class="text-end">$${itemTotal.toFixed(2)}</td>
                </tr>
            `;
        });
    } else {
        itemsHtml = '<tr><td colspan="4" class="text-center">No item details available.</td></tr>';
    }
    
    let discountHtml = '';
    if (order.discount_amount > 0 && order.discount_details) {
        discountHtml = `
            <tr>
                <td>Discount (${order.discount_details.Name})</td>
                <td class="text-end text-success">-$${parseFloat(order.discount_amount).toFixed(2)}</td>
            </tr>
        `;
    }

    let detailsHtml = `
        <p><strong>Order Date:</strong> ${new Date(order.Order_Date).toLocaleDateString()}</p>
        <p><strong>Status:</strong> <span class="status-badge status-${order.Status.toLowerCase()}">${order.Status}</span></p>
        
        <h6 class="mt-4">Items Purchased</h6>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Item Total</th>
                </tr>
            </thead>
            <tbody>${itemsHtml}</tbody>
        </table>
        
        <hr>
        
        <h6 class="mt-4">Payment Summary</h6>
        <table class="table table-sm table-borderless">
            <tbody>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-end">$${parseFloat(order.subtotal).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td class="text-end">$${parseFloat(order.shipping).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Tax (8%)</td>
                    <td class="text-end">$${parseFloat(order.tax).toFixed(2)}</td>
                </tr>
                ${discountHtml}
                <tr class="fw-bold border-top">
                    <td>Grand Total</td>
                    <td class="text-end">$${parseFloat(order.Total_Amount).toFixed(2)}</td>
                </tr>
            </tbody>
        </table>
    `;

    modalBody.innerHTML = detailsHtml;
    modal.show();
}






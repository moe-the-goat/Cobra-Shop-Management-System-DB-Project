// Discount Management JavaScript
let discounts = [];
let currentDiscountId = null;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadDiscounts();
    updateStatistics();
    setDefaultDates();
});

// Set default dates (today and 30 days from now)
function setDefaultDates() {
    const today = new Date();
    const futureDate = new Date();
    futureDate.setDate(today.getDate() + 30);
    
    document.getElementById('startDate').value = today.toISOString().split('T')[0];
    document.getElementById('endDate').value = futureDate.toISOString().split('T')[0];
}

// Load discounts from server
async function loadDiscounts() {
    try {
        showLoading();
        const response = await fetch('get_discounts.php');
        const data = await response.json();
        
        if (data.success) {
            discounts = data.discounts;
            renderDiscountsTable();
            updateStatistics();
        } else {
            showError('Failed to load discounts: ' + data.message);
        }
    } catch (error) {
        console.error('Error loading discounts:', error);
        showError('Failed to load discounts. Please try again.');
    } finally {
        hideLoading();
    }
}

// Render discounts table
function renderDiscountsTable() {
    const tbody = document.getElementById('discountsTableBody');
    const emptyState = document.getElementById('emptyState');
    
    if (discounts.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    tbody.innerHTML = discounts.map(discount => `
        <tr>
            <td>
                <span class="discount-code">${discount.code}</span>
            </td>
            <td>
                <span class="badge bg-secondary">${discount.type}</span>
            </td>
            <td>
                <span class="discount-value ${discount.type}">
                    ${discount.type === 'percentage' ? discount.value : discount.value}
                </span>
            </td>
            <td>$${parseFloat(discount.minimum_purchase).toFixed(2)}</td>
            <td>${formatDate(discount.end_date)}</td>
            <td>
                <div class="usage-info">
                    ${discount.usage_limit ? 
                        `<div class="usage-progress">
                            <div class="usage-progress-bar" style="width: ${(discount.usage_count / discount.usage_limit) * 100}%"></div>
                        </div>
                        <div class="usage-text">${discount.usage_count}/${discount.usage_limit}</div>` 
                        : `<span class="text-muted">${discount.usage_count} times</span>`
                    }
                </div>
            </td>
            <td>
                <span class="badge-status badge-${discount.status}">${discount.status}</span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-edit" onclick="editDiscount(${discount.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-toggle" onclick="toggleDiscountStatus(${discount.id})" title="Toggle Status">
                        <i class="fas fa-power-off"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteDiscount(${discount.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Update statistics
function updateStatistics() {
    const activeCount = discounts.filter(d => d.status === 'active').length;
    const expiredCount = discounts.filter(d => new Date(d.end_date) < new Date()).length;
    const totalUsage = discounts.reduce((sum, d) => sum + parseInt(d.usage_count || 0), 0);
    const totalSavings = discounts.reduce((sum, d) => {
        const usage = parseInt(d.usage_count || 0);
        const avgOrderValue = parseFloat(d.minimum_purchase || 0) * 1.5; // Estimate
        if (d.type === 'percentage') {
            return sum + (avgOrderValue * (parseFloat(d.value) / 100) * usage);
        } else {
            return sum + (parseFloat(d.value) * usage);
        }
    }, 0);
    
    document.getElementById('activeDiscountsCount').textContent = activeCount;
    document.getElementById('expiredDiscountsCount').textContent = expiredCount;
    document.getElementById('totalUsage').textContent = totalUsage;
    document.getElementById('totalSavings').textContent = '$' + totalSavings.toFixed(2);
}

// Open add discount modal
function openAddDiscountModal() {
    currentDiscountId = null;
    document.getElementById('discountModalLabel').textContent = 'Add New Discount';
    document.getElementById('discountForm').reset();
    setDefaultDates();
    updateDiscountValueLabel();
}

// Edit discount
function editDiscount(id) {
    const discount = discounts.find(d => d.id === id);
    if (!discount) return;
    
    currentDiscountId = id;
    document.getElementById('discountModalLabel').textContent = 'Edit Discount';
    
    // Populate form
    document.getElementById('discountId').value = discount.id;
    document.getElementById('discountCode').value = discount.code;
    document.getElementById('discountName').value = discount.name;
    document.getElementById('discountDescription').value = discount.description || '';
    document.getElementById('discountType').value = discount.type;
    document.getElementById('discountValue').value = discount.value;
    document.getElementById('minimumPurchase').value = discount.minimum_purchase;
    document.getElementById('startDate').value = discount.start_date;
    document.getElementById('endDate').value = discount.end_date;
    document.getElementById('usageLimit').value = discount.usage_limit || '';
    document.getElementById('discountStatus').value = discount.status;
    
    updateDiscountValueLabel();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('discountModal'));
    modal.show();
}

// Update discount value label based on type
function updateDiscountValueLabel() {
    const type = document.getElementById('discountType').value;
    const helpText = document.getElementById('discountValueHelp');
    
    if (type === 'percentage') {
        helpText.textContent = 'Enter percentage (e.g., 20 for 20%)';
    } else if (type === 'fixed') {
        helpText.textContent = 'Enter fixed amount in dollars';
    } else {
        helpText.textContent = 'Enter the discount amount';
    }
}

// Save discount
async function saveDiscount() {
    const form = document.getElementById('discountForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Additional validation
    const startDate = new Date(formData.get('startDate'));
    const endDate = new Date(formData.get('endDate'));
    
    if (endDate <= startDate) {
        showError('End date must be after start date');
        return;
    }
    
    try {
        showLoading();
        
        const url = currentDiscountId ? 'update_discount.php' : 'add_discount.php';
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(currentDiscountId ? 'Discount updated successfully!' : 'Discount created successfully!');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('discountModal'));
            modal.hide();
            
            // Reload discounts
            await loadDiscounts();
        } else {
            showError('Failed to save discount: ' + data.message);
        }
    } catch (error) {
        console.error('Error saving discount:', error);
        showError('Failed to save discount. Please try again.');
    } finally {
        hideLoading();
    }
}

// Toggle discount status
async function toggleDiscountStatus(id) {
    const discount = discounts.find(d => d.id === id);
    if (!discount) return;
    
    const newStatus = discount.status === 'active' ? 'inactive' : 'active';
    
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', newStatus);
        
        const response = await fetch('toggle_discount_status.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(`Discount ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully!`);
            await loadDiscounts();
        } else {
            showError('Failed to update discount status: ' + data.message);
        }
    } catch (error) {
        console.error('Error toggling discount status:', error);
        showError('Failed to update discount status. Please try again.');
    }
}

// Delete discount
function deleteDiscount(id) {
    currentDiscountId = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Confirm delete
async function confirmDelete() {
    if (!currentDiscountId) return;
    
    try {
        const formData = new FormData();
        formData.append('id', currentDiscountId);
        
        const response = await fetch('delete_discount.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Discount deleted successfully!');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            
            // Reload discounts
            await loadDiscounts();
        } else {
            showError('Failed to delete discount: ' + data.message);
        }
    } catch (error) {
        console.error('Error deleting discount:', error);
        showError('Failed to delete discount. Please try again.');
    }
    
    currentDiscountId = null;
}

// Filter discounts
function filterDiscounts() {
    const status = document.getElementById('statusFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    let filteredDiscounts = discounts;
    
    if (status !== 'all') {
        filteredDiscounts = filteredDiscounts.filter(d => d.status === status);
    }
    
    if (searchTerm) {
        filteredDiscounts = filteredDiscounts.filter(d => 
            d.code.toLowerCase().includes(searchTerm) ||
            d.name.toLowerCase().includes(searchTerm) ||
            (d.description && d.description.toLowerCase().includes(searchTerm))
        );
    }
    
    // Temporarily replace discounts array for rendering
    const originalDiscounts = discounts;
    discounts = filteredDiscounts;
    renderDiscountsTable();
    discounts = originalDiscounts;
}

// Search discounts
function searchDiscounts() {
    filterDiscounts();
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showLoading() {
    // Add loading spinner or disable buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);
}

function hideLoading() {
    // Remove loading spinner or enable buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = false);
}

function showSuccess(message) {
    showAlert(message, 'success');
}

function showError(message) {
    showAlert(message, 'error');
}

function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-custom');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert-custom alert-${type}-custom`;
    alert.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    // Insert at top of main content
    const mainContent = document.querySelector('.main-content .container');
    mainContent.insertBefore(alert, mainContent.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}


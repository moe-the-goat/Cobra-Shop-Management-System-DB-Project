// cart.js - JavaScript for cart page functionality

let currentUser = null;
let cart = [];

// Initialize cart page
document.addEventListener('DOMContentLoaded', function() {
    checkUserAuthentication();
    loadCartItems();
    setupEventListeners();
});

// Check user authentication
function checkUserAuthentication() {
    const userData = localStorage.getItem('userData');
    const userWelcome = document.getElementById('userWelcome');
    const loginContainer = document.querySelector('.login-container');

    if (userData) {
        currentUser = JSON.parse(userData);
        if (userWelcome) {
            userWelcome.textContent = `مرحباً ${currentUser.name}`;
        }
        if (loginContainer) {
            loginContainer.style.display = 'none';
        }
    } else {
        currentUser = null;
        if (userWelcome) {
            userWelcome.textContent = 'مرحباً';
        }
        if (loginContainer) {
            loginContainer.style.display = 'block';
        }
        // Redirect to login if not authenticated
        window.location.href = 'login_page.html';
    }
}

// Load cart items from server
async function loadCartItems() {
    if (!currentUser) {
        showEmptyCart();
        return;
    }

    try {
        const response = await fetch(`/Database_Project/src/api/get-cart.php?customer_id=${currentUser.Customer_ID}`);
        const data = await response.json();

        if (data.status === 'success') {
            cart = data.cart_items;
            if (cart.length === 0) {
                showEmptyCart();
            } else {
                displayCartItems();
                updateOrderSummary();
            }
        } else {
            showEmptyCart();
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        showError('Failed to load cart items');
        showEmptyCart();
    }
}

// Display cart items
function displayCartItems() {
    const cartItemsList = document.getElementById('cartItemsList');
    const emptyCartMessage = document.getElementById('emptyCartMessage');

    if (cart.length === 0) {
        showEmptyCart();
        return;
    }

    emptyCartMessage.style.display = 'none';
    
    cartItemsList.innerHTML = cart.map(item => `
        <div class="cart-item" data-product-id="${item.Product_ID}">
            <div class="row align-items-center">
                <div class="col-md-2 col-3">
                    <img src="${item.image_path || 'https://placehold.co/80x80/F8FAFC/CCCCCC?text=Product'}" 
                         alt="${item.Title}" class="cart-item-image img-fluid">
                </div>
                <div class="col-md-4 col-9">
                    <h5 class="cart-item-title">${item.Title}</h5>
                    <p class="cart-item-price">$${item.Price}</p>
                </div>
                <div class="col-md-3 col-6">
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="updateQuantity(${item.Product_ID}, -1)">-</button>
                        <span class="quantity-display">${item.Quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.Product_ID}, 1)">+</button>
                    </div>
                </div>
                <div class="col-md-2 col-3">
                    <p class="fw-bold text-primary mb-0">$${(parseFloat(item.Price) * item.Quantity).toFixed(2)}</p>
                </div>
                <div class="col-md-1 col-3">
                    <button class="remove-item btn btn-sm" onclick="removeItem(${item.Product_ID})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Show empty cart message
function showEmptyCart() {
    const cartItemsList = document.getElementById('cartItemsList');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    
    cartItemsList.innerHTML = '';
    emptyCartMessage.style.display = 'block';
    updateOrderSummary();
}

// Update quantity
async function updateQuantity(productId, change) {
    if (!currentUser) {
        showError('Please log in to update cart');
        return;
    }

    const item = cart.find(item => item.Product_ID == productId);
    if (!item) return;

    const newQuantity = item.Quantity + change;

    // --- START: THIS IS THE NEW/FIXED CODE BLOCK ---
    // Check against available stock before sending the request
    if (change > 0 && newQuantity > item.Stock) {
        showError(`Cannot add more. Only ${item.Stock} items available in stock.`);
        return; // Stop the function from proceeding
    }
    // --- END: NEW/FIXED CODE BLOCK ---

    if (newQuantity <= 0) {
        removeItem(productId);
        return;
    }

    try {
        const response = await fetch('/Database_Project/src/api/update-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_id: currentUser.Customer_ID,
                product_id: productId,
                quantity: newQuantity
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            item.Quantity = newQuantity;
            displayCartItems();
            updateOrderSummary();
            showSuccess('Cart updated successfully');
        } else {
            showError('Failed to update cart: ' + data.message);
            // Reload cart from server to show the actual stock limit
            loadCartItems();
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        showError('Failed to update cart. Please try again.');
    }
}

// Remove item from cart
async function removeItem(productId) {
    if (!currentUser) {
        showError('Please log in to remove items');
        return;
    }

    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    try {
        const response = await fetch('/Database_Project/src/api/remove-from-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_id: currentUser.Customer_ID,
                product_id: productId
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            cart = cart.filter(item => item.Product_ID != productId);
            if (cart.length === 0) {
                showEmptyCart();
            } else {
                displayCartItems();
                updateOrderSummary();
            }
            showSuccess('Item removed from cart');
        } else {
            showError('Failed to remove item: ' + data.message);
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showError('Failed to remove item. Please try again.');
    }
}

// Clear entire cart
async function clearCart() {
    if (!currentUser) {
        showError('Please log in to clear cart');
        return;
    }

    if (cart.length === 0) {
        showError('Cart is already empty');
        return;
    }

    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }

    try {
        const response = await fetch('/Database_Project/src/api/clear-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_id: currentUser.Customer_ID
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            cart = [];
            showEmptyCart();
            showSuccess('Cart cleared successfully');
        } else {
            showError('Failed to clear cart: ' + data.message);
        }
    } catch (error) {
        console.error('Error clearing cart:', error);
        showError('Failed to clear cart. Please try again.');
    }
}

// Update order summary
function updateOrderSummary() {
    const subtotalElement = document.getElementById('cartSubtotal');
    const totalElement = document.getElementById('cartTotal');
    const shippingElement = document.getElementById('shippingCost');
    const taxElement = document.getElementById('taxAmount');

    let subtotal = 0;
    cart.forEach(item => {
        subtotal += parseFloat(item.Price) * item.Quantity;
    });

    const shipping = subtotal > 100 ? 0 : 5.00;
    const tax = subtotal * 0.08; // 8% tax
    const total = subtotal + shipping + tax;

    if (subtotalElement) subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
    if (shippingElement) shippingElement.textContent = shipping === 0 ? 'Free' : `$${shipping.toFixed(2)}`;
    if (taxElement) taxElement.textContent = `$${tax.toFixed(2)}`;
    if (totalElement) totalElement.textContent = `$${total.toFixed(2)}`;
}

// Proceed to checkout
function proceedToCheckout() {
    if (!currentUser) {
        showError('Please log in to proceed to checkout');
        return;
    }

    if (cart.length === 0) {
        showError('Your cart is empty');
        return;
    }

    // Redirect to payment page
    window.location.href = 'payment.html';
}

// Setup event listeners
function setupEventListeners() {
    // Newsletter subscription
    const newsletterBtn = document.querySelector('.newsletter-form button');
    if (newsletterBtn) {
        newsletterBtn.addEventListener('click', subscribeNewsletter);
    }
}

// Newsletter subscription
function subscribeNewsletter() {
    const email = document.getElementById('newsletterEmail').value;
    const consent = document.getElementById('newsletterConsent').checked;

    if (!email) {
        showError('Please enter your email address');
        return;
    }

    if (!consent) {
        showError('Please agree to receive promotional emails');
        return;
    }

    // This would typically send to a newsletter API
    showSuccess('Thank you for subscribing to our newsletter!');
    document.getElementById('newsletterEmail').value = '';
    document.getElementById('newsletterConsent').checked = false;
}

// Show success message - uses global toast system
function showSuccess(message) {
    if (window.toast) {
        window.toast.success(message);
    } else {
        console.log('Success:', message);
    }
}

// Show error message - uses global toast system
function showError(message) {
    if (window.toast) {
        window.toast.error(message);
    } else {
        console.error('Error:', message);
    }
}






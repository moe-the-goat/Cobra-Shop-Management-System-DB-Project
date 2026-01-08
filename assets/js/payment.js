// payment.js - Payment page functionality with Visa validation and discount support

let currentUser = null;
let cartItems = [];
let appliedDiscount = null;
let orderTotals = {
    subtotal: 0,
    shipping: 5.00,
    tax: 0,
    discount: 0,
    total: 0
};

// Initialize payment page
document.addEventListener('DOMContentLoaded', function() {
    checkUserAuthentication();
    loadCartItems();
    setupPaymentForm();
    calculateOrderTotals();
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
        if (window.toast) {
            window.toast.warning('Please log in to proceed with payment');
        }
        setTimeout(() => window.location.href = 'login_page.html', 1500);
    }
}

// Load cart items from server
async function loadCartItems() {
    if (!currentUser) {
        return;
    }

    try {
        const response = await fetch(`/Database_Project/src/api/get-cart.php?customer_id=${currentUser.Customer_ID}`);
        const data = await response.json();

        if (data.status === 'success') {
            cartItems = data.cart_items;
            calculateOrderTotals();
        } else {
            cartItems = [];
            // For testing, allow empty cart
            calculateOrderTotals();
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        cartItems = [];
        calculateOrderTotals();
    }
}

// Calculate order totals
function calculateOrderTotals() {
    // Calculate subtotal
    orderTotals.subtotal = cartItems.reduce((sum, item) => {
        return sum + (parseFloat(item.Price || 0) * parseInt(item.Quantity || 1));
    }, 0);
    
    // For testing purposes, set minimum subtotal
    if (orderTotals.subtotal === 0) {
        orderTotals.subtotal = 99.99; // Test amount
    }
    
    // Calculate shipping (free over $100)
    orderTotals.shipping = orderTotals.subtotal >= 100 ? 0 : 5.00;
    
    // Calculate tax (8%)
    orderTotals.tax = orderTotals.subtotal * 0.08;
    
    // --- START: THIS IS THE NEW/FIXED CODE BLOCK ---
    // Apply discount if any
    orderTotals.discount = 0; // Reset discount first
    if (appliedDiscount) {
        // Use the 'savings' value directly from the validated discount response.
        // This is the authoritative discount amount calculated by the server.
        orderTotals.discount = appliedDiscount.savings || 0;
    }
    // --- END: NEW/FIXED CODE BLOCK ---
    
    // Calculate final total
    orderTotals.total = orderTotals.subtotal + orderTotals.shipping + orderTotals.tax - orderTotals.discount;
    
    // Update UI
    updateOrderSummaryUI();
}

// Update order summary UI
function updateOrderSummaryUI() {
    document.getElementById('orderSubtotal').textContent = '$' + orderTotals.subtotal.toFixed(2);
    document.getElementById('orderShipping').textContent = '$' + orderTotals.shipping.toFixed(2);
    document.getElementById('orderTax').textContent = '$' + orderTotals.tax.toFixed(2);
    document.getElementById('orderTotal').textContent = '$' + orderTotals.total.toFixed(2);
    
    // Show/hide discount amount
    const discountAmountDiv = document.getElementById('discountAmount');
    if (orderTotals.discount > 0) {
        discountAmountDiv.style.display = 'flex';
        document.getElementById('discountValue').textContent = '-$' + orderTotals.discount.toFixed(2);
    } else {
        discountAmountDiv.style.display = 'none';
    }
}

// Apply discount code
async function applyDiscount() {
    const discountCode = document.getElementById('discountCode').value.trim().toUpperCase();
    const applyBtn = document.getElementById('applyDiscountBtn');
    const messageDiv = document.getElementById('discountMessage');
    
    if (!discountCode) {
        showDiscountMessage('Please enter a discount code', 'error');
        return;
    }
    
    // Show loading state
    applyBtn.disabled = true;
    applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    showDiscountMessage('Validating discount code...', 'info');
    
    try {
        const formData = new FormData();
        formData.append('discount_code', discountCode);
        formData.append('order_total', orderTotals.subtotal + orderTotals.shipping + orderTotals.tax);
        
        const response = await fetch('/Database_Project/src/api/validate_discount.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            appliedDiscount = data.discount;
            showAppliedDiscount(data.discount);
            calculateOrderTotals();
            showDiscountMessage('Discount applied successfully!', 'success');
            
            // Clear input and disable apply button
            document.getElementById('discountCode').value = '';
            document.getElementById('discountCode').disabled = true;
            applyBtn.style.display = 'none';
        } else {
            showDiscountMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Error applying discount:', error);
        showDiscountMessage('Failed to apply discount. Please try again.', 'error');
    } finally {
        applyBtn.disabled = false;
        applyBtn.innerHTML = 'Apply';
    }
}

// Show applied discount
function showAppliedDiscount(discount) {
    const appliedDiscountDiv = document.getElementById('appliedDiscount');
    const discountNameSpan = document.getElementById('appliedDiscountName');
    const discountSavingsSpan = document.getElementById('discountSavings');
    
    discountNameSpan.textContent = discount.code;
    discountSavingsSpan.textContent = '$' + discount.savings.toFixed(2);
    appliedDiscountDiv.style.display = 'block';
}

// Remove applied discount
function removeDiscount() {
    appliedDiscount = null;
    document.getElementById('appliedDiscount').style.display = 'none';
    document.getElementById('discountCode').disabled = false;
    document.getElementById('discountCode').value = '';
    document.getElementById('applyDiscountBtn').style.display = 'inline-block';
    calculateOrderTotals();
    showDiscountMessage('Discount removed', 'info');
}

// Show discount message
function showDiscountMessage(message, type) {
    const messageDiv = document.getElementById('discountMessage');
    messageDiv.textContent = message;
    messageDiv.className = `small text-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'muted'}`;
    
    if (type !== 'info') {
        setTimeout(() => {
            messageDiv.textContent = '';
            messageDiv.className = 'small text-muted';
        }, 3000);
    }
}

// Setup payment form
function setupPaymentForm() {
    const form = document.getElementById('paymentForm');
    const cardNumberInput = document.getElementById('cardNumber');
    const expiryMonthInput = document.getElementById('expiryMonth');
    const expiryYearInput = document.getElementById('expiryYear');
    const cvvInput = document.getElementById('cvv');

    // Format card number input
    cardNumberInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        if (formattedValue.length > 19) {
            formattedValue = formattedValue.substring(0, 19);
        }
        e.target.value = formattedValue;
        
        // Real-time validation
        if (value.length >= 13) {
            validateCardNumber(value);
        }
    });

    // Format expiry month input
    expiryMonthInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/gi, '');
        if (value.length > 2) {
            value = value.substring(0, 2);
        }
        if (value.length === 2 && parseInt(value) > 12) {
            value = '12';
        }
        if (value.length === 2 && parseInt(value) < 1) {
            value = '01';
        }
        e.target.value = value;
    });

    // Format expiry year input
    expiryYearInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/gi, '');
        if (value.length > 2) {
            value = value.substring(0, 2);
        }
        e.target.value = value;
    });

    // Format CVV input
    cvvInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/gi, '');
        if (value.length > 3) {
            value = value.substring(0, 3);
        }
        e.target.value = value;
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        processPayment();
    });
}

// Luhn algorithm for card validation
function luhnCheck(cardNumber) {
    let sum = 0;
    let isEven = false;
    
    // Loop through values starting from the rightmost side
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber[i]);
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return sum % 10 === 0;
}

// Validate card number
function validateCardNumber(cardNumber) {
    const cardNumberInput = document.getElementById('cardNumber');
    const cleanNumber = cardNumber.replace(/\s+/g, '');
    
    // Check if it's a valid length (13-19 digits)
    if (cleanNumber.length < 13 || cleanNumber.length > 19) {
        setFieldInvalid(cardNumberInput, 'Card number must be between 13-19 digits');
        return false;
    }
    
    // Check if it passes Luhn algorithm
    if (!luhnCheck(cleanNumber)) {
        setFieldInvalid(cardNumberInput, 'Invalid card number');
        return false;
    }
    
    // Check if it's a Visa card (starts with 4)
    if (!cleanNumber.startsWith('4')) {
        setFieldInvalid(cardNumberInput, 'Only Visa cards are accepted');
        return false;
    }
    
    setFieldValid(cardNumberInput);
    return true;
}

// Validate expiry date
function validateExpiryDate(month, year) {
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear() % 100; // Get last 2 digits
    const currentMonth = currentDate.getMonth() + 1;
    
    const expMonth = parseInt(month);
    const expYear = parseInt(year);
    
    if (expMonth < 1 || expMonth > 12) {
        return false;
    }
    
    if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
        return false;
    }
    
    return true;
}

// Validate CVV
function validateCVV(cvv) {
    return cvv.length === 3 && /^\d{3}$/.test(cvv);
}

// Set field as invalid
function setFieldInvalid(field, message) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    const feedback = field.nextElementSibling;
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        feedback.textContent = message;
    }
}

// Set field as valid
function setFieldValid(field) {
    field.classList.add('is-valid');
    field.classList.remove('is-invalid');
}

// Process payment
async function processPayment() {
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s+/g, '');
    const cardName = document.getElementById('cardName').value.trim();
    const expiryMonth = document.getElementById('expiryMonth').value;
    const expiryYear = document.getElementById('expiryYear').value;
    const cvv = document.getElementById('cvv').value;
    const saveCard = document.getElementById('saveCard').checked;

    // Validate all fields
    let isValid = true;

    if (!validateCardNumber(cardNumber)) {
        isValid = false;
    }

    if (!cardName) {
        setFieldInvalid(document.getElementById('cardName'), 'Name on card is required');
        isValid = false;
    } else {
        setFieldValid(document.getElementById('cardName'));
    }

    if (!validateExpiryDate(expiryMonth, expiryYear)) {
        setFieldInvalid(document.getElementById('expiryMonth'), 'Invalid expiry date');
        setFieldInvalid(document.getElementById('expiryYear'), 'Invalid expiry date');
        isValid = false;
    } else {
        setFieldValid(document.getElementById('expiryMonth'));
        setFieldValid(document.getElementById('expiryYear'));
    }

    if (!validateCVV(cvv)) {
        setFieldInvalid(document.getElementById('cvv'), 'Invalid CVV');
        isValid = false;
    } else {
        setFieldValid(document.getElementById('cvv'));
    }

    if (!isValid) {
        showError('Please correct the errors in the form');
        return;
    }

    // Prepare payment data
    const paymentData = {
        customer_id: currentUser.Customer_ID,
        card_number: cardNumber.substring(cardNumber.length - 4), // Store only last 4 digits
        card_name: cardName,
        expiry_month: expiryMonth,
        expiry_year: expiryYear,
        
        // --- START: MODIFIED CODE BLOCK ---
        // Add subtotal, shipping, and tax to the payload
        subtotal: orderTotals.subtotal.toFixed(2),
        shipping_fee: orderTotals.shipping.toFixed(2),
        tax_amount: orderTotals.tax.toFixed(2),
        // --- END: MODIFIED CODE BLOCK ---

        amount: orderTotals.total.toFixed(2),
        original_amount: (orderTotals.subtotal + orderTotals.shipping + orderTotals.tax).toFixed(2),
        discount_id: appliedDiscount ? appliedDiscount.id : null,
        discount_amount: orderTotals.discount.toFixed(2),
        cart_items: cartItems,
        save_card: saveCard
    };

    try {
        // Show loading state
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        const response = await fetch('/Database_Project/src/api/process-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paymentData)
        });

        const result = await response.json();

        if (result.status === 'success') {
            showSuccess('Payment processed successfully!');
            // Clear cart and redirect to success page
            setTimeout(() => {
                window.location.href = `payment-success.html?order_id=${result.order_id}&amount=${result.amount_paid}`;
            }, 2000);
        } else {
            showError('Payment failed: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Payment error:', error);
        showError('Payment processing failed. Please try again.');
        const submitBtn = document.querySelector('button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Pay Now';
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






// Global variables
let currentUser = null;
let products = [];
let filteredProducts = [];
let cart = [];
let currentPage = 1;
const productsPerPage = 12;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    checkUserAuthentication();
    loadCategories(); // Load categories dynamically
    loadProducts();
    initializeEventListeners();
    setupCartPreview();
    updateCartCount();
    // Load cart from server after user authentication is checked
    if (currentUser) {
        loadCartFromServer();
    }
});

// Check if user is authenticated and update UI accordingly
function checkUserAuthentication() {
    const userData = localStorage.getItem('userData');
    const userWelcome = document.getElementById('userWelcome');
    const loginContainer = document.querySelector('.login-container');
    const userInfo = document.querySelector('.user-info');

    if (userData) {
        currentUser = JSON.parse(userData);
        if (userWelcome) {
            userWelcome.textContent = `مرحباً ${currentUser.name}`;
        }
        // Hide login button when user is logged in
        if (loginContainer) {
            loginContainer.style.display = 'none';
        }
        // Show logout button in user info
        if (userInfo) {
            userInfo.innerHTML = `
                <i class="fas fa-user"></i>
                <span>${currentUser.name}</span>
                <button onclick="logoutUser()" class="logout-btn" style="margin-left: 10px; background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            `;
        }
        // Load cart from server when user is authenticated
        loadCartFromServer();
    } else {
        currentUser = null;
        if (userWelcome) {
            userWelcome.textContent = 'مرحباً';
        }
        // Show login button when user is not logged in
        if (loginContainer) {
            loginContainer.style.display = 'block';
        }
        // Reset user info to default
        if (userInfo) {
            userInfo.innerHTML = `
                <i class="fas fa-user"></i>
                <span id="userWelcome">مرحباً</span>
            `;
        }
        // Clear cart when user is not logged in
        cart = [];
        updateCartCount();
    }
}


// Load categories from the server and populate the filter dropdown
async function loadCategories() {
    try {
        const response = await fetch('/Database_Project/src/api/get-categories.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const categoryFilter = document.getElementById('categoryFilter');
            
            // Clear existing options except "All Categories"
            categoryFilter.innerHTML = '<option value="all">جميع الفئات</option>';
            
            // Add categories from database
            data.categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.Name.toLowerCase();
                option.textContent = category.Name;
                categoryFilter.appendChild(option);
            });
        } else {
            console.error('Failed to load categories:', data.message);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Update user interface with user data
function updateUserInterface() {
    if (currentUser) {
        document.getElementById('userWelcome').textContent = `مرحباً ${currentUser.firstName}`;
    }
}

// Initialize event listeners
function initializeEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', debounce(searchProducts, 300));
    
    // Filter change events
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('priceFilter').addEventListener('change', applyFilters);
    document.getElementById('sortFilter').addEventListener('change', applyFilters);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            closeProductModal();
        }
    });
    
    // Load cart from server
    loadCartFromServer();
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Load products from the server
async function loadProducts() {
    try {
        showLoading(true);
        const response = await fetch('/Database_Project/src/api/get-products.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            products = data.products;
            filteredProducts = [...products];
            displayProducts();
        } else {
            showError('Failed to load products: ' + data.message);
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showError('Failed to load products. Please try again.');
    } finally {
        showLoading(false);
    }
}

// Display products in the grid
function displayProducts() {
    const productsGrid = document.getElementById('productsGrid');
    const noProductsDiv = document.getElementById('noProducts');
    const currentCountSpan = document.getElementById('currentCount');
    const totalCountSpan = document.getElementById('totalCount');
    
    const startIndex = (currentPage - 1) * productsPerPage;
    const endIndex = startIndex + productsPerPage;
    const productsToShow = filteredProducts.slice(0, endIndex);
    
    // Update products count
    if (currentCountSpan && totalCountSpan) {
        currentCountSpan.textContent = productsToShow.length;
        totalCountSpan.textContent = filteredProducts.length;
    }
    
    if (productsToShow.length === 0) {
        productsGrid.innerHTML = '';
        if (noProductsDiv) {
            noProductsDiv.style.display = 'block';
        }
    } else {
        if (noProductsDiv) {
            noProductsDiv.style.display = 'none';
        }
        productsGrid.innerHTML = productsToShow.map(product => createProductCard(product)).join('');
    }
    
    // Show/hide load more button
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        if (endIndex >= filteredProducts.length) {
            loadMoreBtn.style.display = 'none';
        } else {
            loadMoreBtn.style.display = 'block';
        }
    }
}

// Create product card HTML
function createProductCard(product) {
    const price = parseFloat(product.Price).toFixed(2);
    const stock = parseInt(product.Stock);
    const isOutOfStock = stock <= 0;
    const isLowStock = stock > 0 && stock <= 5;
    
    return `
        <div class="product-card ${isOutOfStock ? 'out-of-stock' : ''}" onclick="${isOutOfStock ? '' : `openProductModal(${product.Product_ID})`}">
            <div class="product-image-container">
                <img src="${product.Image_Path || 'https://placehold.co/300x300/F8FAFC/CCCCCC?text=Product'}" 
                     alt="${product.Title}" 
                     class="product-image"
                     loading="lazy">
                ${isOutOfStock ? '<div class="out-of-stock-overlay"><span>Out of Stock</span></div>' : ''}
                ${!isOutOfStock ? `
                <div class="product-overlay">
                    <button class="quick-view-btn" onclick="event.stopPropagation(); openProductModal(${product.Product_ID})">
                        <i class="fas fa-eye"></i>
                        عرض سريع
                    </button>
                </div>
                ` : ''}
            </div>
            <div class="product-details">
                <h3 class="product-title">${product.Title}</h3>
                <div class="product-pricing">
                    <span class="current-price">₪${price}</span>
                </div>
                <div class="stock-info">
                    ${isOutOfStock ? 
                        '<span class="stock-status out-of-stock">Out of Stock</span>' : 
                        isLowStock ? 
                            `<span class="stock-status low-stock">Only ${stock} left in stock</span>` :
                            `<span class="stock-status in-stock">${stock} in stock</span>`
                    }
                </div>
                <button class="add-to-cart-btn ${isOutOfStock ? 'disabled' : ''}" 
                        onclick="event.stopPropagation(); ${isOutOfStock ? '' : `addToCart(${product.Product_ID})`}"
                        ${isOutOfStock ? 'disabled' : ''}>
                    <i class="fas fa-shopping-cart"></i>
                    ${isOutOfStock ? 'Out of Stock' : 'إضافة للسلة'}
                </button>
            </div>
        </div>
    `;
}
// Search products
function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    if (searchTerm === '') {
        filteredProducts = [...products];
    } else {
        filteredProducts = products.filter(product => 
            product.Title.toLowerCase().includes(searchTerm) ||
            product.Description.toLowerCase().includes(searchTerm)
        );
    }
    
    currentPage = 1;
    applyFilters();
}

// Filter products by category

// Apply all filters
function applyFilters() {
    let filtered = [...products];
    
    // Apply search filter
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(product => 
            product.Title.toLowerCase().includes(searchTerm) ||
            product.Description.toLowerCase().includes(searchTerm)
        );
    }
    
    // Apply category filter
    const categoryFilter = document.getElementById('categoryFilter').value;
    if (categoryFilter !== 'all') {
        filtered = filtered.filter(product => 
            product.category_name.toLowerCase() === categoryFilter.toLowerCase()
        );
    }
    
    // Apply price filter
    const priceFilter = document.getElementById('priceFilter').value;
    if (priceFilter !== 'all') {
        const [min, max] = priceFilter.split('-').map(p => p === '+' ? Infinity : parseInt(p));
        filtered = filtered.filter(product => {
            const price = parseFloat(product.Price);
            return price >= min && (max === undefined || price <= max);
        });
    }
    
    // Apply sorting
    const sortFilter = document.getElementById('sortFilter').value;
    switch (sortFilter) {
        case 'price-low':
            filtered.sort((a, b) => parseFloat(a.Price) - parseFloat(b.Price));
            break;
        case 'price-high':
            filtered.sort((a, b) => parseFloat(b.Price) - parseFloat(a.Price));
            break;
        case 'newest':
            filtered.sort((a, b) => new Date(b.Created_At) - new Date(a.Created_At));
            break;
        case 'best-selling':
            filtered.sort((a, b) => (b.total_sold || 0) - (a.total_sold || 0));
            break;
        case 'name':
        default:
            filtered.sort((a, b) => a.Title.localeCompare(b.Title));
            break;
    }
    
    filteredProducts = filtered;
    currentPage = 1;
    displayProducts();
}

// Load more products
function loadMoreProducts() {
    currentPage++;
    displayProducts();
}

// Add product to cart
async function addToCart(productId, quantity = 1) {
    if (!currentUser) {
        showError('Please log in to add items to cart');
        return;
    }

    try {
        // First check current stock
        const stockResponse = await fetch(`/Database_Project/src/api/get-product-stock.php?product_id=${productId}`);
        const stockData = await stockResponse.json();
        
        if (stockData.status !== 'success') {
            showError('Failed to check product availability');
            return;
        }
        
        const product = stockData.product;
        
        // Check if product is out of stock
        if (product.Stock <= 0) {
            showError('This product is currently out of stock');
            return;
        }
        
        // Check if requested quantity exceeds available stock
        if (quantity > product.Stock) {
            showError(`Only ${product.Stock} items available in stock`);
            return;
        }

        const response = await fetch('/Database_Project/src/api/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_id: currentUser.Customer_ID,
                product_id: productId,
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            showSuccess('Product added to cart successfully');
            updateCartCount();
            loadCartFromServer(); // Refresh cart display
        } else {
            showError('Failed to add product to cart: ' + data.message);
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showError('Failed to add product to cart. Please try again.');
    }
}

// Update cart display
function updateCartDisplay() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    // Update cart count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    // Update cart items
    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
    } else {
        cartItems.innerHTML = cart.map(item => createCartItemHTML(item)).join('');
    }
    
    // Update cart total
    const total = cart.reduce((sum, item) => sum + (parseFloat(item.Price) * item.quantity), 0);
    cartTotal.textContent = total.toFixed(2);
}

// Create cart item HTML
function createCartItemHTML(item) {
    return `
        <div class="cart-item">
            <div class="cart-item-image">
                <img src="${item.image_url || 'placeholder.jpg'}" alt="${item.Title}">
            </div>
            <div class="cart-item-info">
                <div class="cart-item-title">${item.Title}</div>
                <div class="cart-item-price">$${item.Price}</div>
                <div class="cart-item-controls">
                    <button class="quantity-btn" onclick="updateCartItemQuantity(${item.Product_ID}, -1)">-</button>
                    <span class="quantity-display">${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateCartItemQuantity(${item.Product_ID}, 1)">+</button>
                    <button class="remove-item" onclick="removeFromCart(${item.Product_ID})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Update cart item quantity
async function updateCartItemQuantity(productId, change) {
    if (!currentUser) {
        showError('Please log in to update cart');
        return;
    }
    
    const item = cart.find(item => item.Product_ID === productId);
    if (!item) return;
    
    const newQuantity = item.quantity + change;
    
    if (newQuantity <= 0) {
        removeFromCart(productId);
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
            item.quantity = newQuantity;
            updateCartDisplay();
            updateCartCount();
        } else {
            showError('Failed to update cart: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        showError('Failed to update cart. Please try again.');
    }
}

// Remove item from cart
async function removeFromCart(productId) {
    if (!currentUser) {
        showError('Please log in to remove items from cart');
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
            cart = cart.filter(item => item.Product_ID !== productId);
            updateCartDisplay();
            updateCartCount();
            showSuccess('Item removed from cart');
        } else {
            showError('Failed to remove item: ' + data.message);
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
        showError('Failed to remove item. Please try again.');
    }
}

// Update cart count display
function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

// Show cart preview on hover
function showCartPreview() {
    const cartPreview = document.getElementById('cartPreview');
    if (cartPreview && cart.length > 0) {
        updateCartPreview();
        cartPreview.style.display = 'flex';
    }
}

// Hide cart preview
function hideCartPreview() {
    const cartPreview = document.getElementById('cartPreview');
    if (cartPreview) {
        cartPreview.style.display = 'none';
    }
}

// Update cart preview content
function updateCartPreview() {
    const cartPreviewItems = document.getElementById('cartPreviewItems');
    const cartPreviewTotal = document.getElementById('cartPreviewTotal');
    
    if (!cartPreviewItems || !cartPreviewTotal) return;

    if (cart.length === 0) {
        cartPreviewItems.innerHTML = '<p style="text-align: center; color: var(--text-light); padding: var(--space-lg);">Your cart is empty</p>';
        cartPreviewTotal.textContent = '0.00';
        return;
    }

    // Show only first 3 items in preview
    const previewItems = cart.slice(0, 3);
    cartPreviewItems.innerHTML = previewItems.map(item => `
        <div class="cart-preview-item">
            <img src="${item.image || 'https://via.placeholder.com/40'}" alt="${item.title}" class="cart-preview-item-image">
            <div class="cart-preview-item-info">
                <div class="cart-preview-item-title">${item.title}</div>
                <div class="cart-preview-item-details">${item.quantity}x $${item.price.toFixed(2)}</div>
            </div>
        </div>
    `).join('');

    if (cart.length > 3) {
        cartPreviewItems.innerHTML += `<p style="text-align: center; color: var(--text-light); font-size: 0.8rem; padding: var(--space-sm);">+${cart.length - 3} more items</p>`;
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    cartPreviewTotal.textContent = total.toFixed(2);
}

// Add event listeners for cart preview
function setupCartPreview() {
    const cartContainer = document.querySelector('.cart-container');
    if (cartContainer) {
        cartContainer.addEventListener('mouseenter', showCartPreview);
        cartContainer.addEventListener('mouseleave', () => {
            setTimeout(hideCartPreview, 300); // Small delay to allow moving to preview
        });
    }

    const cartPreview = document.getElementById('cartPreview');
    if (cartPreview) {
        cartPreview.addEventListener('mouseenter', () => {
            // Keep preview open when hovering over it
        });
        cartPreview.addEventListener('mouseleave', hideCartPreview);
    }
}

// Toggle cart sidebar (legacy function - now redirects to cart page)
function toggleCart() {
    window.location.href = 'cart.html';
}

// Open product modal
async function openProductModal(productId) {
    try {
        const response = await fetch(`/Database_Project/src/api/get-product-details.php?id=${productId}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const product = data.product;
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = `
                <div class="product-modal-content">
                    <div class="product-modal-image">
                        <img src="${product.image_url || 'placeholder.jpg'}" alt="${product.Title}">
                    </div>
                    <div class="product-modal-info">
                        <h2>${product.Title}</h2>
                        <div class="product-modal-price">
                            <span class="current-price">$${product.Price}</span>
                        </div>
                        <div class="product-modal-description">
                            <p>${product.Description || 'No description available'}</p>
                        </div>
                        <div class="product-modal-stock">
                            <span class="stock-info">Stock: ${product.Stock} items available</span>
                        </div>
                        <div class="product-modal-actions">
                            <button class="add-to-cart-btn" onclick="addToCart(${product.Product_ID}); closeProductModal();">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('productModal').style.display = 'block';
        } else {
            showError('Failed to load product details');
        }
    } catch (error) {
        console.error('Error loading product details:', error);
        showError('Failed to load product details');
    }
}

// Close product modal
function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
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

// Newsletter subscription
async function subscribeNewsletter() {
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
    
    try {
        const response = await fetch('/Database_Project/src/api/newsletter-subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showSuccess('Successfully subscribed to newsletter!');
            document.getElementById('newsletterEmail').value = '';
            document.getElementById('newsletterConsent').checked = false;
        } else {
            showError('Failed to subscribe: ' + data.message);
        }
    } catch (error) {
        console.error('Error subscribing to newsletter:', error);
        showError('Failed to subscribe. Please try again.');
    }
}

// Load cart from localStorage
function loadCartFromStorage() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartDisplay();
    }
}

// Save cart to localStorage
function saveCartToStorage() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Show loading spinner
function showLoading(show) {
    const loadingSpinner = document.getElementById('loadingSpinner');
    loadingSpinner.style.display = show ? 'block' : 'none';
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
    
    .product-modal-content {
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }
    
    .product-modal-image {
        flex: 1;
        max-width: 400px;
    }
    
    .product-modal-image img {
        width: 100%;
        height: auto;
        border-radius: 10px;
    }
    
    .product-modal-info {
        flex: 1;
    }
    
    .product-modal-info h2 {
        font-size: 2rem;
        margin-bottom: 20px;
        color: #2c3e50;
    }
    
    .product-modal-price {
        margin-bottom: 20px;
    }
    
    .product-modal-price .current-price {
        font-size: 2rem;
        font-weight: 700;
        color: #e74c3c;
        margin-right: 15px;
    }
    
    .product-modal-price .original-price {
        font-size: 1.5rem;
        color: #999;
        text-decoration: line-through;
    }
    
    .product-modal-description {
        margin-bottom: 20px;
        line-height: 1.6;
        color: #666;
    }
    
    .product-modal-stock {
        margin-bottom: 30px;
    }
    
    .stock-info {
        color: #27ae60;
        font-weight: 600;
    }
    
    .product-modal-actions .add-to-cart-btn {
        padding: 15px 30px;
        font-size: 1.1rem;
    }
    
    .empty-cart {
        text-align: center;
        padding: 40px 20px;
        color: #666;
        font-style: italic;
    }
    
    .no-products {
        text-align: center;
        padding: 60px 20px;
        color: #666;
        font-size: 1.2rem;
        grid-column: 1 / -1;
    }
    
    @media (max-width: 768px) {
        .product-modal-content {
            flex-direction: column;
        }
        
        .product-modal-image {
            max-width: 100%;
        }
    }
`;
document.head.appendChild(style);

// Logout function
function logoutUser() {
    // Clear user data from localStorage
    localStorage.removeItem('userData');
    
    // Reset current user
    currentUser = null;
    
    // Update UI to show logged out state
    checkUserAuthentication();
    
    // Clear cart
    cart = [];
    updateCartCount();
    
    // Show success message
    showSuccess('Logged out successfully');
    
    // Optionally redirect to main page or refresh
    // window.location.reload();
}


// Load cart from server
async function loadCartFromServer() {
    if (!currentUser) {
        cart = [];
        updateCartCount();
        return;
    }
    
    try {
        const response = await fetch(`/Database_Project/src/api/get-cart.php?customer_id=${currentUser.Customer_ID}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            cart = data.cart_items.map(item => ({
                Product_ID: item.Product_ID,
                Title: item.Title,
                Price: parseFloat(item.Price),
                quantity: item.Quantity,
                Stock: item.Stock,
                image_path: item.image_path,
                category_name: item.category_name,
                Cart_Item_ID: item.Cart_Item_ID
            }));
            updateCartCount();
        } else {
            cart = [];
            updateCartCount();
        }
    } catch (error) {
        console.error('Error loading cart from server:', error);
        cart = [];
        updateCartCount();
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
            updateCartDisplay();
            updateCartCount();
            showSuccess('Cart cleared successfully');
        } else {
            showError('Failed to clear cart: ' + data.message);
        }
    } catch (error) {
        console.error('Error clearing cart:', error);
        showError('Failed to clear cart. Please try again.');
    }
}






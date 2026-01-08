/**
 * Product Detail Page JavaScript
 * Handles product display, cart functionality, and reviews
 */

class ProductDetail {
    constructor() {
        this.productId = null;
        this.product = null;
        this.currentReviewPage = 1;
        this.reviewSort = 'newest';
        this.init();
    }

    init() {
        this.productId = this.getProductIdFromUrl();
        if (!this.productId) {
            this.showError('No product specified');
            return;
        }
        
        this.bindEvents();
        this.loadProduct();
        this.loadReviews();
    }

    getProductIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }

    bindEvents() {
        // Quantity controls
        document.getElementById('decreaseQty').addEventListener('click', () => this.updateQuantity(-1));
        document.getElementById('increaseQty').addEventListener('click', () => this.updateQuantity(1));
        document.getElementById('quantity').addEventListener('change', (e) => this.validateQuantity(e.target));

        // Add to cart
        document.getElementById('addToCartBtn').addEventListener('click', () => this.addToCart());

        // Review sorting
        document.getElementById('sortReviews').addEventListener('change', (e) => {
            this.reviewSort = e.target.value;
            this.currentReviewPage = 1;
            this.loadReviews();
        });

        // Submit review
        document.getElementById('submitReviewBtn').addEventListener('click', () => this.submitReview());
    }

    async loadProduct() {
        try {
            const response = await fetch(`/Database_Project/src/api/get-product-details.php?id=${this.productId}`);
            const result = await response.json();

            if (result.success && result.product) {
                this.product = result.product;
                this.displayProduct();
            } else {
                this.showError('Product not found');
            }
        } catch (error) {
            console.error('Error loading product:', error);
            this.showError('Failed to load product');
        }
    }

    displayProduct() {
        const p = this.product;
        
        // Show main content
        document.getElementById('loadingState').classList.add('d-none');
        document.getElementById('mainContent').classList.remove('d-none');

        // Basic info
        document.getElementById('productName').textContent = p.Name;
        document.getElementById('productBreadcrumb').textContent = p.Name;
        document.getElementById('productPrice').textContent = this.formatCurrency(p.Price);
        document.getElementById('productDescription').textContent = p.Description || 'No description available.';
        document.getElementById('productCategory').textContent = p.Category_Name || 'Uncategorized';
        document.getElementById('productSku').textContent = `PRD-${String(p.Product_ID).padStart(5, '0')}`;
        document.getElementById('categoryLink').textContent = p.Category_Name || 'Products';

        // Image
        const img = document.getElementById('productImage');
        img.src = p.Image_URL || 'images/products/full-size/default.jpg';
        img.alt = p.Name;
        img.onerror = () => img.src = 'https://via.placeholder.com/500x500?text=No+Image';

        // Stock status
        const stockEl = document.getElementById('stockStatus');
        const stock = parseInt(p.Stock);
        if (stock > 10) {
            stockEl.className = 'stock-status in-stock';
            stockEl.innerHTML = '<i class="fas fa-check-circle"></i> In Stock';
        } else if (stock > 0) {
            stockEl.className = 'stock-status low-stock';
            stockEl.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Only ${stock} left`;
        } else {
            stockEl.className = 'stock-status out-of-stock';
            stockEl.innerHTML = '<i class="fas fa-times-circle"></i> Out of Stock';
            document.getElementById('addToCartBtn').disabled = true;
        }

        // Rating (if available from product data)
        this.displayRatingSummary(
            parseFloat(p.avg_rating) || 0,
            parseInt(p.review_count) || 0
        );
    }

    displayRatingSummary(avgRating, reviewCount) {
        // Stars in product header
        const starsContainer = document.getElementById('productStars');
        starsContainer.innerHTML = this.generateStars(avgRating);
        
        document.getElementById('ratingValue').textContent = avgRating.toFixed(1);
        document.getElementById('reviewCount').textContent = reviewCount;
    }

    async loadReviews() {
        try {
            const url = `api/get_reviews.php?product_id=${this.productId}&page=${this.currentReviewPage}&sort=${this.reviewSort}`;
            const response = await fetch(url);
            const result = await response.json();

            if (result.success) {
                this.displayReviews(result.data);
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }

    displayReviews(data) {
        const { product, rating_distribution, reviews, pagination } = data;

        // Overall rating
        document.getElementById('overallRating').textContent = product.avg_rating.toFixed(1);
        document.getElementById('overallStars').innerHTML = this.generateStars(product.avg_rating, 'large');
        document.getElementById('totalReviews').textContent = product.review_count;

        // Rating distribution bars
        const barsContainer = document.getElementById('ratingBars');
        const totalReviews = product.review_count || 1;
        
        barsContainer.innerHTML = Object.entries(rating_distribution)
            .sort((a, b) => b[0] - a[0])
            .map(([rating, count]) => {
                const percentage = (count / totalReviews) * 100;
                return `
                    <div class="rating-bar-row">
                        <span class="rating-label">${rating} <i class="fas fa-star"></i></span>
                        <div class="rating-bar">
                            <div class="rating-bar-fill" style="width: ${percentage}%"></div>
                        </div>
                        <span class="rating-count">${count}</span>
                    </div>
                `;
            }).join('');

        // Reviews list
        const listContainer = document.getElementById('reviewsList');
        
        if (reviews.length === 0) {
            listContainer.innerHTML = `
                <div class="no-reviews">
                    <i class="fas fa-comments"></i>
                    <h4>No Reviews Yet</h4>
                    <p>Be the first to review this product!</p>
                </div>
            `;
        } else {
            listContainer.innerHTML = reviews.map(review => `
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-avatar">${review.customer_name.charAt(0)}</div>
                            <div>
                                <div class="reviewer-name">${this.escapeHtml(review.customer_name)}</div>
                                <div class="review-date">${this.formatDate(review.Created_At)}</div>
                                ${review.Is_Verified_Purchase == 1 ? 
                                    '<div class="verified-badge"><i class="fas fa-check"></i> Verified Purchase</div>' : ''}
                            </div>
                        </div>
                        <div class="review-rating">
                            ${this.generateStars(parseInt(review.Rating))}
                        </div>
                    </div>
                    ${review.Title ? `<div class="review-title">${this.escapeHtml(review.Title)}</div>` : ''}
                    ${review.Review_Text ? `<div class="review-text">${this.escapeHtml(review.Review_Text)}</div>` : ''}
                    <div class="review-actions">
                        <button class="helpful-btn">
                            <i class="far fa-thumbs-up"></i> Helpful (${review.Helpful_Count})
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Pagination
        this.displayPagination(pagination);
    }

    displayPagination(pagination) {
        const container = document.getElementById('reviewsPagination');
        
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        html += `<button class="page-btn" ${pagination.current_page === 1 ? 'disabled' : ''} 
                         onclick="productDetail.goToPage(${pagination.current_page - 1})">
                    <i class="fas fa-chevron-left"></i>
                 </button>`;
        
        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === 1 || i === pagination.total_pages || 
                (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)) {
                html += `<button class="page-btn ${i === pagination.current_page ? 'active' : ''}" 
                                 onclick="productDetail.goToPage(${i})">${i}</button>`;
            } else if (i === pagination.current_page - 2 || i === pagination.current_page + 2) {
                html += `<span class="page-btn" style="border: none;">...</span>`;
            }
        }
        
        // Next button
        html += `<button class="page-btn" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''} 
                         onclick="productDetail.goToPage(${pagination.current_page + 1})">
                    <i class="fas fa-chevron-right"></i>
                 </button>`;
        
        container.innerHTML = html;
    }

    goToPage(page) {
        this.currentReviewPage = page;
        this.loadReviews();
        // Scroll to reviews section
        document.querySelector('.reviews-section').scrollIntoView({ behavior: 'smooth' });
    }

    generateStars(rating, size = '') {
        let html = '';
        const fullStars = Math.floor(rating);
        const hasHalf = rating % 1 >= 0.5;
        
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                html += '<i class="fas fa-star filled"></i>';
            } else if (i === fullStars + 1 && hasHalf) {
                html += '<i class="fas fa-star-half-alt filled"></i>';
            } else {
                html += '<i class="far fa-star"></i>';
            }
        }
        return html;
    }

    updateQuantity(delta) {
        const input = document.getElementById('quantity');
        let value = parseInt(input.value) + delta;
        value = Math.max(1, Math.min(99, value));
        input.value = value;
    }

    validateQuantity(input) {
        let value = parseInt(input.value);
        if (isNaN(value) || value < 1) value = 1;
        if (value > 99) value = 99;
        input.value = value;
    }

    async addToCart() {
        const quantity = parseInt(document.getElementById('quantity').value);
        const btn = document.getElementById('addToCartBtn');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        try {
            const response = await fetch('/Database_Project/src/api/add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: this.productId,
                    quantity: quantity
                })
            });

            const result = await response.json();

            if (result.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                    btn.disabled = false;
                }, 2000);
            } else {
                throw new Error(result.message || 'Failed to add to cart');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            btn.innerHTML = '<i class="fas fa-times"></i> Error';
            btn.classList.add('btn-danger');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                btn.classList.remove('btn-danger');
                btn.disabled = false;
            }, 2000);
        }
    }

    async submitReview() {
        const rating = document.querySelector('input[name="rating"]:checked');
        const title = document.getElementById('reviewTitle').value.trim();
        const text = document.getElementById('reviewText').value.trim();

        if (!rating) {
            if (window.toast) {
                window.toast.warning('Please select a rating');
            } else {
                alert('Please select a rating');
            }
            return;
        }

        // Get customer ID from session (simplified - in production, use proper auth)
        const customerId = sessionStorage.getItem('customer_id') || localStorage.getItem('customer_id');
        
        if (!customerId) {
            if (window.toast) {
                window.toast.warning('Please log in to submit a review');
            }
            setTimeout(() => window.location.href = 'login_page.html', 1500);
            return;
        }

        const btn = document.getElementById('submitReviewBtn');
        if (window.loading) {
            window.loading.setButtonLoading(btn, true);
        } else {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        }

        try {
            const response = await fetch('/Database_Project/src/api/add_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: parseInt(this.productId),
                    customer_id: parseInt(customerId),
                    rating: parseInt(rating.value),
                    title: title,
                    review_text: text
                })
            });

            const result = await response.json();

            if (result.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                
                // Reset form
                document.getElementById('reviewForm').reset();
                
                // Reload reviews
                this.loadReviews();
                
                if (window.toast) {
                    window.toast.success('Thank you for your review!');
                } else {
                    alert('Thank you for your review!');
                }
            } else {
                if (window.toast) {
                    window.toast.error(result.message || 'Failed to submit review');
                } else {
                    alert(result.message || 'Failed to submit review');
                }
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            if (window.toast) {
                window.toast.error('Failed to submit review. Please try again.');
            } else {
                alert('Failed to submit review. Please try again.');
            }
        } finally {
            if (window.loading) {
                window.loading.setButtonLoading(btn, false);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Review';
            }
        }
    }

    showError(message) {
        // Also show toast if available
        if (window.toast) {
            window.toast.error(message);
        }
        
        document.getElementById('loadingState').innerHTML = `
            <div class="text-center">
                <i class="fas fa-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                <h3 class="mt-3">${message}</h3>
                <a href="index.html" class="btn btn-primary mt-3">Back to Shop</a>
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
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
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
    window.productDetail = new ProductDetail();
});






# Cobra Shop - E-Commerce Platform

A full-featured e-commerce web application built with PHP, MySQL, and modern JavaScript. This project demonstrates proficiency in full-stack development, database design, security implementation, and responsive UI/UX design.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Installation](#installation)
- [Database Schema](#database-schema)
- [API Documentation](#api-documentation)
- [Security Features](#security-features)
- [Screenshots](#screenshots)
- [Future Enhancements](#future-enhancements)

## Features

### Customer Features
- **Shopping Cart** - Add, update, remove items with real-time updates
- **User Authentication** - Secure login/registration with session management
- **Order Tracking** - Visual timeline showing order progress
- **Product Reviews** - Rate and review purchased products
- **Secure Checkout** - Multiple payment methods with validation
- **Discount Codes** - Apply promotional discounts at checkout
- **Newsletter Subscription** - Stay updated with latest offers

### Admin Features
- **Analytics Dashboard** - Real-time sales metrics and charts
- **Staff Management** - Assign orders, track performance
- **Discount Management** - Create, edit, toggle promotional codes
- **Inventory Alerts** - Low stock notifications
- **Sales Reports** - Daily, weekly, monthly revenue tracking

### Technical Features
- **Security First** - CSRF protection, rate limiting, SQL injection prevention
- **Responsive Design** - Mobile-first approach with Bootstrap 5
- **Optimized Performance** - Efficient queries, lazy loading
- **Modern UI/UX** - Smooth animations, intuitive navigation

## Tech Stack

### Backend
| Technology | Purpose |
|------------|---------|
| PHP 7.4+ | Server-side logic |
| MySQL 8.0 | Relational database |
| PDO | Database abstraction layer |

### Frontend
| Technology | Purpose |
|------------|---------|
| HTML5 | Semantic markup |
| CSS3 | Styling & animations |
| JavaScript ES6+ | Interactive features |
| Bootstrap 5.3 | Responsive framework |
| Chart.js | Data visualization |
| Font Awesome 6 | Icon library |

### Development
| Tool | Purpose |
|------|---------|
| XAMPP | Local development server |
| VS Code | IDE |
| Git | Version control |

## Architecture

```
cobra-shop/
├── assets/                     # Static resources
│   ├── css/                    # Stylesheets
│   │   ├── style.css           # Main styles
│   │   ├── order_style.css     # Order page styles
│   │   ├── admin_dashboard.css # Admin panel styles
│   │   └── ...
│   ├── js/                     # Client-side scripts
│   │   ├── script.js           # Auth & core logic
│   │   ├── cart.js             # Shopping cart
│   │   ├── toast.js            # Notification system
│   │   ├── loading.js          # Loading animations
│   │   ├── a11y.js             # Accessibility utils
│   │   ├── security.js         # CSRF & session
│   │   └── ...
│   └── images/                 # Static assets
│       ├── logo.jpg
│       └── products/
│           ├── full-size/
│           └── thumbnails/
│
├── src/                        # Backend source code
│   ├── api/                    # REST API endpoints
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── get-products.php
│   │   ├── add-to-cart.php
│   │   ├── process-payment.php
│   │   ├── dashboard_stats.php
│   │   └── ...
│   ├── config/                 # Configuration
│   │   ├── config.php          # App constants
│   │   └── database.php        # DB singleton
│   └── includes/               # Shared modules
│       ├── bootstrap.php       # API initialization
│       ├── api_helpers.php     # Response utilities
│       ├── validation.php      # Input validation
│       ├── session.php         # Session management
│       ├── csrf.php            # CSRF protection
│       ├── rate_limit.php      # Rate limiting
│       └── auth.php            # Auth helpers
│
├── views/                      # HTML templates
│   ├── admin/                  # Admin panel pages
│   │   ├── admin_dashboard.html
│   │   ├── staff_management.html
│   │   ├── discount_management.html
│   │   └── add-product.html
│   ├── auth/                   # Authentication pages
│   │   └── login_page.html
│   └── shop/                   # Customer pages
│       ├── cart.html
│       ├── payment.html
│       ├── profile.html
│       ├── product_detail.html
│       └── order_tracking.html
│
├── docs/                       # Documentation
│   └── sql/                    # Database schemas
│       ├── tables.sql
│       ├── security_tables.sql
│       └── reviews_table.sql
│
├── logs/                       # Application logs
└── index.html                  # Main entry point
```

### Design Patterns Used
- **Singleton Pattern** - Database connection management
- **MVC-like Structure** - Separation of concerns
- **RESTful API Design** - Consistent endpoint structure
- **DRY Principle** - Reusable components and utilities

## Installation

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 8.0 or higher

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/cobra-shop.git
   ```

2. **Move to web directory**
   ```bash
   # Windows (XAMPP)
   mv cobra-shop C:/xampp/htdocs/Database_Project
   
   # macOS (MAMP)
   mv cobra-shop /Applications/MAMP/htdocs/Database_Project
   ```

3. **Create the database**
   ```sql
   CREATE DATABASE cobra_shop_project;
   USE cobra_shop_project;
   ```

4. **Import database schema**
   ```bash
   # Import main tables
   mysql -u root cobra_shop_project < docs/sql/tables.sql
   
   # Import security tables
   mysql -u root cobra_shop_project < docs/sql/security_tables.sql
   
   # Import reviews table
   mysql -u root cobra_shop_project < docs/sql/reviews_table.sql
   ```

5. **Configure database connection**
   
   Edit `src/config/database.php` and update credentials if needed:
   ```php
   private $host = 'localhost';
   private $dbname = 'cobra_shop_project';
   private $username = 'root';
   private $password = '';
   ```

6. **Start the server**
   - Start Apache and MySQL in XAMPP
   - Visit `http://localhost/Database_Project`

## Database Schema

### Entity Relationship Diagram

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Customers  │────<│Sales_Orders │>────│    Staff    │
└─────────────┘     └─────────────┘     └─────────────┘
       │                   │
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────────┐     ┌─────────────┐
│   Reviews   │     │Sales_Order_     │────<│  Products   │
│             │────<│    Details      │     └─────────────┘
└─────────────┘     └─────────────────┘           │
                                                   │
                           ┌─────────────┐         │
                           │  Category   │>────────┘
                           └─────────────┘

┌─────────────┐     ┌─────────────┐
│  Payments   │────<│  Discounts  │
└─────────────┘     └─────────────┘
```

### Core Tables
| Table | Description | Key Fields |
|-------|-------------|------------|
| Customers | User accounts | Customer_ID, Email, Password (hashed) |
| Products | Product catalog | Product_ID, Name, Price, Stock |
| Category | Product categories | Category_ID, Name |
| Sales_Orders | Order records | SO_ID, Customer_ID, Status, Total_Amount |
| Sales_Order_Details | Order line items | SO_ID, Product_ID, Quantity |
| Payments | Payment records | Payment_ID, SO_ID, Payment_Method |
| Staff | Employee records | Staff_ID, Name, Position |
| Discount_Management | Promo codes | Discount_ID, Name, Discount_Value |
| Product_Reviews | Customer reviews | Review_ID, Product_ID, Rating |

### Security Tables
| Table | Purpose |
|-------|---------|
| Login_Attempts | Rate limiting |
| User_Sessions | Session tracking |
| Password_Reset_Tokens | Secure password reset |
| Security_Audit_Log | Activity logging |

## API Documentation

### Authentication
```http
POST /src/api/login.php
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securepassword"
}
```

### Products
```http
GET /src/api/get-products.php?category_id=1&limit=10
GET /src/api/get-product-details.php?id=123
```

### Cart
```http
POST /src/api/add-to-cart.php
GET /src/api/get-cart.php
PUT /src/api/update-cart.php
DELETE /src/api/remove-from-cart.php
```

### Orders
```http
GET /src/api/order_tracking.php?order_id=1001
POST /src/api/process-payment.php
```

### Reviews
```http
GET /src/api/get_reviews.php?product_id=123&page=1&sort=newest
POST /src/api/add_review.php
```

### Admin Dashboard
```http
GET /src/api/dashboard_stats.php?days=30
```

### Response Format
All API endpoints return consistent JSON:
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

## Security Features

| Feature | Implementation |
|---------|----------------|
| **Password Hashing** | bcrypt with cost factor 12 |
| **SQL Injection Prevention** | PDO prepared statements |
| **XSS Protection** | Output escaping, CSP headers |
| **CSRF Protection** | Token-based validation |
| **Rate Limiting** | Login attempt tracking |
| **Session Security** | Fingerprinting, regeneration |
| **Input Validation** | Server-side validation layer |

### Security Headers
```php
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
```

## Screenshots

### Home Page
Modern product catalog with category filtering and search

### Admin Dashboard
Real-time analytics with sales charts and order statistics

### Order Tracking
Visual timeline showing order progress from placement to delivery

### Product Reviews
Star ratings with verified purchase badges

## Future Enhancements

- [ ] **Email Notifications** - Order confirmations, shipping updates
- [ ] **Wishlist Feature** - Save products for later
- [ ] **Advanced Search** - Filters, sorting, faceted search
- [ ] **Real-time Chat** - Customer support integration
- [ ] **Multi-language Support** - i18n implementation
- [ ] **Payment Gateway Integration** - Stripe, PayPal
- [ ] **Mobile App** - React Native companion app
- [ ] **Recommendation Engine** - ML-based product suggestions


<p align="center">
  Built for learning and portfolio purposes
</p>

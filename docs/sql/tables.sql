-- Cobra Shop Management System Database
-- Reordered based on foreign key dependencies

show databases;
use cobra_shop_project;
-- ============================================================================
-- INDEPENDENT TABLES (No foreign key dependencies)
-- ============================================================================

-- 1. Managers Table (Independent - referenced by other tables)
CREATE TABLE managers (
    manager_id INT PRIMARY KEY AUTO_INCREMENT,
    manager_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Customers Table (Independent - referenced by other tables)
CREATE TABLE Customers (
    Customer_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Address TEXT,
    Birth_Date DATE,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Discount Management Table (Independent - referenced by other tables)
CREATE TABLE Discount_Management (
    Discount_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Description TEXT,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Discount_Type ENUM('Percentage', 'Fixed Amount') NOT NULL,
    Discount_Value DECIMAL(10,2) NOT NULL CHECK (Discount_Value >= 0),
    Minimum_Purchase DECIMAL(10,2) DEFAULT 0.00 CHECK (Minimum_Purchase >= 0),
    Status ENUM('Active', 'Inactive', 'Expired') DEFAULT 'Active',
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (End_Date >= Start_Date)
);

-- ============================================================================
-- FIRST LEVEL DEPENDENT TABLES (Depend on independent tables only)
-- ============================================================================

-- 4. Sections Table (Depends on: managers)
CREATE TABLE sections (
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    section_name VARCHAR(50) NOT NULL, 
    manager_id INT NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES managers(manager_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 5. Suppliers Table (Depends on: managers)
CREATE TABLE Suppliers (
    Supplier_ID INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Contact VARCHAR(100),
    Email VARCHAR(100),
    Address TEXT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES managers(manager_id) ON DELETE CASCADE ON UPDATE CASCADE  
);

-- 6. Warehouse Table (Depends on: managers)
CREATE TABLE Warehouse (
    Warehouse_ID INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    Location_Name VARCHAR(100) NOT NULL,
    Stock_Quantity INT NOT NULL DEFAULT 0 CHECK (Stock_Quantity >= 0),
    Last_Updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES managers(manager_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 7. Staff Table (Depends on: managers, sections)
CREATE TABLE Staff (
    Staff_ID INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    section_id INT NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Position VARCHAR(50) NOT NULL,
    Salary DECIMAL(10,2) NOT NULL,
    Hire_Date DATE NOT NULL,
    Gender ENUM('Male', 'Female') NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES managers(manager_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 8. Shopping Carts Table (Depends on: Customers)
CREATE TABLE Shopping_Carts (
    Cart_ID INT PRIMARY KEY AUTO_INCREMENT,
    Customer_ID INT NOT NULL,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Last_Modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Customer_ID) REFERENCES Customers(Customer_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================================
-- SECOND LEVEL DEPENDENT TABLES (Depend on first level tables)
-- ============================================================================

-- 9. Category Table (Depends on: managers, suppliers, warehouse)
CREATE TABLE category (
    Category_ID INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    Name VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES managers(manager_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES Suppliers(Supplier_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES Warehouse(Warehouse_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 10. Purchase Orders Table (Depends on: Suppliers)
CREATE TABLE Purchase_Orders (
    PO_ID INT PRIMARY KEY AUTO_INCREMENT,
    Supplier_ID INT NOT NULL,
    Order_Date DATE NOT NULL,
    Status ENUM('Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    Expected_Delivery_Date DATE,
    Total_Amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 CHECK (Total_Amount >= 0),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Supplier_ID) REFERENCES Suppliers(Supplier_ID) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- ============================================================================
-- THIRD LEVEL DEPENDENT TABLES (Depend on second level tables)
-- ============================================================================

-- 11. Products Table (Depends on: Category)
CREATE TABLE Products (
    Product_ID INT PRIMARY KEY AUTO_INCREMENT,
    Category_ID INT NOT NULL,
    Title VARCHAR(200) NOT NULL,
    Price DECIMAL(10,2) NOT NULL CHECK (Price >= 0),
    Stock INT NOT NULL DEFAULT 0 CHECK (Stock >= 0),
    Description TEXT,
    image_path VARCHAR(255),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Category_ID) REFERENCES category(Category_ID) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 12. Sales Orders Table (Depends on: Customers, Staff)
CREATE TABLE Sales_Orders (
    SO_ID INT PRIMARY KEY AUTO_INCREMENT,
    Customer_ID INT NOT NULL,
    Staff_ID INT,
    Order_Date DATE NOT NULL,
    Status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Returned') DEFAULT 'Pending',
    Total_Amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 CHECK (Total_Amount >= 0),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Customer_ID) REFERENCES Customers(Customer_ID) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (Staff_ID) REFERENCES Staff(Staff_ID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ============================================================================
-- FOURTH LEVEL DEPENDENT TABLES (Detail/Junction tables)
-- ============================================================================

-- 13. Purchase Order Details Table (Depends on: Purchase_Orders, Products)
CREATE TABLE Purchase_Order_Details (
    PO_Detail_ID INT PRIMARY KEY AUTO_INCREMENT,
    PO_ID INT NOT NULL,
    Product_ID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    Unit_Price DECIMAL(10,2) NOT NULL CHECK (Unit_Price >= 0),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (PO_ID) REFERENCES Purchase_Orders(PO_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Product_ID) REFERENCES Products(Product_ID) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 14. Sales Order Details Table (Depends on: Sales_Orders, Products)
CREATE TABLE Sales_Order_Details (
    SO_Detail_ID INT PRIMARY KEY AUTO_INCREMENT,
    SO_ID INT NOT NULL,
    Product_ID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    Unit_Price DECIMAL(10,2) NOT NULL CHECK (Unit_Price >= 0),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (SO_ID) REFERENCES Sales_Orders(SO_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Product_ID) REFERENCES Products(Product_ID) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 15. Cart Items Table (Depends on: Shopping_Carts, Products)
CREATE TABLE Cart_Items (
    Cart_Item_ID INT PRIMARY KEY AUTO_INCREMENT,
    Cart_ID INT NOT NULL,
    Product_ID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    Added_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Cart_ID) REFERENCES Shopping_Carts(Cart_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Product_ID) REFERENCES Products(Product_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_cart_product (Cart_ID, Product_ID)
);

-- 16. Payments Table (Depends on: Sales_Orders, Discount_Management)
CREATE TABLE Payments (
    Payment_ID INT PRIMARY KEY AUTO_INCREMENT,
    SO_ID INT NOT NULL,
    Discount_ID INT,
    Amount DECIMAL(12,2) NOT NULL CHECK (Amount >= 0),
    Payment_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Payment_Method ENUM('Credit Card', 'Debit Card', 'PayPal', 'Cash', 'Bank Transfer') NOT NULL,
    Payment_Status ENUM('Pending', 'Completed', 'Failed', 'Refunded') DEFAULT 'Pending',
    Transaction_ID VARCHAR(100) UNIQUE,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (SO_ID) REFERENCES Sales_Orders(SO_ID) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (Discount_ID) REFERENCES Discount_Management(Discount_ID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ============================================================================
-- JUNCTION TABLES (Many-to-Many relationships)
-- ============================================================================

-- 17. Supplier Categories Junction Table (Depends on: Suppliers, Category)
CREATE TABLE Supplier_Categories (
    Supplier_ID INT NOT NULL,
    Category_ID INT NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Supplier_ID, Category_ID),
    FOREIGN KEY (Supplier_ID) REFERENCES Suppliers(Supplier_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Category_ID) REFERENCES category(Category_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 18. Product Discounts Junction Table (Depends on: Products, Discount_Management)
CREATE TABLE Product_Discounts (
    Product_ID INT NOT NULL,
    Discount_ID INT NOT NULL,
    Applied_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Product_ID, Discount_ID),
    FOREIGN KEY (Product_ID) REFERENCES Products(Product_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Discount_ID) REFERENCES Discount_Management(Discount_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 19. Cart Discounts Junction Table (Depends on: Shopping_Carts, Discount_Management)
CREATE TABLE Cart_Discounts (
    Cart_ID INT NOT NULL,
    Discount_ID INT NOT NULL,
    Applied_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Cart_ID, Discount_ID),
    FOREIGN KEY (Cart_ID) REFERENCES Shopping_Carts(Cart_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Discount_ID) REFERENCES Discount_Management(Discount_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================

-- Create Indexes for Better Performance
CREATE INDEX idx_products_category ON Products(Category_ID);
CREATE INDEX idx_products_price ON Products(Price);
CREATE INDEX idx_products_stock ON Products(Stock);
CREATE INDEX idx_sales_orders_customer ON Sales_Orders(Customer_ID);
CREATE INDEX idx_sales_orders_staff ON Sales_Orders(Staff_ID);
CREATE INDEX idx_sales_orders_date ON Sales_Orders(Order_Date);
CREATE INDEX idx_sales_orders_status ON Sales_Orders(Status);
CREATE INDEX idx_purchase_orders_supplier ON Purchase_Orders(Supplier_ID);
CREATE INDEX idx_purchase_orders_date ON Purchase_Orders(Order_Date);
CREATE INDEX idx_purchase_orders_status ON Purchase_Orders(Status);
CREATE INDEX idx_payments_so ON Payments(SO_ID);
CREATE INDEX idx_payments_date ON Payments(Payment_Date);
CREATE INDEX idx_payments_status ON Payments(Payment_Status);
CREATE INDEX idx_cart_items_cart ON Cart_Items(Cart_ID);
CREATE INDEX idx_cart_items_product ON Cart_Items(Product_ID);
CREATE INDEX idx_staff_manager ON Staff(manager_id);
CREATE INDEX idx_staff_section ON Staff(section_id);
CREATE INDEX idx_customers_email ON Customers(Email);

-- ============================================================================
-- TRIGGERS FOR DATA INTEGRITY AND AUTOMATION
-- ============================================================================

DELIMITER //

-- Trigger to update total amount in Sales Orders
CREATE TRIGGER update_sales_order_total 
AFTER INSERT ON Sales_Order_Details
FOR EACH ROW
BEGIN
    UPDATE Sales_Orders 
    SET Total_Amount = (
        SELECT SUM(Quantity * Unit_Price) 
        FROM Sales_Order_Details 
        WHERE SO_ID = NEW.SO_ID
    )
    WHERE SO_ID = NEW.SO_ID;
END//

-- Trigger to update total amount when sales order details are updated
CREATE TRIGGER update_sales_order_total_on_update
AFTER UPDATE ON Sales_Order_Details
FOR EACH ROW
BEGIN
    UPDATE Sales_Orders 
    SET Total_Amount = (
        SELECT SUM(Quantity * Unit_Price) 
        FROM Sales_Order_Details 
        WHERE SO_ID = NEW.SO_ID
    )
    WHERE SO_ID = NEW.SO_ID;
END//

-- Trigger to update total amount in Purchase Orders
CREATE TRIGGER update_purchase_order_total 
AFTER INSERT ON Purchase_Order_Details
FOR EACH ROW
BEGIN
    UPDATE Purchase_Orders 
    SET Total_Amount = (
        SELECT SUM(Quantity * Unit_Price) 
        FROM Purchase_Order_Details 
        WHERE PO_ID = NEW.PO_ID
    )
    WHERE PO_ID = NEW.PO_ID;
END//

-- Trigger to update total amount when purchase order details are updated
CREATE TRIGGER update_purchase_order_total_on_update
AFTER UPDATE ON Purchase_Order_Details
FOR EACH ROW
BEGIN
    UPDATE Purchase_Orders 
    SET Total_Amount = (
        SELECT SUM(Quantity * Unit_Price) 
        FROM Purchase_Order_Details 
        WHERE PO_ID = NEW.PO_ID
    )
    WHERE PO_ID = NEW.PO_ID;
END//

-- Trigger to update product stock when sale is made
CREATE TRIGGER update_product_stock_on_sale
AFTER INSERT ON Sales_Order_Details
FOR EACH ROW
BEGIN
    UPDATE Products 
    SET Stock = Stock - NEW.Quantity
    WHERE Product_ID = NEW.Product_ID;
END//

-- Trigger to update product stock when purchase order is received
CREATE TRIGGER update_product_stock_on_purchase
AFTER UPDATE ON Purchase_Orders
FOR EACH ROW
BEGIN
    IF NEW.Status = 'Delivered' AND OLD.Status != 'Delivered' THEN
        UPDATE Products p
        INNER JOIN Purchase_Order_Details pod ON p.Product_ID = pod.Product_ID
        SET p.Stock = p.Stock + pod.Quantity
        WHERE pod.PO_ID = NEW.PO_ID;
    END IF;
END//

-- Trigger to prevent negative stock
CREATE TRIGGER prevent_negative_stock
BEFORE UPDATE ON Products
FOR EACH ROW
BEGIN
    IF NEW.Stock < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock cannot be negative';
    END IF;
END//

DELIMITER ;

CREATE TABLE IF NOT EXISTS Login_Attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL COMMENT 'Email or other identifier',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    user_id INT NULL COMMENT 'Customer ID if login was successful',
    
    -- Indexes for fast lookups
    INDEX idx_identifier_time (identifier, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_attempt_time (attempt_time),
    
    -- Foreign key to Customers (optional, for tracking successful logins)
    FOREIGN KEY (user_id) REFERENCES Customers(Customer_ID) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Sessions Table (for server-side session management)
CREATE TABLE IF NOT EXISTS User_Sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity),
    
    FOREIGN KEY (user_id) REFERENCES Customers(Customer_ID) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. Password Reset Tokens Table
CREATE TABLE IF NOT EXISTS Password_Reset_Tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL COMMENT 'Hashed token for security',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    is_used BOOLEAN DEFAULT FALSE,
    
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_expires (user_id, expires_at),
    
    FOREIGN KEY (user_id) REFERENCES Customers(Customer_ID) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. Security Audit Log Table
CREATE TABLE IF NOT EXISTS Security_Audit_Log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    event_type ENUM(
        'LOGIN_SUCCESS', 
        'LOGIN_FAILED', 
        'LOGOUT', 
        'PASSWORD_CHANGE',
        'PASSWORD_RESET_REQUEST',
        'PASSWORD_RESET_COMPLETE',
        'ACCOUNT_LOCKED',
        'ACCOUNT_UNLOCKED',
        'CSRF_VIOLATION',
        'RATE_LIMIT_EXCEEDED'
    ) NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    details JSON NULL COMMENT 'Additional event details',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES Customers(Customer_ID) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //

-- Procedure to clean up expired sessions
CREATE PROCEDURE CleanupExpiredSessions()
BEGIN
    DELETE FROM User_Sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
    
    DELETE FROM Password_Reset_Tokens 
    WHERE expires_at < NOW() OR is_used = TRUE;
    
    DELETE FROM Login_Attempts 
    WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 7 DAY);
END//
-- Procedure to check and update account lock status
CREATE PROCEDURE CheckAccountLockStatus(
    IN p_identifier VARCHAR(255),
    IN p_lockout_minutes INT,
    IN p_max_attempts INT,
    OUT p_is_locked BOOLEAN,
    OUT p_remaining_minutes INT
)
BEGIN
    DECLARE v_failed_count INT;
    DECLARE v_last_attempt TIMESTAMP;
    DECLARE v_lockout_end TIMESTAMP;
    
    SELECT COUNT(*), MAX(attempt_time)
    INTO v_failed_count, v_last_attempt
    FROM Login_Attempts
    WHERE identifier = p_identifier
      AND success = FALSE
      AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR);
    
    IF v_failed_count >= p_max_attempts THEN
        SET v_lockout_end = DATE_ADD(v_last_attempt, INTERVAL p_lockout_minutes MINUTE);
        
        IF v_lockout_end > NOW() THEN
            SET p_is_locked = TRUE;
            SET p_remaining_minutes = TIMESTAMPDIFF(MINUTE, NOW(), v_lockout_end) + 1;
        ELSE
            SET p_is_locked = FALSE;
            SET p_remaining_minutes = 0;
        END IF;
    ELSE
        SET p_is_locked = FALSE;
        SET p_remaining_minutes = 0;
    END IF;
END//

DELIMITER ;



-- Create cleanup event (runs daily)
CREATE EVENT IF NOT EXISTS daily_security_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL CleanupExpiredSessions();
    
SET GLOBAL event_scheduler = ON;


SELECT 'Security tables created successfully!' AS Status;



CREATE TABLE IF NOT EXISTS Product_Reviews (
    Review_ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    Customer_ID INT NOT NULL,
    Rating TINYINT NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
    Title VARCHAR(100) DEFAULT NULL,
    Review_Text TEXT DEFAULT NULL,
    Is_Verified_Purchase BOOLEAN DEFAULT FALSE,
    Is_Approved BOOLEAN DEFAULT TRUE,
    Helpful_Count INT DEFAULT 0,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (Product_ID) REFERENCES Products(Product_ID) ON DELETE CASCADE,
    FOREIGN KEY (Customer_ID) REFERENCES Customers(Customer_ID) ON DELETE CASCADE,
    
    -- Prevent duplicate reviews from same customer for same product
    UNIQUE KEY unique_customer_product (Customer_ID, Product_ID)
);

-- Index for faster queries
CREATE INDEX idx_product_reviews_product ON Product_Reviews(Product_ID);
CREATE INDEX idx_product_reviews_rating ON Product_Reviews(Rating);
CREATE INDEX idx_product_reviews_created ON Product_Reviews(Created_At);


-- Add average rating cache to Products table for performance
ALTER TABLE Products 
ADD COLUMN  avg_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN  review_count INT DEFAULT 0;

-- Trigger to update product rating stats when a review is added
DELIMITER //

CREATE TRIGGER update_product_rating_insert
AFTER INSERT ON Product_Reviews
FOR EACH ROW
UPDATE Products 
SET avg_rating = (
    SELECT COALESCE(AVG(Rating), 0) 
    FROM Product_Reviews 
    WHERE Product_ID = NEW.Product_ID AND Is_Approved = 1
),
review_count = (
    SELECT COUNT(*) 
    FROM Product_Reviews 
    WHERE Product_ID = NEW.Product_ID AND Is_Approved = 1
)
WHERE Product_ID = NEW.Product_ID;

CREATE TRIGGER update_product_rating_update
AFTER UPDATE ON Product_Reviews
FOR EACH ROW
UPDATE Products 
SET avg_rating = (
    SELECT COALESCE(AVG(Rating), 0) 
    FROM Product_Reviews 
    WHERE Product_ID = NEW.Product_ID AND Is_Approved = 1
),
review_count = (
    SELECT COUNT(*) 
    FROM Product_Reviews 
    WHERE Product_ID = NEW.Product_ID AND Is_Approved = 1
)
WHERE Product_ID = NEW.Product_ID;

CREATE TRIGGER update_product_rating_delete
AFTER DELETE ON Product_Reviews
FOR EACH ROW
UPDATE Products 
SET avg_rating = (
    SELECT COALESCE(AVG(Rating), 0) 
    FROM Product_Reviews 
    WHERE Product_ID = OLD.Product_ID AND Is_Approved = 1
),
review_count = (
    SELECT COUNT(*) 
    FROM Product_Reviews 
    WHERE Product_ID = OLD.Product_ID AND Is_Approved = 1
)
WHERE Product_ID = OLD.Product_ID;

INSERT INTO Product_Reviews (Product_ID, Customer_ID, Rating, Title, Review_Text, Is_Verified_Purchase) VALUES
(1, 1, 5, 'Excellent Product!', 'Really happy with this purchase. Quality is outstanding.', TRUE),
(1, 2, 4, 'Good value', 'Works as expected. Would recommend.', TRUE),
(2, 1, 5, 'Perfect!', 'Exactly what I was looking for.', TRUE);

SHOW TABLES;



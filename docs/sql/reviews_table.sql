-- Product Reviews Table
-- Run this SQL in your MySQL database

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
ADD COLUMN IF NOT EXISTS avg_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0;

-- Trigger to update product rating stats when a review is added
-- Note: Run these triggers ONE AT A TIME in phpMyAdmin or MySQL CLI

DELIMITER //

CREATE TRIGGER update_product_rating_insert
AFTER INSERT ON Product_Reviews
FOR EACH ROW
BEGIN
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
END//

DELIMITER ;

DELIMITER //

CREATE TRIGGER update_product_rating_update
AFTER UPDATE ON Product_Reviews
FOR EACH ROW
BEGIN
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
END//

DELIMITER ;

DELIMITER //

CREATE TRIGGER update_product_rating_delete
AFTER DELETE ON Product_Reviews
FOR EACH ROW
BEGIN
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
END//

DELIMITER ;

-- Sample reviews (optional - for testing)
-- INSERT INTO Product_Reviews (Product_ID, Customer_ID, Rating, Title, Review_Text, Is_Verified_Purchase) VALUES
-- (1, 1, 5, 'Excellent Product!', 'Really happy with this purchase. Quality is outstanding.', TRUE),
-- (1, 2, 4, 'Good value', 'Works as expected. Would recommend.', TRUE),
-- (2, 1, 5, 'Perfect!', 'Exactly what I was looking for.', TRUE);

-- ============================================================================
-- SECURITY TABLES FOR COBRA SHOP
-- Run these SQL commands to add the necessary security tables
-- ============================================================================

-- 1. Add PasswordHash column to Customers table (if not exists)
-- Check if it already exists first
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'cobra_shop_project' 
    AND TABLE_NAME = 'Customers' 
    AND COLUMN_NAME = 'PasswordHash'
);

-- If you get an error, run this manually:
-- ALTER TABLE Customers ADD COLUMN PasswordHash VARCHAR(255) AFTER Address;

-- 2. Login Attempts Table for Rate Limiting
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

-- ============================================================================
-- STORED PROCEDURES FOR SECURITY OPERATIONS
-- ============================================================================

DELIMITER //

-- Procedure to clean up expired sessions
CREATE PROCEDURE IF NOT EXISTS CleanupExpiredSessions()
BEGIN
    DELETE FROM User_Sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
    
    DELETE FROM Password_Reset_Tokens 
    WHERE expires_at < NOW() OR is_used = TRUE;
    
    DELETE FROM Login_Attempts 
    WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 7 DAY);
END//

-- Procedure to check and update account lock status
CREATE PROCEDURE IF NOT EXISTS CheckAccountLockStatus(
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

-- ============================================================================
-- EVENT SCHEDULER FOR AUTOMATIC CLEANUP
-- ============================================================================

-- Enable event scheduler (run as admin)
-- SET GLOBAL event_scheduler = ON;

-- Create cleanup event (runs daily)
CREATE EVENT IF NOT EXISTS daily_security_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL CleanupExpiredSessions();

-- ============================================================================
-- GRANT STATEMENTS (if using a dedicated app user)
-- Replace 'app_user'@'localhost' with your application's database user
-- ============================================================================

-- GRANT SELECT, INSERT, UPDATE, DELETE ON cobra_shop_project.Login_Attempts TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON cobra_shop_project.User_Sessions TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON cobra_shop_project.Password_Reset_Tokens TO 'app_user'@'localhost';
-- GRANT INSERT ON cobra_shop_project.Security_Audit_Log TO 'app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE cobra_shop_project.CleanupExpiredSessions TO 'app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE cobra_shop_project.CheckAccountLockStatus TO 'app_user'@'localhost';

SELECT 'Security tables created successfully!' AS Status;

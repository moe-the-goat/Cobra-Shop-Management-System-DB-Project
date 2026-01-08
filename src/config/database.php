<?php
/**
 * Database Configuration
 * Centralized database connection for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('COBRA_SHOP')) {
    define('COBRA_SHOP', true);
}

// Database credentials - In production, use environment variables
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'cobra_shop_project');
define('DB_CHARSET', 'utf8mb4');

/**
 * Database Connection Class (Singleton Pattern)
 */
class Database {
    private static ?PDO $instance = null;
    private static ?mysqli $mysqliInstance = null;
    
    /**
     * Get PDO connection instance
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Failed: " . $e->getMessage());
                throw new PDOException("Database connection error. Please try again later.");
            }
        }
        return self::$instance;
    }
    
    /**
     * Get MySQLi connection instance (for backward compatibility)
     * @return mysqli
     * @throws Exception
     */
    public static function getMysqliConnection(): mysqli {
        if (self::$mysqliInstance === null || !self::$mysqliInstance->ping()) {
            self::$mysqliInstance = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            if (self::$mysqliInstance->connect_error) {
                error_log("Database Connection Failed: " . self::$mysqliInstance->connect_error);
                throw new Exception("Database connection error. Please try again later.");
            }
            
            self::$mysqliInstance->set_charset(DB_CHARSET);
        }
        return self::$mysqliInstance;
    }
    
    /**
     * Close all connections
     */
    public static function closeConnections(): void {
        self::$instance = null;
        if (self::$mysqliInstance !== null) {
            self::$mysqliInstance->close();
            self::$mysqliInstance = null;
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get PDO connection
 * @return PDO
 */
function getDB(): PDO {
    return Database::getConnection();
}

/**
 * Helper function to get MySQLi connection
 * @return mysqli
 */
function getDBMysqli(): mysqli {
    return Database::getMysqliConnection();
}
?>

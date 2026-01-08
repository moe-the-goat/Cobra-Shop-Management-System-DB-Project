<?php
/**
 * Application Bootstrap
 * Common initialization for all API endpoints
 * 
 * Include this file at the start of each API endpoint to get:
 * - Configuration
 * - Database connection
 * - Session management
 * - API helpers
 * - Validation functions
 * 
 * @package CobraShop
 * @version 1.0.0
 * 
 * Usage:
 *   require_once __DIR__ . '/includes/bootstrap.php';
 *   
 *   setApiHeaders(['POST']);
 *   requireMethod('POST');
 *   
 *   $input = getJsonInput();
 *   requireFields($input, ['email', 'password']);
 *   
 *   try {
 *       $db = getDB();
 *       // ... your logic
 *       sendSuccess('Operation completed', ['data' => $result]);
 *   } catch (Exception $e) {
 *       sendServerError('Operation failed', $e);
 *   }
 */

// Define app constant to prevent direct access to config files
if (!defined('COBRA_SHOP')) {
    define('COBRA_SHOP', true);
}

// Load all required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/api_helpers.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/session.php';

// Initialize session for all requests
initSession();

/**
 * Quick setup for a standard API endpoint
 * 
 * @param array $allowedMethods Allowed HTTP methods
 * @param bool $requireAuth Whether authentication is required
 * @param bool $requireCsrf Whether CSRF validation is required
 */
function initApi(array $allowedMethods = ['POST'], bool $requireAuth = false, bool $requireCsrf = false): void {
    // Set headers
    setApiHeaders($allowedMethods);
    
    // Validate method
    requireMethod($allowedMethods);
    
    // Check authentication if required
    if ($requireAuth && !isLoggedIn()) {
        sendUnauthorized('Please log in to continue');
    }
    
    // Validate CSRF if required
    if ($requireCsrf) {
        require_once __DIR__ . '/csrf.php';
        requireCsrfToken();
    }
}

/**
 * Get database connection with error handling
 * 
 * @param bool $usePdo Use PDO (true) or MySQLi (false)
 * @return PDO|mysqli
 */
function db(bool $usePdo = true) {
    try {
        return $usePdo ? getDB() : getDBMysqli();
    } catch (Exception $e) {
        logError('Database connection failed: ' . $e->getMessage(), 'ERROR');
        sendServerError('Database connection error. Please try again later.');
    }
}

/**
 * Execute a database query with error handling
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @param bool $fetchAll Whether to fetch all results
 * @return array|null Results or null on failure
 */
function dbQuery(string $sql, array $params = [], bool $fetchAll = true) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (stripos(trim($sql), 'SELECT') === 0) {
            return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
        }
        
        return ['affected_rows' => $stmt->rowCount(), 'insert_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        logError('Database query failed: ' . $e->getMessage(), 'ERROR', ['sql' => $sql]);
        throw $e;
    }
}

/**
 * Begin a database transaction
 * 
 * @return PDO
 */
function dbBeginTransaction(): PDO {
    $pdo = db();
    $pdo->beginTransaction();
    return $pdo;
}

/**
 * Commit the current transaction
 * 
 * @param PDO $pdo
 */
function dbCommit(PDO $pdo): void {
    $pdo->commit();
}

/**
 * Rollback the current transaction
 * 
 * @param PDO $pdo
 */
function dbRollback(PDO $pdo): void {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
?>

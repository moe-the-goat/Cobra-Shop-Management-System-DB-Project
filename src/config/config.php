<?php
/**
 * Application Configuration
 * Central configuration file for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('COBRA_SHOP')) {
    define('COBRA_SHOP', true);
}

// Error reporting (disable in production)
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/images/products');

// Application URLs (adjust for your environment)
define('BASE_URL', 'http://localhost/Database_Project');
define('ASSETS_URL', BASE_URL . '/assets');

// Session configuration
define('SESSION_NAME', 'cobra_shop_session');
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_SECURE', false); // Set to true in production with HTTPS
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAME_SITE', 'Strict');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Rate limiting settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds
define('RATE_LIMIT_WINDOW', 3600); // 1 hour window for tracking attempts

// Password requirements
define('MIN_PASSWORD_LENGTH', 8);
define('REQUIRE_UPPERCASE', true);
define('REQUIRE_LOWERCASE', true);
define('REQUIRE_NUMBER', true);
define('REQUIRE_SPECIAL', false);

// API response codes
define('API_SUCCESS', 'success');
define('API_ERROR', 'error');
define('API_UNAUTHORIZED', 'unauthorized');
define('API_FORBIDDEN', 'forbidden');
define('API_RATE_LIMITED', 'rate_limited');

/**
 * Standard API Response function
 * @param string $status
 * @param string $message
 * @param array $data
 * @param int $httpCode
 */
function apiResponse(string $status, string $message, array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Log application errors
 * @param string $message
 * @param string $level
 * @param array $context
 */
function logError(string $message, string $level = 'ERROR', array $context = []): void {
    $logFile = ROOT_PATH . '/logs/app_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $level,
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Include database configuration
require_once CONFIG_PATH . '/database.php';
?>

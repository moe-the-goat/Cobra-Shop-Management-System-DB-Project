<?php
/**
 * Session Management
 * Secure session handling for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

// Load configuration
require_once dirname(__DIR__) . '/config/config.php';

/**
 * Initialize secure session
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTP_ONLY,
            'samesite' => SESSION_SAME_SITE
        ]);
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // Regenerate session ID every 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Set session fingerprint for additional security
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = generateFingerprint();
        }
    }
}

/**
 * Generate a session fingerprint based on user agent and IP
 * @return string
 */
function generateFingerprint(): string {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $userAgent . $ip);
}

/**
 * Validate session fingerprint
 * @return bool
 */
function validateSession(): bool {
    if (!isset($_SESSION['fingerprint'])) {
        return false;
    }
    return $_SESSION['fingerprint'] === generateFingerprint();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn(): bool {
    initSession();
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true &&
           validateSession();
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId(): ?int {
    if (isLoggedIn()) {
        return (int)$_SESSION['user_id'];
    }
    return null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null
        ];
    }
    return null;
}

/**
 * Login user - create session
 * @param array $user User data from database
 * @return bool
 */
function loginUser(array $user): bool {
    initSession();
    
    // Regenerate session ID on login
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['Customer_ID'];
    $_SESSION['user_email'] = $user['Email'];
    $_SESSION['user_name'] = $user['Name'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['fingerprint'] = generateFingerprint();
    
    return true;
}

/**
 * Logout user - destroy session
 */
function logoutUser(): void {
    initSession();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Require authentication - redirect if not logged in
 * @param bool $apiMode If true, return JSON response instead of redirect
 */
function requireAuth(bool $apiMode = true): void {
    if (!isLoggedIn()) {
        if ($apiMode) {
            apiResponse(API_UNAUTHORIZED, 'Authentication required', [], 401);
        } else {
            header('Location: ' . BASE_URL . '/login_page.html');
            exit;
        }
    }
}
?>

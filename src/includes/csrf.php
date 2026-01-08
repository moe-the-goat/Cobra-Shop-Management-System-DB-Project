<?php
/**
 * CSRF Protection
 * Cross-Site Request Forgery protection for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/session.php';

/**
 * Generate CSRF token
 * @return string
 */
function generateCsrfToken(): string {
    initSession();
    
    // Generate new token if not exists or expired
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string|null $token Token to validate
 * @return bool
 */
function validateCsrfToken(?string $token): bool {
    initSession();
    
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Check if token has expired
    if (!isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    // Use timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token from request
 * Checks both POST data and headers
 * @return string|null
 */
function getCsrfTokenFromRequest(): ?string {
    // Check POST data
    if (isset($_POST[CSRF_TOKEN_NAME])) {
        return $_POST[CSRF_TOKEN_NAME];
    }
    
    // Check JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input[CSRF_TOKEN_NAME])) {
        return $input[CSRF_TOKEN_NAME];
    }
    
    // Check headers (X-CSRF-TOKEN)
    $headers = getallheaders();
    if (isset($headers['X-CSRF-TOKEN'])) {
        return $headers['X-CSRF-TOKEN'];
    }
    if (isset($headers['X-Csrf-Token'])) {
        return $headers['X-Csrf-Token'];
    }
    
    return null;
}

/**
 * Require valid CSRF token
 * Validates and exits with error if invalid
 * @param bool $regenerate Whether to regenerate token after validation
 */
function requireCsrfToken(bool $regenerate = false): void {
    $token = getCsrfTokenFromRequest();
    
    if (!validateCsrfToken($token)) {
        logError('CSRF validation failed', 'SECURITY', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        apiResponse(API_FORBIDDEN, 'Invalid or expired security token. Please refresh the page and try again.', [], 403);
    }
    
    if ($regenerate) {
        // Force regeneration of token
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        generateCsrfToken();
    }
}

/**
 * Output hidden CSRF field for forms
 * @return string HTML input field
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return sprintf('<input type="hidden" name="%s" value="%s">', CSRF_TOKEN_NAME, htmlspecialchars($token));
}

/**
 * Get CSRF token for AJAX requests
 * @return array Token data for JSON response
 */
function getCsrfTokenData(): array {
    return [
        'token' => generateCsrfToken(),
        'name' => CSRF_TOKEN_NAME
    ];
}
?>

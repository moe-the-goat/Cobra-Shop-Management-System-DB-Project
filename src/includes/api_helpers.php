<?php
/**
 * API Helper Functions
 * Standardized API responses and request handling
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Send a standardized JSON API response
 * 
 * @param bool $success Whether the operation was successful
 * @param string $message Human-readable message
 * @param array $data Additional data to include
 * @param int $httpCode HTTP status code
 */
function sendResponse(bool $success, string $message, array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'status' => $success ? 'success' : 'error',
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send a success response
 * 
 * @param string $message Success message
 * @param array $data Additional data
 * @param int $httpCode HTTP status code (default 200)
 */
function sendSuccess(string $message, array $data = [], int $httpCode = 200): void {
    sendResponse(true, $message, $data, $httpCode);
}

/**
 * Send an error response
 * 
 * @param string $message Error message
 * @param array $data Additional data
 * @param int $httpCode HTTP status code (default 400)
 */
function sendError(string $message, array $data = [], int $httpCode = 400): void {
    sendResponse(false, $message, $data, $httpCode);
}

/**
 * Send a validation error response
 * 
 * @param string $message Error message
 * @param array $errors Array of field-specific errors
 */
function sendValidationError(string $message, array $errors = []): void {
    sendResponse(false, $message, ['errors' => $errors], 422);
}

/**
 * Send an unauthorized response
 * 
 * @param string $message Error message
 */
function sendUnauthorized(string $message = 'Authentication required'): void {
    sendResponse(false, $message, [], 401);
}

/**
 * Send a forbidden response
 * 
 * @param string $message Error message
 */
function sendForbidden(string $message = 'Access denied'): void {
    sendResponse(false, $message, [], 403);
}

/**
 * Send a not found response
 * 
 * @param string $message Error message
 */
function sendNotFound(string $message = 'Resource not found'): void {
    sendResponse(false, $message, [], 404);
}

/**
 * Send a server error response
 * 
 * @param string $message Error message (generic for security)
 * @param Exception|null $exception Optional exception for logging
 */
function sendServerError(string $message = 'An internal error occurred', ?Exception $exception = null): void {
    if ($exception) {
        logError('Server Error: ' . $exception->getMessage(), 'ERROR', [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    sendResponse(false, $message, [], 500);
}

/**
 * Get JSON input from request body
 * 
 * @param bool $required Whether input is required
 * @return array|null Decoded JSON data
 */
function getJsonInput(bool $required = true): ?array {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($required && (!$input || !is_array($input))) {
        sendError('Invalid or missing JSON input', [], 400);
    }
    
    return $input;
}

/**
 * Validate required fields in input
 * 
 * @param array $input Input data
 * @param array $requiredFields List of required field names
 * @return array Missing fields (empty if all present)
 */
function validateRequiredFields(array $input, array $requiredFields): array {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

/**
 * Require specific fields in input, send error if missing
 * 
 * @param array $input Input data
 * @param array $requiredFields List of required field names
 */
function requireFields(array $input, array $requiredFields): void {
    $missing = validateRequiredFields($input, $requiredFields);
    
    if (!empty($missing)) {
        sendValidationError(
            'Missing required fields: ' . implode(', ', $missing),
            array_fill_keys($missing, 'This field is required')
        );
    }
}

/**
 * Set standard API headers
 * 
 * @param array $allowedMethods Allowed HTTP methods
 */
function setApiHeaders(array $allowedMethods = ['GET', 'POST', 'OPTIONS']): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN, Authorization');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Require specific HTTP method
 * 
 * @param string|array $methods Allowed method(s)
 */
function requireMethod($methods): void {
    $methods = is_array($methods) ? $methods : [$methods];
    $currentMethod = $_SERVER['REQUEST_METHOD'];
    
    if (!in_array($currentMethod, $methods)) {
        sendError('Method not allowed. Use: ' . implode(', ', $methods), [], 405);
    }
}

/**
 * Get integer parameter from input
 * 
 * @param array $input Input data
 * @param string $key Parameter key
 * @param int|null $default Default value
 * @return int|null
 */
function getIntParam(array $input, string $key, ?int $default = null): ?int {
    if (!isset($input[$key])) {
        return $default;
    }
    return intval($input[$key]);
}

/**
 * Get float parameter from input
 * 
 * @param array $input Input data
 * @param string $key Parameter key
 * @param float|null $default Default value
 * @return float|null
 */
function getFloatParam(array $input, string $key, ?float $default = null): ?float {
    if (!isset($input[$key])) {
        return $default;
    }
    return floatval($input[$key]);
}

/**
 * Get string parameter from input (trimmed)
 * 
 * @param array $input Input data
 * @param string $key Parameter key
 * @param string|null $default Default value
 * @return string|null
 */
function getStringParam(array $input, string $key, ?string $default = null): ?string {
    if (!isset($input[$key])) {
        return $default;
    }
    return trim($input[$key]);
}

/**
 * Get boolean parameter from input
 * 
 * @param array $input Input data
 * @param string $key Parameter key
 * @param bool $default Default value
 * @return bool
 */
function getBoolParam(array $input, string $key, bool $default = false): bool {
    if (!isset($input[$key])) {
        return $default;
    }
    return filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
}

/**
 * Sanitize output data to prevent XSS
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    if (is_string($data)) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}
?>

<?php
/**
 * Login API Endpoint
 * Secure authentication with session management, CSRF protection, and rate limiting
 * 
 * @package CobraShop
 * @version 2.0.0
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(API_ERROR, 'Method not allowed', [], 405);
}

// Initialize session
initSession();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['email']) || !isset($input['password'])) {
    apiResponse(API_ERROR, 'Email and password are required.', [], 400);
}

$email = trim($input['email']);
$password = $input['password'];

// Validate email format
if (empty($email) || !isValidEmail($email)) {
    apiResponse(API_ERROR, 'Invalid email format.', [], 400);
}

// Validate password not empty
if (empty($password)) {
    apiResponse(API_ERROR, 'Password cannot be empty.', [], 400);
}

// Check rate limiting
enforceRateLimit($email);

// Validate CSRF token (optional for API, but recommended)
// Uncomment the following line to enforce CSRF for login:
// requireCsrfToken();

try {
    // Attempt authentication
    $authResult = authenticateUser($email, $password);
    
    if ($authResult['success']) {
        // Record successful login
        recordLoginAttempt($email, true, $authResult['user']['Customer_ID']);
        
        // Create session
        loginUser($authResult['user']);
        
        // Prepare response
        $user = $authResult['user'];
        
        apiResponse(API_SUCCESS, 'Login successful! Welcome back, ' . htmlspecialchars($user['Name']) . '.', [
            'user' => [
                'Customer_ID' => $user['Customer_ID'],
                'email' => $user['Email'],
                'name' => $user['Name'],
                'Phone' => $user['Phone'],
                'Birth_Date' => $user['Birth_Date']
            ],
            'csrf_token' => generateCsrfToken() // Provide new CSRF token after login
        ]);
    } else {
        // Record failed login attempt
        recordLoginAttempt($email, false);
        
        // Get remaining attempts info
        $rateStatus = getRateLimitStatus($email);
        
        $message = $authResult['message'];
        if ($rateStatus['attempts_remaining'] <= 3 && $rateStatus['attempts_remaining'] > 0) {
            $message .= sprintf(' You have %d attempts remaining.', $rateStatus['attempts_remaining']);
        }
        
        apiResponse(API_ERROR, $message, [
            'attempts_remaining' => $rateStatus['attempts_remaining']
        ], 401);
    }
    
} catch (Exception $e) {
    logError('Login error: ' . $e->getMessage(), 'ERROR', [
        'email' => $email,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    apiResponse(API_ERROR, 'An error occurred during login. Please try again.', [], 500);
}
?>

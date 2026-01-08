<?php
/**
 * Registration API Endpoint
 * Secure user registration with validation
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

// Validate required fields
if (!$input || !isset($input['email'], $input['password'], $input['name'])) {
    apiResponse(API_ERROR, 'Missing required fields: Full Name, Email, and Password.', [], 400);
}

// Sanitize and validate inputs
$name = sanitizeInput($input['name']);
$email = trim($input['email']);
$password = $input['password'];
$phone = isset($input['phone']) ? trim($input['phone']) : null;
$birthDate = isset($input['birth_date']) && !empty($input['birth_date']) ? trim($input['birth_date']) : null;

// Validate name
if (empty($name)) {
    apiResponse(API_ERROR, 'Full Name is required.', [], 400);
}

if (strlen($name) < 2 || strlen($name) > 100) {
    apiResponse(API_ERROR, 'Full Name must be between 2 and 100 characters.', [], 400);
}

// Validate email
if (empty($email)) {
    apiResponse(API_ERROR, 'Email is required.', [], 400);
}

if (!isValidEmail($email)) {
    apiResponse(API_ERROR, 'Invalid email format.', [], 400);
}

// Validate password
if (empty($password)) {
    apiResponse(API_ERROR, 'Password is required.', [], 400);
}

$passwordValidation = validatePasswordStrength($password);
if (!$passwordValidation['valid']) {
    apiResponse(API_ERROR, implode(' ', $passwordValidation['errors']), [], 400);
}

// Validate phone (if provided)
if ($phone && !isValidPhone($phone)) {
    apiResponse(API_ERROR, 'Invalid phone number format. It should start with + and be 10-15 digits long.', [], 400);
}

// Validate birth date (if provided)
if ($birthDate) {
    $d = DateTime::createFromFormat('Y-m-d', $birthDate);
    if (!$d || $d->format('Y-m-d') !== $birthDate) {
        apiResponse(API_ERROR, 'Invalid birth date format. Please use YYYY-MM-DD.', [], 400);
    }
    
    // Check if birth date is reasonable (not in future, not more than 120 years ago)
    $today = new DateTime();
    $minDate = (new DateTime())->modify('-120 years');
    
    if ($d > $today) {
        apiResponse(API_ERROR, 'Birth date cannot be in the future.', [], 400);
    }
    
    if ($d < $minDate) {
        apiResponse(API_ERROR, 'Please enter a valid birth date.', [], 400);
    }
}

// Validate CSRF token (optional for API, but recommended)
// Uncomment the following line to enforce CSRF for registration:
// requireCsrfToken();

try {
    // Attempt registration
    $registrationResult = registerUser([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'phone' => $phone,
        'birth_date' => $birthDate
    ]);
    
    if ($registrationResult['success']) {
        // Log successful registration
        logError('New user registered', 'INFO', [
            'user_id' => $registrationResult['user_id'],
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        apiResponse(API_SUCCESS, $registrationResult['message'], [
            'user_id' => $registrationResult['user_id'],
            'redirect' => 'login_page.html'
        ]);
    } else {
        apiResponse(API_ERROR, $registrationResult['message'], [], 400);
    }
    
} catch (Exception $e) {
    logError('Registration error: ' . $e->getMessage(), 'ERROR', [
        'email' => $email,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    apiResponse(API_ERROR, 'An error occurred during registration. Please try again.', [], 500);
}
?>

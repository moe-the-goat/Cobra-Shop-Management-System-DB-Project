<?php
/**
 * Login API Endpoint (Legacy - redirects to secure version)
 * This file is kept for backward compatibility
 * New implementations should use login_secure.php
 * 
 * @package CobraShop
 * @version 2.0.0
 */

// Use centralized configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize session
initSession();

$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation for Login ---
if (!$input || !isset($input['email']) || !isset($input['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required for login.']);
    exit;
}

$email = trim($input['email']);
$submitted_password = $input['password'];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}
if (empty($submitted_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password cannot be empty.']);
    exit;
}

// Check rate limiting
enforceRateLimit($email);

// Use centralized database connection
$conn = getDBMysqli();

if ($conn->connect_error) {
    error_log("Database Connection Failed (Login Script): " . $conn->connect_error);
    echo json_encode(['status' => 'error', 'message' => 'Database connection error. Please try again later.']);
    exit;
}

// --- User Authentication ---
// MODIFIED: Added Phone and Birth_Date to the SELECT statement
$stmt = $conn->prepare("SELECT Customer_ID, Name, Email, Phone, Birth_Date, PasswordHash FROM Customers WHERE Email = ?");
if (false === $stmt) {
    error_log("Prepare statement failed (login): " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred (DB prepare).']);
    $conn->close();
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($submitted_password, $user['PasswordHash'])) {
        // Record successful login attempt
        recordLoginAttempt($email, true, $user['Customer_ID']);
        
        // Create session for the user
        loginUser($user);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful! Welcome back, ' . htmlspecialchars($user['Name']) . '.',
            'user' => [
                'Customer_ID' => $user['Customer_ID'],
                'email' => $user['Email'],
                'name' => $user['Name'],
                'Phone' => $user['Phone'],
                'Birth_Date' => $user['Birth_Date']
            ]
        ]);
    } else {
        // Record failed login attempt
        recordLoginAttempt($email, false);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }
} else {
    // Record failed login attempt
    recordLoginAttempt($email, false);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
}

$stmt->close();
// Don't close singleton connection
?>

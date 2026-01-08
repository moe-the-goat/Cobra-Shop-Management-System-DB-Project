<?php
/**
 * Registration API Endpoint (Legacy - kept for backward compatibility)
 * New implementations should use register_secure.php
 * 
 * @package CobraShop
 * @version 2.0.0
 */

// Use centralized configuration
require_once __DIR__ . '/../config/config.php';

// Set the content type to JSON for all responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the raw POST data from the request body
$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
if (!$input || !isset($input['email'], $input['password'], $input['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: Full Name, Email, and Password.']);
    exit;
}

// Trim whitespace from inputs
$fullName = trim($input['name']);
$email = trim($input['email']);
$password = $input['password'];
$phone = isset($input['phone']) ? trim($input['phone']) : null;
// MODIFIED: Added birth_date
$birth_date = isset($input['birth_date']) && !empty($input['birth_date']) ? trim($input['birth_date']) : null;


// --- Server-Side Data Validation ---
if (empty($fullName)) {
    echo json_encode(['status' => 'error', 'message' => 'Full Name is required.']);
    exit;
}
if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}
if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
    exit;
}
if ($phone && !preg_match('/^\+[0-9]{10,15}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format. It should start with + and be 10-15 digits long.']);
    exit;
}
// MODIFIED: Added birth_date validation
if ($birth_date) {
    $d = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!$d || $d->format('Y-m-d') !== $birth_date) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid birth date format. Please use YYYY-MM-DD.']);
        exit;
    }
}


// --- Database Connection ---
// Use centralized database connection
$conn = getDBMysqli();

// --- Check for Existing User ---
// Check if the email address is already in use
$stmt_check_email = $conn->prepare("SELECT Customer_ID FROM Customers WHERE Email = ?");
if (false === $stmt_check_email) {
    error_log("Prepare failed (check email): " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred preparing your request.']);
    $conn->close();
    exit;
}
$stmt_check_email->bind_param("s", $email);
$stmt_check_email->execute();
$stmt_check_email->store_result();

if ($stmt_check_email->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This email address is already registered.']);
    $stmt_check_email->close();
    $conn->close();
    exit;
}
$stmt_check_email->close();

// Check if the phone number is already in use (if provided)
if ($phone) {
    $stmt_check_phone = $conn->prepare("SELECT Customer_ID FROM Customers WHERE Phone = ?");
    if (false === $stmt_check_phone) {
        error_log("Prepare failed (check phone): " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'An error occurred preparing your request.']);
        $conn->close();
        exit;
    }
    $stmt_check_phone->bind_param("s", $phone);
    $stmt_check_phone->execute();
    $stmt_check_phone->store_result();

    if ($stmt_check_phone->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This phone number is already registered.']);
        $stmt_check_phone->close();
        $conn->close();
        exit;
    }
    $stmt_check_phone->close();
}

// --- Create New User ---
// Hash the password for secure storage. Never store plain text passwords.
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
if ($hashedPassword === false) {
    error_log("Password hashing failed for email: " . $email);
    echo json_encode(['status' => 'error', 'message' => 'Error processing registration.']);
    $conn->close();
    exit;
}

// MODIFIED: Updated INSERT query to include Birth_Date
$stmt_insert_user = $conn->prepare("INSERT INTO Customers (Name, Email, Phone, Birth_Date, PasswordHash) VALUES (?, ?, ?, ?, ?)");
if (false === $stmt_insert_user) {
    error_log("Prepare failed (insert user): " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred preparing your registration.']);
    $conn->close();
    exit;
}

// MODIFIED: Updated bind_param to include birth_date
$stmt_insert_user->bind_param("sssss", $fullName, $email, $phone, $birth_date, $hashedPassword);

// Execute the statement and provide feedback
if ($stmt_insert_user->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registration successful! You can now log in.']);
} else {
    // Provide a more specific error if the PasswordHash column is missing
    if (strpos($stmt_insert_user->error, "Unknown column 'PasswordHash'") !== false) {
         error_log("Execute failed: " . $stmt_insert_user->error . ". The 'PasswordHash' column is missing from the 'Customers' table.");
         echo json_encode(['status' => 'error', 'message' => "Database configuration error. Please contact support. (Ref: PassHashCol)"]);
    } else {
        error_log("Execute failed (insert user): " . $stmt_insert_user->error . " for email: " . $email);
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again later.']);
    }
}

// Close the statement and the connection
$stmt_insert_user->close();
$conn->close();
?>

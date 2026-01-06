<?php
// --- Database Credentials ---
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

header('Content-Type: application/json');

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

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

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
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful! Welcome back, ' . htmlspecialchars($user['Name']) . '.',
            // MODIFIED: Added Phone and Birth_Date to the user object
            'user' => [
                'Customer_ID' => $user['Customer_ID'],
                'email' => $user['Email'],
                'name' => $user['Name'],
                'Phone' => $user['Phone'],
                'Birth_Date' => $user['Birth_Date']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
}

$stmt->close();
$conn->close();
?>
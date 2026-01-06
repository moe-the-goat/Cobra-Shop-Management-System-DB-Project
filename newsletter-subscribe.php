<?php
// Database configuration
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['email'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email is required'
    ]);
    exit;
}

$email = trim($input['email']);

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format'
    ]);
    exit;
}

try {
    // Database connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8");
    
    // Check if newsletter subscribers table exists, if not create it
    $create_table_sql = "CREATE TABLE IF NOT EXISTS Newsletter_Subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'unsubscribed') DEFAULT 'active'
    )";
    
    $conn->query($create_table_sql);
    
    // Check if email already exists
    $check_sql = "SELECT id, status FROM Newsletter_Subscribers WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $subscriber = $check_result->fetch_assoc();
        if ($subscriber['status'] === 'active') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email is already subscribed to newsletter'
            ]);
            exit;
        } else {
            // Reactivate subscription
            $update_sql = "UPDATE Newsletter_Subscribers SET status = 'active', subscribed_at = CURRENT_TIMESTAMP WHERE email = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $email);
            $update_stmt->execute();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Newsletter subscription reactivated successfully'
            ]);
        }
    } else {
        // Add new subscriber
        $insert_sql = "INSERT INTO Newsletter_Subscribers (email) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $email);
        $insert_stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully subscribed to newsletter'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in newsletter-subscribe.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to subscribe to newsletter'
    ]);
} finally {
    // Close statements and connection
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($insert_stmt)) $insert_stmt->close();
    if (isset($conn)) $conn->close();
}
?>


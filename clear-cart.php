<?php
// clear-cart.php

// Database configuration
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Customer ID is required.']);
    exit;
}

$customer_id = intval($input['customer_id']);

if ($customer_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID.']);
    exit;
}

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // Get cart ID for the customer
    $cart_sql = "SELECT Cart_ID FROM Shopping_Carts WHERE Customer_ID = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("i", $customer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_row = $cart_result->fetch_assoc()) {
        $cart_id = $cart_row['Cart_ID'];
        
        // Clear all items from the cart
        $clear_sql = "DELETE FROM Cart_Items WHERE Cart_ID = ?";
        $clear_stmt = $conn->prepare($clear_sql);
        $clear_stmt->bind_param("i", $cart_id);
        
        if ($clear_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cart cleared successfully.']);
        } else {
            throw new Exception("Failed to clear cart items.");
        }
        
        $clear_stmt->close();
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Cart is already empty.']);
    }
    
    $cart_stmt->close();

} catch (Exception $e) {
    error_log("Clear Cart Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear cart.']);
} finally {
    if (isset($conn)) $conn->close();
}
?>


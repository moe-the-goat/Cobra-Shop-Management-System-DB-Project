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
if (!$input || !isset($input['customer_id']) || !isset($input['product_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: customer_id, product_id'
    ]);
    exit;
}

$customer_id = intval($input['customer_id']);
$product_id = intval($input['product_id']);

// Validate values
if ($customer_id <= 0 || $product_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input values'
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
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get cart ID for customer
    $cart_sql = "SELECT Cart_ID FROM Shopping_Carts WHERE Customer_ID = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("i", $customer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        throw new Exception("Cart not found");
    }
    
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['Cart_ID'];
    
    // Remove item from cart
    $delete_sql = "DELETE FROM Cart_Items WHERE Cart_ID = ? AND Product_ID = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $cart_id, $product_id);
    $delete_stmt->execute();
    
    if ($delete_stmt->affected_rows === 0) {
        throw new Exception("Item not found in cart");
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from cart successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("Error in remove-from-cart.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close statements and connection
    if (isset($cart_stmt)) $cart_stmt->close();
    if (isset($delete_stmt)) $delete_stmt->close();
    if (isset($conn)) $conn->close();
}
?>


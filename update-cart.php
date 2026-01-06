<?php
// Database_Project/update-cart.php

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
if (!$input || !isset($input['customer_id']) || !isset($input['product_id']) || !isset($input['quantity'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: customer_id, product_id, quantity'
    ]);
    exit;
}

$customer_id = intval($input['customer_id']);
$product_id = intval($input['product_id']);
$quantity = intval($input['quantity']);

// Validate values
if ($customer_id <= 0 || $product_id <= 0 || $quantity <= 0) {
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

    // --- START: THIS IS THE NEW/FIXED CODE BLOCK ---
    // Step 1: Check the product's available stock first.
    $stock_check_sql = "SELECT Stock FROM Products WHERE Product_ID = ?";
    $stock_stmt = $conn->prepare($stock_check_sql);
    if (!$stock_stmt) {
        throw new Exception("Failed to prepare stock check statement: " . $conn->error);
    }
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();

    if ($stock_result->num_rows === 0) {
        throw new Exception("Product not found");
    }

    $product = $stock_result->fetch_assoc();
    if ($product['Stock'] < $quantity) {
        // If stock is insufficient, stop the transaction.
        throw new Exception("Insufficient stock. Only " . $product['Stock'] . " items available.");
    }
    $stock_stmt->close();
    // --- END: NEW/FIXED CODE BLOCK ---
    
    // Step 2: Get cart ID for customer
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
    
    // Step 3: Find the specific cart item to update
    $item_check_sql = "SELECT Cart_Item_ID FROM Cart_Items WHERE Cart_ID = ? AND Product_ID = ?";
    $item_check_stmt = $conn->prepare($item_check_sql);
    $item_check_stmt->bind_param("ii", $cart_id, $product_id);
    $item_check_stmt->execute();
    $item_result = $item_check_stmt->get_result();
    
    if ($item_result->num_rows === 0) {
        throw new Exception("Item not found in cart");
    }
    
    $item = $item_result->fetch_assoc();
    $cart_item_id = $item['Cart_Item_ID'];
    
    // Step 4: Update cart item quantity (now that stock is validated)
    $update_sql = "UPDATE Cart_Items SET Quantity = ? WHERE Cart_Item_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $quantity, $cart_item_id);
    $update_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Cart updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("Error in update-cart.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close statements and connection
    if (isset($cart_stmt)) $cart_stmt->close();
    if (isset($item_check_stmt)) $item_check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($conn)) $conn->close();
}
?>
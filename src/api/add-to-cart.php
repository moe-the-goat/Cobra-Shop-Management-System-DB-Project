<?php
/**
 * Add to Cart API Endpoint
 * @package CobraShop
 */

// Use centralized configuration
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

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
    // Use centralized database connection
    $conn = getDBMysqli();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if product exists and has sufficient stock
    $check_sql = "SELECT Stock FROM Products WHERE Product_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception("Product not found");
    }
    
    $product = $check_result->fetch_assoc();
    if ($product['Stock'] < $quantity) {
        throw new Exception("Insufficient stock available");
    }
    
    // Check if customer has an active cart
    $cart_sql = "SELECT Cart_ID FROM Shopping_Carts WHERE Customer_ID = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("i", $customer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        // Create new cart
        $create_cart_sql = "INSERT INTO Shopping_Carts (Customer_ID) VALUES (?)";
        $create_cart_stmt = $conn->prepare($create_cart_sql);
        $create_cart_stmt->bind_param("i", $customer_id);
        $create_cart_stmt->execute();
        $cart_id = $conn->insert_id;
    } else {
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['Cart_ID'];
    }
    
    // Check if item already exists in cart
    $item_check_sql = "SELECT Cart_Item_ID, Quantity FROM Cart_Items WHERE Cart_ID = ? AND Product_ID = ?";
    $item_check_stmt = $conn->prepare($item_check_sql);
    $item_check_stmt->bind_param("ii", $cart_id, $product_id);
    $item_check_stmt->execute();
    $item_result = $item_check_stmt->get_result();
    
    if ($item_result->num_rows > 0) {
        // Update existing item - check total quantity against stock
        $existing_item = $item_result->fetch_assoc();
        $new_quantity = $existing_item['Quantity'] + $quantity;
        
        // Check if new total quantity exceeds available stock
        if ($new_quantity > $product['Stock']) {
            throw new Exception("Cannot add " . $quantity . " items. Only " . ($product['Stock'] - $existing_item['Quantity']) . " more items available in stock");
        }
        
        $update_sql = "UPDATE Cart_Items SET Quantity = ? WHERE Cart_Item_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_quantity, $existing_item['Cart_Item_ID']);
        $update_stmt->execute();
    } else {
        // Add new item to cart - already checked stock above
        $insert_sql = "INSERT INTO Cart_Items (Cart_ID, Product_ID, Quantity) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $cart_id, $product_id, $quantity);
        $insert_stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to cart successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("Error in add-to-cart.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close statements and connection
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($cart_stmt)) $cart_stmt->close();
    if (isset($create_cart_stmt)) $create_cart_stmt->close();
    if (isset($item_check_stmt)) $item_check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($insert_stmt)) $insert_stmt->close();
    if (isset($conn)) $conn->close();
}
?>


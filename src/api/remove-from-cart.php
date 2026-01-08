<?php
/**
 * Remove from Cart API Endpoint
 * Remove a specific product from customer's cart
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

$input = getJsonInput();
requireFields($input, ['customer_id', 'product_id']);

$customer_id = getIntParam($input, 'customer_id');
$product_id = getIntParam($input, 'product_id');

if ($customer_id <= 0 || $product_id <= 0) {
    sendError('Invalid input values');
}

try {
    $conn = db(false); // Use MySQLi
    
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


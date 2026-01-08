<?php
/**
 * Clear Cart API Endpoint
 * Remove all items from customer's cart
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

$input = getJsonInput();
requireFields($input, ['customer_id']);

$customer_id = getIntParam($input, 'customer_id');

if ($customer_id <= 0) {
    sendError('Invalid customer ID.');
}

try {
    $conn = db(false); // Use MySQLi

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
            sendSuccess('Cart cleared successfully.');
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


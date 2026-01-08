<?php
/**
 * Get Product Stock API Endpoint
 * Get current stock for a specific product
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['GET']);

// Get product ID from query parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    sendError('Invalid product ID');
}

try {
    $conn = db(false); // Use MySQLi
    
    // Get product stock
    $sql = "SELECT Product_ID, Title, Stock, Price FROM Products WHERE Product_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendNotFound('Product not found');
    }
    
    $product = $result->fetch_assoc();
    sendSuccess('Product stock retrieved', ['product' => $product]);
    
} catch (Exception $e) {
    sendServerError('Failed to retrieve product stock', $e);
}
?>


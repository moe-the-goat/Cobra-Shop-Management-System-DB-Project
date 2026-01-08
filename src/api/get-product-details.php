<?php
/**
 * Get Product Details API Endpoint
 * Retrieve detailed information for a specific product
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['GET', 'POST']);

// Get product ID from query parameter
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    sendError('Invalid product ID');
}

try {
    $conn = db(false); // Use MySQLi
    
    // Query to get product details
    $sql = "SELECT 
                p.Product_ID,
                p.Title,
                p.Price,
                p.Stock,
                p.Description,
                p.Created_At,
                p.Image_Path,
                c.Name as category_name
            FROM Products p
            LEFT JOIN Category c ON p.Category_ID = c.Category_ID
            WHERE p.Product_ID = ?";
        
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendNotFound('Product not found');
    }
    
    $product = $result->fetch_assoc();
    
    // Add placeholder image URL
    $product['image_url'] = $product['Image_Path'];
    
    // Add original price for discount calculation (demo purposes)
    if (rand(1, 3) === 1) {
        $product['original_price'] = $product['Price'] * 1.2;
    }
    
    echo json_encode([
        'status' => 'success',
        'product' => $product
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-product-details.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve product details'
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>


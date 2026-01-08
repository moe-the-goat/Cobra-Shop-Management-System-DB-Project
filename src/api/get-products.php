<?php
/**
 * Get Products API Endpoint
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

try {
    // Use centralized database connection
    $conn = getDBMysqli();
    
    // Query to get products with category information and total sales
    $sql = "SELECT 
                p.Product_ID,
                p.Title,
                p.Price,
                p.Stock,
                p.Description,
                p.Created_At,
                p.Image_Path,
                c.Name as category_name,
                COALESCE(SUM(sod.Quantity), 0) as total_sold
            FROM Products p
            LEFT JOIN Category c ON p.Category_ID = c.Category_ID
            LEFT JOIN Sales_Order_Details sod ON p.Product_ID = sod.Product_ID
            GROUP BY p.Product_ID
            ORDER BY p.Created_At DESC";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Add placeholder image URL (you can modify this based on your image storage)
        $row['image_url'] = $row['Image_Path'];
        
        // Add original price for discount calculation (you can modify this logic)
        if (rand(1, 3) === 1) { // Randomly add original price for demo
            $row['original_price'] = $row['Price'] * 1.2; // 20% discount
        }
        
        $products[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-products.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve products'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

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

// Get product ID from query parameter
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid product ID'
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
    
    // Query to get product details
    $sql = "SELECT 
                p.Product_ID,
                p.Title,
                p.Price,
                p.Stock,
                p.Description,
                p.Created_At,
                p.Image_Path, -- <<< ADD THIS LINE
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
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
        exit;
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


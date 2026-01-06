<?php
// get-product-stock.php - Get current stock for a specific product

// Database configuration
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get product ID from query parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

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
    
    // Get product stock
    $sql = "SELECT Product_ID, Title, Stock, Price FROM Products WHERE Product_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
    } else {
        $product = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'product' => $product
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in get-product-stock.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve product stock'
    ]);
} finally {
    // Close statement and connection
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>


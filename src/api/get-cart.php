<?php
/**
 * Get Cart API Endpoint
 * Retrieve cart items for a customer
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

// Get customer ID from query parameter or POST data
$customer_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['customer_id'])) {
    $customer_id = intval($_GET['customer_id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['customer_id'])) {
        $customer_id = intval($input['customer_id']);
    }
}

// Validate customer ID
if (!$customer_id || $customer_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Valid customer ID is required'
    ]);
    exit;
}

try {
    // Use centralized database connection
    $conn = getDBMysqli();
    
    // Get cart items with product details
    $sql = "SELECT 
                ci.Cart_Item_ID,
                ci.Product_ID,
                ci.Quantity,
                ci.Added_Date,
                p.Title,
                p.Price,
                p.image_path,
                p.Stock,
                c.Name as category_name
            FROM Shopping_Carts sc
            JOIN Cart_Items ci ON sc.Cart_ID = ci.Cart_ID
            JOIN Products p ON ci.Product_ID = p.Product_ID
            JOIN category c ON p.Category_ID = c.Category_ID
            WHERE sc.Customer_ID = ?
            ORDER BY ci.Added_Date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $total_amount = 0;
    $total_items = 0;
    
    while ($row = $result->fetch_assoc()) {
        $item_total = $row['Price'] * $row['Quantity'];
        $total_amount += $item_total;
        $total_items += $row['Quantity'];
        
        $cart_items[] = [
            'Cart_Item_ID' => $row['Cart_Item_ID'],
            'Product_ID' => $row['Product_ID'],
            'Title' => $row['Title'],
            'Price' => floatval($row['Price']),
            'Quantity' => $row['Quantity'],
            'Stock' => $row['Stock'],
            'image_path' => $row['image_path'],
            'category_name' => $row['category_name'],
            'item_total' => number_format($item_total, 2),
            'Added_Date' => $row['Added_Date']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'cart_items' => $cart_items,
        'summary' => [
            'total_items' => $total_items,
            'total_amount' => number_format($total_amount, 2),
            'item_count' => count($cart_items)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-cart.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve cart items'
    ]);
} finally {
    // Close statements and connection
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>


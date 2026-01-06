<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cobra_shop_project";

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (!isset($input['order_id']) || !isset($input['status'])) {
        throw new Exception('Order ID and status are required');
    }
    
    $order_id = intval($input['order_id']);
    $new_status = trim($input['status']);
    $note = isset($input['note']) ? trim($input['note']) : '';
    
    // Validate order ID
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }
    
    // Validate status
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Returned'];
    if (!in_array($new_status, $allowed_statuses)) {
        throw new Exception('Invalid status. Allowed statuses: ' . implode(', ', $allowed_statuses));
    }
    
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $conn->beginTransaction();
    
    // First, verify the order exists and get current details
    $check_sql = "SELECT so.SO_ID, so.Status as Current_Status, so.Staff_ID, so.Customer_ID, 
                         c.Name as Customer_Name, s.Name as Staff_Name
                  FROM Sales_Orders so
                  INNER JOIN Customers c ON so.Customer_ID = c.Customer_ID
                  LEFT JOIN Staff s ON so.Staff_ID = s.Staff_ID
                  WHERE so.SO_ID = :order_id";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    $order = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Check if the status is actually changing
    if ($order['Current_Status'] === $new_status) {
        throw new Exception('Order is already in ' . $new_status . ' status');
    }
    
    // Update the order status
    $update_sql = "UPDATE Sales_Orders 
                   SET Status = :status, Updated_At = CURRENT_TIMESTAMP 
                   WHERE SO_ID = :order_id";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
    $update_stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $update_stmt->execute();
    
    // Check if the update was successful
    if ($update_stmt->rowCount() === 0) {
        throw new Exception('Failed to update order status');
    }
    
    // Log the status change (optional - create a status history table if needed)
    // For now, we'll just include it in the response
    
    // If status is changed to 'Cancelled' or 'Returned', we might want to restore stock
    if (in_array($new_status, ['Cancelled', 'Returned'])) {
        // Get order details to restore stock
        $details_sql = "SELECT Product_ID, Quantity 
                        FROM Sales_Order_Details 
                        WHERE SO_ID = :order_id";
        
        $details_stmt = $conn->prepare($details_sql);
        $details_stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $details_stmt->execute();
        
        $order_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Restore stock for each product
        foreach ($order_details as $detail) {
            $restore_stock_sql = "UPDATE Products 
                                  SET Stock = Stock + :quantity 
                                  WHERE Product_ID = :product_id";
            
            $restore_stmt = $conn->prepare($restore_stock_sql);
            $restore_stmt->bindParam(':quantity', $detail['Quantity'], PDO::PARAM_INT);
            $restore_stmt->bindParam(':product_id', $detail['Product_ID'], PDO::PARAM_INT);
            $restore_stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'order_id' => $order_id,
        'old_status' => $order['Current_Status'],
        'new_status' => $new_status,
        'customer_name' => $order['Customer_Name'],
        'staff_name' => $order['Staff_Name'],
        'note' => $note,
        'updated_at' => date('Y-m-d H:i:s')
    ]);

} catch(PDOException $e) {
    // Rollback transaction on database error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    // Rollback transaction on general error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn = null;
?>


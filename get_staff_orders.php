<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cobra_shop_project";

try {
    // Get staff_id from query parameters
    if (!isset($_GET['staff_id']) || empty($_GET['staff_id'])) {
        throw new Exception('Staff ID is required');
    }
    
    $staff_id = intval($_GET['staff_id']);
    
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First, get staff information
    $staff_sql = "SELECT 
                    Staff_ID,
                    Name,
                    Position,
                    assignment_count
                  FROM Staff 
                  WHERE Staff_ID = :staff_id AND Position = 'Sales Management'";
    
    $staff_stmt = $conn->prepare($staff_sql);
    $staff_stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $staff_stmt->execute();
    
    $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        throw new Exception('Staff member not found or not in Sales Management position');
    }

    // Query to get all sales orders assigned to this staff member
    $orders_sql = "SELECT 
                    so.SO_ID,
                    so.Customer_ID,
                    c.Name as Customer_Name,
                    c.Email as Customer_Email,
                    so.Order_Date,
                    so.Status,
                    so.Total_Amount,
                    so.Created_At,
                    so.Updated_At
                   FROM Sales_Orders so
                   INNER JOIN Customers c ON so.Customer_ID = c.Customer_ID
                   WHERE so.Staff_ID = :staff_id
                   ORDER BY so.Order_Date DESC, so.SO_ID DESC";
    
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $orders_stmt->execute();
    
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'orders' => $orders,
        'order_count' => count($orders)
    ]);

} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    // Return general error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn = null;
?>


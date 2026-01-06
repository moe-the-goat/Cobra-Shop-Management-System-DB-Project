<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cobra_shop_project";

/**
 * Automatically assign a sales order to a staff member with balanced distribution
 * Returns the Staff_ID of the assigned staff member
 */
function assignSalesOrderToStaff($conn) {
    try {
        // Get the staff member with the lowest assignment count
        $sql = "SELECT Staff_ID, Name, assignment_count 
                FROM Staff 
                WHERE Position = 'Sales Management' 
                ORDER BY assignment_count ASC, Staff_ID ASC 
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            throw new Exception('No sales management staff available for assignment');
        }
        
        // Increment the assignment count for this staff member
        $update_sql = "UPDATE Staff 
                       SET assignment_count = assignment_count + 1 
                       WHERE Staff_ID = :staff_id";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindParam(':staff_id', $staff['Staff_ID'], PDO::PARAM_INT);
        $update_stmt->execute();
        
        return $staff['Staff_ID'];
        
    } catch(Exception $e) {
        throw new Exception('Error assigning staff: ' . $e->getMessage());
    }
}

/**
 * Create a new sales order with automatic staff assignment
 */
function createSalesOrderWithAssignment($conn, $customer_id, $total_amount) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Assign staff member
        $assigned_staff_id = assignSalesOrderToStaff($conn);
        
        // Create the sales order
        $sql = "INSERT INTO Sales_Orders (Customer_ID, Staff_ID, Order_Date, Status, Total_Amount) 
                VALUES (:customer_id, :staff_id, CURDATE(), 'Pending', :total_amount)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':staff_id', $assigned_staff_id, PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
        $stmt->execute();
        
        $order_id = $conn->lastInsertId();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'order_id' => $order_id,
            'assigned_staff_id' => $assigned_staff_id
        ];
        
    } catch(Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
}

// Handle POST request for creating new sales order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        if (!isset($input['customer_id']) || !isset($input['total_amount'])) {
            throw new Exception('Customer ID and total amount are required');
        }
        
        $customer_id = intval($input['customer_id']);
        $total_amount = floatval($input['total_amount']);
        
        if ($customer_id <= 0) {
            throw new Exception('Invalid customer ID');
        }
        
        if ($total_amount < 0) {
            throw new Exception('Invalid total amount');
        }
        
        // Create connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create sales order with automatic assignment
        $result = createSalesOrderWithAssignment($conn, $customer_id, $total_amount);
        
        // Get assigned staff information
        $staff_sql = "SELECT Name FROM Staff WHERE Staff_ID = :staff_id";
        $staff_stmt = $conn->prepare($staff_sql);
        $staff_stmt->bindParam(':staff_id', $result['assigned_staff_id'], PDO::PARAM_INT);
        $staff_stmt->execute();
        $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Sales order created and assigned successfully',
            'order_id' => $result['order_id'],
            'assigned_staff_id' => $result['assigned_staff_id'],
            'assigned_staff_name' => $staff['Name']
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    // Close connection
    $conn = null;
}

// Handle GET request for testing assignment logic
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Create connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get current staff assignment counts
        $sql = "SELECT Staff_ID, Name, assignment_count 
                FROM Staff 
                WHERE Position = 'Sales Management' 
                ORDER BY assignment_count ASC, Staff_ID ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Current staff assignment status',
            'staff' => $staff
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    // Close connection
    $conn = null;
}
?>


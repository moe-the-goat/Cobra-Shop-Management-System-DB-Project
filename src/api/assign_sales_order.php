<?php
require_once __DIR__ . '/../includes/bootstrap.php';

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
    initApi(['POST']);
    
    try {
        // Get JSON input
        $input = getJsonInput();
        
        if (!$input) {
            sendValidationError(['json' => 'Invalid JSON input']);
        }
        
        // Validate required fields
        if (!isset($input['customer_id']) || !isset($input['total_amount'])) {
            sendValidationError(['customer_id' => 'Required', 'total_amount' => 'Required']);
        }
        
        $customer_id = intval($input['customer_id']);
        $total_amount = floatval($input['total_amount']);
        
        if ($customer_id <= 0) {
            sendValidationError(['customer_id' => 'Invalid customer ID']);
        }
        
        if ($total_amount < 0) {
            sendValidationError(['total_amount' => 'Invalid total amount']);
        }
        
        // Get database connection
        $conn = db();
        
        // Create sales order with automatic assignment
        $result = createSalesOrderWithAssignment($conn, $customer_id, $total_amount);
        
        // Get assigned staff information
        $staff_sql = "SELECT Name FROM Staff WHERE Staff_ID = :staff_id";
        $staff_stmt = $conn->prepare($staff_sql);
        $staff_stmt->bindParam(':staff_id', $result['assigned_staff_id'], PDO::PARAM_INT);
        $staff_stmt->execute();
        $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return success response
        sendSuccess([
            'message' => 'Sales order created and assigned successfully',
            'order_id' => $result['order_id'],
            'assigned_staff_id' => $result['assigned_staff_id'],
            'assigned_staff_name' => $staff['Name']
        ]);
        
    } catch(PDOException $e) {
        sendServerError('Database error: ' . $e->getMessage());
    } catch(Exception $e) {
        sendError('Error: ' . $e->getMessage());
    }
}

// Handle GET request for testing assignment logic
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    initApi(['GET']);
    
    try {
        $conn = db();
        
        // Get current staff assignment counts
        $sql = "SELECT Staff_ID, Name, assignment_count 
                FROM Staff 
                WHERE Position = 'Sales Management' 
                ORDER BY assignment_count ASC, Staff_ID ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccess([
            'message' => 'Current staff assignment status',
            'staff' => $staff
        ]);
        
    } catch(PDOException $e) {
        sendServerError('Database error: ' . $e->getMessage());
    } catch(Exception $e) {
        sendError('Error: ' . $e->getMessage());
    }
}
?>


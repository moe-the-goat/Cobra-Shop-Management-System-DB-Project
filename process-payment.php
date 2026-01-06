<?php
// Enhanced process-payment.php with automatic staff assignment and card info saving

// Database configuration
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
        if (!$stmt) {
            throw new Exception("Failed to prepare staff assignment query: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();
        
        if (!$staff) {
            // If no sales management staff found, return null (order will be created without assignment)
            return null;
        }
        
        // Increment the assignment count for this staff member
        $update_sql = "UPDATE Staff 
                       SET assignment_count = assignment_count + 1 
                       WHERE Staff_ID = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Failed to prepare assignment count update: " . $conn->error);
        }
        
        $update_stmt->bind_param("i", $staff['Staff_ID']);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update assignment count: " . $update_stmt->error);
        }
        $update_stmt->close();
        
        return $staff['Staff_ID'];
        
    } catch(Exception $e) {
        // Log error but don't fail the entire order process
        error_log("Staff assignment error: " . $e->getMessage());
        return null;
    }
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// --- START: MODIFIED CODE BLOCK (VALIDATION) ---
// Validate input, now including the new fields from payment.js
if (!$input || !isset($input['customer_id'], $input['card_number'], $input['card_name'], $input['amount'], $input['cart_items'], $input['subtotal'], $input['shipping_fee'], $input['tax_amount'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required payment information'
    ]);
    exit;
}
// --- END: MODIFIED CODE BLOCK (VALIDATION) ---


// --- START: MODIFIED CODE BLOCK (VARIABLE ASSIGNMENT) ---
$customer_id = intval($input['customer_id']);
$card_number_last4 = $input['card_number']; // Only last 4 digits
$card_name = trim($input['card_name']);
$expiry_month = isset($input['expiry_month']) ? intval($input['expiry_month']) : null;
$expiry_year = isset($input['expiry_year']) ? intval($input['expiry_year']) : null;
$amount = floatval($input['amount']); // This is the final total
$original_amount = isset($input['original_amount']) ? floatval($input['original_amount']) : $amount;
$discount_id = isset($input['discount_id']) ? intval($input['discount_id']) : null;
$discount_amount = isset($input['discount_amount']) ? floatval($input['discount_amount']) : 0;
$cart_items = $input['cart_items'];
$save_card = isset($input['save_card']) ? $input['save_card'] : false;

// Read the new values from the request payload
$subtotal = floatval($input['subtotal']);
$shipping_fee = floatval($input['shipping_fee']);
$tax_amount = floatval($input['tax_amount']);
// --- END: MODIFIED CODE BLOCK (VARIABLE ASSIGNMENT) ---


// Validate values
if ($customer_id <= 0 || $amount < 0 || empty($cart_items)) { // Allow $0 amount for free items with discounts
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid payment data'
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
    
    // Start transaction
    $conn->begin_transaction();
    
    // ... (Discount validation logic remains the same) ...
    
    // Automatically assign staff member
    $assigned_staff_id = assignSalesOrderToStaff($conn);
    
    // --- START: MODIFIED CODE BLOCK (DATABASE INSERT) ---
    // Create sales order with automatic staff assignment
    if ($assigned_staff_id) {
        $order_sql = "INSERT INTO Sales_Orders (Customer_ID, Staff_ID, Order_Date, Subtotal_Amount, Discount_Amount, Shipping_Fee, Tax_Amount, Total_Amount, Status) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, 'Processing')";
        $order_stmt = $conn->prepare($order_sql);
        // Correctly bind all the required parameters
        $order_stmt->bind_param("iiddddd", $customer_id, $assigned_staff_id, $subtotal, $discount_amount, $shipping_fee, $tax_amount, $amount);
    } else {
        // Create order without staff assignment if no sales management staff available
        // FIX: This query now includes all the necessary amount columns
        $order_sql = "INSERT INTO Sales_Orders (Customer_ID, Order_Date, Subtotal_Amount, Discount_Amount, Shipping_Fee, Tax_Amount, Total_Amount, Status) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, 'Processing')";
        $order_stmt = $conn->prepare($order_sql);
        // Correctly bind parameters for this case as well
        $order_stmt->bind_param("iddddd", $customer_id, $subtotal, $discount_amount, $shipping_fee, $tax_amount, $amount);
    }
    // --- END: MODIFIED CODE BLOCK (DATABASE INSERT) ---
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create sales order: " . $order_stmt->error);
    }
    $so_id = $conn->insert_id;
    $order_stmt->close();
    
    if (!$so_id) {
        throw new Exception("Failed to get new sales order ID");
    }
    
    // ... (rest of the script: add order items, update stock, create payment, clear cart, etc. remains the same) ...

    // Add order items
    $order_item_sql = "INSERT INTO Sales_Order_Details (SO_ID, Product_ID, Quantity, Unit_Price) VALUES (?, ?, ?, ?)";
    $order_item_stmt = $conn->prepare($order_item_sql);
    
    foreach ($cart_items as $item) {
        $product_id = intval($item['Product_ID']);
        $quantity = intval($item['Quantity']);
        $price = floatval($item['Price']);
        
        // Check stock availability
        $check_stock_sql = "SELECT Stock FROM Products WHERE Product_ID = ?";
        $check_stock_stmt = $conn->prepare($check_stock_sql);
        if (!$check_stock_stmt) {
            throw new Exception("Failed to prepare stock check statement: " . $conn->error);
        }
        $check_stock_stmt->bind_param("i", $product_id);
        $check_stock_stmt->execute();
        $stock_result = $check_stock_stmt->get_result()->fetch_assoc();
        $check_stock_stmt->close();

        if (!$stock_result || $stock_result['Stock'] < $quantity) {
            throw new Exception("Insufficient stock for product ID: " . $product_id . ". Only " . ($stock_result['Stock'] ?? 0) . " available.");
        }

        // Add item to order
        $order_item_stmt->bind_param("iiid", $so_id, $product_id, $quantity, $price);
        if (!$order_item_stmt->execute()) {
            throw new Exception("Failed to add item to order details: " . $order_item_stmt->error);
        }
    }
    $order_item_stmt->close();
    
    // Generate unique transaction ID
    $transaction_id = uniqid('txn_');
    
    // Create payment record
    $payment_sql = "INSERT INTO Payments (SO_ID, Discount_ID, Amount, Payment_Method, Payment_Status, Transaction_ID, Card_Last4, Card_Name, Expiry_Month, Expiry_Year) VALUES (?, ?, ?, 'Credit Card', 'Completed', ?, ?, ?, ?, ?)";
    $payment_stmt = $conn->prepare($payment_sql);
    
    if (!$payment_stmt) {
        throw new Exception("Failed to prepare payment statement: " . $conn->error);
    }
    
    $payment_stmt->bind_param("iidsssii", $so_id, $discount_id, $amount, $transaction_id, $card_number_last4, $card_name, $expiry_month, $expiry_year);
    
    if (!$payment_stmt->execute()) {
        throw new Exception("Failed to create payment record: " . $payment_stmt->error);
    }
    $payment_stmt->close();
    
    // Clear customer's cart
    $clear_cart_sql = "DELETE ci FROM Cart_Items ci 
                       INNER JOIN Shopping_Carts sc ON ci.Cart_ID = sc.Cart_ID 
                       WHERE sc.Customer_ID = ?";
    $clear_cart_stmt = $conn->prepare($clear_cart_sql);
    $clear_cart_stmt->bind_param("i", $customer_id);
    $clear_cart_stmt->execute();
    $clear_cart_stmt->close();
    
    // Get assigned staff name for response
    $assigned_staff_name = null;
    if ($assigned_staff_id) {
        $staff_sql = "SELECT Name FROM Staff WHERE Staff_ID = ?";
        $staff_stmt = $conn->prepare($staff_sql);
        $staff_stmt->bind_param("i", $assigned_staff_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result()->fetch_assoc();
        $staff_stmt->close();
        $assigned_staff_name = $staff_result ? $staff_result['Name'] : null;
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment processed successfully',
        'order_id' => $so_id,
        'transaction_id' => $transaction_id,
        'assigned_staff_id' => $assigned_staff_id,
        'assigned_staff_name' => $assigned_staff_name,
        'amount_paid' => $amount,
        'discount_applied' => $discount_amount > 0 ? $discount_amount : null
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
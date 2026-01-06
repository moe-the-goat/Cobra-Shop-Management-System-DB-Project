<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database configuration
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Customer ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get orders with payment information
    $sql = "SELECT 
            so.SO_ID, 
            so.Order_Date, 
            so.Status, 
            so.Subtotal_Amount,
            so.Discount_Amount,
            so.Shipping_Fee,
            so.Tax_Amount,
            so.Total_Amount,
            p.Payment_Method,
            p.Transaction_ID,
            p.Discount_ID
            FROM Sales_Orders so
            LEFT JOIN Payments p ON so.SO_ID = p.SO_ID
            WHERE so.Customer_ID = :customer_id 
            ORDER BY so.Order_Date DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['customer_id' => $customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare statements for details and discounts
    $details_sql = "SELECT p.Title, sod.Quantity, sod.Unit_Price
                    FROM Sales_Order_Details sod
                    JOIN Products p ON sod.Product_ID = p.Product_ID
                    WHERE sod.SO_ID = :so_id";
    $details_stmt = $pdo->prepare($details_sql);

    $discount_sql = "SELECT * FROM Discount_Management WHERE Discount_ID = :discount_id";
    $discount_stmt = $pdo->prepare($discount_sql);

    foreach ($orders as &$order) {
        // Get order items
        $details_stmt->execute(['so_id' => $order['SO_ID']]);
        $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
        $order['details'] = $details;

        // Calculate subtotal
        $subtotal = 0;
        foreach ($details as $item) {
            $subtotal += $item['Quantity'] * $item['Unit_Price'];
        }
        $order['subtotal'] = $subtotal;

        // Calculate shipping (logic from payment.js)
        $order['shipping'] = ($subtotal >= 100) ? 0.00 : 5.00;

        // Calculate tax (logic from payment.js)
        $order['tax'] = $subtotal * 0.08;

        // Calculate discount
        $order['discount_amount'] = 0;
        $order['discount_details'] = null;
        if ($order['Discount_ID']) {
            $discount_stmt->execute(['discount_id' => $order['Discount_ID']]);
            $discount_details = $discount_stmt->fetch(PDO::FETCH_ASSOC);
            if ($discount_details) {
                $order['discount_details'] = $discount_details;
                $pre_discount_total = $subtotal + $order['shipping'] + $order['tax'];

                if (strpos(strtolower($discount_details['Discount_Type']), 'percentage') !== false) {
                    $order['discount_amount'] = ($pre_discount_total * $discount_details['Discount_Value']) / 100;
                } else {
                    $order['discount_amount'] = $discount_details['Discount_Value'];
                }
                $order['discount_amount'] = min($order['discount_amount'], $pre_discount_total);
            }
        }
    }

    echo json_encode(['success' => true, 'orders' => $orders]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
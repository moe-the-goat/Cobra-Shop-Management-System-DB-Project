<?php
/**
 * Order Tracking API
 * Returns order details with timeline/status history
 */
require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['GET']);

try {
    // Get order_id from query params
    $orderId = getIntParam('order_id', 0);
    
    if ($orderId <= 0) {
        sendValidationError(['order_id' => 'Valid order ID is required']);
    }
    
    $conn = db();
    
    // Get order details with customer and staff info
    $orderSql = "SELECT 
                    so.SO_ID,
                    so.Customer_ID,
                    so.Staff_ID,
                    so.Order_Date,
                    so.Status,
                    so.Total_Amount,
                    so.Created_At,
                    so.Updated_At,
                    c.Name as customer_name,
                    c.Email as customer_email,
                    c.Phone as customer_phone,
                    c.Address as customer_address,
                    s.Name as staff_name
                 FROM Sales_Orders so
                 INNER JOIN Customers c ON so.Customer_ID = c.Customer_ID
                 LEFT JOIN Staff s ON so.Staff_ID = s.Staff_ID
                 WHERE so.SO_ID = :order_id";
    
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([':order_id' => $orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        sendNotFound('Order not found');
    }
    
    // Get order items
    $itemsSql = "SELECT 
                    sod.Product_ID,
                    sod.Quantity,
                    sod.Unit_Price,
                    (sod.Quantity * sod.Unit_Price) as subtotal,
                    p.Name as product_name,
                    p.Image_URL as product_image
                 FROM Sales_Order_Details sod
                 INNER JOIN Products p ON sod.Product_ID = p.Product_ID
                 WHERE sod.SO_ID = :order_id";
    
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->execute([':order_id' => $orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment info if exists
    $paymentSql = "SELECT 
                    Payment_ID,
                    Payment_Method,
                    Amount,
                    Payment_Status,
                    Payment_Date
                   FROM Payments 
                   WHERE SO_ID = :order_id
                   ORDER BY Payment_Date DESC
                   LIMIT 1";
    
    $paymentStmt = $conn->prepare($paymentSql);
    $paymentStmt->execute([':order_id' => $orderId]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate timeline based on current status
    $statusOrder = ['Pending', 'Processing', 'Shipped', 'Delivered'];
    $currentStatusIndex = array_search($order['Status'], $statusOrder);
    
    // Handle cancelled/returned orders
    $isCancelled = $order['Status'] === 'Cancelled';
    $isReturned = $order['Status'] === 'Returned';
    
    $timeline = [];
    
    // Order Placed
    $timeline[] = [
        'status' => 'Order Placed',
        'description' => 'Your order has been received',
        'icon' => 'fa-shopping-cart',
        'completed' => true,
        'date' => $order['Created_At'] ?? $order['Order_Date'],
        'active' => false
    ];
    
    // Processing
    $processingCompleted = $currentStatusIndex !== false && $currentStatusIndex >= 1;
    $timeline[] = [
        'status' => 'Processing',
        'description' => 'Order is being prepared',
        'icon' => 'fa-cog',
        'completed' => $processingCompleted && !$isCancelled,
        'date' => $processingCompleted ? null : null,
        'active' => $order['Status'] === 'Processing'
    ];
    
    // Shipped
    $shippedCompleted = $currentStatusIndex !== false && $currentStatusIndex >= 2;
    $timeline[] = [
        'status' => 'Shipped',
        'description' => 'Order has been shipped',
        'icon' => 'fa-truck',
        'completed' => $shippedCompleted && !$isCancelled,
        'date' => $shippedCompleted ? null : null,
        'active' => $order['Status'] === 'Shipped'
    ];
    
    // Delivered
    $deliveredCompleted = $currentStatusIndex !== false && $currentStatusIndex >= 3;
    $timeline[] = [
        'status' => 'Delivered',
        'description' => 'Order has been delivered',
        'icon' => 'fa-check-circle',
        'completed' => $deliveredCompleted && !$isCancelled,
        'date' => $deliveredCompleted ? $order['Updated_At'] : null,
        'active' => $order['Status'] === 'Delivered'
    ];
    
    // Add cancelled/returned if applicable
    if ($isCancelled) {
        $timeline[] = [
            'status' => 'Cancelled',
            'description' => 'Order has been cancelled',
            'icon' => 'fa-times-circle',
            'completed' => true,
            'date' => $order['Updated_At'],
            'active' => true,
            'type' => 'cancelled'
        ];
    }
    
    if ($isReturned) {
        $timeline[] = [
            'status' => 'Returned',
            'description' => 'Order has been returned',
            'icon' => 'fa-undo',
            'completed' => true,
            'date' => $order['Updated_At'],
            'active' => true,
            'type' => 'returned'
        ];
    }
    
    sendSuccess([
        'order' => [
            'id' => $order['SO_ID'],
            'date' => $order['Order_Date'],
            'status' => $order['Status'],
            'total' => (float)$order['Total_Amount'],
            'created_at' => $order['Created_At'],
            'updated_at' => $order['Updated_At']
        ],
        'customer' => [
            'name' => $order['customer_name'],
            'email' => $order['customer_email'],
            'phone' => $order['customer_phone'],
            'address' => $order['customer_address']
        ],
        'staff' => [
            'name' => $order['staff_name']
        ],
        'items' => $items,
        'payment' => $payment,
        'timeline' => $timeline
    ]);
    
} catch (PDOException $e) {
    sendServerError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>

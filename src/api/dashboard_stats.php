<?php
/**
 * Dashboard Statistics API
 * Provides comprehensive analytics for the admin dashboard
 */
require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['GET']);

try {
    $conn = db();
    
    // Get date range from query params (default: last 30 days)
    $days = getIntParam('days', 30);
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    
    // 1. Sales Overview
    $salesSql = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(Total_Amount), 0) as total_revenue,
                    COALESCE(AVG(Total_Amount), 0) as avg_order_value
                 FROM Sales_Orders 
                 WHERE Order_Date >= :start_date";
    $salesStmt = $conn->prepare($salesSql);
    $salesStmt->execute([':start_date' => $startDate]);
    $salesOverview = $salesStmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Orders by Status
    $statusSql = "SELECT 
                    Status,
                    COUNT(*) as count
                  FROM Sales_Orders 
                  WHERE Order_Date >= :start_date
                  GROUP BY Status";
    $statusStmt = $conn->prepare($statusSql);
    $statusStmt->execute([':start_date' => $startDate]);
    $ordersByStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Daily Sales (for chart)
    $dailySql = "SELECT 
                    DATE(Order_Date) as date,
                    COUNT(*) as orders,
                    COALESCE(SUM(Total_Amount), 0) as revenue
                 FROM Sales_Orders 
                 WHERE Order_Date >= :start_date
                 GROUP BY DATE(Order_Date)
                 ORDER BY date ASC";
    $dailyStmt = $conn->prepare($dailySql);
    $dailyStmt->execute([':start_date' => $startDate]);
    $dailySales = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Top Products
    $topProductsSql = "SELECT 
                        p.Product_ID,
                        p.Name,
                        p.Price,
                        COALESCE(SUM(sod.Quantity), 0) as total_sold,
                        COALESCE(SUM(sod.Quantity * sod.Unit_Price), 0) as total_revenue
                       FROM Products p
                       LEFT JOIN Sales_Order_Details sod ON p.Product_ID = sod.Product_ID
                       LEFT JOIN Sales_Orders so ON sod.SO_ID = so.SO_ID AND so.Order_Date >= :start_date
                       GROUP BY p.Product_ID, p.Name, p.Price
                       ORDER BY total_sold DESC
                       LIMIT 5";
    $topProductsStmt = $conn->prepare($topProductsSql);
    $topProductsStmt->execute([':start_date' => $startDate]);
    $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Category Performance
    $categorySql = "SELECT 
                        c.Category_ID,
                        c.Name as category_name,
                        COUNT(DISTINCT p.Product_ID) as product_count,
                        COALESCE(SUM(sod.Quantity), 0) as items_sold,
                        COALESCE(SUM(sod.Quantity * sod.Unit_Price), 0) as revenue
                    FROM Category c
                    LEFT JOIN Products p ON c.Category_ID = p.Category_ID
                    LEFT JOIN Sales_Order_Details sod ON p.Product_ID = sod.Product_ID
                    LEFT JOIN Sales_Orders so ON sod.SO_ID = so.SO_ID AND so.Order_Date >= :start_date
                    GROUP BY c.Category_ID, c.Name
                    ORDER BY revenue DESC";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->execute([':start_date' => $startDate]);
    $categoryPerformance = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Recent Orders
    $recentSql = "SELECT 
                    so.SO_ID,
                    so.Order_Date,
                    so.Status,
                    so.Total_Amount,
                    c.Name as customer_name,
                    c.Email as customer_email
                  FROM Sales_Orders so
                  INNER JOIN Customers c ON so.Customer_ID = c.Customer_ID
                  ORDER BY so.Order_Date DESC, so.SO_ID DESC
                  LIMIT 10";
    $recentStmt = $conn->prepare($recentSql);
    $recentStmt->execute();
    $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Customer Stats
    $customerSql = "SELECT 
                        COUNT(*) as total_customers,
                        COUNT(CASE WHEN created_at >= :start_date THEN 1 END) as new_customers
                    FROM Customers";
    $customerStmt = $conn->prepare($customerSql);
    $customerStmt->execute([':start_date' => $startDate]);
    $customerStats = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    // 8. Low Stock Products
    $lowStockSql = "SELECT 
                        Product_ID,
                        Name,
                        Stock,
                        Price
                    FROM Products 
                    WHERE Stock <= 10
                    ORDER BY Stock ASC
                    LIMIT 5";
    $lowStockStmt = $conn->prepare($lowStockSql);
    $lowStockStmt->execute();
    $lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 9. Staff Performance
    $staffSql = "SELECT 
                    s.Staff_ID,
                    s.Name,
                    s.assignment_count,
                    COUNT(so.SO_ID) as orders_handled,
                    COALESCE(SUM(so.Total_Amount), 0) as revenue_generated
                 FROM Staff s
                 LEFT JOIN Sales_Orders so ON s.Staff_ID = so.Staff_ID AND so.Order_Date >= :start_date
                 WHERE s.Position = 'Sales Management'
                 GROUP BY s.Staff_ID, s.Name, s.assignment_count
                 ORDER BY revenue_generated DESC";
    $staffStmt = $conn->prepare($staffSql);
    $staffStmt->execute([':start_date' => $startDate]);
    $staffPerformance = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess([
        'period' => [
            'days' => $days,
            'start_date' => $startDate,
            'end_date' => date('Y-m-d')
        ],
        'sales_overview' => [
            'total_orders' => (int)$salesOverview['total_orders'],
            'total_revenue' => (float)$salesOverview['total_revenue'],
            'avg_order_value' => round((float)$salesOverview['avg_order_value'], 2)
        ],
        'orders_by_status' => $ordersByStatus,
        'daily_sales' => $dailySales,
        'top_products' => $topProducts,
        'category_performance' => $categoryPerformance,
        'recent_orders' => $recentOrders,
        'customer_stats' => [
            'total_customers' => (int)$customerStats['total_customers'],
            'new_customers' => (int)$customerStats['new_customers']
        ],
        'low_stock_products' => $lowStockProducts,
        'staff_performance' => $staffPerformance
    ]);
    
} catch (PDOException $e) {
    sendServerError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>

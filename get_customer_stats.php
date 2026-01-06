<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Customer ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stats = [];

    // If a category is selected, get stats for that category
    if ($category_id) {
        // Total spent in this category
        $sql_cat_total = "SELECT SUM(sod.Quantity * sod.Unit_Price) as total_in_category
                          FROM Sales_Order_Details sod
                          JOIN Sales_Orders so ON sod.SO_ID = so.SO_ID
                          JOIN Products p ON sod.Product_ID = p.Product_ID
                          WHERE so.Customer_ID = :customer_id AND p.Category_ID = :category_id";
        $stmt = $pdo->prepare($sql_cat_total);
        $stmt->execute(['customer_id' => $customer_id, 'category_id' => $category_id]);
        $stats['category_specific']['total_spent'] = $stmt->fetchColumn() ?: 0;

        // Most bought product in this category
        $sql_most_bought = "SELECT p.Title, SUM(sod.Quantity) as total_quantity
                            FROM Sales_Order_Details sod
                            JOIN Sales_Orders so ON sod.SO_ID = so.SO_ID
                            JOIN Products p ON sod.Product_ID = p.Product_ID
                            WHERE so.Customer_ID = :customer_id AND p.Category_ID = :category_id
                            GROUP BY p.Product_ID, p.Title
                            ORDER BY total_quantity DESC
                            LIMIT 1";
        $stmt = $pdo->prepare($sql_most_bought);
        $stmt->execute(['customer_id' => $customer_id, 'category_id' => $category_id]);
        $stats['category_specific']['most_bought_product'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    } else {
        // General stats when no category is selected
        // Summary stats
        $sql_summary = "SELECT 
                    SUM(Total_Amount) as total_spent,
                    SUM(Discount_Amount) as total_savings,
                    COUNT(SO_ID) as total_orders
                FROM Sales_Orders 
                WHERE Customer_ID = :customer_id";

        $sql_discount_details = "SELECT 
                            dm.Name as discount_name,
                            COUNT(so.SO_ID) as times_used,
                            SUM(so.Discount_Amount) as total_saved
                         FROM Sales_Orders so
                         JOIN Payments p ON so.SO_ID = p.SO_ID
                         JOIN Discount_Management dm ON p.Discount_ID = dm.Discount_ID
                         WHERE so.Customer_ID = :customer_id AND so.Discount_Amount > 0
                         GROUP BY dm.Discount_ID, dm.Name";
                
        $stmt = $pdo->prepare($sql_summary);
        $stmt->execute(['customer_id' => $customer_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['summary'] = [
            'total_spent' => $summary['total_spent'] ?: 0,
            'total_orders' => $summary['total_orders'] ?: 0,
        ];

        // Spending by category (for pie chart)
        $sql_by_cat = "SELECT c.Name as category_name, c.Category_ID, SUM(sod.Quantity * sod.Unit_Price) as category_total
                       FROM Sales_Order_Details sod
                       JOIN Sales_Orders so ON sod.SO_ID = so.SO_ID
                       JOIN Products p ON sod.Product_ID = p.Product_ID
                       JOIN category c ON p.Category_ID = c.Category_ID
                       WHERE so.Customer_ID = :customer_id
                       GROUP BY c.Category_ID, c.Name
                       ORDER BY category_total DESC";
        $stmt = $pdo->prepare($sql_by_cat);
        $stmt->execute(['customer_id' => $customer_id]);
        $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Spending by month (for line chart)
        $sql_by_month = "SELECT DATE_FORMAT(Order_Date, '%Y-%m') as month, SUM(Total_Amount) as monthly_total
                         FROM Sales_Orders
                         WHERE Customer_ID = :customer_id AND Order_Date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                         GROUP BY month
                         ORDER BY month ASC";
        $stmt = $pdo->prepare($sql_by_month);
        $stmt->execute(['customer_id' => $customer_id]);
        $stats['by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Favorite category
        $stats['summary']['favorite_category'] = !empty($stats['by_category']) ? $stats['by_category'][0]['category_name'] : 'N/A';
    }

    echo json_encode(['success' => true, 'stats' => $stats]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
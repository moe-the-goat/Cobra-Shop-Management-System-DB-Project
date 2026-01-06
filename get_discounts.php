<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cobra_shop_project";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all discounts with usage count
    $sql = "SELECT 
                dm.*,
                COALESCE(usage_stats.usage_count, 0) as usage_count
            FROM Discount_Management dm
            LEFT JOIN (
                SELECT 
                    Discount_ID,
                    COUNT(*) as usage_count
                FROM Payments 
                WHERE Discount_ID IS NOT NULL
                GROUP BY Discount_ID
            ) usage_stats ON dm.Discount_ID = usage_stats.Discount_ID
            ORDER BY dm.Created_At DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedDiscounts = [];
    foreach ($discounts as $discount) {
        // Determine status based on dates
        $currentDate = new DateTime();
        $startDate = new DateTime($discount['Start_Date']);
        $endDate = new DateTime($discount['End_Date']);
        
        $status = $discount['Status'];
        if ($currentDate > $endDate) {
            $status = 'expired';
        }
        
        $formattedDiscounts[] = [
            'id' => $discount['Discount_ID'],
            'code' => $discount['Name'], // Using Name as code for now
            'name' => $discount['Name'],
            'description' => $discount['Description'],
            'type' => strtolower($discount['Discount_Type']) === 'percentage' ? 'percentage' : 'fixed',
            'value' => $discount['Discount_Value'],
            'minimum_purchase' => $discount['Minimum_Purchase'],
            'start_date' => $discount['Start_Date'],
            'end_date' => $discount['End_Date'],
            'usage_limit' => null, // Not in current schema, can be added
            'usage_count' => $discount['usage_count'],
            'status' => strtolower($status),
            'created_at' => $discount['Created_At']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'discounts' => $formattedDiscounts
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


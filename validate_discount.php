<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cobra_shop_project";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get form data
    $discountCode = trim($_POST['discount_code'] ?? '');
    $orderTotal = floatval($_POST['order_total'] ?? 0);
    
    if (empty($discountCode)) {
        echo json_encode(['success' => false, 'message' => 'Discount code is required']);
        exit;
    }
    
    if ($orderTotal <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order total']);
        exit;
    }
    
    // Get discount details
    $sql = "SELECT * FROM Discount_Management 
            WHERE Name = ? AND Status = 'Active' 
            AND Start_Date <= CURDATE() AND End_Date >= CURDATE()";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$discountCode]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$discount) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired discount code']);
        exit;
    }
    
    // Check minimum purchase requirement
    if ($orderTotal < $discount['Minimum_Purchase']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum purchase amount of $' . number_format($discount['Minimum_Purchase'], 2) . ' required'
        ]);
        exit;
    }
    
    // Calculate discount amount
    $discountAmount = 0;
    if (strtolower($discount['Discount_Type']) === 'percentage') {
        $discountAmount = ($orderTotal * $discount['Discount_Value']) / 100;
    } else {
        $discountAmount = $discount['Discount_Value'];
    }
    
    // Ensure discount doesn't exceed order total
    $discountAmount = min($discountAmount, $orderTotal);
    
    // Calculate final total
    $finalTotal = $orderTotal - $discountAmount;
    
    echo json_encode([
        'success' => true,
        'discount' => [
            'id' => $discount['Discount_ID'],
            'code' => $discount['Name'],
            'name' => $discount['Name'],
            'type' => strtolower($discount['Discount_Type']),
            'value' => $discount['Discount_Value'],
            'discount_amount' => round($discountAmount, 2),
            'original_total' => round($orderTotal, 2),
            'final_total' => round($finalTotal, 2),
            'savings' => round($discountAmount, 2)
        ]
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


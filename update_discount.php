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
    $discountId = intval($_POST['discountId'] ?? 0);
    $discountCode = trim($_POST['discountCode'] ?? '');
    $discountName = trim($_POST['discountName'] ?? '');
    $discountDescription = trim($_POST['discountDescription'] ?? '');
    $discountType = $_POST['discountType'] ?? '';
    $discountValue = floatval($_POST['discountValue'] ?? 0);
    $minimumPurchase = floatval($_POST['minimumPurchase'] ?? 0);
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $discountStatus = $_POST['discountStatus'] ?? 'active';
    
    // Validation
    if ($discountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid discount ID']);
        exit;
    }
    
    if (empty($discountCode) || empty($discountName) || empty($discountType) || 
        $discountValue <= 0 || $minimumPurchase < 0 || empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }
    
    if (!in_array($discountType, ['percentage', 'fixed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid discount type']);
        exit;
    }
    
    if ($discountType === 'percentage' && $discountValue > 100) {
        echo json_encode(['success' => false, 'message' => 'Percentage discount cannot exceed 100%']);
        exit;
    }
    
    // Check if discount code already exists (excluding current discount)
    $checkSql = "SELECT COUNT(*) FROM Discount_Management WHERE Name = ? AND Discount_ID != ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$discountCode, $discountId]);
    
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Discount code already exists']);
        exit;
    }
    
    // Update discount
    $sql = "UPDATE Discount_Management 
            SET Name = ?, Description = ?, Start_Date = ?, End_Date = ?, 
                Discount_Type = ?, Discount_Value = ?, Minimum_Purchase = ?, Status = ?
            WHERE Discount_ID = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $discountCode,
        $discountDescription,
        $startDate,
        $endDate,
        ucfirst($discountType) . ($discountType === 'fixed' ? ' Amount' : ''),
        $discountValue,
        $minimumPurchase,
        ucfirst($discountStatus),
        $discountId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Discount updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes made or discount not found'
        ]);
    }
    
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


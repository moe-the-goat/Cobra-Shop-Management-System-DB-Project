<?php
/**
 * Add Discount API Endpoint
 * Create a new discount code
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

try {
    $pdo = db(); // Use PDO
    
    // Get form data
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
    if (empty($discountCode) || empty($discountName) || empty($discountType) || 
        $discountValue <= 0 || $minimumPurchase < 0 || empty($startDate) || empty($endDate)) {
        sendValidationError('All required fields must be filled');
    }
    
    if (!in_array($discountType, ['percentage', 'fixed'])) {
        sendError('Invalid discount type');
    }
    
    if ($discountType === 'percentage' && $discountValue > 100) {
        sendError('Percentage discount cannot exceed 100%');
    }
    
    // Check if discount code already exists
    $checkSql = "SELECT COUNT(*) FROM Discount_Management WHERE Name = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$discountCode]);
    
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Discount code already exists']);
        exit;
    }
    
    // Insert new discount
    $sql = "INSERT INTO Discount_Management 
            (Name, Description, Start_Date, End_Date, Discount_Type, Discount_Value, Minimum_Purchase, Status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $discountCode,
        $discountDescription,
        $startDate,
        $endDate,
        ucfirst($discountType) . ($discountType === 'fixed' ? ' Amount' : ''),
        $discountValue,
        $minimumPurchase,
        ucfirst($discountStatus)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Discount created successfully',
        'discount_id' => $pdo->lastInsertId()
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


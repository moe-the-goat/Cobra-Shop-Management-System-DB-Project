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
    $discountId = intval($_POST['id'] ?? 0);
    
    if ($discountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid discount ID']);
        exit;
    }
    
    // Check if discount exists
    $checkSql = "SELECT Discount_ID FROM Discount_Management WHERE Discount_ID = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$discountId]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Discount not found']);
        exit;
    }
    
    // Delete discount
    $sql = "DELETE FROM Discount_Management WHERE Discount_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$discountId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Discount deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete discount'
        ]);
    }
    
} catch (PDOException $e) {
    // Check if it's a foreign key constraint error
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete discount: it has been used in payments'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


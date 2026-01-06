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
    $newStatus = $_POST['status'] ?? '';
    
    if ($discountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid discount ID']);
        exit;
    }
    
    if (!in_array($newStatus, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
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
    
    // Update status
    $sql = "UPDATE Discount_Management SET Status = ? WHERE Discount_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([ucfirst($newStatus), $discountId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Discount status updated successfully'
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


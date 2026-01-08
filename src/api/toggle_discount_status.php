<?php
require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

try {
    $pdo = db();
    
    // Get form data
    $discountId = intval($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    
    if ($discountId <= 0) {
        sendValidationError(['id' => 'Invalid discount ID']);
    }
    
    if (!in_array($newStatus, ['active', 'inactive'])) {
        sendValidationError(['status' => 'Invalid status']);
    }
    
    // Check if discount exists
    $checkSql = "SELECT Discount_ID FROM Discount_Management WHERE Discount_ID = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$discountId]);
    
    if ($checkStmt->rowCount() === 0) {
        sendNotFound('Discount not found');
    }
    
    // Update status
    $sql = "UPDATE Discount_Management SET Status = ? WHERE Discount_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([ucfirst($newStatus), $discountId]);
    
    if ($stmt->rowCount() > 0) {
        sendSuccess(['message' => 'Discount status updated successfully']);
    } else {
        sendError('No changes made or discount not found', 400);
    }
    
} catch (PDOException $e) {
    sendServerError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>


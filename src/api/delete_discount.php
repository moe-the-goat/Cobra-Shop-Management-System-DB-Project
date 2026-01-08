<?php
require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

try {
    $pdo = db();
    
    // Get form data
    $discountId = intval($_POST['id'] ?? 0);
    
    if ($discountId <= 0) {
        sendValidationError(['id' => 'Invalid discount ID']);
    }
    
    // Check if discount exists
    $checkSql = "SELECT Discount_ID FROM Discount_Management WHERE Discount_ID = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$discountId]);
    
    if ($checkStmt->rowCount() === 0) {
        sendNotFound('Discount not found');
    }
    
    // Delete discount
    $sql = "DELETE FROM Discount_Management WHERE Discount_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$discountId]);
    
    if ($stmt->rowCount() > 0) {
        sendSuccess(['message' => 'Discount deleted successfully']);
    } else {
        sendError('Failed to delete discount', 400);
    }
    
} catch (PDOException $e) {
    // Check if it's a foreign key constraint error
    if ($e->getCode() == '23000') {
        sendError('Cannot delete discount: it has been used in payments', 409);
    } else {
        sendServerError('Database error: ' . $e->getMessage());
    }
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>


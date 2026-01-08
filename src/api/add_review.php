<?php
/**
 * Add Product Review API
 * Allows customers to submit reviews for products they've purchased
 */
require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

try {
    $input = getJsonInput();
    
    if (!$input) {
        sendValidationError(['json' => 'Invalid JSON input']);
    }
    
    // Validate required fields
    $errors = [];
    
    $productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
    $customerId = isset($input['customer_id']) ? intval($input['customer_id']) : 0;
    $rating = isset($input['rating']) ? intval($input['rating']) : 0;
    $title = isset($input['title']) ? trim($input['title']) : '';
    $reviewText = isset($input['review_text']) ? trim($input['review_text']) : '';
    
    if ($productId <= 0) {
        $errors['product_id'] = 'Valid product ID is required';
    }
    
    if ($customerId <= 0) {
        $errors['customer_id'] = 'Valid customer ID is required';
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Rating must be between 1 and 5';
    }
    
    if (!empty($title) && strlen($title) > 100) {
        $errors['title'] = 'Title must be 100 characters or less';
    }
    
    if (!empty($reviewText) && strlen($reviewText) > 5000) {
        $errors['review_text'] = 'Review text must be 5000 characters or less';
    }
    
    if (!empty($errors)) {
        sendValidationError($errors);
    }
    
    $conn = db();
    
    // Check if product exists
    $productCheck = $conn->prepare("SELECT Product_ID FROM Products WHERE Product_ID = ?");
    $productCheck->execute([$productId]);
    if (!$productCheck->fetch()) {
        sendNotFound('Product not found');
    }
    
    // Check if customer exists
    $customerCheck = $conn->prepare("SELECT Customer_ID FROM Customers WHERE Customer_ID = ?");
    $customerCheck->execute([$customerId]);
    if (!$customerCheck->fetch()) {
        sendNotFound('Customer not found');
    }
    
    // Check if customer already reviewed this product
    $existingCheck = $conn->prepare(
        "SELECT Review_ID FROM Product_Reviews WHERE Product_ID = ? AND Customer_ID = ?"
    );
    $existingCheck->execute([$productId, $customerId]);
    if ($existingCheck->fetch()) {
        sendError('You have already reviewed this product', 409);
    }
    
    // Check if customer has purchased this product (for verified purchase badge)
    $purchaseCheck = $conn->prepare(
        "SELECT sod.SO_ID 
         FROM Sales_Order_Details sod
         INNER JOIN Sales_Orders so ON sod.SO_ID = so.SO_ID
         WHERE so.Customer_ID = ? AND sod.Product_ID = ? AND so.Status = 'Delivered'
         LIMIT 1"
    );
    $purchaseCheck->execute([$customerId, $productId]);
    $isVerifiedPurchase = $purchaseCheck->fetch() ? true : false;
    
    // Insert review
    $insertSql = "INSERT INTO Product_Reviews 
                  (Product_ID, Customer_ID, Rating, Title, Review_Text, Is_Verified_Purchase) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        $productId,
        $customerId,
        $rating,
        $title ?: null,
        $reviewText ?: null,
        $isVerifiedPurchase ? 1 : 0
    ]);
    
    $reviewId = $conn->lastInsertId();
    
    // Get updated product rating stats
    $statsSql = "SELECT avg_rating, review_count FROM Products WHERE Product_ID = ?";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute([$productId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess([
        'message' => 'Review submitted successfully',
        'review_id' => (int)$reviewId,
        'is_verified_purchase' => $isVerifiedPurchase,
        'product_stats' => [
            'avg_rating' => (float)$stats['avg_rating'],
            'review_count' => (int)$stats['review_count']
        ]
    ], 201);
    
} catch (PDOException $e) {
    // Check for duplicate entry error
    if ($e->getCode() == '23000') {
        sendError('You have already reviewed this product', 409);
    }
    sendServerError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>

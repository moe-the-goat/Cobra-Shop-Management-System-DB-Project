<?php
/**
 * Get Product Reviews API
 * Returns reviews for a specific product with pagination
 */
require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['GET']);

try {
    $productId = getIntParam('product_id', 0);
    
    if ($productId <= 0) {
        sendValidationError(['product_id' => 'Valid product ID is required']);
    }
    
    $conn = db();
    
    // Pagination
    $page = getIntParam('page', 1);
    $limit = getIntParam('limit', 10);
    $offset = ($page - 1) * $limit;
    
    // Sorting
    $sortBy = getStringParam('sort', 'newest');
    $orderClause = match($sortBy) {
        'oldest' => 'pr.Created_At ASC',
        'highest' => 'pr.Rating DESC, pr.Created_At DESC',
        'lowest' => 'pr.Rating ASC, pr.Created_At DESC',
        'helpful' => 'pr.Helpful_Count DESC, pr.Created_At DESC',
        default => 'pr.Created_At DESC'
    };
    
    // Get product info with rating stats
    $productSql = "SELECT 
                        Product_ID, Name, avg_rating, review_count
                   FROM Products 
                   WHERE Product_ID = :product_id";
    $productStmt = $conn->prepare($productSql);
    $productStmt->execute([':product_id' => $productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        sendNotFound('Product not found');
    }
    
    // Get rating distribution
    $ratingDistSql = "SELECT 
                        Rating,
                        COUNT(*) as count
                      FROM Product_Reviews 
                      WHERE Product_ID = :product_id AND Is_Approved = TRUE
                      GROUP BY Rating
                      ORDER BY Rating DESC";
    $ratingDistStmt = $conn->prepare($ratingDistSql);
    $ratingDistStmt->execute([':product_id' => $productId]);
    $ratingDistribution = $ratingDistStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format distribution (ensure all ratings 1-5 are present)
    $distribution = [];
    for ($i = 5; $i >= 1; $i--) {
        $found = false;
        foreach ($ratingDistribution as $rd) {
            if ((int)$rd['Rating'] === $i) {
                $distribution[$i] = (int)$rd['count'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $distribution[$i] = 0;
        }
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM Product_Reviews 
                 WHERE Product_ID = :product_id AND Is_Approved = TRUE";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute([':product_id' => $productId]);
    $totalReviews = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get reviews
    $reviewsSql = "SELECT 
                        pr.Review_ID,
                        pr.Rating,
                        pr.Title,
                        pr.Review_Text,
                        pr.Is_Verified_Purchase,
                        pr.Helpful_Count,
                        pr.Created_At,
                        c.Name as customer_name
                   FROM Product_Reviews pr
                   INNER JOIN Customers c ON pr.Customer_ID = c.Customer_ID
                   WHERE pr.Product_ID = :product_id AND pr.Is_Approved = TRUE
                   ORDER BY {$orderClause}
                   LIMIT :limit OFFSET :offset";
    
    $reviewsStmt = $conn->prepare($reviewsSql);
    $reviewsStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $reviewsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $reviewsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $reviewsStmt->execute();
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mask customer names for privacy (show first name + last initial)
    foreach ($reviews as &$review) {
        $nameParts = explode(' ', $review['customer_name']);
        if (count($nameParts) > 1) {
            $review['customer_name'] = $nameParts[0] . ' ' . substr(end($nameParts), 0, 1) . '.';
        }
    }
    
    sendSuccess([
        'product' => [
            'id' => (int)$product['Product_ID'],
            'name' => $product['Name'],
            'avg_rating' => (float)$product['avg_rating'],
            'review_count' => (int)$product['review_count']
        ],
        'rating_distribution' => $distribution,
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_reviews' => $totalReviews,
            'total_pages' => ceil($totalReviews / $limit)
        ]
    ]);
    
} catch (PDOException $e) {
    sendServerError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>

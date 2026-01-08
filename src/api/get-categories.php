<?php
/**
 * Get Categories API Endpoint
 * @package CobraShop
 */

// Use centralized configuration
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

try {
    // Use centralized database connection
    $conn = getDBMysqli();
    
    $sql = "SELECT Category_ID, Name, Description FROM Category ORDER BY Name";
    $result = $conn->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load categories'
    ]);
}
?>

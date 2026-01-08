<?php
/**
 * Get Staff API Endpoint
 * Retrieve sales management staff
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['GET']);

try {
    $pdo = db(); // Use PDO

    // Query to get all staff members with 'Sales Management' position
    $sql = "SELECT 
                s.Staff_ID,
                s.Name,
                s.Position,
                s.assignment_count,
                s.Created_At,
                s.Updated_At
            FROM Staff s 
            WHERE s.Position = 'Sales Management'
            ORDER BY s.assignment_count ASC, s.Staff_ID ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess('Staff retrieved successfully', [
        'staff' => $staff,
        'count' => count($staff)
    ]);

} catch(Exception $e) {
    sendServerError('Failed to retrieve staff', $e);
}
?>
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn = null;
?>


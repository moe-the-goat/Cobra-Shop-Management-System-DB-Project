<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cobra_shop_project";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'count' => count($staff)
    ]);

} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    // Return general error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn = null;
?>


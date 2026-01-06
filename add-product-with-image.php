<?php
$db_host = "127.0.0.1";
$db_port = 3306;
$db_user = "root";
$db_pass = "";
$db_name = "cobra_shop_project";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Validate required fields
$required_fields = ['category_id', 'title', 'price', 'stock'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8");
    $conn->begin_transaction();
    
    // Step 1: Insert product details WITHOUT the image path first
    $sql = "INSERT INTO Products (Category_ID, Title, Price, Stock, Description) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    $description = $_POST['description'] ?? '';

    $stmt->bind_param("isdis", 
        $_POST['category_id'],
        $_POST['title'],
        $_POST['price'],
        $_POST['stock'],
        $description
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert product details");
    }
    
    $product_id = $conn->insert_id;
    
    // Step 2: Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_result = uploadProductImage($product_id, $_FILES['image']);
        $image_path = $image_result['full_url']; // Get the path from the upload function

        // --- START: THIS IS THE NEW/FIXED CODE BLOCK ---
        // Step 3: Update the product row with the new image path
        $update_sql = "UPDATE Products SET Image_Path = ? WHERE Product_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        $update_stmt->bind_param("si", $image_path, $product_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update product with image path: " . $update_stmt->error);
        }
        $update_stmt->close();
        // --- END: NEW/FIXED CODE BLOCK ---
    }
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Product added successfully',
        'product_id' => $product_id
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Error in add-product-with-image.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}

function uploadProductImage($product_id, $uploaded_file) {
    $upload_dir = 'images/products/full-size/';
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($uploaded_file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and WebP are allowed.');
    }
    
    if ($uploaded_file['size'] > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
    $filename = $product_id . '_' . time() . '.' . $file_extension;
    
    $full_path = $upload_dir . $filename;
    
    if (move_uploaded_file($uploaded_file['tmp_name'], $full_path)) {
        return ['full_url' => $full_path, 'filename' => $filename];
    } else {
        throw new Exception('Failed to upload file');
    }
}
// Note: The createThumbnail function was removed for simplicity as it was not essential for fixing the main issue.
// You can add it back if you need thumbnails.
?>
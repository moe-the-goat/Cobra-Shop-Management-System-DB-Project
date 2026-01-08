<?php
/**
 * Newsletter Subscribe API Endpoint
 * Subscribe email to newsletter
 * @package CobraShop
 */

require_once __DIR__ . '/../includes/bootstrap.php';

initApi(['POST']);

$input = getJsonInput();
requireFields($input, ['email']);

$email = getStringParam($input, 'email');

if (!isValidEmail($email)) {
    sendError('Invalid email format');
}

try {
    $conn = db(false); // Use MySQLi
    
    // Check if newsletter subscribers table exists, if not create it
    $create_table_sql = "CREATE TABLE IF NOT EXISTS Newsletter_Subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'unsubscribed') DEFAULT 'active'
    )";
    
    $conn->query($create_table_sql);
    
    // Check if email already exists
    $check_sql = "SELECT id, status FROM Newsletter_Subscribers WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $subscriber = $check_result->fetch_assoc();
        if ($subscriber['status'] === 'active') {
            sendError('Email is already subscribed to newsletter');
        } else {
            // Reactivate subscription
            $update_sql = "UPDATE Newsletter_Subscribers SET status = 'active', subscribed_at = CURRENT_TIMESTAMP WHERE email = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $email);
            $update_stmt->execute();
            sendSuccess('Successfully resubscribed to newsletter!');
        }
    } else {
        // Insert new subscriber
        $insert_sql = "INSERT INTO Newsletter_Subscribers (email) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $email);
        
        if ($insert_stmt->execute()) {
            sendSuccess('Successfully subscribed to newsletter!');
        } else {
            throw new Exception('Failed to subscribe');
        }
    }
    
} catch (Exception $e) {
    sendServerError('Failed to subscribe to newsletter', $e);
}
?>            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $email);
            $update_stmt->execute();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Newsletter subscription reactivated successfully'
            ]);
        }
    } else {
        // Add new subscriber
        $insert_sql = "INSERT INTO Newsletter_Subscribers (email) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $email);
        $insert_stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully subscribed to newsletter'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in newsletter-subscribe.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to subscribe to newsletter'
    ]);
} finally {
    // Close statements and connection
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($insert_stmt)) $insert_stmt->close();
    if (isset($conn)) $conn->close();
}
?>


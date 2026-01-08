<?php
/**
 * Check Session Status API Endpoint
 * Returns current user session status
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

initSession();

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo json_encode([
        'status' => 'success',
        'logged_in' => true,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'logged_in' => false,
        'user' => null
    ]);
}
?>

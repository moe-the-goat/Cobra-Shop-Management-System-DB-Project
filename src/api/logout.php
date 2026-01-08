<?php
/**
 * Logout API Endpoint
 * Destroys user session
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests for logout (security best practice)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(API_ERROR, 'Method not allowed', [], 405);
}

// Logout user
logoutUser();

echo json_encode([
    'status' => 'success',
    'message' => 'Logged out successfully'
]);
?>

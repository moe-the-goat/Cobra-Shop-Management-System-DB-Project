<?php
/**
 * Get CSRF Token API Endpoint
 * Returns a CSRF token for use in AJAX requests
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiResponse(API_ERROR, 'Method not allowed', [], 405);
}

// Generate and return CSRF token
$tokenData = getCsrfTokenData();

echo json_encode([
    'status' => 'success',
    'csrf_token' => $tokenData['token'],
    'csrf_name' => $tokenData['name']
]);
?>

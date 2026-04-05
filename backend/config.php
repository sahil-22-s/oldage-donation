<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'eldercare');
define('DB_USER', 'postgres');
define('DB_PASSWORD', 'your_password_here'); // Change this to your PostgreSQL password

// Create Database Connection
try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// UPI / Receipt Settings - update these for your merchant VPA/name and base URL
define('UPI_VPA', 'merchant@bank'); // change to your receiving VPA
define('UPI_NAME', 'ElderCare'); // payee name shown in UPI apps
define('BASE_URL', 'http://localhost'); // change to your site base URL (no trailing slash)
define('RECEIPT_DIR', __DIR__ . '/receipts');
?>

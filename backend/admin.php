<?php
require_once 'config.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Admin Login
if ($action === 'login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }
    
    try {
        $query = "SELECT id, username, password FROM admins WHERE username = :username LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'admin_id' => $admin['id']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Admin Logout
elseif ($action === 'logout' && $method === 'POST') {
    session_destroy();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Logout successful']);
}

// Check Admin Session
elseif ($action === 'check-session' && $method === 'GET') {
    if (isset($_SESSION['admin_id'])) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'admin_username' => $_SESSION['admin_username']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'logged_in' => false]);
    }
}

// Get Dashboard Stats
elseif ($action === 'dashboard-stats' && $method === 'GET') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    try {
        // Total inventory items
        $query = "SELECT SUM(stock_quantity) as total_items FROM inventory";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $items_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total donations
        $query = "SELECT COUNT(*) as total_donations FROM donations";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $donations_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Pending visits
        $query = "SELECT COUNT(*) as pending_visits FROM visits WHERE status = :status";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':status' => 'Pending']);
        $visits_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_items' => $items_result['total_items'] ?? 0,
                'total_donations' => $donations_result['total_donations'] ?? 0,
                'pending_visits' => $visits_result['pending_visits'] ?? 0
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Default response
else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Ensure receipts directory exists (for HTML/PDF receipts)
if (!defined('RECEIPT_DIR')) {
    define('RECEIPT_DIR', __DIR__ . '/receipts');
}
if (!is_dir(RECEIPT_DIR)) {
    mkdir(RECEIPT_DIR, 0755, true);
}
 
// Helper: build absolute receipt URL
function receipt_url($filename) {
    $base = defined('BASE_URL') ? BASE_URL : '';
    $scriptPath = trim(dirname($_SERVER['SCRIPT_NAME']), '\\/');
    return rtrim($base, '/') . '/' . trim($scriptPath, '/') . '/receipts/' . $filename;
}
+
+// Simple PDF generator for receipts (text only)
+function generate_pdf_receipt($lines, $outputPath) {
+    // create a very minimal PDF containing the specified lines
+    $pdf = "%PDF-1.4\n";
+    $offsets = [];
+    
+    // object 1: catalog
+    $offsets[] = strlen($pdf);
+    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
+
+    // object 2: pages
+    $offsets[] = strlen($pdf);
+    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
+
+    // prepare text stream
+    $text = "BT\n/F1 12 Tf\n50 760 Td\n";
+    foreach ($lines as $line) {
+        // escape parentheses and backslashes
+        $esc = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
+        $text .= "($esc) Tj\n0 -14 Td\n";
+    }
+    $text .= "ET";
+    $len = strlen($text);
+
+    // object 3: page
+    $offsets[] = strlen($pdf);
+    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
+
+    // object 4: contents
+    $offsets[] = strlen($pdf);
+    $pdf .= "4 0 obj\n<< /Length $len >>\nstream\n$text\nendstream\nendobj\n";
+
+    // object 5: font
+    $offsets[] = strlen($pdf);
+    $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
+
+    // xref
+    $xref_pos = strlen($pdf);
+    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
+    foreach ($offsets as $off) {
+        $pdf .= sprintf("%010d 00000 n \n", $off);
+    }
+    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref_pos\n%%EOF";
+
+    file_put_contents($outputPath, $pdf);
+}

// Handle UPI QR generation, payment confirmation and receipt
if ($action === 'generate_upi') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    $donor_name = $input['donor_name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';

    try {
        // Insert a donation record (monetary)
        $query = "INSERT INTO donations (donor_name, email, phone, address, item_name, quantity, payment_method) \
                 VALUES (:donor_name, :email, :phone, '', :item_name, 1, 'UPI')";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':donor_name' => $donor_name,
            ':email' => $email,
            ':phone' => $phone,
            ':item_name' => 'Monetary Donation'
        ]);
        $donation_id = $pdo->lastInsertId();

        // Create order id and UPI URI
        $order_id = 'DON' . time() . rand(100,999);
        $am = number_format($amount, 2, '.', '');
        $pa = defined('UPI_VPA') ? UPI_VPA : '';
        $pn = defined('UPI_NAME') ? UPI_NAME : '';
        $upi_uri = "upi://pay?pa=" . urlencode($pa) . "&pn=" . urlencode($pn) . "&tn=" . urlencode('Donation') . "&am={$am}&cu=INR&tr=" . urlencode($order_id);

        // Use Google Charts API to create a quick QR link (client can render or fetch the image)
        $qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($upi_uri) . "&chld=L|1";

        // Insert payment record
        $query = "INSERT INTO payments (donation_id, order_id, amount, upi_uri, qr_url, status) VALUES (:donation_id, :order_id, :amount, :upi_uri, :qr_url, 'pending')";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':donation_id' => $donation_id,
            ':order_id' => $order_id,
            ':amount' => $am,
            ':upi_uri' => $upi_uri,
            ':qr_url' => $qr_url
        ]);
        $payment_id = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'payment_id' => $payment_id,
            'order_id' => $order_id,
            'amount' => $am,
            'upi_uri' => $upi_uri,
            'qr_url' => $qr_url
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

elseif ($action === 'confirm_payment') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $payment_id = $input['payment_id'] ?? null;
    $transaction_id = $input['transaction_id'] ?? null;
    $payer_vpa = $input['payer_vpa'] ?? null;
    $payer_name = $input['payer_name'] ?? null;
    $payer_email = $input['payer_email'] ?? null;
    $payer_phone = $input['payer_phone'] ?? null;
    $amount = isset($input['amount']) ? number_format(floatval($input['amount']),2,'.','') : null;

    if (!$payment_id || !$transaction_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'payment_id and transaction_id are required']);
        exit;
    }

    try {
        // Fetch payment
        $stmt = $pdo->prepare("SELECT p.*, d.donor_name, d.email as donor_email, d.phone as donor_phone FROM payments p LEFT JOIN donations d ON p.donation_id = d.id WHERE p.id = :id");
        $stmt->execute([':id' => $payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }

        // Optional amount check
        if ($amount !== null && floatval($amount) !== floatval($payment['amount'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Amount mismatch']);
            exit;
        }

        // Update payment as successful
        $update = $pdo->prepare("UPDATE payments SET transaction_id = :tx, payer_vpa = :pv, payer_name = :pn, payer_email = :pe, payer_phone = :pp, status = 'success', payment_time = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $update->execute([
            ':tx' => $transaction_id,
            ':pv' => $payer_vpa,
            ':pn' => $payer_name,
            ':pe' => $payer_email,
            ':pp' => $payer_phone,
            ':id' => $payment_id
        ]);

        // Generate receipt HTML
        $receiptFile = 'receipt_' . $payment_id . '.html';
        $receiptPath = RECEIPT_DIR . '/' . $receiptFile;

        $paidAt = date('Y-m-d H:i:s');
        $paidAmount = number_format($payment['amount'],2,'.','');
        $donorName = $payment['donor_name'] ?? ($payer_name ?? '');
        $donorEmail = $payment['donor_email'] ?? ($payer_email ?? '');
        $donorPhone = $payment['donor_phone'] ?? ($payer_phone ?? '');
        $receiverName = defined('UPI_NAME') ? UPI_NAME : '';
        $receiverVPA = defined('UPI_VPA') ? UPI_VPA : '';

        // build HTML receipt
        $html = "<!doctype html><html><head><meta charset=\"utf-8\"><title>Payment Receipt</title>
        <style>body{font-family:Arial,Helvetica,sans-serif;max-width:700px;margin:20px auto;color:#222}header{display:flex;justify-content:space-between;align-items:center}h1{margin:0}table{width:100%;border-collapse:collapse;margin-top:20px}td,th{padding:8px;border:1px solid #ddd} .muted{color:#666}</style>
        </head><body>
        <header><div><h1>Payment Receipt</h1><div class=\"muted\">Order: " . htmlspecialchars($payment['order_id']) . "</div></div>
        <div><strong>Status:</strong> Successful</div></header>
        <section>
        <table>
        <tr><th>Transaction ID</th><td>" . htmlspecialchars($transaction_id) . "</td></tr>
        <tr><th>Paid Amount</th><td>₹ " . htmlspecialchars($paidAmount) . "</td></tr>
        <tr><th>Date & Time</th><td>" . htmlspecialchars($paidAt) . "</td></tr>
        <tr><th>Payer Name</th><td>" . htmlspecialchars($donorName) . "</td></tr>
        <tr><th>Payer Email</th><td>" . htmlspecialchars($donorEmail) . "</td></tr>
        <tr><th>Payer Phone</th><td>" . htmlspecialchars($donorPhone) . "</td></tr>
        <tr><th>Receiver Name</th><td>" . htmlspecialchars($receiverName) . "</td></tr>
        <tr><th>Receiver VPA</th><td>" . htmlspecialchars($receiverVPA) . "</td></tr>
        </table>
        </section>
        <div style=\"margin-top:20px;\"><button onclick=\"window.print()\">Download PDF / Print</button></div>
        </body></html>";
 
        file_put_contents($receiptPath, $html);
 
        // also create simple PDF version
        $lines = [
            'Payment Receipt',
            'Order: ' . $payment['order_id'],
            'Status: Successful',
            'Transaction ID: ' . $transaction_id,
            'Paid Amount: ₹ ' . $paidAmount,
            'Date & Time: ' . $paidAt,
            'Payer Name: ' . $donorName,
            'Payer Email: ' . $donorEmail,
            'Payer Phone: ' . $donorPhone,
            'Receiver Name: ' . $receiverName,
            'Receiver VPA: ' . $receiverVPA
        ];
        $pdfFile = 'receipt_' . $payment_id . '.pdf';
        $pdfPath = RECEIPT_DIR . '/' . $pdfFile;
        generate_pdf_receipt($lines, $pdfPath);
 
        $rurl = receipt_url($receiptFile);
        $prurl = receipt_url($pdfFile);
 
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment confirmed and receipt generated',
            'receipt_url' => $rurl,
            'pdf_url' => $prurl,
            'payment_id' => $payment_id,
            'transaction_id' => $transaction_id
        ]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

elseif ($action === 'get_receipt') {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
    if (!$payment_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'payment_id required']);
        exit;
    }

    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';
    $receiptFile = 'receipt_' . $payment_id . '.' . ($format === 'pdf' ? 'pdf' : 'html');
    $receiptPath = RECEIPT_DIR . '/' . $receiptFile;
    if (!file_exists($receiptPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
        exit;
    }
 
    if ($format === 'pdf') {
        header('Content-Type: application/pdf');
    } else {
        header('Content-Type: text/html; charset=utf-8');
    }
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $donations,
                'count' => count($donations)
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } 
    elseif ($method === 'POST') {
        // Create new donation
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $query = "INSERT INTO donations (donor_name, email, phone, address, item_name, quantity, payment_method) 
                     VALUES (:donor_name, :email, :phone, :address, :item_name, :quantity, :payment_method)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':donor_name' => $input['donor_name'] ?? '',
                ':email' => $input['email'] ?? '',
                ':phone' => $input['phone'] ?? '',
                ':address' => $input['address'] ?? '',
                ':item_name' => $input['item_name'] ?? '',
                ':quantity' => $input['quantity'] ?? 1,
                ':payment_method' => $input['payment_method'] ?? 'other'
            ]);
            
            $donation_id = $pdo->lastInsertId();
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Donation recorded successfully',
                'donation_id' => $donation_id
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Handle Visits
elseif ($action === 'visits') {
    if ($method === 'GET') {
        // Get all visits
        try {
            $query = "SELECT * FROM visits ORDER BY visit_date DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $visits,
                'count' => count($visits)
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($method === 'POST') {
        // Create new visit booking
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $query = "INSERT INTO visits (visitor_name, email, phone, visit_date, visit_time, message) 
                     VALUES (:visitor_name, :email, :phone, :visit_date, :visit_time, :message)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':visitor_name' => $input['visitor_name'] ?? '',
                ':email' => $input['email'] ?? '',
                ':phone' => $input['phone'] ?? '',
                ':visit_date' => $input['visit_date'] ?? '',
                ':visit_time' => $input['visit_time'] ?? '',
                ':message' => $input['message'] ?? ''
            ]);
            
            $visit_id = $pdo->lastInsertId();
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Visit booked successfully',
                'visit_id' => $visit_id
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Handle Inventory
elseif ($action === 'inventory') {
    if ($method === 'GET') {
        // Get all inventory items
        try {
            $query = "SELECT * FROM inventory ORDER BY created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $items,
                'count' => count($items)
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($method === 'POST') {
        // Add new inventory item
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $query = "INSERT INTO inventory (name, description, stock_quantity, image_url) 
                     VALUES (:name, :description, :stock_quantity, :image_url)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':name' => $input['name'] ?? '',
                ':description' => $input['description'] ?? '',
                ':stock_quantity' => $input['stock_quantity'] ?? 0,
                ':image_url' => $input['image_url'] ?? ''
            ]);
            
            $item_id = $pdo->lastInsertId();
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Item added successfully',
                'item_id' => $item_id
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($method === 'PUT') {
        // Update inventory item
        $input = json_decode(file_get_contents('php://input'), true);
        $item_id = $input['id'] ?? null;
        
        if (!$item_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Item ID is required']);
            exit;
        }
        
        try {
            $query = "UPDATE inventory SET name = :name, description = :description, 
                     stock_quantity = :stock_quantity, image_url = :image_url, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                ':id' => $item_id,
                ':name' => $input['name'] ?? '',
                ':description' => $input['description'] ?? '',
                ':stock_quantity' => $input['stock_quantity'] ?? 0,
                ':image_url' => $input['image_url'] ?? ''
            ]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($method === 'DELETE') {
        // Delete inventory item
        $input = json_decode(file_get_contents('php://input'), true);
        $item_id = $input['id'] ?? null;
        
        if (!$item_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Item ID is required']);
            exit;
        }
        
        try {
            $query = "DELETE FROM inventory WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $item_id]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// additional helper endpoint for polling status
elseif ($action === 'payment_status') {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
    if (!$payment_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'payment_id required']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT status FROM payments WHERE id = :id");
    $stmt->execute([':id' => $payment_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }
    echo json_encode(['success'=>true,'status'=>$row['status']]);
    exit;
}
// Default response
else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

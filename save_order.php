<?php
require_once __DIR__ . '/admin/includes/db.php';

header('Content-Type: application/json');

// Get the raw POST data
$json_data = file_get_contents('php://input');
// Decode the JSON data
$data = json_decode($json_data, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
    exit;
}

// --- Basic Validation ---
if (!isset($data['id']) || !isset($data['customer']) || !isset($data['items']) || !isset($data['totalAmount'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required order data.']);
    exit;
}

$order_id = $data['id'];
$customer = $data['customer'];
$items = $data['items'];
$total_amount = $data['totalAmount'];
$payment_method = $data['paymentMethod'] ?? 'N/A';
$transaction_id = $data['transactionId'] ?? 'N/A';
$status = $data['status'] ?? 'pending';

try {
    $pdo->beginTransaction();

    // Insert into orders table
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (id, customer_name, customer_email, customer_phone, total_price, payment_method, transaction_id, status, order_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $order_stmt->execute([
        $order_id,
        $customer['name'] ?? null,
        $customer['email'] ?? null,
        $customer['phone'] ?? null,
        $total_amount,
        $payment_method,
        $transaction_id,
        $status,
        $data['timestamp'] ?? date('Y-m-d H:i:s')
    ]);

    // Insert into order_items table
    $item_stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, selected_duration_label)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $item_stmt->execute([
            $order_id,
            $item['id'],
            $item['quantity'] ?? 1,
            $item['price'],
            $item['selectedDurationLabel'] ?? null
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Order saved successfully.', 'orderId' => $order_id]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>

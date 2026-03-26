<?php
/**
 * API: Plugin — Delivery Callback
 * Plugin reports back after executing a command
 */
header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// Verify HMAC signature
$serverId  = $_SERVER['HTTP_X_SERVER_ID'] ?? '';
$timestamp = $_SERVER['HTTP_X_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$secretKey = Settings::get('plugin_api_secret', '');

if (empty($serverId) || empty($timestamp) || empty($signature) || empty($secretKey)) {
    jsonResponse(['error' => 'Missing auth headers'], 401);
}

if (abs(time() - (int)$timestamp) > 300) {
    jsonResponse(['error' => 'Timestamp expired'], 401);
}

$expectedSig = hash_hmac('sha256', "{$serverId}:{$timestamp}", $secretKey);
if (!hash_equals($expectedSig, $signature)) {
    jsonResponse(['error' => 'Invalid signature'], 401);
}

// Parse body
$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['delivery_id'])) {
    jsonResponse(['error' => 'Invalid body'], 400);
}

$deliveryId = (int) $body['delivery_id'];
$status     = ($body['status'] ?? 'delivered') === 'failed' ? 'failed' : 'delivered';
$response   = $body['response'] ?? '';

// Verify delivery belongs to this server
$delivery = $db->fetch("SELECT * FROM delivery_queue WHERE id = ? AND server_id = ?", [$deliveryId, $serverId]);
if (!$delivery) {
    jsonResponse(['error' => 'Delivery not found'], 404);
}

if ($delivery['status'] !== 'pending') {
    jsonResponse(['error' => 'Delivery already processed'], 409);
}

// Update delivery status
$db->execute("UPDATE delivery_queue SET status = ?, delivered_at = NOW() WHERE id = ?", [$status, $deliveryId]);

// Log delivery
$db->execute(
    "INSERT INTO delivery_logs (delivery_id, status, response) VALUES (?, ?, ?)",
    [$deliveryId, $status, $response]
);

// Update order status if all deliveries complete
if ($delivery['order_id']) {
    $pendingCount = $db->count(
        "SELECT COUNT(*) FROM delivery_queue WHERE order_id = ? AND status = 'pending'",
        [$delivery['order_id']]
    );
    if ($pendingCount === 0) {
        $db->execute("UPDATE orders SET status = 'delivered' WHERE id = ? AND status = 'processing'", [$delivery['order_id']]);
    }
}

jsonResponse(['success' => true, 'delivery_id' => $deliveryId, 'status' => $status]);

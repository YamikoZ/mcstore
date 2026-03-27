<?php
/**
 * API: Shop — Buy Now (direct, no cart)
 * รองรับ: quantity, item sets (JSON array command), one_per_user check
 */
header('Content-Type: application/json; charset=utf-8');
License::requireValid();

if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

if (!csrf_check()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 403);
}

if (!rateLimitCheck('buynow_' . ($_SESSION['user_id'] ?? ''), 10, 60)) {
    jsonResponse(['success' => false, 'message' => 'ทำรายการบ่อยเกินไป กรุณารอสักครู่'], 429);
}

$body      = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($body['product_id'] ?? 0);
$serverId  = trim((string) ($body['server_id'] ?? ''));
$quantity  = max(1, min(99, (int) ($body['quantity'] ?? 1)));

if (!$productId || !$serverId) {
    jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
}

$db      = Database::getInstance();
$user    = Auth::user();

$product = $db->fetch("SELECT * FROM products WHERE id = ? AND is_active = 1", [$productId]);

if (!$product) {
    jsonResponse(['success' => false, 'message' => 'ไม่พบสินค้า'], 404);
}

// ─── Validate server exists ───────────────────────────────────────────────
$server = $db->fetch("SELECT id FROM servers WHERE id = ? AND is_active = 1", [$serverId]);
if (!$server) {
    jsonResponse(['success' => false, 'message' => 'ไม่พบเซิร์ฟเวอร์'], 400);
}

// ─── One-per-user check (แรงค์/VIP ห้ามซื้อซ้ำ) ──────────────────────────
if ($product['one_per_user']) {
    $quantity = 1; // บังคับ 1 เสมอ
    $alreadyBought = $db->fetch(
        "SELECT oi.id FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE o.username = ? AND oi.product_id = ? AND o.status NOT IN ('cancelled','refunded')
         LIMIT 1",
        [$user['username'], $productId]
    );
    if ($alreadyBought) {
        jsonResponse(['success' => false, 'message' => 'คุณมียศ/สิทธิ์นี้อยู่แล้ว ไม่สามารถซื้อซ้ำได้']);
    }
}

// ─── Stock check เบื้องต้น (-1 = unlimited) ──────────────────────────────
if ($product['stock'] >= 0 && $product['stock'] < $quantity) {
    jsonResponse(['success' => false, 'message' => 'สินค้าไม่เพียงพอ (เหลือ ' . (int)$product['stock'] . ')']);
}

// ─── Balance check ────────────────────────────────────────────────────────
$total = (float) $product['price'] * $quantity;

if ($user['balance'] < $total) {
    jsonResponse(['success' => false, 'message' => 'ยอดเงินไม่เพียงพอ (มี ' . formatMoney($user['balance']) . ' ต้องการ ' . formatMoney($total) . ')']);
}

// ─── Parse commands (รองรับ single string และ JSON array สำหรับ set) ─────
$commands = json_decode($product['command'], true);
if (!is_array($commands)) {
    $commands = [$product['command']];
}
$commands = array_filter($commands); // กรองค่าว่าง

if (empty($commands)) {
    jsonResponse(['success' => false, 'message' => 'สินค้านี้ยังไม่มี command กรุณาติดต่อผู้ดูแล'], 500);
}

// ─── Transaction ─────────────────────────────────────────────────────────
$db->beginTransaction();
try {
    // Deduct balance (atomic — ป้องกัน race condition)
    $db->execute(
        "UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?",
        [$total, $user['id'], $total]
    );
    if ($db->rowCount() === 0) {
        throw new Exception('ยอดเงินไม่เพียงพอ');
    }

    // Create order
    $db->execute(
        "INSERT INTO orders (username, total, status) VALUES (?, ?, 'processing')",
        [$user['username'], $total]
    );
    $orderId = $db->lastInsertId();

    // Order item
    $db->execute(
        "INSERT INTO order_items (order_id, product_id, server_id, name, quantity, price, command) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$orderId, $product['id'], $serverId, $product['name'], $quantity, $product['price'], $product['command']]
    );

    // Reduce stock (atomic — ป้องกัน race condition)
    if ($product['stock'] >= 0) {
        $db->execute(
            "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
            [$quantity, $product['id'], $quantity]
        );
        if ($db->rowCount() === 0) {
            throw new Exception('สินค้าไม่เพียงพอ');
        }
    }

    // Delivery queue — วน quantity × commands (set items รองรับครบ)
    for ($q = 0; $q < $quantity; $q++) {
        foreach ($commands as $cmd) {
            $finalCmd = str_replace(
                ['{player}', '{username}', '{amount}'],
                [$user['username'], $user['username'], (string)$quantity],
                $cmd
            );
            $db->execute(
                "INSERT INTO delivery_queue (order_id, username, server_id, player_name, command, item_name) VALUES (?, ?, ?, ?, ?, ?)",
                [$orderId, $user['username'], $serverId, $user['username'], $finalCmd, $product['name']]
            );
        }
    }

    // Wallet ledger
    $db->execute(
        "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) VALUES (?, 'debit', ?, (SELECT balance FROM users WHERE id = ?), ?, ?)",
        [$user['username'], $total, $user['id'], 'order#' . $orderId, 'ซื้อ ' . $product['name'] . ' x' . $quantity]
    );

    $db->commit();

    createNotification($user['id'], 'ซื้อสำเร็จ', $product['name'] . ' x' . $quantity . ' — ' . formatMoney($total), 'success', 'orders');

    jsonResponse([
        'success'  => true,
        'message'  => 'ซื้อสำเร็จ! กำลังส่งของเข้าเกม...',
        'redirect' => url('orders'),
    ]);

} catch (Exception $e) {
    $db->rollback();
    $msg = in_array($e->getMessage(), ['ยอดเงินไม่เพียงพอ', 'สินค้าไม่เพียงพอ'])
        ? $e->getMessage()
        : 'เกิดข้อผิดพลาด กรุณาลองใหม่';
    jsonResponse(['success' => false, 'message' => $msg], 500);
}

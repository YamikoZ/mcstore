<?php
/**
 * API: Cart — Add item
 */
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

$body = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($body['product_id'] ?? 0);
$serverId  = (int) ($body['server_id'] ?? 0);
$quantity  = max(1, (int) ($body['quantity'] ?? 1));

if (!$productId || !$serverId) {
    jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
}

$db = Database::getInstance();
$product = $db->fetch("SELECT * FROM products WHERE id = ? AND is_active = 1", [$productId]);
if (!$product) {
    jsonResponse(['success' => false, 'message' => 'ไม่พบสินค้า'], 404);
}

// Check stock
if ($product['stock'] !== null && $product['stock'] < $quantity) {
    jsonResponse(['success' => false, 'message' => 'สินค้าหมด']);
}

// Add to session cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$cartKey = "{$productId}_{$serverId}";
if (isset($_SESSION['cart'][$cartKey])) {
    $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$cartKey] = [
        'product_id' => $productId,
        'server_id'  => $serverId,
        'quantity'   => $quantity,
    ];
}

jsonResponse([
    'success'    => true,
    'message'    => 'เพิ่มลงตะกร้าแล้ว!',
    'cart_count' => count($_SESSION['cart']),
]);

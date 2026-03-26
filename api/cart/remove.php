<?php
/**
 * API: Cart — Remove item
 */
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

$body = json_decode(file_get_contents('php://input'), true);
$itemKey = $body['item_id'] ?? '';

if (empty($itemKey) || !isset($_SESSION['cart'][$itemKey])) {
    jsonResponse(['success' => false, 'message' => 'ไม่พบสินค้าในตะกร้า'], 404);
}

unset($_SESSION['cart'][$itemKey]);

jsonResponse([
    'success'    => true,
    'message'    => 'ลบออกจากตะกร้าแล้ว',
    'cart_count' => count($_SESSION['cart']),
]);

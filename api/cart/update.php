<?php
/**
 * API: Cart — add / update / remove items (session-based)
 * POST { action: 'add'|'update'|'remove'|'clear', product_id, server_id, quantity }
 */
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

if (!csrf_check()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 403);
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$action    = trim((string)($body['action']    ?? ''));
$productId = (int)($body['product_id'] ?? 0);
$serverId  = trim((string)($body['server_id'] ?? ''));
$quantity  = max(1, min(99, (int)($body['quantity'] ?? 1)));

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {

    case 'add':
        if (!$productId || !$serverId) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
        }
        $db      = Database::getInstance();
        $product = $db->fetch("SELECT id, name, price, stock, is_active FROM products WHERE id = ? AND is_active = 1", [$productId]);
        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'ไม่พบสินค้า'], 404);
        }
        // หาตำแหน่งที่มีอยู่แล้ว
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $productId && $item['server_id'] === $serverId) {
                $newQty = $item['quantity'] + $quantity;
                if ($product['stock'] >= 0 && $newQty > $product['stock']) {
                    jsonResponse(['success' => false, 'message' => 'สินค้ามีไม่เพียงพอ (เหลือ ' . (int)$product['stock'] . ')']);
                }
                $item['quantity'] = $newQty;
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) {
            if ($product['stock'] >= 0 && $quantity > $product['stock']) {
                jsonResponse(['success' => false, 'message' => 'สินค้ามีไม่เพียงพอ (เหลือ ' . (int)$product['stock'] . ')']);
            }
            $_SESSION['cart'][] = [
                'product_id' => $productId,
                'server_id'  => $serverId,
                'quantity'   => $quantity,
            ];
        }
        jsonResponse(['success' => true, 'message' => 'เพิ่มในตะกร้าแล้ว', 'cart_count' => count($_SESSION['cart'])]);

    case 'update':
        if (!$productId || !$serverId) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
        }
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $productId && $item['server_id'] === $serverId) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        unset($item);
        jsonResponse(['success' => true, 'cart_count' => count($_SESSION['cart'])]);

    case 'remove':
        $_SESSION['cart'] = array_values(array_filter(
            $_SESSION['cart'],
            fn($item) => !($item['product_id'] === $productId && $item['server_id'] === $serverId)
        ));
        jsonResponse(['success' => true, 'cart_count' => count($_SESSION['cart'])]);

    case 'clear':
        $_SESSION['cart'] = [];
        jsonResponse(['success' => true, 'cart_count' => 0]);

    default:
        jsonResponse(['success' => false, 'message' => 'action ไม่ถูกต้อง'], 400);
}

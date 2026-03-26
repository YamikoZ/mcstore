<?php
/**
 * API: Realtime Status
 * Polled every 3 seconds by realtime.js
 */
header('Content-Type: application/json; charset=utf-8');

$data = ['online_count' => 0];

// Server online count from settings or cache
$data['online_count'] = (int) Settings::get('server_online_count', 0);

if (Auth::check()) {
    $db = Database::getInstance();
    $user = Auth::user();
    $username = $user['username'];
    
    // Unread notifications
    $data['unread_notifications'] = $db->count(
        "SELECT COUNT(*) FROM notifications WHERE username = ? AND is_read = 0",
        [$username]
    );
    
    // Cart count
    $cart = $_SESSION['cart'] ?? [];
    $data['cart_count'] = count($cart);
    
    // Balance
    $data['balance'] = formatMoney($user['balance'] ?? 0);
    
    // Recent delivery (last 5 seconds)
    $recent = $db->fetch(
        "SELECT dq.command FROM delivery_logs dl 
         JOIN delivery_queue dq ON dl.delivery_id = dq.id 
         WHERE dq.username = ? AND dl.created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND) 
         ORDER BY dl.created_at DESC LIMIT 1",
        [$username]
    );
    if ($recent) {
        $data['recent_delivery'] = ['message' => 'ส่งของเข้าเกมแล้ว!'];
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

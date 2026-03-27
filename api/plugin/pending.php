<?php
/**
 * API: Plugin — Get Pending Deliveries
 * Called by Minecraft plugin every 30 seconds
 * Requires HMAC-SHA256 signature
 */
header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// Verify HMAC signature
$serverId     = $_SERVER['HTTP_X_SERVER_ID'] ?? '';
$timestamp    = $_SERVER['HTTP_X_TIMESTAMP'] ?? '';
$signature    = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$secretKey    = Settings::get('plugin_api_secret', '');

if (empty($serverId) || empty($timestamp) || empty($signature) || empty($secretKey)) {
    jsonResponse(['error' => 'Missing auth headers'], 401);
}

// Verify timestamp (within 5 minutes)
if (abs(time() - (int)$timestamp) > 300) {
    jsonResponse(['error' => 'Timestamp expired'], 401);
}

// Verify HMAC
$expectedSig = hash_hmac('sha256', "{$serverId}:{$timestamp}", $secretKey);
if (!hash_equals($expectedSig, $signature)) {
    jsonResponse(['error' => 'Invalid signature'], 401);
}

// Verify server exists
$server = $db->fetch("SELECT * FROM servers WHERE id = ? AND is_active = 1", [$serverId]);
if (!$server) {
    jsonResponse(['error' => 'Server not found'], 404);
}

// Get command whitelist
$whitelist = json_decode(Settings::get('command_whitelist', '[]'), true) ?: [];

// Get pending deliveries for this server
$pending = $db->fetchAll(
    "SELECT dq.id, dq.username, dq.command, dq.item_name
     FROM delivery_queue dq
     WHERE dq.server_id = ? AND dq.status = 'pending'
     ORDER BY dq.created_at ASC LIMIT 20",
    [$serverId]
);

// Filter commands against whitelist
$deliveries = [];
foreach ($pending as $p) {
    $cmdBase = explode(' ', $p['command'])[0];
    if (!empty($whitelist) && !in_array($cmdBase, $whitelist)) {
        // Mark as failed — command not whitelisted
        $db->execute("UPDATE delivery_queue SET status = 'failed', delivered_at = NOW() WHERE id = ?", [$p['id']]);
        $db->execute("INSERT INTO delivery_logs (delivery_id, status, response) VALUES (?, 'failed', 'Command not whitelisted')", [$p['id']]);
        continue;
    }
    $deliveries[] = [
        'id'        => (int) $p['id'],
        'username'  => $p['username'],
        'command'   => $p['command'],
        'item_name' => $p['item_name'] ?? '',
    ];
}

// Update online player count if provided
$onlineCount = $_SERVER['HTTP_X_ONLINE_COUNT'] ?? null;
if ($onlineCount !== null) {
    Settings::set('server_online_count', (int)$onlineCount);
}

jsonResponse([
    'success'    => true,
    'server_id'  => (int) $serverId,
    'deliveries' => $deliveries,
    'count'      => count($deliveries),
]);

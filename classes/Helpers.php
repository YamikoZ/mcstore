<?php
/**
 * Helper Functions
 */

function url($path = '') {
    $base = rtrim(Settings::get('site_url', '/mcstore'), '/');
    return $base . '/' . ltrim($path, '/');
}

function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function csrf_field() {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function old($key, $default = '') {
    return e($_SESSION['old_input'][$key] ?? $default);
}

function flash($key, $value = null) {
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
    } else {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
}

function formatMoney($amount) {
    return number_format((float)$amount, 2) . ' ' . Settings::get('currency_symbol', '฿');
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'เมื่อสักครู่';
    if ($diff < 3600) return floor($diff / 60) . ' นาทีที่แล้ว';
    if ($diff < 86400) return floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
    if ($diff < 604800) return floor($diff / 86400) . ' วันที่แล้ว';
    return date('d/m/Y H:i', $time);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ตรวจ command ว่าอยู่ใน whitelist หรือไม่
 * คืน true ถ้าปลอดภัย, false ถ้าต้องบล็อก
 */
function commandIsAllowed(string $cmd): bool {
    $whitelist = json_decode(Settings::get('command_whitelist', '["give","lp","eco","say","mi","mm","kit","mmoitems"]'), true);
    if (!is_array($whitelist) || empty($whitelist)) return true; // ถ้าไม่ตั้งค่า → อนุญาตทั้งหมด
    $firstWord = strtolower(trim(explode(' ', ltrim($cmd, '/'))[0]));
    return in_array($firstWord, array_map('strtolower', $whitelist), true);
}

/**
 * แทนที่ placeholder ใน command และตรวจ whitelist
 * คืน array ของ commands ที่ผ่านการ sanitize แล้ว
 * โยน Exception ถ้ามี command ไม่ผ่าน whitelist
 */
function buildDeliveryCommands(string $cmdJson, string $playerName, int $quantity): array {
    $commands = json_decode($cmdJson, true);
    if (!is_array($commands)) {
        $commands = [$cmdJson];
    }
    $commands = array_filter($commands);
    $result = [];
    foreach ($commands as $cmd) {
        $finalCmd = str_replace(
            ['{player}', '{username}', '{amount}'],
            [
                preg_replace('/[^a-zA-Z0-9_]/', '', $playerName), // sanitize player name
                preg_replace('/[^a-zA-Z0-9_]/', '', $playerName),
                (string)(int)$quantity,
            ],
            $cmd
        );
        if (!commandIsAllowed($finalCmd)) {
            throw new \RuntimeException("Command ไม่ได้รับอนุญาต: " . substr($finalCmd, 0, 30));
        }
        $result[] = $finalCmd;
    }
    return $result;
}

function rateLimitCheck($action, $maxAttempts = 5, $windowSeconds = 300) {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Clean old entries
    $db->execute("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)", [$windowSeconds]);
    
    $row = $db->fetch(
        "SELECT attempts FROM rate_limits WHERE identifier = ? AND action = ? AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$ip, $action, $windowSeconds]
    );
    
    if ($row && $row['attempts'] >= $maxAttempts) return false;
    
    if ($row) {
        $db->execute(
            "UPDATE rate_limits SET attempts = attempts + 1 WHERE identifier = ? AND action = ?",
            [$ip, $action]
        );
    } else {
        $db->execute(
            "INSERT INTO rate_limits (identifier, action, attempts, window_start) VALUES (?, ?, 1, NOW())",
            [$ip, $action]
        );
    }
    return true;
}

function resolveUsername($identifier) {
    if (!$identifier) return 'system';
    if (!is_numeric($identifier)) return $identifier;
    $db = Database::getInstance();
    $user = $db->fetch("SELECT username FROM users WHERE id = ?", [(int)$identifier]);
    return $user['username'] ?? 'unknown';
}

function auditLog($userIdentifier, $action, $detail = '') {
    $db = Database::getInstance();
    $username = resolveUsername($userIdentifier);
    $db->execute(
        "INSERT INTO audit_log (username, action, detail, ip) VALUES (?, ?, ?, ?)",
        [$username, $action, $detail, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']
    );
}

function getUnreadNotifications($userIdentifier) {
    $db = Database::getInstance();
    $username = resolveUsername($userIdentifier);
    return $db->count("SELECT COUNT(*) FROM notifications WHERE username = ? AND is_read = 0", [$username]);
}

function createNotification($userIdentifier, $title, $message, $type = 'info', $link = null) {
    $db = Database::getInstance();
    $username = resolveUsername($userIdentifier);
    $db->execute(
        "INSERT INTO notifications (username, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
        [$username, $title, $message, $type, $link]
    );
}

/**
 * ส่ง Discord Webhook embed
 * @param string $channel  'orders' | 'payments' | 'users' | 'system'
 * @param string $title    หัวข้อ embed
 * @param string $desc     รายละเอียด
 * @param int    $color    สี hex ตัวเลข (default เขียว)
 * @param array  $fields   [['name'=>'', 'value'=>'', 'inline'=>bool], ...]
 */
function sendWebhook(string $channel, string $title, string $desc, int $color = 0x57F287, array $fields = []): void {
    $key = 'webhook_' . $channel;
    $url = Settings::get($key, '');
    if (!$url || !str_starts_with($url, 'https://')) return;

    $embed = [
        'title'       => $title,
        'description' => $desc,
        'color'       => $color,
        'timestamp'   => date('c'),
        'footer'      => ['text' => Settings::get('site_name', 'MCStore')],
    ];
    if (!empty($fields)) $embed['fields'] = $fields;

    $payload = json_encode(['embeds' => [$embed]], JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

/**
 * Render ‹ 1 2 3 › pagination HTML
 * @param int $currentPage  current page (1-based)
 * @param int $totalPages   total number of pages
 * @param string $baseUrl   URL without ?page= (query params already included)
 * @param string $pageParam query param name, default 'page'
 */
function paginationHtml(int $currentPage, int $totalPages, string $baseUrl, string $pageParam = 'page'): string {
    if ($totalPages <= 1) return '';

    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $html  = '<div class="flex items-center justify-center gap-1 mt-8">';

    // Prev
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . $sep . $pageParam . '=' . ($currentPage - 1) . '" class="px-3 py-1 rounded text-sm opacity-70 hover:opacity-100" style="background:var(--color-surface-dark);">‹</a>';
    }

    // Page numbers (show up to 5 around current)
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . $sep . $pageParam . '=1" class="px-3 py-1 rounded text-sm opacity-70 hover:opacity-100" style="background:var(--color-surface-dark);">1</a>';
        if ($start > 2) $html .= '<span class="px-2 opacity-30">…</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage;
        $style  = $active ? 'background:var(--color-primary);color:#fff;' : 'background:var(--color-surface-dark);';
        $html  .= '<a href="' . $baseUrl . $sep . $pageParam . '=' . $i . '" class="px-3 py-1 rounded text-sm font-semibold" style="' . $style . '">' . $i . '</a>';
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="px-2 opacity-30">…</span>';
        $html .= '<a href="' . $baseUrl . $sep . $pageParam . '=' . $totalPages . '" class="px-3 py-1 rounded text-sm opacity-70 hover:opacity-100" style="background:var(--color-surface-dark);">' . $totalPages . '</a>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . $sep . $pageParam . '=' . ($currentPage + 1) . '" class="px-3 py-1 rounded text-sm opacity-70 hover:opacity-100" style="background:var(--color-surface-dark);">›</a>';
    }

    $html .= '</div>';
    return $html;
}

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

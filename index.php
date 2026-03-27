<?php
/**
 * MCStore - Front Controller / Router
 * ทุก request เข้าที่นี่ แล้ว route ไปหน้าที่ถูกต้อง
 */

session_start();

// Base path
define('BASE_PATH', __DIR__);
define('BASE_URL', '');

// Load core
require_once BASE_PATH . '/classes/Database.php';
require_once BASE_PATH . '/classes/Settings.php';
require_once BASE_PATH . '/classes/Auth.php';
require_once BASE_PATH . '/classes/Helpers.php';
require_once BASE_PATH . '/classes/Voucher.php';
require_once BASE_PATH . '/classes/License.php';
// PromptPay class removed — ใช้แค่อังเปา TrueWallet

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Maintenance mode check
if (Settings::get('maintenance_mode') === '1') {
    $route = trim($_GET['route'] ?? '', '/');
    $parts = explode('/', $route);
    if (($parts[0] ?? '') !== 'admin' && ($parts[0] ?? '') !== 'api') {
        require BASE_PATH . '/pages/maintenance.php';
        exit;
    }
}

// License check
{
    // รับ POST จากหน้า license setup (ไม่ต้อง login)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'setup_license') {
        // อนุญาตเฉพาะเมื่อยังไม่มี license ที่ valid เท่านั้น
        if (License::check()) {
            header('Location: ' . url(''));
            exit;
        }
        csrf_check();
        $submittedKey = trim($_POST['license_key'] ?? '');
        Settings::set('license_key', $submittedKey);
        License::bust();
        if (License::check()) {
            header('Location: ' . url(''));
        } else {
            $_SESSION['license_error'] = 'License Key ไม่ถูกต้อง หรือ domain ไม่ตรง';
            header('Location: ' . url(''));  // redirect ไป home แทน REQUEST_URI
        }
        exit;
    }

    $licRoute  = trim($_GET['route'] ?? '', '/');
    $licPage   = explode('/', $licRoute)[0] ?? '';
    $licExempt = ['admin', 'api', 'assets'];
    if (!in_array($licPage, $licExempt) && !License::check()) {
        $licReason = License::reason();
        if ($licReason === 'revoked') {
            require BASE_PATH . '/pages/license_suspended.php';
        } else {
            require BASE_PATH . '/pages/license_invalid.php';
        }
        exit;
    }
}

// Route parsing
$route = trim($_GET['route'] ?? '', '/');
$parts = explode('/', $route);

$page   = $parts[0] ?? 'home';
$param1 = $parts[1] ?? null;
$param2 = $parts[2] ?? null;
$param3 = $parts[3] ?? null;

// Welcome gate — บังคับเข้าทุกครั้ง
$welcomeExempt = ['welcome', 'api', 'assets', 'admin', 'login', 'register', 'logout'];
if (Settings::get('welcome_enabled') === '1') {
    if (!in_array($page, $welcomeExempt) && empty($_SESSION['welcome_accepted'])) {
        header('Location: ' . url('welcome'));
        exit;
    }
}

// Auth check helper
$requireAuth = function() {
    if (empty($_SESSION['username'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . url('login'));
        exit;
    }
};

$requireAdmin = function() use ($requireAuth) {
    $requireAuth();
    $db = Database::getInstance();
    $user = $db->fetch("SELECT role FROM users WHERE username = ?", [$_SESSION['username']]);
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        require BASE_PATH . '/pages/404.php';
        exit;
    }
};

// Feature toggle check helper
$requireFeature = function($key) {
    if (Settings::get($key, '1') !== '1') {
        http_response_code(404);
        require BASE_PATH . '/pages/404.php';
        exit;
    }
};

// ═══ ROUTING ═══
switch ($page) {

    // --- Welcome ---
    case 'welcome':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['welcome_accepted'] = true;
            header('Location: ' . url('home'));
            exit;
        }
        require BASE_PATH . '/pages/welcome.php';
        break;

    // --- Home ---
    case '':
    case 'home':
        require BASE_PATH . '/pages/home.php';
        break;

    // --- Shop ---
    case 'shop':
        $requireFeature('shop_enabled');
        $_GET['server']   = $param1;
        $_GET['category'] = $param2;
        require BASE_PATH . '/pages/shop.php';
        break;

    // --- Gacha ---
    case 'gacha':
        $requireFeature('gacha_enabled');
        $_GET['server'] = $param1;
        $_GET['type']   = $param2;
        require BASE_PATH . '/pages/gacha.php';
        break;

    // --- Auth ---
    case 'login':
        require BASE_PATH . '/pages/auth/login.php';
        break;
    case 'register':
        $requireFeature('register_enabled');
        require BASE_PATH . '/pages/auth/register.php';
        break;
    case 'logout':
        require BASE_PATH . '/pages/auth/logout.php';
        break;

    // --- Profile ---
    case 'profile':
        $requireAuth();
        $subPage = $param1 ?? 'overview';
        // Redirect profile sub-pages that have their own top-level route
        $profileRedirects = ['orders' => 'orders', 'topup' => 'topup', 'redeem' => 'topup'];
        if (isset($profileRedirects[$subPage])) {
            redirect($profileRedirects[$subPage]);
        }
        // Check feature toggles for profile sub-pages
        if ($subPage === 'gacha') $requireFeature('gacha_enabled');
        $allowed = ['overview','edit','password','gacha','wallet','deliveries','notifications','settings'];
        if (in_array($subPage, $allowed)) {
            require BASE_PATH . "/pages/profile/{$subPage}.php";
        } else {
            http_response_code(404);
            require BASE_PATH . '/pages/404.php';
        }
        break;

    // --- Topup ---
    case 'topup':
        $requireFeature('topup_enabled');
        $requireAuth();
        require BASE_PATH . '/pages/topup.php';
        break;

    // --- Checkout ---
    case 'checkout':
        $requireFeature('shop_enabled');
        $requireAuth();
        require BASE_PATH . '/pages/checkout.php';
        break;

    // --- Orders ---
    case 'orders':
        $requireAuth();
        require BASE_PATH . '/pages/orders.php';
        break;

    // --- Redeem (redirect to topup) ---
    case 'redeem':
        $requireFeature('redeem_enabled');
        redirect('topup');
        break;

    // --- Contact ---
    case 'contact':
        $requireFeature('contact_enabled');
        require BASE_PATH . '/pages/contact.php';
        break;

    // --- Download ---
    case 'download':
        $requireFeature('download_enabled');
        require BASE_PATH . '/pages/download.php';
        break;

    // --- Static pages ---
    case 'page':
        $_GET['slug'] = $param1;
        require BASE_PATH . '/pages/static.php';
        break;

    // --- API ---
    case 'api':
        $apiGroup  = preg_replace('/[^a-z0-9]/', '', $param1 ?? '');
        $apiAction = preg_replace('/[^a-z0-9_]/', '', $param2 ?? '');
        $apiFile   = BASE_PATH . "/api/{$apiGroup}/{$apiAction}.php";
        if ($apiGroup && $apiAction && file_exists($apiFile)) {
            require $apiFile;
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'API not found']);
        }
        break;

    // --- Admin ---
    case 'admin':
        $requireAdmin();
        $adminPage = preg_replace('/[^a-z0-9_]/', '', $param1 ?? 'dashboard');
        $adminFile = BASE_PATH . "/admin/{$adminPage}.php";
        if (file_exists($adminFile)) {
            require $adminFile;
        } else {
            http_response_code(404);
            require BASE_PATH . '/pages/404.php';
        }
        break;

    // --- 404 ---
    default:
        http_response_code(404);
        require BASE_PATH . '/pages/404.php';
        break;
}

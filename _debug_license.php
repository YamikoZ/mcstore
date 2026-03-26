<?php
// ลบไฟล์นี้ทิ้งหลังใช้งานเสร็จ!
define('BASE_PATH', __DIR__);
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Settings.php';
require_once __DIR__ . '/classes/License.php';

$db = Database::getInstance();

// ล้าง cache แล้ว force re-check
$db->execute("DELETE FROM settings WHERE setting_key IN ('license_cache_result','license_cache_time')");
Settings::reload();

$domain = $_SERVER['HTTP_HOST'] ?? 'unknown';
$key    = Settings::get('license_key', '(ยังไม่ได้ใส่)');

// รัน License::check() จริงๆ
$valid = License::check();

// อ่านค่า cache หลัง check
$cacheResult = Settings::get('license_cache_result', 'none');
$cacheTime   = (int) Settings::get('license_cache_time', '0');

echo "<pre style='font-family:monospace;font-size:14px;padding:20px'>";
echo "=== License Debug ===\n\n";
echo "HTTP_HOST  : {$domain}\n";
echo "Key ใน DB  : {$key}\n\n";
echo "License::check() result : " . ($valid ? "✅ VALID" : "❌ INVALID") . "\n\n";
echo "Cache result : {$cacheResult}\n";
echo "Cache time   : " . ($cacheTime ? date('Y-m-d H:i:s', $cacheTime) : 'none') . "\n";
echo "</pre>";

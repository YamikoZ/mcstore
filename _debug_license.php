<?php
// ลบไฟล์นี้ทิ้งหลังใช้งานเสร็จ!
define('BASE_PATH', __DIR__);
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Settings.php';

$db = Database::getInstance();

// Force set license cache = valid (7 วัน)
$db->execute("INSERT INTO settings (setting_key, setting_value) VALUES ('license_cache_result', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
$db->execute("INSERT INTO settings (setting_key, setting_value) VALUES ('license_cache_time', ?) ON DUPLICATE KEY UPDATE setting_value = ?", [time(), time()]);

echo "<pre style='font-family:monospace;font-size:14px;padding:20px'>";
echo "✅ Force activated!\n\n";
echo "License cache set to VALID\n";
echo "มีผล 1 ชม. (ต่ออายุอัตโนมัติถ้า GitHub ตอบได้)\n\n";
echo "เปิดเว็บได้เลย: http://" . ($_SERVER['HTTP_HOST'] ?? '') . "/mcstore/\n";
echo "</pre>";

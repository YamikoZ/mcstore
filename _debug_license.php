<?php
// ลบไฟล์นี้ทิ้งหลังใช้งานเสร็จ!
define('BASE_PATH', __DIR__);
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Settings.php';

$cfg      = require __DIR__ . '/config/license.php';
$domain   = $_SERVER['HTTP_HOST'] ?? 'unknown';
$savedKey = Settings::get('license_key', '(ยังไม่ได้ใส่)');
$cacheResult = Settings::get('license_cache_result', 'none');
$cacheTime   = (int) Settings::get('license_cache_time', '0');

// Fetch Gist
$ch = curl_init("https://api.github.com/gists/{$cfg['gist_id']}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $cfg['token'],
        'User-Agent: MCStore-Debug/1.0',
        'Accept: application/vnd.github+json',
    ],
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$gistDomain = '(fetch failed)';
$gistKey    = '(fetch failed)';
if ($code === 200) {
    $gist = json_decode($body, true);
    $content = array_values($gist['files'])[0]['content'] ?? '{}';
    $data = json_decode($content, true) ?? [];
    $key = $savedKey;
    if (isset($data[$key])) {
        $gistDomain = $data[$key]['domain'] ?? '(empty)';
        $gistKey    = 'พบ key ✅';
    } else {
        $gistKey = 'ไม่พบ key ❌';
    }
}

echo "<pre style='font-family:monospace;font-size:14px;padding:20px'>";
echo "=== License Debug ===\n\n";
echo "HTTP_HOST (domain จริง) : {$domain}\n";
echo "Domain ใน Gist          : {$gistDomain}\n";
echo "ตรงกันไหม               : " . ($domain === $gistDomain ? "✅ ตรง" : "❌ ไม่ตรง") . "\n\n";
echo "Key ใน DB               : {$savedKey}\n";
echo "Key ใน Gist             : {$gistKey}\n\n";
echo "Cache result            : {$cacheResult}\n";
echo "Cache time              : " . ($cacheTime ? date('Y-m-d H:i:s', $cacheTime) : 'none') . "\n\n";

// Hash check
$hash       = hash_file('sha256', __DIR__ . '/classes/License.php');
$data2      = json_decode(array_values(json_decode($body,true)['files'])[0]['content'] ?? '{}', true);
$metaHash   = $data2['_meta']['license_hash'] ?? '(ไม่มี)';
echo "License.php hash (เครื่องนี้) : {$hash}\n";
echo "License.php hash (Gist)       : {$metaHash}\n";
echo "Hash ตรงกันไหม                : " . ($hash === $metaHash ? "✅ ตรง" : "❌ ไม่ตรง") . "\n";
echo "</pre>";

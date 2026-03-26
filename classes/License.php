<?php
/**
 * License — ตรวจสอบ license key กับ GitHub Private Gist
 *
 * Gist format (licenses.json):
 * {
 *   "KEY-XXXX": { "domain": "shop.example.com", "expires": "2027-01-01", "active": true }
 * }
 */
class License {
    const CACHE_TTL   = 3600;       // cache 1 ชม.
    const GRACE_DAYS  = 7;          // grace period ถ้า GitHub ไม่ตอบ

    private static $result = null;  // per-request cache

    // ─── Public ───────────────────────────────────────────────

    /** ตรวจสอบว่า license valid ไหม */
    public static function check(): bool {
        if (self::$result !== null) return self::$result;

        $key = Settings::get('license_key', '');
        if (!$key) return self::$result = false;

        // ใช้ cache ในฐานข้อมูลก่อน
        $cached = self::getCached();
        if ($cached !== null) return self::$result = $cached;

        // fetch จาก GitHub Gist
        $valid = self::fetchAndValidate($key);
        self::saveCache($valid);

        return self::$result = $valid;
    }

    /** บังคับ re-validate ครั้งหน้า (ล้าง cache) */
    public static function bust(): void {
        Settings::set('license_cache_time', '0');
        self::$result = null;
    }

    /** ข้อมูลสำหรับแสดงในหน้า Admin */
    public static function info(): array {
        $key      = Settings::get('license_key', '');
        $status   = Settings::get('license_cache_result', '');
        $cachedAt = (int) Settings::get('license_cache_time', '0');
        $domain   = $_SERVER['HTTP_HOST'] ?? 'unknown';

        return [
            'key'        => $key ? (substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4)) : '',
            'key_raw'    => $key,
            'status'     => $status === '1' ? 'active' : ($status === '0' ? 'invalid' : 'not_set'),
            'cached_at'  => $cachedAt > 0 ? date('d/m/Y H:i:s', $cachedAt) : null,
            'domain'     => $domain,
            'valid'      => self::check(),
        ];
    }

    // ─── Private ──────────────────────────────────────────────

    private static function fetchAndValidate(string $key): bool {
        $cfg = self::config();
        if (!$cfg) return false;

        $response = self::githubGet($cfg['gist_id'], $cfg['token']);

        if ($response === null) {
            // GitHub ไม่ตอบ — ใช้ grace period
            return self::graceCache();
        }

        $gist    = json_decode($response, true);
        $content = $gist['files']['licenses.json']['content'] ?? null;
        if (!$content) return false;

        $licenses = json_decode($content, true);
        if (!is_array($licenses) || !isset($licenses[$key])) return false;

        $lic    = $licenses[$key];
        $domain = $_SERVER['HTTP_HOST'] ?? '';

        return ($lic['active'] ?? false) === true
            && ($lic['domain'] ?? '') === $domain
            && strtotime($lic['expires'] ?? '0') > time();
    }

    private static function githubGet(string $gistId, string $token): ?string {
        if (!function_exists('curl_init')) return null;

        $ch = curl_init("https://api.github.com/gists/{$gistId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'User-Agent: MCStore-License/1.0',
                'Accept: application/vnd.github+json',
            ],
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code === 200 && $body) ? $body : null;
    }

    private static function config(): ?array {
        $path = BASE_PATH . '/config/license.php';
        if (!file_exists($path)) return null;
        $cfg = require $path;
        if (empty($cfg['gist_id']) || empty($cfg['token'])) return null;
        return $cfg;
    }

    private static function getCached(): ?bool {
        $val  = Settings::get('license_cache_result');
        $time = (int) Settings::get('license_cache_time', '0');

        if ($val === null || $time === 0) return null;
        if (time() - $time > self::CACHE_TTL) return null;  // หมดอายุ

        return $val === '1';
    }

    private static function saveCache(bool $valid): void {
        Settings::set('license_cache_result', $valid ? '1' : '0');
        Settings::set('license_cache_time',   (string) time());
    }

    private static function graceCache(): bool {
        $val  = Settings::get('license_cache_result');
        $time = (int) Settings::get('license_cache_time', '0');

        // ถ้าเคย valid และยังอยู่ในช่วง grace
        return $val === '1' && $time > 0
            && (time() - $time) < (self::GRACE_DAYS * 86400);
    }
}

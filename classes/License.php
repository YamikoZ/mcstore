<?php
/**
 * License — ระบบป้องกันหลายชั้น
 *
 * ชั้นที่ 1 — Domain binding      : key ผูกกับ domain เท่านั้น
 * ชั้นที่ 2 — Activation lock      : ใช้ครั้งแรก → lock domain ลง Gist ถาวร
 * ชั้นที่ 3 — Write-back log       : บันทึก domain + IP + เวลา กลับ Gist
 * ชั้นที่ 4 — Remote integrity     : Gist เก็บ hash ของ License.php ถ้าถูกแก้ไม่ผ่าน
 * ชั้นที่ 5 — Multi checkpoints    : check ซ้ำในทุก API สำคัญ
 * ชั้นที่ 6 — Cache + Grace period : เร็ว + ทนทานถ้า GitHub ล่ม
 */
class License {
    const CACHE_TTL         = 86400;  // cache 24 ชั่วโมง
    const REVOKED_CACHE_TTL = 60;     // revoked cache 1 นาที (มีผลเร็ว)
    const GRACE_DAYS        = 7;      // ถ้า GitHub ล่ม → ใช้ผลเดิม 7 วัน

    private static $result         = null;
    private static $reason         = null;  // 'valid','revoked','expired','domain_mismatch','invalid','no_key','network'
    private static $cachedExpires  = null;  // เก็บ expires date จาก Gist เพื่อ save ลง cache

    // ─── Public ───────────────────────────────────────────────

    public static function check(): bool {
        if (self::$result !== null) return self::$result;

        $key = Settings::get('license_key', '');
        if (!$key) {
            self::$reason = 'no_key';
            return self::$result = false;
        }

        $cached = self::getCached();
        if ($cached !== null) return self::$result = $cached;

        $valid = self::fetchValidateActivate($key);
        self::saveCache($valid, self::$reason ?? ($valid ? 'valid' : 'invalid'), self::$cachedExpires ?? '');
        return self::$result = $valid;
    }

    public static function reason(): ?string {
        if (self::$result === null) self::check();
        return self::$reason;
    }

    public static function bust(): void {
        Settings::set('license_cache_time', '0');
        Settings::set('license_cache_reason', '');
        Settings::set('license_cache_expires', '');
        self::$result        = null;
        self::$reason        = null;
        self::$cachedExpires = null;
    }

    public static function requireValid(): void {
        if (!self::check()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'License ไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public static function info(): array {
        $key      = Settings::get('license_key', '');
        $status   = Settings::get('license_cache_result', '');
        $cachedAt = (int) Settings::get('license_cache_time', '0');

        return [
            'key'       => $key ? (substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4)) : '',
            'key_raw'   => $key,
            'status'    => $status === '1' ? 'active' : ($status === '0' ? 'invalid' : 'not_set'),
            'cached_at' => $cachedAt > 0 ? date('d/m/Y H:i:s', $cachedAt) : null,
            'domain'    => $_SERVER['HTTP_HOST'] ?? '',
            'valid'     => self::check(),
            'reason'    => self::reason(),
        ];
    }

    // ─── Core ─────────────────────────────────────────────────

    private static function fetchValidateActivate(string $key): bool {
        $cfg = self::config();
        if (!$cfg) {
            self::$reason = 'invalid';
            return false;
        }

        $response = self::githubGet($cfg['gist_id'], $cfg['token']);
        if ($response === null) {
            self::$reason = 'network';
            return self::graceCache();
        }

        $gist    = json_decode($response, true);
        $files   = $gist['files'] ?? [];
        $content  = null;
        $filename = null;
        foreach ($files as $fname => $file) {
            $content  = $file['content'] ?? null;
            $filename = $fname;
            if ($content) break;
        }
        if (!$content || !$filename) {
            self::$reason = 'invalid';
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            self::$reason = 'invalid';
            return false;
        }

        // ชั้น 4: Remote integrity
        $expectedHash = $data['_meta']['license_hash'] ?? null;
        if ($expectedHash !== null) {
            if (!hash_equals($expectedHash, hash_file('sha256', __FILE__))) {
                self::$reason = 'invalid';
                return false;
            }
        }

        if (!isset($data[$key])) {
            self::$reason = 'invalid';
            return false;
        }

        $lic    = $data[$key];
        // Normalize HTTP_HOST: strip port and www. prefix
        $domain = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $domain = preg_replace('/:\d+$/', '', $domain);          // strip :port
        $domain = preg_replace('/^www\./', '', $domain);          // strip www.

        // ชั้น 2: ถูกระงับ
        if (!($lic['active'] ?? false)) {
            self::$reason = 'revoked';
            return false;
        }

        // หมดอายุ
        if (strtotime($lic['expires'] ?? '0') <= time()) {
            self::$reason = 'expired';
            return false;
        }

        $licDomain = $lic['domain'] ?? '';
        $licIp     = $lic['ip']     ?? '';
        $serverIp  = $_SERVER['SERVER_ADDR'] ?? '';

        // First activation — lock domain + IP + write back
        if (empty($licDomain) && empty($licIp)) {
            $data[$key]['domain']       = $domain;
            $data[$key]['ip']           = $serverIp;
            $data[$key]['activated_at'] = date('Y-m-d H:i:s');
            $data[$key]['activated_ip'] = $serverIp;
            $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            self::githubPatch($cfg['gist_id'], $cfg['token'], $filename, $newContent);
            self::$reason = 'valid';
            return true;
        }

        // ตรวจ: ผ่านถ้า domain ตรง หรือ IP ตรง อย่างใดอย่างหนึ่ง
        $domainMatch = !empty($licDomain) && $licDomain === $domain;
        $ipMatch     = !empty($licIp)     && ($licIp === $domain || $licIp === $serverIp);

        if (!$domainMatch && !$ipMatch) {
            self::$reason = 'domain_mismatch';
            return false;
        }

        self::$reason        = 'valid';
        self::$cachedExpires = $lic['expires'] ?? '';
        return true;
    }

    // ─── GitHub API ───────────────────────────────────────────

    private static function githubGet(string $gistId, string $token): ?string {
        $url = "https://api.github.com/gists/{$gistId}";
        $headers = [
            'Authorization: Bearer ' . $token,
            'User-Agent: MCStore-License/1.0',
            'Accept: application/vnd.github+json',
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && $body) return $body;
        }

        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'header'  => implode("\r\n", $headers),
                    'method'  => 'GET',
                ],
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body !== false) return $body;
        }

        return null;
    }

    private static function githubPatch(string $gistId, string $token, string $filename, string $content): void {
        if (!function_exists('curl_init')) return;

        $payload = json_encode(['files' => [$filename => ['content' => $content]]]);
        $ch = curl_init("https://api.github.com/gists/{$gistId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'User-Agent: MCStore-License/1.0',
                'Accept: application/vnd.github+json',
                'Content-Type: application/json',
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // ─── Cache ────────────────────────────────────────────────

    private static function getCached(): ?bool {
        $val     = Settings::get('license_cache_result');
        $time    = (int) Settings::get('license_cache_time', '0');
        $reason  = Settings::get('license_cache_reason', '');
        $expires = Settings::get('license_cache_expires', '');

        if ($val === null || $time === 0) return null;

        // ตรวจ expires ทันทีแบบ local — ไม่ต้องรอ cache หมด
        if ($val === '1' && $expires && strtotime($expires) <= time()) {
            self::$reason = 'expired';
            self::saveCache(false, 'expired');
            return false;
        }

        // revoked → short TTL (5 นาที) เพื่อให้มีผลเร็ว
        $ttl = ($reason === 'revoked') ? self::REVOKED_CACHE_TTL : self::CACHE_TTL;
        if (time() - $time > $ttl) return null;

        if ($val === '0' && $reason) self::$reason = $reason;
        return $val === '1';
    }

    private static function saveCache(bool $valid, string $reason = '', string $expires = ''): void {
        Settings::set('license_cache_result', $valid ? '1' : '0');
        Settings::set('license_cache_reason', $reason);
        Settings::set('license_cache_expires', $expires);

        // network error → ไม่อัปเดต timestamp (ใช้ grace period เดิม)
        if ($reason !== 'network') {
            Settings::set('license_cache_time', (string) time());
        }
    }

    private static function graceCache(): bool {
        $val  = Settings::get('license_cache_result');
        $time = (int) Settings::get('license_cache_time', '0');
        if ($val === '1' && $time > 0 && (time() - $time) < (self::GRACE_DAYS * 86400)) {
            self::$reason = 'valid';
            return true;
        }
        self::$reason = 'network';
        return false;
    }

    private static function config(): ?array {
        $path = BASE_PATH . '/config/license.php';
        if (!file_exists($path)) return null;
        $cfg = require $path;
        if (empty($cfg['gist_id']) || empty($cfg['token'])) return null;
        return $cfg;
    }
}

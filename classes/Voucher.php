<?php

/**
 * TrueWallet Voucher redemption
 * @author M4h45amu7x (adapted for MCStore)
 *
 * 3 modes (ลำดับความสำคัญ):
 *  1. Proxy API   — ตั้ง tw_proxy_url + tw_proxy_key ใน settings
 *  2. curl-impersonate binary ใน bin/ (Linux only)
 *  3. PHP curl fallback (อาจโดน Cloudflare block)
 */

class Voucher
{
    private string $mobile;
    private string $voucherId;
    private string $proxyUrl;
    private string $proxyKey;
    private string $binPath;

    public function __construct(string $mobile, string $voucher)
    {
        $this->mobile    = $mobile;
        $this->voucherId = $this->extractVoucherId($voucher);

        // อ่าน proxy config จาก Settings (ถ้ามี)
        $this->proxyUrl = class_exists('Settings') ? Settings::get('tw_proxy_url', '') : '';
        $this->proxyKey = class_exists('Settings') ? Settings::get('tw_proxy_key', '') : '';
        $this->binPath  = $this->resolveBinary();
    }

    public function getVoucherId(): string
    {
        return $this->voucherId;
    }

    public function verify(): mixed
    {
        if ($this->proxyUrl) {
            return $this->requestViaProxy('verify');
        }
        $url = "https://gift.truemoney.com/campaign/vouchers/{$this->voucherId}/verify?mobile={$this->mobile}";
        return $this->requestDirect('GET', $url);
    }

    public function redeem(): mixed
    {
        // pre-flight check
        if (class_exists('Settings') && Settings::get('license_cache_result', '0') !== '1') {
            return ['status' => 'fail', 'message' => ['en' => 'Service unavailable', 'th' => 'ไม่สามารถดำเนินการได้']];
        }
        if ($this->proxyUrl) {
            return $this->requestViaProxy('redeem');
        }
        $url  = "https://gift.truemoney.com/campaign/vouchers/{$this->voucherId}/redeem";
        $body = json_encode([
            'mobile'       => $this->mobile,
            'voucher_hash' => $this->voucherId,
        ]);
        return $this->requestDirect('POST', $url, $body);
    }

    // ─── Mode 1: Proxy API ───────────────────────────────

    private function requestViaProxy(string $action): mixed
    {
        $url  = rtrim($this->proxyUrl, '/') . '/' . $action;
        $body = json_encode([
            'mobile'      => $this->mobile,
            'voucher_url' => 'https://gift.truemoney.com/campaign/?v=' . $this->voucherId,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->proxyKey,
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $resp     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return $this->err('PROXY_ERROR', 'ไม่สามารถเชื่อมต่อ Proxy: ' . $curlErr);
        }
        if ($httpCode === 401) {
            return $this->err('PROXY_AUTH', 'Proxy API Key ไม่ถูกต้อง');
        }
        if ($httpCode < 200 || $httpCode >= 500) {
            return $this->err('PROXY_ERROR', "Proxy HTTP {$httpCode}");
        }

        $decoded = json_decode($resp);
        if ($decoded === null) {
            return $this->err('PARSE_ERROR', 'Proxy returned invalid JSON');
        }

        // Proxy returns { proxy_error, data } — unwrap
        if (isset($decoded->proxy_error) && $decoded->proxy_error) {
            return $this->err($decoded->code ?? 'PROXY_ERROR', $decoded->message ?? 'Proxy error');
        }
        if (isset($decoded->data)) {
            return json_decode(json_encode($decoded->data));
        }

        return $decoded;
    }

    // ─── Mode 2 & 3: Direct call ────────────────────────

    private function requestDirect(string $method, string $url, ?string $body = null): mixed
    {
        if ($this->binPath) {
            return $this->requestViaBinary($method, $url, $body);
        }
        return $this->requestViaPhpCurl($method, $url, $body);
    }

    /** Mode 2: curl-impersonate binary — Chrome TLS fingerprint */
    private function requestViaBinary(string $method, string $url, ?string $body = null): mixed
    {
        $cmd = escapeshellarg($this->binPath)
             . ' -s -S --max-time 15'
             . ' -H ' . escapeshellarg('Content-Type: application/json')
             . ' -H ' . escapeshellarg('Accept: application/json, text/plain, */*')
             . ' -H ' . escapeshellarg('Accept-Language: th-TH,th;q=0.9,en;q=0.8')
             . ' -H ' . escapeshellarg('Origin: https://gift.truemoney.com')
             . ' -H ' . escapeshellarg('Referer: https://gift.truemoney.com/campaign/');

        if ($method === 'POST' && $body !== null) {
            $cmd .= ' -X POST -d ' . escapeshellarg($body);
        }

        $cmd .= ' -w ' . escapeshellarg('\n%{http_code}')
              . ' ' . escapeshellarg($url)
              . ' 2>&1';

        $output   = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        $raw = implode("\n", $output);

        if ($exitCode !== 0) {
            return $this->err('EXEC_ERROR', "curl-impersonate exit {$exitCode}: " . substr($raw, 0, 200));
        }

        $lines    = explode("\n", trim($raw));
        $httpCode = (int)array_pop($lines);
        $jsonBody = implode("\n", $lines);

        return $this->parseResponse($httpCode, $jsonBody);
    }

    /** Mode 3: PHP curl fallback */
    private function requestViaPhpCurl(string $method, string $url, ?string $body = null): mixed
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: th-TH,th;q=0.9,en;q=0.8',
                'Origin: https://gift.truemoney.com',
                'Referer: https://gift.truemoney.com/campaign/',
                'User-Agent: Mozilla/5.0 (Linux; Android 14; SM-A546E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
            ],
            CURLOPT_ENCODING       => '',
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if (defined('CURL_HTTP_VERSION_2_0')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }

        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $resp     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return $this->err('CURL_ERROR', $curlErr ?: 'curl request failed');
        }

        return $this->parseResponse($httpCode, $resp);
    }

    // ─── helpers ─────────────────────────────────────────

    private function parseResponse(int $httpCode, string $body): mixed
    {
        if ($httpCode === 403) {
            return $this->err('BLOCKED', 'Cloudflare blocked (HTTP 403) — ตั้ง tw_proxy_url หรือใช้ curl-impersonate บน Linux');
        }
        if ($httpCode < 200 || $httpCode >= 500) {
            return $this->err('HTTP_ERROR', "HTTP {$httpCode}: " . substr($body, 0, 200));
        }

        $decoded = json_decode($body);
        if ($decoded === null) {
            return $this->err('PARSE_ERROR', "Invalid JSON (HTTP {$httpCode}): " . substr($body, 0, 200));
        }
        return $decoded;
    }

    private function resolveBinary(): string
    {
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;

        foreach (['curl_chrome116', 'curl_chrome110', 'curl-impersonate-chrome'] as $name) {
            $path = $base . $name;
            if (file_exists($path) && is_executable($path)) return $path;
        }
        foreach (glob($base . 'curl_chrome*') as $f) {
            if (is_executable($f)) return $f;
        }

        return '';
    }

    private function extractVoucherId(string $voucher): string
    {
        $parts = explode('?v=', $voucher);
        return $parts[1] ?? $voucher;
    }

    private function err(string $code, string $message): object
    {
        return (object)['status' => (object)compact('code', 'message')];
    }
}

<?php
/**
 * Auth Class — Dual mode (plugin/standalone)
 * Handles login, register, password hashing/verification, session management
 */
class Auth {
    private static $config = null;

    private static function config() {
        if (self::$config === null) {
            self::$config = require BASE_PATH . '/config/auth.php';
        }
        return self::$config;
    }

    // ── Hash Functions ──

    public static function hashPassword($password) {
        $config = self::config();
        $algo = ($config['mode'] === 'plugin')
            ? $config['plugin']['hash_algorithm']
            : $config['standalone']['hash_algorithm'];

        switch (strtoupper($algo)) {
            case 'SHA256':
                $salt = self::generateSalt(16);
                $hash = hash('sha256', hash('sha256', $password) . $salt);
                return '$SHA$' . $salt . '$' . $hash;
            case 'BCRYPT':
                return password_hash($password, PASSWORD_BCRYPT);
            case 'ARGON2':
                return password_hash($password, PASSWORD_ARGON2ID);
            case 'PBKDF2':
                $salt = self::generateSalt(16);
                $hash = hash_pbkdf2('sha256', $password, $salt, 10000, 64);
                return '$PBKDF2$' . $salt . '$' . $hash;
            default:
                return password_hash($password, PASSWORD_BCRYPT);
        }
    }

    public static function verifyPassword($password, $storedHash) {
        // Detect hash format
        if (strpos($storedHash, '$SHA$') === 0) {
            $parts = explode('$', $storedHash);
            // $SHA$salt$hash
            $salt = $parts[2];
            $expectedHash = $parts[3];
            $computedHash = hash('sha256', hash('sha256', $password) . $salt);
            return hash_equals($expectedHash, $computedHash);
        }
        if (strpos($storedHash, '$PBKDF2$') === 0) {
            $parts = explode('$', $storedHash);
            $salt = $parts[2];
            $expectedHash = $parts[3];
            $computedHash = hash_pbkdf2('sha256', $password, $salt, 10000, 64);
            return hash_equals($expectedHash, $computedHash);
        }
        // BCRYPT or ARGON2 — use password_verify
        return password_verify($password, $storedHash);
    }

    private static function generateSalt($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }

    // ── User Lookup ──

    public static function findByUsername($username) {
        $db = Database::getInstance();
        $config = self::config();

        if ($config['mode'] === 'plugin') {
            $tbl = $config['plugin']['table'];
            $cols = $config['plugin']['columns'];
            $sql = "SELECT {$cols['id']} AS id, {$cols['username']} AS username, {$cols['password']} AS password,
                    {$cols['email']} AS email
                    FROM {$tbl} WHERE LOWER({$cols['username']}) = LOWER(?)";
        } else {
            $sql = "SELECT id, username, password, email FROM users WHERE LOWER(username) = LOWER(?)";
        }
        return $db->fetch($sql, [$username]);
    }

    // ── Register ──

    public static function register($username, $password, $email = null) {
        $db = Database::getInstance();
        $config = self::config();
        $hashedPassword = self::hashPassword($password);

        if ($config['mode'] === 'plugin') {
            $tbl = $config['plugin']['table'];
            $cols = $config['plugin']['columns'];
            // AuthMe: username=lowercase, realname($cols['username'])=ตามที่พิมพ์จริง
            $db->execute(
                "INSERT INTO {$tbl} (username, {$cols['username']}, {$cols['password']}, {$cols['email']}, {$cols['ip']}, {$cols['register_date']}, {$cols['last_login']})
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [strtolower($username), $username, $hashedPassword, $email ?? '', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', time() * 1000, time() * 1000]
            );
            $pluginUserId = $db->lastInsertId();

            // Create web profile in users table
            $db->execute(
                "INSERT INTO users (username, email, authme_id, role, balance) VALUES (?, ?, ?, 'user', 0.00)",
                [$username, $email ?? '', $pluginUserId]
            );
            return $db->lastInsertId();
        } else {
            $db->execute(
                "INSERT INTO users (username, password, email, role, balance) VALUES (?, ?, ?, 'user', 0.00)",
                [$username, $hashedPassword, $email ?? '']
            );
            return $db->lastInsertId();
        }
    }

    // ── Login ──

    public static function login($username, $password) {
        $pluginUser = self::findByUsername($username);
        if (!$pluginUser) return false;
        if (!self::verifyPassword($password, $pluginUser['password'])) return false;

        // Ensure web profile exists
        $db = Database::getInstance();
        $config = self::config();
        $webUser = null;

        if ($config['mode'] === 'plugin') {
            $webUser = $db->fetch("SELECT * FROM users WHERE authme_id = ?", [$pluginUser['id']]);
            if (!$webUser) {
                // Auto-create web profile
                $db->execute(
                    "INSERT INTO users (username, email, authme_id, role, balance) VALUES (?, ?, ?, 'user', 0.00)",
                    [$pluginUser['username'], $pluginUser['email'] ?? '', $pluginUser['id']]
                );
                $webUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$db->lastInsertId()]);
            }
        } else {
            $webUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$pluginUser['id']]);
        }

        if (!$webUser || $webUser['is_banned']) return false;

        // Set session
        $_SESSION['user_id']  = $webUser['id'];
        $_SESSION['username'] = $webUser['username'];
        $_SESSION['role']     = $webUser['role'];

        // Update last login
        $db->execute("UPDATE users SET last_login_web = NOW() WHERE id = ?", [$webUser['id']]);

        return $webUser;
    }

    // ── Session Helpers ──

    public static function check() {
        if (!isset($_SESSION['user_id'])) return false;
        $db = Database::getInstance();
        $exists = $db->fetch("SELECT id FROM users WHERE id = ?", [$_SESSION['user_id']]);
        if (!$exists) {
            session_unset();
            return false;
        }
        return true;
    }

    public static function user() {
        if (!self::check()) return null;
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function isAdmin() {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }

    // ── Password Change ──

    public static function changePassword($userId, $newPassword) {
        $db = Database::getInstance();
        $config = self::config();
        $hashedPassword = self::hashPassword($newPassword);

        if ($config['mode'] === 'plugin') {
            $webUser = $db->fetch("SELECT authme_id FROM users WHERE id = ?", [$userId]);
            if ($webUser && $webUser['authme_id']) {
                $tbl = $config['plugin']['table'];
                $cols = $config['plugin']['columns'];
                $db->execute("UPDATE {$tbl} SET {$cols['password']} = ? WHERE {$cols['id']} = ?", [$hashedPassword, $webUser['authme_id']]);
            }
        } else {
            $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
        }
    }
}

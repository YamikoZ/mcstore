<?php
/**
 * Settings Class — Loads all settings from DB, cached per request
 */
class Settings {
    private static $cache = null;

    public static function load() {
        if (self::$cache !== null) return;
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    public static function get($key, $default = null) {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function getAll() {
        self::load();
        return self::$cache;
    }

    public static function getGroup($prefix) {
        self::load();
        $result = [];
        foreach (self::$cache as $k => $v) {
            if (strpos($k, $prefix) === 0) {
                $result[substr($k, strlen($prefix))] = $v;
            }
        }
        return $result;
    }

    public static function set($key, $value) {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?",
            [$key, $value, $value]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function reload() {
        self::$cache = null;
        self::load();
    }
}

<?php
/**
 * Database Class — Singleton PDO Wrapper
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = require BASE_PATH . '/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute($sql, $params = []) {
        $retries = 3;
        while ($retries-- > 0) {
            try {
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            } catch (PDOException $e) {
                // 1205 = Lock wait timeout, 1213 = Deadlock — retry
                if ($retries > 0 && in_array($e->errorInfo[1] ?? 0, [1205, 1213])) {
                    usleep(300000); // รอ 0.3 วิแล้วลองใหม่
                    continue;
                }
                throw $e;
            }
        }
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }

    public function count($sql, $params = []) {
        $row = $this->fetch($sql, $params);
        return $row ? (int) reset($row) : 0;
    }
}

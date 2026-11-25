<?php
/**
 * Relational Database Connection & Query Wrapper (PHP PDO + SQLite 3)
 * Enforces foreign-key integrity (PRAGMA foreign_keys = ON), prepared statements
 * with bound parameters to prevent SQL injection, and atomic transactions.
 */

declare(strict_types=1);

class Database
{
    private static ?Database $instance = null;
    private PDO $conn;
    private int $txDepth = 0;

    private function __construct(array $config)
    {
        $dbPath    = $config['path'];
        $isMemory  = ($dbPath === ':memory:');
        $needsInit = $isMemory || !file_exists($dbPath);

        if (!$isMemory) {
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $this->conn = $pdo;

        if ($needsInit) {
            $this->conn->exec((string) file_get_contents($config['schema_file']));
            $this->conn->exec((string) file_get_contents($config['seed_file']));
        }
    }

    public static function getInstance(?array $overrideConfig = null): Database
    {
        if (self::$instance === null || $overrideConfig !== null) {
            $config = $overrideConfig ?? require dirname(__DIR__) . '/config/db.php';
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Execute SELECT query with named bound parameters (:param) and return all rows.
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn(array $r): array => array_change_key_case($r, CASE_LOWER), $rows);
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params);
        return $rows[0] ?? null;
    }

    /**
     * Execute UPDATE or DELETE with bound parameters; returns affected row count.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->conn->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Execute INSERT with bound parameters and return the generated primary key ID.
     */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->conn->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }

    public function beginTransaction(): void
    {
        if ($this->txDepth === 0) {
            $this->conn->beginTransaction();
        }
        $this->txDepth++;
    }

    public function commit(): void
    {
        if ($this->txDepth > 0) {
            $this->txDepth--;
            if ($this->txDepth === 0 && $this->conn->inTransaction()) {
                $this->conn->commit();
            }
        }
    }

    public function rollback(): void
    {
        if ($this->txDepth > 0) {
            $this->txDepth = 0;
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
        }
    }

    private function bindParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $val) {
            $placeholder = str_starts_with((string) $key, ':') ? (string) $key : ':' . $key;
            if ($val === null) {
                $stmt->bindValue($placeholder, null, PDO::PARAM_NULL);
            } elseif (is_int($val)) {
                $stmt->bindValue($placeholder, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($placeholder, (string) $val, PDO::PARAM_STR);
            }
        }
    }
}

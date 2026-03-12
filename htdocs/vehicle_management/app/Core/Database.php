<?php
/**
 * Core Database Class
 * 
 * Singleton wrapper around mysqli for clean access throughout the application.
 * Uses lazy connection: the actual MySQL connection is created only when the
 * first query is executed, not during init(). This prevents boot() from
 * crashing when the database is unavailable, allowing controllers to handle
 * DB errors gracefully.
 */

namespace App\Core;

class Database
{
    private static ?Database $instance = null;
    private ?\mysqli $conn = null;
    private array $config;

    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Establish the actual database connection (called lazily on first use).
     */
    private function connect(): void
    {
        if ($this->conn !== null) {
            return;
        }

        $conn = new \mysqli(
            $this->config['host'] ?? 'localhost',
            $this->config['username'] ?? '',
            $this->config['password'] ?? '',
            $this->config['database'] ?? ''
        );

        if ($conn->connect_error) {
            $error = $conn->connect_error;
            error_log("Database connection failed: " . $error);
            throw new \RuntimeException('Database connection failed: ' . $error);
        }

        $conn->set_charset($this->config['charset'] ?? 'utf8mb4');
        $this->conn = $conn;
    }

    /**
     * Initialize the singleton with config array.
     * Does NOT connect to the database yet (lazy connection).
     */
    public static function init(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not initialized. Call Database::init() first.');
        }
        return self::$instance;
    }

    /**
     * Get the raw mysqli connection (for backward compatibility with existing code).
     */
    public function getConnection(): \mysqli
    {
        $this->connect();
        return $this->conn;
    }

    /**
     * Execute a prepared statement and return the result.
     *
     * @param string $sql   SQL query with ? placeholders
     * @param string $types Parameter types string (e.g. 'iss')
     * @param array  $params Parameter values
     * @return \mysqli_result|bool
     */
    public function query(string $sql, string $types = '', array $params = [])
    {
        $this->connect();

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('Query prepare failed: ' . $this->conn->error);
        }

        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false && $stmt->errno === 0) {
            // For INSERT/UPDATE/DELETE that don't return result sets
            $info = [
                'affected_rows' => $stmt->affected_rows,
                'insert_id'     => $stmt->insert_id,
            ];
            $stmt->close();
            return (object) $info;
        }

        $stmt->close();
        return $result;
    }

    /**
     * Fetch all rows as associative arrays.
     */
    public function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        $result = $this->query($sql, $types, $params);
        if ($result instanceof \mysqli_result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Fetch a single row as an associative array.
     */
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        $result = $this->query($sql, $types, $params);
        if ($result instanceof \mysqli_result) {
            $row = $result->fetch_assoc();
            return $row ?: null;
        }
        return null;
    }

    /**
     * Execute an INSERT/UPDATE/DELETE statement and return affected info.
     */
    public function execute(string $sql, string $types = '', array $params = []): object
    {
        $this->connect();

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('Query prepare failed: ' . $this->conn->error);
        }

        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $success = $stmt->execute();
        $info = (object) [
            'success'       => $success,
            'affected_rows' => $stmt->affected_rows,
            'insert_id'     => $stmt->insert_id,
            'error'         => $stmt->error,
        ];
        $stmt->close();
        return $info;
    }

    /**
     * Close the database connection.
     */
    public function close(): void
    {
        if ($this->conn !== null) {
            $this->conn->close();
            $this->conn = null;
        }
        self::$instance = null;
    }
}

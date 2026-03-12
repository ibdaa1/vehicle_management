<?php
/**
 * Base Model
 * 
 * Provides common database operations for all models.
 * Each model represents a database table and extends this class.
 */

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a record by its primary key.
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            'i',
            [$id]
        );
    }

    /**
     * Get all records from the table.
     */
    public function all(): array
    {
        return $this->db->fetchAll("SELECT * FROM `{$this->table}`");
    }

    /**
     * Find records matching a set of conditions.
     *
     * @param array $conditions ['column' => value, ...]
     */
    public function where(array $conditions): array
    {
        if (empty($conditions)) {
            return $this->all();
        }

        $clauses = [];
        $types   = '';
        $params  = [];

        foreach ($conditions as $column => $value) {
            $clauses[] = "`{$column}` = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $clauses);
        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Insert a new record.
     *
     * @param array $data ['column' => value, ...]
     * @return int|false Insert ID on success, false on failure
     */
    public function create(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $types = '';
        $params = [];

        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }

        $columnList = implode('`, `', $columns);
        $placeholderList = implode(', ', $placeholders);
        $sql = "INSERT INTO `{$this->table}` (`{$columnList}`) VALUES ({$placeholderList})";

        $result = $this->db->execute($sql, $types, $params);
        return $result->success ? $result->insert_id : false;
    }

    /**
     * Update a record by primary key.
     *
     * @param int   $id   Primary key value
     * @param array $data ['column' => value, ...]
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        $types = '';
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`{$column}` = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }

        // Add the WHERE id param
        $types .= 'i';
        $params[] = $id;

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setClauses) . " WHERE `{$this->primaryKey}` = ?";
        $result = $this->db->execute($sql, $types, $params);
        return $result->success;
    }

    /**
     * Delete a record by primary key.
     */
    public function delete(int $id): bool
    {
        $result = $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            'i',
            [$id]
        );
        return $result->success && $result->affected_rows > 0;
    }

    /**
     * Count records, optionally with conditions.
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            $row = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM `{$this->table}`");
            return (int)($row['cnt'] ?? 0);
        }

        $clauses = [];
        $types   = '';
        $params  = [];

        foreach ($conditions as $column => $value) {
            $clauses[] = "`{$column}` = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE " . implode(' AND ', $clauses);
        $row = $this->db->fetchOne($sql, $types, $params);
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get the raw database connection for complex queries.
     */
    protected function connection(): \mysqli
    {
        return $this->db->getConnection();
    }
}

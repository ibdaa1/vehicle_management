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
     * Validate that a column name is safe for SQL interpolation.
     * Only allows alphanumeric characters and underscores.
     */
    protected function validateColumnName(string $column): bool
    {
        return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column);
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
            if (!$this->validateColumnName($column)) {
                throw new \InvalidArgumentException("Invalid column name: {$column}");
            }
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
     * @return int Insert ID on success
     * @throws \RuntimeException on database failure
     */
    public function create(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        foreach ($columns as $col) {
            if (!$this->validateColumnName($col)) {
                throw new \InvalidArgumentException("Invalid column name: {$col}");
            }
        }
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
        if (!$result->success) {
            $err = $result->error ?? 'Unknown database error';
            error_log("BaseModel::create [{$this->table}] failed: {$err}");
            throw new \RuntimeException("Insert into {$this->table} failed: {$err}");
        }
        return $result->insert_id;
    }

    /**
     * Update a record by primary key.
     *
     * @param int   $id   Primary key value
     * @param array $data ['column' => value, ...]
     * @return bool true on success
     * @throws \RuntimeException on database failure
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
            if (!$this->validateColumnName($column)) {
                throw new \InvalidArgumentException("Invalid column name: {$column}");
            }
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
        if (!$result->success) {
            $err = $result->error ?? 'Unknown database error';
            error_log("BaseModel::update [{$this->table}] id={$id} failed: {$err}");
            throw new \RuntimeException("Update {$this->table} id={$id} failed: {$err}");
        }
        return true;
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
            if (!$this->validateColumnName($column)) {
                throw new \InvalidArgumentException("Invalid column name: {$column}");
            }
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

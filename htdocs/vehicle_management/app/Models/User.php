<?php
/**
 * User Model
 * 
 * Represents the users table.
 * Handles user data access and authentication queries.
 * 
 * Table: users (id, emp_id, username, email, password_hash, preferred_language,
 *               phone, gender, role_id, profile_image, is_active,
 *               department_id, section_id, division_id, created_at, updated_at)
 */

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';

    /**
     * Find a user by identifier (emp_id, email, username, or phone).
     */
    public function findByIdentifier(string $identifier): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE emp_id = ? OR email = ? OR username = ? OR phone = ? LIMIT 1",
            'ssss',
            [$identifier, $identifier, $identifier, $identifier]
        );
    }

    /**
     * Find a user by emp_id.
     */
    public function findByEmpId(string $empId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE emp_id = ? LIMIT 1",
            's',
            [$empId]
        );
    }

    /**
     * Get user public data (without password_hash).
     */
    public function getPublicData(int $userId): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT id, emp_id, username, email, phone, role_id, preferred_language,
                    gender, profile_image, is_active, department_id, section_id, division_id,
                    created_at, updated_at
             FROM users WHERE id = ? LIMIT 1",
            'i',
            [$userId]
        );

        if (!$row) {
            return null;
        }

        $row['id'] = (int)$row['id'];
        $row['role_id'] = (int)$row['role_id'];
        $row['is_active'] = (bool)$row['is_active'];
        $row['department_id'] = $row['department_id'] ? (int)$row['department_id'] : null;
        $row['section_id'] = $row['section_id'] ? (int)$row['section_id'] : null;
        $row['division_id'] = $row['division_id'] ? (int)$row['division_id'] : null;

        return $row;
    }

    /**
     * Verify a user's password.
     */
    public function verifyPassword(array $user, string $password): bool
    {
        return isset($user['password_hash']) && password_verify($password, $user['password_hash']);
    }

    /**
     * Create a session token for a user.
     *
     * @return string|false Token string on success, false on failure
     */
    public function createSessionToken(int $userId, string $userAgent = '', string $ip = '')
    {
        $token = bin2hex(random_bytes(32));
        $tz = new \DateTimeZone('Asia/Dubai');
        $now = new \DateTime('now', $tz);
        $createdAt = $now->format('Y-m-d H:i:s');
        $expiresAt = (clone $now)->modify('+7 days')->format('Y-m-d H:i:s');

        $result = $this->db->execute(
            "INSERT INTO user_sessions (user_id, token, user_agent, ip, created_at, expires_at, revoked, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?)",
            'issssss',
            [$userId, $token, $userAgent, $ip, $createdAt, $expiresAt, $createdAt]
        );

        return $result->success ? $token : false;
    }

    /**
     * Revoke a session token.
     */
    public function revokeToken(string $token): bool
    {
        $result = $this->db->execute(
            "UPDATE user_sessions SET revoked = 1 WHERE token = ?",
            's',
            [$token]
        );
        return $result->success;
    }

    /**
     * Revoke all session tokens for a user.
     */
    public function revokeAllTokens(int $userId): bool
    {
        $result = $this->db->execute(
            "UPDATE user_sessions SET revoked = 1 WHERE user_id = ?",
            'i',
            [$userId]
        );
        return $result->success;
    }
}

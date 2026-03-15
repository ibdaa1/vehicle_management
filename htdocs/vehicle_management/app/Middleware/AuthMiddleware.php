<?php
/**
 * Authentication Middleware
 * 
 * Validates user authentication via Bearer token or PHP session.
 * Provides the authenticated user data to downstream controllers.
 */

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    /**
     * Authenticate the request and return user data.
     * Returns null if not authenticated (does not stop execution).
     */
    public static function check(Request $request): ?array
    {
        $db = Database::getInstance();

        // 1. Try Bearer token authentication
        $token = $request->bearerToken();
        if ($token) {
            $row = $db->fetchOne(
                "SELECT user_id FROM user_sessions WHERE token = ? AND revoked = 0 AND expires_at > NOW() LIMIT 1",
                's',
                [$token]
            );
            if ($row) {
                return self::getUserById((int)$row['user_id']);
            }
        }

        // 2. Fallback to PHP session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessUser = $_SESSION['user'] ?? null;
        if ($sessUser && !empty($sessUser['id'])) {
            return self::getUserById((int)$sessUser['id']);
        }

        return null;
    }

    /**
     * Require authentication. Sends 401 response if not authenticated.
     * Returns user array or empty array (check Response::isSent() after calling).
     */
    public static function requireAuth(Request $request): array
    {
        $user = self::check($request);
        if (!$user) {
            Response::error('Not authenticated', 401);
            return [];
        }
        return $user;
    }

    /**
     * Require admin role. Sends 403 response if not admin.
     * Returns user array or empty array (check Response::isSent() after calling).
     */
    public static function requireAdmin(Request $request): array
    {
        $user = self::requireAuth($request);
        if (Response::isSent()) {
            return [];
        }
        if (!in_array((int)$user['role_id'], [1, 2], true)) {
            Response::error('Forbidden: admin access required', 403);
            return [];
        }
        return $user;
    }

    /**
     * Fetch user data by ID.
     */
    private static function getUserById(int $userId): ?array
    {
        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT id, emp_id, username, email, phone, gender, role_id, preferred_language, sector_id, department_id, section_id, division_id FROM users WHERE id = ? AND is_active = 1 LIMIT 1",
            'i',
            [$userId]
        );

        if (!$row) {
            return null;
        }

        return [
            'id'                 => (int)$row['id'],
            'emp_id'             => $row['emp_id'],
            'username'           => $row['username'],
            'email'              => $row['email'],
            'phone'              => $row['phone'],
            'gender'             => $row['gender'] ?? null,
            'role_id'            => (int)$row['role_id'],
            'preferred_language' => $row['preferred_language'] ?? 'ar',
            'sector_id'          => $row['sector_id'] ? (int)$row['sector_id'] : null,
            'department_id'      => $row['department_id'] ? (int)$row['department_id'] : null,
            'section_id'         => $row['section_id'] ? (int)$row['section_id'] : null,
            'division_id'        => $row['division_id'] ? (int)$row['division_id'] : null,
        ];
    }
}

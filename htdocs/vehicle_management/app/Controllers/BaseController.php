<?php
/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 * Handles authentication state and response helpers.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

abstract class BaseController
{
    protected ?array $currentUser = null;

    /**
     * Require the user to be authenticated.
     * Sends 401 error if not authenticated and returns empty array.
     */
    protected function requireAuth(Request $request): array
    {
        $user = $this->authenticate($request);
        if (!$user) {
            Response::error('Not authenticated', 401);
            return [];
        }
        return $user;
    }

    /**
     * Require the user to have admin role (role_id 1 or 2).
     * Sends 403 error if not authorized and returns empty array.
     */
    protected function requireAdmin(Request $request): array
    {
        $user = $this->requireAuth($request);
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
     * Authenticate the user via token or PHP session.
     * Returns user data array or null.
     */
    protected function authenticate(Request $request): ?array
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $db = Database::getInstance();

        // Try token authentication first
        $token = $request->bearerToken();
        if ($token) {
            try {
                $row = $db->fetchOne(
                    "SELECT user_id FROM user_sessions WHERE token = ? AND revoked = 0 AND expires_at > NOW() LIMIT 1",
                    's',
                    [$token]
                );
                if ($row) {
                    $this->currentUser = $this->getUserById((int)$row['user_id']);
                    return $this->currentUser;
                }
            } catch (\Throwable $e) {
                error_log("BaseController::authenticate token error: " . $e->getMessage());
            }
        }

        // Fallback to PHP session
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $sessUser = $_SESSION['user'] ?? null;
            if ($sessUser && !empty($sessUser['id'])) {
                $this->currentUser = $this->getUserById((int)$sessUser['id']);
                return $this->currentUser;
            }
        } catch (\Throwable $e) {
            error_log("BaseController::authenticate session error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch user data by ID from database.
     */
    protected function getUserById(int $userId): ?array
    {
        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT id, emp_id, username, email, phone, gender, role_id, preferred_language, department_id, section_id, division_id FROM users WHERE id = ? LIMIT 1",
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
            'department_id'      => $row['department_id'] ? (int)$row['department_id'] : null,
            'section_id'         => $row['section_id'] ? (int)$row['section_id'] : null,
            'division_id'        => $row['division_id'] ? (int)$row['division_id'] : null,
        ];
    }

    /**
     * Require the user to have a specific permission.
     * Sends 403 error if the user's role does not have the permission.
     */
    protected function requirePermission(Request $request, string $permissionKey): array
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) {
            return [];
        }
        if (!\App\Middleware\PermissionMiddleware::hasPermission((int)$user['role_id'], $permissionKey)) {
            Response::error('Forbidden: missing permission ' . $permissionKey, 403);
            return [];
        }
        return $user;
    }

    /**
     * Validate that required fields are present in input.
     *
     * @param array $data   Input data
     * @param array $fields Required field names
     * @return array        Missing fields (empty if all present)
     */
    protected function validateRequired(array $data, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}

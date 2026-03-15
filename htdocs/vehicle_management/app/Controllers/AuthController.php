<?php
/**
 * Auth Controller
 * 
 * Handles authentication: login, logout, session check.
 * MVC replacement for api/users/login.php and api/users/session_check.php
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AuthController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * POST /api/v1/auth/login
     * 
     * Authenticate a user with identifier (emp_id/email/username/phone) + password.
     * Returns auth token and user data on success.
     */
    public function login(Request $request, array $params = []): void
    {
        // Check multiple identifier fields in priority order
        $identifier = '';
        foreach (['username', 'login', 'emp_id', 'email', 'phone'] as $field) {
            $value = $request->input($field, '');
            if ($value !== '') {
                $identifier = trim((string)$value);
                break;
            }
        }
        $password = (string)$request->input('password', '');

        if ($identifier === '' || $password === '') {
            Response::error('emp_id/email/phone/username and password are required.', 400);
            return;
        }

        // Find user by any identifier
        try {
            $user = $this->userModel->findByIdentifier($identifier);
        } catch (\Throwable $e) {
            error_log("AuthController::login DB error: " . $e->getMessage());
            Response::error('Database connection error. Please contact administrator.', 500);
            return;
        }

        if (!$user || !$this->userModel->verifyPassword($user, $password)) {
            Response::error('Invalid credentials.', 401);
            return;
        }

        if ((int)$user['is_active'] !== 1) {
            Response::error('Account not active.', 403);
            return;
        }

        // Create session token
        try {
            $token = $this->userModel->createSessionToken(
                (int)$user['id'],
                $request->userAgent(),
                $request->ip()
            );
        } catch (\Throwable $e) {
            error_log("AuthController::login token error: " . $e->getMessage());
            Response::error('Server error: failed to create session token.', 500);
            return;
        }

        if ($token === false) {
            Response::error('Server error: failed to create session token.', 500);
            return;
        }

        // Load permissions for the user (same logic as check())
        $permissions = [];
        $resources = [];
        $roleId = (int)($user['role_id'] ?? 0);
        try {
            if ($roleId === 1) {
                $permissions = ['*'];
                // Also load resource permissions for superadmin
                $rolePerms = \App\Middleware\PermissionMiddleware::getRolePermissions($roleId);
                foreach (($rolePerms['resources'] ?? []) as $r) {
                    $resources[] = $r;
                }
            } else {
                $rolePerms = \App\Middleware\PermissionMiddleware::getRolePermissions($roleId);
                foreach (($rolePerms['permissions'] ?? []) as $p) {
                    $permissions[] = $p['key_name'];
                }
                foreach (($rolePerms['resources'] ?? []) as $r) {
                    $resources[] = $r;
                }
            }
        } catch (\Throwable $e) {
            error_log("AuthController::login permissions error: " . $e->getMessage());
            // Superadmin fallback: ensure full access even if DB query fails
            if ($roleId === 1) {
                $permissions = ['*'];
                if (empty($resources)) {
                    $resources = \App\Middleware\PermissionMiddleware::getDefaultSuperadminResources();
                }
            }
        }

        // Store in PHP session as well
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user'] = [
            'id'                 => (int)$user['id'],
            'emp_id'             => $user['emp_id'],
            'username'           => $user['username'],
            'email'              => $user['email'],
            'role_id'            => (int)$user['role_id'],
            'preferred_language' => $user['preferred_language'] ?? 'ar',
        ];
        session_write_close();

        Response::json([
            'success'    => true,
            'message'    => 'Login successful.',
            'token'      => $token,
            'session_id' => session_id(),
            'user'       => [
                'id'                 => (int)$user['id'],
                'emp_id'             => $user['emp_id'],
                'username'           => $user['username'],
                'name'               => $user['username'],
                'email'              => $user['email'],
                'phone'              => $user['phone'],
                'gender'             => $user['gender'] ?? null,
                'role_id'            => (int)$user['role_id'],
                'preferred_language' => $user['preferred_language'] ?? 'ar',
                'department_id'      => $user['department_id'] ? (int)$user['department_id'] : null,
                'section_id'         => $user['section_id'] ? (int)$user['section_id'] : null,
                'division_id'        => $user['division_id'] ? (int)$user['division_id'] : null,
                'permissions'        => $permissions,
                'resources'          => $resources,
            ],
        ]);
    }

    /**
     * GET /api/v1/auth/check
     * 
     * Validate the current session/token.
     * Returns user data if authenticated.
     */
    public function check(Request $request, array $params = []): void
    {
        $user = $this->authenticate($request);

        if ($user) {
            // Load permissions for the user
            $permissions = [];
            $resources = [];
            $roleId = (int)($user['role_id'] ?? 0);
            try {
                // Look up role name
                $db = \App\Core\Database::getInstance();
                $roleRow = $db->fetchOne(
                    "SELECT key_name, display_name FROM roles WHERE id = ? LIMIT 1",
                    'i',
                    [$roleId]
                );
                $user['role_name'] = $roleRow['key_name'] ?? '';

                if ($roleId === 1) {
                    // Superadmin has all permissions
                    $permissions = ['*'];
                    // Also load resource permissions so frontend has full data
                    $rolePerms = \App\Middleware\PermissionMiddleware::getRolePermissions($roleId);
                    foreach (($rolePerms['resources'] ?? []) as $r) {
                        $resources[] = $r;
                    }
                } else {
                    // Load module-level permissions from role_permissions + permissions tables
                    $rolePerms = \App\Middleware\PermissionMiddleware::getRolePermissions($roleId);
                    foreach (($rolePerms['permissions'] ?? []) as $p) {
                        $permissions[] = $p['key_name'];
                    }
                    // Load resource-level permissions
                    foreach (($rolePerms['resources'] ?? []) as $r) {
                        $resources[] = $r;
                    }
                }
            } catch (\Throwable $e) {
                error_log("AuthController::check permissions error: " . $e->getMessage());
                // Superadmin fallback: ensure full access even if DB query fails
                if ($roleId === 1) {
                    $permissions = ['*'];
                    if (empty($resources)) {
                        $resources = \App\Middleware\PermissionMiddleware::getDefaultSuperadminResources();
                    }
                }
            }

            $user['permissions'] = $permissions;
            $user['resources'] = $resources;

            Response::json([
                'success'    => true,
                'user'       => $user,
                'data'       => $user,
                'isLoggedIn' => true,
            ]);
            return;
        }

        Response::json([
            'success'    => false,
            'message'    => 'Not authenticated',
            'isLoggedIn' => false,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     * 
     * Revoke the current token and destroy session.
     */
    public function logout(Request $request, array $params = []): void
    {
        // Revoke token if present
        $token = $request->bearerToken();
        if ($token) {
            $this->userModel->revokeToken($token);
        }

        // Destroy PHP session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        Response::success(null, 'Logged out successfully.');
    }
}

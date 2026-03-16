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

        // Ensure user_sessions table exists (safety net for fresh installs)
        try {
            $db = \App\Core\Database::getInstance();
            $db->execute("CREATE TABLE IF NOT EXISTS `user_sessions` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `token` CHAR(64) NOT NULL,
                `user_agent` TEXT DEFAULT NULL,
                `ip` VARCHAR(45) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `expires_at` DATETIME DEFAULT NULL,
                `revoked` TINYINT(1) DEFAULT 0,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_token` (`token`),
                KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            error_log("AuthController::login user_sessions table check: " . $e->getMessage());
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
                'sector_id'          => $user['sector_id'] ? (int)$user['sector_id'] : null,
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
     * POST /api/v1/auth/register
     *
     * Self-registration endpoint (no auth required).
     * Accepts: emp_id, username, email, password, role_id,
     *          department_id, section_id, division_id, sector_id,
     *          preferred_language, phone
     */
    public function register(Request $request, array $params = []): void
    {
        $data = $request->all();

        $required = ['emp_id', 'username', 'email', 'password', 'role_id'];
        $missing = [];
        foreach ($required as $r) {
            if (!isset($data[$r]) || trim((string)$data[$r]) === '') {
                $missing[] = $r;
            }
        }
        if (!empty($missing)) {
            Response::error('Missing fields: ' . implode(',', $missing), 400);
            return;
        }

        $emp_id   = trim((string)$data['emp_id']);
        $username = trim((string)$data['username']);
        $email    = trim((string)$data['email']);
        $password = (string)$data['password'];
        $role_id  = (int)$data['role_id'];
        $department_id = isset($data['department_id']) && $data['department_id'] !== '' ? (int)$data['department_id'] : null;
        $section_id    = isset($data['section_id'])    && $data['section_id'] !== ''    ? (int)$data['section_id']    : null;
        $division_id   = isset($data['division_id'])   && $data['division_id'] !== ''   ? (int)$data['division_id']   : null;
        $sector_id     = isset($data['sector_id'])     && $data['sector_id'] !== ''     ? (int)$data['sector_id']     : null;
        $preferred_language = (isset($data['preferred_language']) && in_array($data['preferred_language'], ['en','ar'])) ? $data['preferred_language'] : 'en';
        $phone = isset($data['phone']) && $data['phone'] !== '' ? trim((string)$data['phone']) : null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email.', 400);
            return;
        }
        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters.', 400);
            return;
        }

        $db = \App\Core\Database::getInstance();

        try {
            // Ensure sectors infrastructure exists
            $db->getConnection()->query("CREATE TABLE IF NOT EXISTS `sectors` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `sector_code` VARCHAR(50) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `name_en` VARCHAR(255) DEFAULT NULL,
                `description` MEDIUMTEXT DEFAULT NULL,
                `manager_user_id` INT(11) DEFAULT NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_sector_code` (`sector_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Ensure sector_id column exists on users table
            $colCheck = $db->fetchAll("SHOW COLUMNS FROM `users` LIKE 'sector_id'");
            if (empty($colCheck)) {
                $db->getConnection()->query("ALTER TABLE `users` ADD COLUMN `sector_id` INT(11) DEFAULT NULL AFTER `role_id`");
            }

            // Validate optional references
            $invalid = [];
            if ($sector_id !== null) {
                $r = $db->fetchOne("SELECT 1 FROM `sectors` WHERE `id` = ? LIMIT 1", 'i', [$sector_id]);
                if (!$r) $invalid[] = "sector_id:{$sector_id}";
            }
            if ($department_id !== null) {
                $r = $db->fetchOne("SELECT 1 FROM `Departments` WHERE `department_id` = ? LIMIT 1", 'i', [$department_id]);
                if (!$r) $invalid[] = "department_id:{$department_id}";
            }
            if ($section_id !== null) {
                $r = $db->fetchOne("SELECT 1 FROM `Sections` WHERE `section_id` = ? LIMIT 1", 'i', [$section_id]);
                if (!$r) $invalid[] = "section_id:{$section_id}";
            }
            if ($division_id !== null) {
                $r = $db->fetchOne("SELECT 1 FROM `Divisions` WHERE `division_id` = ? LIMIT 1", 'i', [$division_id]);
                if (!$r) $invalid[] = "division_id:{$division_id}";
            }
            if (!empty($invalid)) {
                Response::json(['success' => false, 'message' => 'Invalid reference ids.', 'invalid' => $invalid], 400);
                return;
            }

            // Uniqueness check
            $existing = $db->fetchOne(
                "SELECT emp_id, username, email FROM users WHERE emp_id = ? OR username = ? OR email = ? LIMIT 1",
                'sss',
                [$emp_id, $username, $email]
            );
            if ($existing) {
                $conflict = [];
                if ($existing['emp_id'] === $emp_id) $conflict[] = 'emp_id';
                if ($existing['username'] === $username) $conflict[] = 'username';
                if ($existing['email'] === $email) $conflict[] = 'email';
                Response::json(['success' => false, 'message' => 'Conflict: ' . implode(',', $conflict)], 409);
                return;
            }

            // Build insert
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $fields = 'emp_id, username, email, password_hash, preferred_language, phone, role_id, is_active, created_at, updated_at';
            $placeholders = '?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW()';
            $types = 'ssssssi';
            $binds = [$emp_id, $username, $email, $passwordHash, $preferred_language, $phone, $role_id];

            if ($sector_id !== null) {
                $fields .= ', sector_id';
                $placeholders .= ', ?';
                $types .= 'i';
                $binds[] = $sector_id;
            }
            if ($department_id !== null) {
                $fields .= ', department_id';
                $placeholders .= ', ?';
                $types .= 'i';
                $binds[] = $department_id;
            }
            if ($section_id !== null) {
                $fields .= ', section_id';
                $placeholders .= ', ?';
                $types .= 'i';
                $binds[] = $section_id;
            }
            if ($division_id !== null) {
                $fields .= ', division_id';
                $placeholders .= ', ?';
                $types .= 'i';
                $binds[] = $division_id;
            }

            $result = $db->execute(
                "INSERT INTO users ({$fields}) VALUES ({$placeholders})",
                $types,
                $binds
            );

            if (!$result->success) {
                Response::error('Server error: failed to create user.', 500);
                return;
            }
            $newUserId = $result->insert_id;

            // Activation token
            $activationLink = '';
            try {
                $db->getConnection()->query("CREATE TABLE IF NOT EXISTS `user_activations` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `token` VARCHAR(255) NOT NULL,
                    `created_at` DATETIME NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    `used` TINYINT(1) NOT NULL DEFAULT 0,
                    UNIQUE KEY `uk_token` (`token`),
                    KEY `idx_user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $token = bin2hex(random_bytes(32));
                $tz = new \DateTimeZone('Asia/Dubai');
                $now = new \DateTime('now', $tz);
                $created_at = $now->format('Y-m-d H:i:s');
                $expires_at = (clone $now)->modify('+2 days')->format('Y-m-d H:i:s');

                $db->execute(
                    "INSERT INTO user_activations (user_id, token, created_at, expires_at, used) VALUES (?, ?, ?, ?, 0)",
                    'isss',
                    [$newUserId, $token, $created_at, $expires_at]
                );

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $baseUrl = $scheme . '://' . $host;
                $activationLink = rtrim($baseUrl, '/') . "/vehicle_management/api/v1/auth/activate?token={$token}";

                // Send activation email
                $mailSubject = ($preferred_language === 'ar')
                    ? "تفعيل حسابك - نظام متابعة السيارات"
                    : "Activate your account - Vehicle Management";
                if ($preferred_language === 'ar') {
                    $mailBody = "مرحباً {$username},\n\nشكراً لتسجيلك في نظام متابعة السيارات.\nالرجاء الضغط على الرابط لتفعيل حسابك:\n{$activationLink}\n\nهذا الرابط صالح حتى {$expires_at} (Asia/Dubai).\n\nإذا لم تقم بطلب هذا الحساب، تجاهل هذا البريد.";
                } else {
                    $mailBody = "Hello {$username},\n\nThanks for registering at Vehicle Management.\nPlease click the link below to activate your account:\n{$activationLink}\n\nThis link is valid until {$expires_at} (Asia/Dubai).\n\nIf you did not request this, please ignore this email.";
                }

                $this->sendActivationMail($email, $mailSubject, $mailBody);
            } catch (\Throwable $actEx) {
                error_log("Activation step failed for user {$newUserId}: " . $actEx->getMessage());
            }

            Response::json([
                'success' => true,
                'message' => 'User registered successfully.',
                'user_id' => $newUserId,
                'activation_link' => $activationLink ?: null,
            ]);
        } catch (\Throwable $e) {
            error_log("AuthController::register error: " . $e->getMessage());
            Response::error('Server error.', 500);
        }
    }

    /**
     * GET /api/v1/auth/activate?token=...
     *
     * Activates a user account via token from email link.
     * Returns HTML page.
     */
    public function activate(Request $request, array $params = []): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $token = trim((string)($request->input('token', '')));

        if (empty($token)) {
            echo '<h2>Invalid activation link.</h2>';
            return;
        }

        $db = \App\Core\Database::getInstance();

        try {
            // Check if table exists
            $tables = $db->fetchAll("SHOW TABLES LIKE 'user_activations'");
            if (empty($tables)) {
                echo '<h2>Activation system not configured.</h2>';
                return;
            }

            $activation = $db->fetchOne(
                "SELECT id, user_id, expires_at, used FROM user_activations WHERE token = ? LIMIT 1",
                's',
                [$token]
            );

            if (!$activation) {
                echo '<h2>Invalid or expired activation link.</h2>';
                return;
            }

            if ($activation['used']) {
                echo '<h2>Account already activated. You can login.</h2>';
                return;
            }

            $tz = new \DateTimeZone('Asia/Dubai');
            $now = new \DateTime('now', $tz);
            $expires = new \DateTime($activation['expires_at'], $tz);
            if ($now > $expires) {
                echo '<h2>Activation link has expired.</h2>';
                return;
            }

            // Activate the user
            $userId = (int)$activation['user_id'];
            $db->execute("UPDATE users SET is_active = 1 WHERE id = ?", 'i', [$userId]);
            $activationId = (int)$activation['id'];
            $db->execute("UPDATE user_activations SET used = 1 WHERE id = ?", 'i', [$activationId]);

            echo '<h2>Account activated successfully! You can now login.</h2>';
            echo '<p><a href="/vehicle_management/public/login.html">Go to Login</a></p>';
        } catch (\Throwable $e) {
            error_log("AuthController::activate error: " . $e->getMessage());
            echo '<h2>An error occurred during activation.</h2>';
        }
    }

    /**
     * Send activation email using PHP mail().
     */
    private function sendActivationMail(string $to, string $subject, string $bodyPlain): void
    {
        $from = 'hcsfcsto@hcsfcs.top';
        $bodyHtml = nl2br(htmlspecialchars($bodyPlain, ENT_QUOTES, 'UTF-8'));
        $bodyHtml = "<html><body>{$bodyHtml}</body></html>";

        $boundary = md5(uniqid((string)time(), true));
        $headers = [];
        $headers[] = "From: {$from}";
        $headers[] = "Reply-To: {$from}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $multipart = [];
        $multipart[] = "--{$boundary}";
        $multipart[] = "Content-Type: text/plain; charset=UTF-8";
        $multipart[] = "Content-Transfer-Encoding: 8bit";
        $multipart[] = "";
        $multipart[] = $bodyPlain;
        $multipart[] = "";
        $multipart[] = "--{$boundary}";
        $multipart[] = "Content-Type: text/html; charset=UTF-8";
        $multipart[] = "Content-Transfer-Encoding: 8bit";
        $multipart[] = "";
        $multipart[] = $bodyHtml;
        $multipart[] = "";
        $multipart[] = "--{$boundary}--";

        $headersStr = implode("\r\n", $headers) . "\r\n";
        $message = implode("\r\n", $multipart);

        $mailOk = false;
        set_error_handler(function () { return true; });
        try {
            $mailOk = @mail($to, $subject, $message, $headersStr, "-f{$from}");
        } catch (\Throwable $e) {
            // ignore
        }
        restore_error_handler();
        error_log(date('Y-m-d H:i:s') . " sendActivationMail -> to={$to} ok=" . ($mailOk ? '1' : '0'));
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

<?php
/**
 * User Controller
 * 
 * Handles user CRUD operations.
 * All endpoints require admin authentication.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class UserController extends BaseController
{
    /**
     * GET /api/v1/users
     * List all users with optional filters.
     */
    public function index(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $db = Database::getInstance();

        $where = ['1=1'];
        $types = '';
        $binds = [];

        // Filter by role
        $roleId = $request->query('role_id');
        if ($roleId !== null && $roleId !== '') {
            $where[] = 'u.role_id = ?';
            $types .= 'i';
            $binds[] = (int)$roleId;
        }

        // Filter by is_active
        $isActive = $request->query('is_active');
        if ($isActive !== null && $isActive !== '') {
            $where[] = 'u.is_active = ?';
            $types .= 'i';
            $binds[] = (int)$isActive;
        }

        // Filter by department
        $deptId = $request->query('department_id');
        if ($deptId !== null && $deptId !== '') {
            $where[] = 'u.department_id = ?';
            $types .= 'i';
            $binds[] = (int)$deptId;
        }

        // Filter by sector
        $sectorId = $request->query('sector_id');
        if ($sectorId !== null && $sectorId !== '') {
            $where[] = 'u.sector_id = ?';
            $types .= 'i';
            $binds[] = (int)$sectorId;
        }

        // Filter by gender
        $gender = $request->query('gender');
        if ($gender !== null && $gender !== '') {
            $where[] = 'u.gender = ?';
            $types .= 's';
            $binds[] = $gender;
        }

        // Search
        $search = $request->query('search');
        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.emp_id LIKE ? OR u.phone LIKE ?)';
            $types .= 'ssss';
            $binds[] = $like;
            $binds[] = $like;
            $binds[] = $like;
            $binds[] = $like;
        }

        $whereStr = implode(' AND ', $where);

        try {
            $rows = $db->fetchAll(
                "SELECT u.id, u.emp_id, u.username, u.email, u.phone, u.gender,
                        u.role_id, u.is_active, u.preferred_language,
                        u.sector_id, u.department_id, u.section_id, u.division_id,
                        u.profile_image, u.created_at, u.updated_at,
                        r.display_name AS role_name,
                        sec.name AS sector_name, sec.name_en AS sector_name_en,
                        d.name_ar AS department_name_ar
                 FROM users u
                 LEFT JOIN roles r ON r.id = u.role_id
                 LEFT JOIN sectors sec ON sec.id = u.sector_id
                 LEFT JOIN Departments d ON d.department_id = u.department_id
                 WHERE {$whereStr}
                 ORDER BY u.id DESC",
                $types ?: '',
                $binds ?: []
            );

            Response::success($rows);
        } catch (\Throwable $e) {
            error_log("UserController::index error: " . $e->getMessage());
            Response::success([]);
        }
    }

    /**
     * GET /api/v1/users/{id}
     * Get a single user by ID.
     */
    public function show(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid user ID', 400);
            return;
        }

        $db = Database::getInstance();

        try {
            $row = $db->fetchOne(
                "SELECT u.id, u.emp_id, u.username, u.email, u.phone, u.gender,
                        u.role_id, u.is_active, u.preferred_language,
                        u.sector_id, u.department_id, u.section_id, u.division_id,
                        u.profile_image, u.created_at, u.updated_at,
                        r.display_name AS role_name
                 FROM users u
                 LEFT JOIN roles r ON r.id = u.role_id
                 WHERE u.id = ?",
                'i',
                [$id]
            );

            if (!$row) {
                Response::error('User not found', 404);
                return;
            }

            $row['id'] = (int)$row['id'];
            $row['role_id'] = (int)$row['role_id'];
            $row['is_active'] = (int)$row['is_active'];

            Response::success($row);
        } catch (\Throwable $e) {
            error_log("UserController::show error: " . $e->getMessage());
            Response::error('Failed to load user', 500);
        }
    }

    /**
     * POST /api/v1/users
     * Create a new user. Requires admin.
     */
    public function store(Request $request, array $params = []): void
    {
        $admin = $this->requireAdmin($request);
        if (Response::isSent()) return;

        $data = $request->all();

        // Validate required fields
        $missing = $this->validateRequired($data, ['username', 'password']);
        if (!empty($missing)) {
            Response::error('Missing required fields: ' . implode(', ', $missing), 400);
            return;
        }

        $username = trim($data['username']);
        $password = $data['password'];
        $empId = trim($data['emp_id'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $gender = $data['gender'] ?? null;
        $roleId = (int)($data['role_id'] ?? 3);
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $prefLang = $data['preferred_language'] ?? 'ar';
        $sectorId = !empty($data['sector_id']) ? (int)$data['sector_id'] : null;
        $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
        $secId = !empty($data['section_id']) ? (int)$data['section_id'] : null;
        $divId = !empty($data['division_id']) ? (int)$data['division_id'] : null;

        if (strlen($password) < 4) {
            Response::error('Password must be at least 4 characters', 400);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $db = Database::getInstance();

        // Check unique username
        try {
            $existing = $db->fetchOne(
                "SELECT id FROM users WHERE username = ? LIMIT 1",
                's',
                [$username]
            );
            if ($existing) {
                Response::error('اسم المستخدم موجود مسبقاً', 409);
                return;
            }

            // Check unique emp_id (if provided)
            if ($empId !== '') {
                $existingEmp = $db->fetchOne(
                    "SELECT id FROM users WHERE emp_id = ? LIMIT 1",
                    's',
                    [$empId]
                );
                if ($existingEmp) {
                    Response::error('رقم الموظف موجود مسبقاً', 409);
                    return;
                }
            }

            // Check unique email (if provided)
            if ($email !== '') {
                $existingEmail = $db->fetchOne(
                    "SELECT id FROM users WHERE email = ? LIMIT 1",
                    's',
                    [$email]
                );
                if ($existingEmail) {
                    Response::error('البريد الإلكتروني موجود مسبقاً', 409);
                    return;
                }
            }
        } catch (\Throwable $e) {
            // Ignore, let the insert fail if duplicate
        }

        try {
            // Build INSERT dynamically to handle nullable fields
            $cols = ['emp_id', 'username', 'email', 'password_hash', 'phone', 'gender', 'role_id', 'is_active', 'preferred_language', 'created_at'];
            $vals = [$empId, $username, $email, $passwordHash, $phone, $gender, $roleId, $isActive, $prefLang];
            $types = 'sssssssis';
            $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?', 'NOW()'];

            if ($sectorId !== null) {
                $cols[] = 'sector_id';
                $placeholders[] = '?';
                $types .= 'i';
                $vals[] = $sectorId;
            }
            if ($deptId !== null) {
                $cols[] = 'department_id';
                $placeholders[] = '?';
                $types .= 'i';
                $vals[] = $deptId;
            }
            if ($secId !== null) {
                $cols[] = 'section_id';
                $placeholders[] = '?';
                $types .= 'i';
                $vals[] = $secId;
            }
            if ($divId !== null) {
                $cols[] = 'division_id';
                $placeholders[] = '?';
                $types .= 'i';
                $vals[] = $divId;
            }

            $sql = "INSERT INTO users (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $result = $db->execute($sql, $types, $vals);

            if ($result->success) {
                Response::success(['id' => $result->insert_id], 'User created successfully');
            } else {
                $errMsg = 'Failed to create user';
                if (!empty($result->error)) {
                    if (strpos($result->error, 'Duplicate') !== false) {
                        if (strpos($result->error, 'emp_id') !== false) {
                            $errMsg = 'رقم الموظف موجود مسبقاً';
                        } elseif (strpos($result->error, 'email') !== false) {
                            $errMsg = 'البريد الإلكتروني موجود مسبقاً';
                        } elseif (strpos($result->error, 'username') !== false) {
                            $errMsg = 'اسم المستخدم موجود مسبقاً';
                        } else {
                            $errMsg = 'بيانات مكررة: ' . $result->error;
                        }
                    } else {
                        $errMsg = $result->error;
                    }
                }
                Response::error($errMsg, 500);
            }
        } catch (\Throwable $e) {
            error_log("UserController::store error: " . $e->getMessage());
            $errMsg = 'Failed to create user';
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate') !== false) {
                if (strpos($msg, 'emp_id') !== false) {
                    $errMsg = 'رقم الموظف موجود مسبقاً';
                } elseif (strpos($msg, 'email') !== false) {
                    $errMsg = 'البريد الإلكتروني موجود مسبقاً';
                } elseif (strpos($msg, 'username') !== false) {
                    $errMsg = 'اسم المستخدم موجود مسبقاً';
                } else {
                    $errMsg = 'بيانات مكررة';
                }
            }
            Response::error($errMsg, 500);
        }
    }

    /**
     * PUT /api/v1/users/{id}
     * Update a user. Requires admin.
     */
    public function update(Request $request, array $params = []): void
    {
        $admin = $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid user ID', 400);
            return;
        }

        $data = $request->all();
        $db = Database::getInstance();

        // Check user exists
        try {
            $existing = $db->fetchOne("SELECT id FROM users WHERE id = ?", 'i', [$id]);
            if (!$existing) {
                Response::error('User not found', 404);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Database error', 500);
            return;
        }

        // Build dynamic update
        $sets = [];
        $types = '';
        $binds = [];

        $fields = [
            'emp_id'             => 's',
            'username'           => 's',
            'email'              => 's',
            'phone'              => 's',
            'gender'             => 's',
            'preferred_language' => 's',
        ];

        foreach ($fields as $field => $type) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $types .= $type;
                $binds[] = $data[$field] !== '' ? $data[$field] : null;
            }
        }

        // Integer fields
        $intFields = ['role_id', 'is_active', 'sector_id', 'department_id', 'section_id', 'division_id'];
        foreach ($intFields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $types .= 'i';
                $binds[] = $data[$field] !== null && $data[$field] !== '' ? (int)$data[$field] : null;
            }
        }

        // Password update (optional)
        if (!empty($data['password']) && strlen($data['password']) >= 4) {
            $sets[] = "password_hash = ?";
            $types .= 's';
            $binds[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($sets)) {
            Response::error('No fields to update', 400);
            return;
        }

        $sets[] = "updated_at = NOW()";
        $setStr = implode(', ', $sets);
        $types .= 'i';
        $binds[] = $id;

        try {
            $result = $db->execute(
                "UPDATE users SET {$setStr} WHERE id = ?",
                $types,
                $binds
            );

            if ($result->success) {
                Response::success(null, 'User updated successfully');
            } else {
                Response::error('Failed to update user', 500);
            }
        } catch (\Throwable $e) {
            error_log("UserController::update error: " . $e->getMessage());
            Response::error('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/users/{id}
     * Delete a user. Requires admin.
     */
    public function destroy(Request $request, array $params = []): void
    {
        $admin = $this->requireAdmin($request);
        if (Response::isSent()) return;

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid user ID', 400);
            return;
        }

        // Prevent deleting yourself
        if ($id === (int)($admin['id'] ?? 0)) {
            Response::error('Cannot delete your own account', 400);
            return;
        }

        $db = Database::getInstance();

        try {
            // Revoke all sessions first
            $db->execute("UPDATE user_sessions SET revoked = 1 WHERE user_id = ?", 'i', [$id]);

            $result = $db->execute("DELETE FROM users WHERE id = ?", 'i', [$id]);

            if ($result->success && $result->affected_rows > 0) {
                Response::success(null, 'User deleted successfully');
            } elseif (!$result->success) {
                // Foreign key constraint or other DB error
                Response::error('Cannot delete user: referenced by existing records', 409);
            } else {
                Response::error('User not found or already deleted', 404);
            }
        } catch (\Throwable $e) {
            error_log("UserController::destroy error: " . $e->getMessage());
            Response::error('Failed to delete user', 500);
        }
    }
}
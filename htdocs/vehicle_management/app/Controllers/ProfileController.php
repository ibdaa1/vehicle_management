<?php
/**
 * Profile Controller
 *
 * Handles authenticated user's own profile operations.
 * All endpoints require authentication but NOT admin role.
 * Users can view/update their own profile and change password.
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class ProfileController extends BaseController
{
    /**
     * GET /api/v1/profile
     * Get the current authenticated user's profile.
     */
    public function show(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $db = Database::getInstance();

        try {
            $row = $db->fetchOne(
                "SELECT u.id, u.emp_id, u.username, u.email, u.phone, u.gender,
                        u.role_id, u.is_active, u.preferred_language,
                        u.sector_id, u.department_id, u.section_id, u.division_id,
                        u.profile_image, u.created_at, u.updated_at,
                        r.display_name AS role_name,
                        sec.name AS sector_name, sec.name_en AS sector_name_en,
                        d.name_ar AS department_name_ar,
                        s.name_ar AS section_name_ar,
                        dv.name_ar AS division_name_ar
                 FROM users u
                 LEFT JOIN roles r ON r.id = u.role_id
                 LEFT JOIN sectors sec ON sec.id = u.sector_id
                 LEFT JOIN Departments d ON d.department_id = u.department_id
                 LEFT JOIN Sections s ON s.section_id = u.section_id
                 LEFT JOIN Divisions dv ON dv.division_id = u.division_id
                 WHERE u.id = ?",
                'i',
                [(int)$user['id']]
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
            error_log("ProfileController::show error: " . $e->getMessage());
            Response::error('Failed to load profile', 500);
        }
    }

    /**
     * PUT /api/v1/profile
     * Update the current user's own profile.
     * Users can update: email, phone, preferred_language
     * Users CANNOT update: role_id, is_active, emp_id, username
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $data = $request->all();
        $db = Database::getInstance();
        $userId = (int)$user['id'];

        $sets = [];
        $types = '';
        $binds = [];

        // Only allow these fields for self-update
        $allowedFields = [
            'email'              => 's',
            'phone'              => 's',
            'preferred_language' => 's',
        ];

        foreach ($allowedFields as $field => $type) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $types .= $type;
                $binds[] = $data[$field] !== '' ? $data[$field] : null;
            }
        }

        if (empty($sets)) {
            Response::error('No fields to update', 400);
            return;
        }

        // Check unique email if changed
        if (array_key_exists('email', $data) && $data['email'] !== '') {
            try {
                $existingEmail = $db->fetchOne(
                    "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1",
                    'si',
                    [$data['email'], $userId]
                );
                if ($existingEmail) {
                    Response::error('البريد الإلكتروني مستخدم مسبقاً', 409);
                    return;
                }
            } catch (\Throwable $e) {
                // ignore, let update fail
            }
        }

        $sets[] = "updated_at = NOW()";
        $setStr = implode(', ', $sets);
        $types .= 'i';
        $binds[] = $userId;

        try {
            $result = $db->execute(
                "UPDATE users SET {$setStr} WHERE id = ?",
                $types,
                $binds
            );

            if ($result->success) {
                Response::success(null, 'Profile updated successfully');
            } else {
                Response::error('Failed to update profile', 500);
            }
        } catch (\Throwable $e) {
            error_log("ProfileController::update error: " . $e->getMessage());
            Response::error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/profile/password
     * Change the current user's password.
     * Requires current_password and new_password.
     */
    public function changePassword(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $data = $request->all();
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '') {
            Response::error('Current password and new password are required', 400);
            return;
        }

        if (strlen($newPassword) < 4) {
            Response::error('New password must be at least 4 characters', 400);
            return;
        }

        $db = Database::getInstance();
        $userId = (int)$user['id'];

        try {
            // Verify current password
            $row = $db->fetchOne(
                "SELECT password_hash FROM users WHERE id = ? LIMIT 1",
                'i',
                [$userId]
            );

            if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
                Response::error('كلمة المرور الحالية غير صحيحة', 400);
                return;
            }

            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $db->execute(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                'si',
                [$newHash, $userId]
            );

            if ($result->success) {
                Response::success(null, 'Password changed successfully');
            } else {
                Response::error('Failed to change password', 500);
            }
        } catch (\Throwable $e) {
            error_log("ProfileController::changePassword error: " . $e->getMessage());
            Response::error('Failed to change password', 500);
        }
    }

    /**
     * GET /api/v1/profile/movements
     * Get the current user's vehicle movement history.
     * Returns all pickups and returns performed by this user.
     */
    public function movements(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $db = Database::getInstance();
        $empId = $user['emp_id'] ?? '';

        if ($empId === '') {
            Response::success([]);
            return;
        }

        try {
            $rows = $db->fetchAll(
                "SELECT vm.id, vm.vehicle_code, vm.operation_type,
                        vm.performed_by, vm.movement_datetime,
                        vm.notes, vm.vehicle_condition, vm.fuel_level,
                        vm.created_at,
                        v.type AS vehicle_type, v.driver_name, v.vehicle_category
                 FROM vehicle_movements vm
                 LEFT JOIN vehicles v ON v.vehicle_code = vm.vehicle_code
                 WHERE vm.performed_by = ?
                 ORDER BY vm.movement_datetime DESC
                 LIMIT 100",
                's',
                [$empId]
            );

            Response::success($rows ?: []);
        } catch (\Throwable $e) {
            error_log("ProfileController::movements error: " . $e->getMessage());
            Response::success([]);
        }
    }

    /**
     * GET /api/v1/profile/violations
     * Get violations where the current user was holding the vehicle at the time of violation.
     */
    public function violations(Request $request, array $params = []): void
    {
        $user = $this->requireAuth($request);
        if (Response::isSent()) return;

        $db = Database::getInstance();
        $empId = $user['emp_id'] ?? '';

        if ($empId === '') {
            Response::success([]);
            return;
        }

        try {
            $rows = $db->fetchAll(
                "SELECT
                    vv.id, vv.vehicle_code, vv.violation_datetime,
                    vv.violation_amount, vv.violation_status,
                    vv.notes, vv.created_at,
                    v.type AS vehicle_type, v.driver_name
                 FROM vehicle_violations vv
                 LEFT JOIN vehicles v ON v.vehicle_code = vv.vehicle_code
                 LEFT JOIN vehicle_movements vm
                   ON vm.vehicle_code = vv.vehicle_code
                  AND vm.operation_type = 'pickup'
                  AND vm.movement_datetime = (
                     SELECT MAX(vm2.movement_datetime)
                     FROM vehicle_movements vm2
                     WHERE vm2.vehicle_code = vv.vehicle_code
                       AND vm2.operation_type = 'pickup'
                       AND vm2.movement_datetime <= vv.violation_datetime
                       AND vm2.movement_datetime >= IFNULL((
                           SELECT MAX(vm3.movement_datetime)
                           FROM vehicle_movements vm3
                           WHERE vm3.vehicle_code = vv.vehicle_code
                             AND vm3.operation_type = 'return'
                             AND vm3.movement_datetime <= vv.violation_datetime
                       ), '1970-01-01 00:00:00')
                  )
                 WHERE vm.performed_by = ?
                 ORDER BY vv.violation_datetime DESC
                 LIMIT 100",
                's',
                [$empId]
            );

            Response::success($rows ?: []);
        } catch (\Throwable $e) {
            error_log("ProfileController::violations error: " . $e->getMessage());
            Response::success([]);
        }
    }
}
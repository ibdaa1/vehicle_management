<?php
// vehicle_management/api/vehicle/raffle_assign.php
// POST or GET:
//  - if GET: returns a candidate vehicle (not claimed)
//  - if POST with claim=1 (and current user authenticated): creates a pickup movement for the user
//
// Behavior:
//  - Only selects vehicles with vehicle_mode='shift' and currently available (last movement is return or none).
//  - Excludes vehicles that the same user picked up within last 24 hours.
//  - Returns { success:true, vehicle: { ... } } or { success:false, message: ... }
//  - Requires session or Authorization bearer token.

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// include DB and session
$paths = [ __DIR__ . '/../../config/db.php', __DIR__ . '/../config/db.php', __DIR__ . '/config/db.php' ];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server misconfiguration: DB missing']);
    exit;
}

// determine user (token or session)
$permHelper = __DIR__ . '/../permissions/perm_helper.php';
if (file_exists($permHelper)) require_once $permHelper;

$currentUser = null;
if (function_exists('get_current_user')) $currentUser = get_current_user($conn);
else {
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    $currentUser = $_SESSION['user'] ?? null;
}
if (!$currentUser || empty($currentUser['emp_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}
$userEmp = $currentUser['emp_id'];

// If user already has an outstanding pickup -> reject
$hasPickup = false;
try {
    $chkSql = "SELECT COUNT(*) AS cnt FROM vehicle_movements vm WHERE vm.performed_by = ? AND vm.operation_type = 'pickup' AND NOT EXISTS (SELECT 1 FROM vehicle_movements r WHERE r.vehicle_code = vm.vehicle_code AND r.operation_type = 'return' AND r.movement_datetime > vm.movement_datetime)";
    $chk = $conn->prepare($chkSql);
    $chk->bind_param('s', $userEmp);
    $chk->execute();
    $cres = $chk->get_result()->fetch_assoc();
    $hasPickup = intval($cres['cnt'] ?? 0) > 0;
    $chk->close();
} catch (Throwable $e) {
    error_log('raffle_assign: check has pickup error: ' . $e->getMessage());
}

if ($hasPickup) {
    echo json_encode(['success'=>false,'message'=>'User already has an outstanding pickup and cannot be assigned another vehicle.']);
    exit;
}

// Build candidate selection: available 'shift' vehicles not currently picked up and not picked by this user in last 24 hours
try {
    $sql = "
      SELECT v.*
      FROM vehicles v
      LEFT JOIN (
        SELECT vm1.vehicle_code, vm1.operation_type, vm1.movement_datetime
        FROM vehicle_movements vm1
        INNER JOIN (SELECT vehicle_code, MAX(movement_datetime) AS max_dt FROM vehicle_movements GROUP BY vehicle_code) m2
          ON m2.vehicle_code = vm1.vehicle_code AND m2.max_dt = vm1.movement_datetime
      ) lm ON lm.vehicle_code = v.vehicle_code
      WHERE (lm.operation_type IS NULL OR lm.operation_type = 'return')
        AND v.vehicle_mode = 'shift'
        AND NOT EXISTS (
            SELECT 1 FROM vehicle_movements up
            WHERE up.vehicle_code = v.vehicle_code
              AND up.performed_by = ?
              AND up.operation_type = 'pickup'
              AND up.movement_datetime >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        )
      ORDER BY RAND()
      LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $userEmp);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$r) {
        echo json_encode(['success'=>false,'message'=>'No available vehicle found for raffle']);
        exit;
    }

    // If claim requested, insert movement pickup
    $claim = isset($_REQUEST['claim']) && ($_REQUEST['claim'] == '1' || $_REQUEST['claim'] === 1);
    if ($claim) {
        $ins = $conn->prepare("INSERT INTO vehicle_movements (vehicle_code, operation_type, performed_by, movement_datetime, notes, created_by, created_at) VALUES (?, 'pickup', ?, NOW(), ?, ?, NOW())");
        if (!$ins) {
            error_log('raffle_assign: prepare insert failed: ' . $conn->error);
            echo json_encode(['success'=>false,'message'=>'Server error']);
            exit;
        }
        $notes = 'Auto-assigned via raffle';
        $createdBy = $currentUser['emp_id'] ?? $currentUser['id'] ?? 'system';
        $ins->bind_param('ssss', $r['vehicle_code'], $userEmp, $notes, $createdBy);
        if (!$ins->execute()) {
            error_log('raffle_assign: insert failed: ' . $ins->error);
            echo json_encode(['success'=>false,'message'=>'Server error: failed to create pickup record']);
            $ins->close();
            exit;
        }
        $ins->close();
        // return claimed vehicle info
        echo json_encode(['success'=>true,'claimed'=>true,'vehicle'=>[
            'id'=> (int)$r['id'],
            'vehicle_code'=>$r['vehicle_code'],
            'type'=>$r['type']
        ]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // otherwise return candidate but not claim
    echo json_encode(['success'=>true,'claimed'=>false,'vehicle'=>[
        'id'=> (int)$r['id'],
        'vehicle_code'=>$r['vehicle_code'],
        'type'=>$r['type']
    ]], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('raffle_assign error: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
?>
<?php
// vehicle_management/api/vehicle/get_violations_with_editor.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

try {
    global $conn;
    if (!$conn) throw new Exception('DB connection failed');

    $sql = "
    SELECT
      vv.id AS violation_id,
      vv.vehicle_code,
      vv.violation_datetime,
      vv.violation_amount,
      vv.violation_status,
      vm.performed_by AS pickup_emp_id,
      u.username AS pickup_emp_name,
      vm.movement_datetime AS pickup_datetime
    FROM vehicle_violations vv

    LEFT JOIN vehicle_movements vm
      ON vm.vehicle_code = vv.vehicle_code
     AND vm.operation_type = 'pickup'
     AND vm.movement_datetime = (
        SELECT MAX(vm2.movement_datetime)
        FROM vehicle_movements vm2
        WHERE vm2.vehicle_code = vv.vehicle_code
          AND vm2.operation_type = 'pickup'
          AND vm2.movement_datetime <= vv.violation_datetime
          AND (
              NOT EXISTS (
                  SELECT 1 FROM vehicle_movements vm_r
                  WHERE vm_r.vehicle_code = vv.vehicle_code
                    AND vm_r.operation_type = 'return'
                    AND vm_r.movement_datetime > vm2.movement_datetime
                    AND vm_r.movement_datetime <= vv.violation_datetime
              )
          )
          AND (
              vm2.movement_datetime >= IFNULL((
                  SELECT MAX(vm3.movement_datetime)
                  FROM vehicle_movements vm3
                  WHERE vm3.vehicle_code = vv.vehicle_code
                    AND vm3.operation_type = 'return'
                    AND vm3.movement_datetime <= vv.violation_datetime
              ), '1970-01-01 00:00:00')
          )
      )
    LEFT JOIN users u ON u.emp_id = vm.performed_by
    ORDER BY vv.violation_datetime DESC, vv.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);
    $stmt->execute();
    $result = $stmt->get_result();
    $violations = [];
    while($row = $result->fetch_assoc()) {
        $violations[] = [
            'violation_id'      => $row['violation_id'],
            'vehicle_code'      => $row['vehicle_code'],
            'violation_datetime'=> ($row['violation_datetime']=='0000-00-00 00:00:00' ? null : $row['violation_datetime']),
            'violation_amount'  => $row['violation_amount'],
            'violation_status'  => $row['violation_status'] === 'paid' ? 'مدفوعة' : 'غير مدفوعة',
            'pickup_emp_id'     => $row['pickup_emp_id'],
            'pickup_emp_name'   => $row['pickup_emp_name'],
            'pickup_datetime'   => $row['pickup_datetime']
        ];
    }
    echo json_encode([
        'success' => true,
        'data' => $violations
    ], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
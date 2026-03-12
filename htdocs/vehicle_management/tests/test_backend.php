<?php
/**
 * ===================================================================
 * Vehicle Management System - Backend & Router Test Suite
 * ===================================================================
 * 
 * ملف اختبار شامل للتأكد من أن الباك إند والراوتر يعملان بشكل صحيح
 * Comprehensive test file to verify backend and router functionality
 * 
 * Usage (CLI - Unit Tests):
 *   DB_HOST=localhost DB_USER=vm_user DB_PASS=vm_pass_2024 DB_NAME=vehicle_management php tests/test_backend.php
 * 
 * Usage (HTTP - Integration Tests via built-in server):
 *   1. Start server:
 *      DB_HOST=localhost DB_USER=vm_user DB_PASS=vm_pass_2024 DB_NAME=vehicle_management \
 *      php -S localhost:8080 -t /path/to/htdocs/vehicle_management
 *   2. Run:
 *      php tests/test_backend.php --http http://localhost:8080
 * 
 * ===================================================================
 */

// ─── Configuration ───────────────────────────────────────────────
$BASE_DIR = dirname(__DIR__);
$passed   = 0;
$failed   = 0;
$errors   = [];
$httpBase = null;

// Check for --http flag (integration test mode)
foreach ($argv ?? [] as $i => $arg) {
    if ($arg === '--http' && isset($argv[$i + 1])) {
        $httpBase = rtrim($argv[$i + 1], '/');
    }
}

// ─── Helper Functions ────────────────────────────────────────────
function test_pass(string $name): void {
    global $passed;
    $passed++;
    echo "  ✅ PASS: {$name}\n";
}

function test_fail(string $name, string $reason = ''): void {
    global $failed, $errors;
    $failed++;
    $msg = "  ❌ FAIL: {$name}" . ($reason ? " — {$reason}" : "");
    $errors[] = $msg;
    echo $msg . "\n";
}

function assert_true(bool $condition, string $testName, string $failReason = ''): void {
    if ($condition) {
        test_pass($testName);
    } else {
        test_fail($testName, $failReason);
    }
}

function assert_equals($expected, $actual, string $testName): void {
    if ($expected === $actual) {
        test_pass($testName);
    } else {
        test_fail($testName, "Expected: " . var_export($expected, true) . " Got: " . var_export($actual, true));
    }
}

function http_request(string $method, string $url, array $data = [], array $headers = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    $httpHeaders = ['Accept: application/json'];
    foreach ($headers as $k => $v) {
        $httpHeaders[] = "{$k}: {$v}";
    }
    
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $httpHeaders[] = 'Content-Type: application/json';
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body'   => $response,
        'json'   => json_decode($response, true),
        'error'  => $error,
    ];
}

function section(string $title): void {
    echo "\n" . str_repeat('─', 60) . "\n";
    echo "▶ {$title}\n";
    echo str_repeat('─', 60) . "\n";
}

// ═══════════════════════════════════════════════════════════════════
// PART 1: CLI UNIT TESTS (no HTTP server needed)
// ═══════════════════════════════════════════════════════════════════
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   Vehicle Management System - Backend Test Suite            ║\n";
echo "║   نظام إدارة المركبات - اختبار الباك إند والراوتر          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

// Load the autoloader
require_once $BASE_DIR . '/config/autoload.php';

use App\Core\Database;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\App;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

// ─── Test 1: Autoloader & Class Loading ──────────────────────────
section('1. Autoloader & Class Loading / تحميل الكلاسات');

$classes = [
    'App\Core\Database',
    'App\Core\Router',
    'App\Core\Request',
    'App\Core\Response',
    'App\Core\App',
    'App\Models\BaseModel',
    'App\Models\User',
    'App\Models\Role',
    'App\Models\Permission',
    'App\Controllers\BaseController',
    'App\Controllers\AuthController',
    'App\Controllers\RoleController',
    'App\Controllers\PermissionController',
    'App\Middleware\AuthMiddleware',
    'App\Middleware\PermissionMiddleware',
];

foreach ($classes as $class) {
    assert_true(class_exists($class), "Class exists: {$class}");
}

// ─── Test 2: Configuration Loading ───────────────────────────────
section('2. Configuration Loading / تحميل الإعدادات');

$dbConfig  = require $BASE_DIR . '/config/database.php';
$appConfig = require $BASE_DIR . '/config/app.php';

assert_true(is_array($dbConfig), 'database.php returns array');
assert_true(!empty($dbConfig['host']), 'DB host is set: ' . $dbConfig['host']);
assert_true(!empty($dbConfig['username']), 'DB username is set');
assert_true(!empty($dbConfig['database']), 'DB name is set: ' . $dbConfig['database']);
assert_true(is_array($appConfig), 'app.php returns array');
assert_equals('Vehicle Management System', $appConfig['name'], 'App name correct');
assert_equals('Asia/Dubai', $appConfig['timezone'], 'Timezone is Asia/Dubai');

// ─── Test 3: Database Connection ─────────────────────────────────
section('3. Database Connection / الاتصال بقاعدة البيانات');

$dbOk = false;
try {
    $db = Database::init($dbConfig);
    $dbOk = true;
    test_pass('Database::init() connected successfully');
    
    $conn = $db->getConnection();
    assert_true($conn instanceof \mysqli, 'getConnection() returns mysqli');
    assert_true(!$conn->connect_error, 'No connection error');
    
    // Test query
    $result = $db->fetchOne("SELECT 1 as test_val");
    assert_equals(1, (int)$result['test_val'], 'Simple query works');
    
    // Check tables exist
    $tables = $db->fetchAll("SHOW TABLES");
    $tableNames = array_map(fn($r) => array_values($r)[0], $tables);
    
    assert_true(in_array('users', $tableNames), 'Table exists: users');
    assert_true(in_array('user_sessions', $tableNames), 'Table exists: user_sessions');
    assert_true(in_array('roles', $tableNames), 'Table exists: roles');
    assert_true(in_array('permissions', $tableNames), 'Table exists: permissions');
    assert_true(in_array('role_permissions', $tableNames), 'Table exists: role_permissions');
    assert_true(in_array('resource_permissions', $tableNames), 'Table exists: resource_permissions');

} catch (\Exception $e) {
    test_fail('Database connection', $e->getMessage());
}

if (!$dbOk) {
    echo "\n⚠️  Database not available — skipping database-dependent tests.\n";
    echo "   Set DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables.\n";
    goto ROUTER_TESTS;
}

// ─── Test 4: Models ──────────────────────────────────────────────
section('4. Models / النماذج');

// Role Model
$roleModel = new Role();
$roles = $roleModel->all();
assert_true(count($roles) >= 5, 'Role::all() returns 5+ roles (got ' . count($roles) . ')');

$superadmin = $roleModel->find(1);
assert_true($superadmin !== null, 'Role::find(1) returns superadmin');
assert_equals('superadmin', $superadmin['key_name'], 'Superadmin key_name correct');

$byKey = $roleModel->findByKey('admin');
assert_true($byKey !== null, 'Role::findByKey("admin") found');
assert_equals(2, (int)$byKey['id'], 'Admin role has id=2');

// Permission Model
$permModel = new Permission();
$allPerms = $permModel->allActive();
assert_true(count($allPerms) >= 20, 'Permission::allActive() returns 20+ permissions (got ' . count($allPerms) . ')');

$modules = $permModel->getModules();
assert_true(count($modules) >= 5, 'Permission::getModules() returns 5+ modules (got ' . count($modules) . ')');

$grouped = $permModel->groupedByModule();
assert_true(is_array($grouped), 'Permission::groupedByModule() returns array');
assert_true(isset($grouped['vehicles']), 'Grouped has "vehicles" module');

$usersRead = $permModel->findByKey('users_read');
assert_true($usersRead !== null, 'Permission::findByKey("users_read") found');
assert_true($permModel->isActive('users_read'), 'Permission users_read is active');

// User Model
$userModel = new User();
$admin = $userModel->findByIdentifier('admin');
assert_true($admin !== null, 'User::findByIdentifier("admin") found');
assert_equals('EMP001', $admin['emp_id'], 'Admin emp_id is EMP001');

$adminById = $userModel->find(1);
assert_true($adminById !== null, 'User::find(1) found');

$publicData = $userModel->getPublicData(1);
assert_true($publicData !== null, 'User::getPublicData(1) returns data');
assert_true(!isset($publicData['password_hash']), 'Public data excludes password_hash');

assert_true($userModel->verifyPassword($admin, 'admin123'), 'Password verify: admin123 is correct');
assert_true(!$userModel->verifyPassword($admin, 'wrongpass'), 'Password verify: wrongpass is incorrect');

// ─── Test 5: Session Token Management ────────────────────────────
section('5. Session Token Management / إدارة الجلسات');

$token = $userModel->createSessionToken(1, 'TestAgent/1.0', '127.0.0.1');
assert_true($token !== false, 'createSessionToken() returns token');
assert_true(strlen($token) === 64, 'Token is 64 chars (hex): ' . strlen($token));

// Verify token exists in DB
$sess = $db->fetchOne("SELECT * FROM user_sessions WHERE token = ?", 's', [$token]);
assert_true($sess !== null, 'Token stored in user_sessions');
assert_equals(1, (int)$sess['user_id'], 'Token belongs to user_id=1');
assert_equals(0, (int)$sess['revoked'], 'Token not revoked');

// Revoke token
$userModel->revokeToken($token);
$sess2 = $db->fetchOne("SELECT revoked FROM user_sessions WHERE token = ?", 's', [$token]);
assert_equals(1, (int)$sess2['revoked'], 'Token revoked successfully');

// Create another token for later tests
$testToken = $userModel->createSessionToken(1, 'TestSuite/1.0', '127.0.0.1');
assert_true($testToken !== false, 'Second test token created');

// ─── Test 6: Role-Permission Relationships ───────────────────────
section('6. Role-Permission Relationships / علاقات الأدوار والصلاحيات');

$roleWithPerms = $roleModel->getWithPermissions(1);
assert_true($roleWithPerms !== null, 'getWithPermissions(1) returns data');
assert_true(isset($roleWithPerms['permissions']), 'Role data includes permissions');
assert_true(count($roleWithPerms['permissions']) > 0, 'Superadmin has permissions assigned');

$countWithPerms = $roleModel->allWithPermissionCount();
assert_true(count($countWithPerms) >= 5, 'allWithPermissionCount() returns roles');
$superadminRow = array_values(array_filter($countWithPerms, fn($r) => (int)$r['id'] === 1))[0] ?? null;
assert_true($superadminRow !== null, 'Superadmin found in count list');
assert_true((int)($superadminRow['permission_count'] ?? 0) > 0, 'Superadmin has permission_count > 0');

// ─── Test 7: Permission Middleware ───────────────────────────────
section('7. Permission Middleware / وسيط الصلاحيات');

// Superadmin should have all permissions
assert_true(PermissionMiddleware::hasPermission(1, 'users_read'), 'Superadmin has users_read');
assert_true(PermissionMiddleware::hasPermission(1, 'roles_manage'), 'Superadmin has roles_manage');
assert_true(PermissionMiddleware::hasPermission(1, 'nonexistent_perm'), 'Superadmin has any permission (role_id=1 bypass)');

// Get role permissions
$rolePerms = PermissionMiddleware::getRolePermissions(1);
assert_true(is_array($rolePerms), 'getRolePermissions(1) returns array');
assert_true(isset($rolePerms['permissions']), 'Role permissions has "permissions" key');
assert_true(isset($rolePerms['resources']), 'Role permissions has "resources" key');

// Clean up test token
$userModel->revokeToken($testToken);
$db->execute("DELETE FROM user_sessions WHERE user_id = 1 AND revoked = 1");

// ─── ROUTER TESTS (no DB needed) ────────────────────────────────
ROUTER_TESTS:

section('8. Router / الراوتر');

$router = new Router('/vehicle_management');

// Register test routes
$router->get('api/v1/test', 'TestController', 'index');
$router->get('api/v1/items/{id}', 'ItemController', 'show');
$router->post('api/v1/items', 'ItemController', 'store');
$router->put('api/v1/items/{id}', 'ItemController', 'update');
$router->delete('api/v1/items/{id}', 'ItemController', 'destroy');
$router->get('api/v1/items/{id}/details', 'ItemController', 'details');

// Test route matching using a mock request
$reflection = new ReflectionClass($router);
$matchMethod = $reflection->getMethod('matchRoute');
$matchMethod->setAccessible(true);

// Test exact match
$result = $matchMethod->invoke($router, '/vehicle_management/api/v1/test', '/vehicle_management/api/v1/test');
assert_true($result !== false, 'Router matches exact path /api/v1/test');
assert_true(is_array($result) && empty($result), 'Exact match returns empty params');

// Test parametric match
$result = $matchMethod->invoke($router, '/vehicle_management/api/v1/items/{id}', '/vehicle_management/api/v1/items/42');
assert_true($result !== false, 'Router matches /api/v1/items/42');
assert_equals('42', $result['id'], 'Extracted param id=42');

// Test nested parametric match
$result = $matchMethod->invoke($router, '/vehicle_management/api/v1/items/{id}/details', '/vehicle_management/api/v1/items/7/details');
assert_true($result !== false, 'Router matches /api/v1/items/7/details');
assert_equals('7', $result['id'], 'Extracted param id=7 from nested route');

// Test no match
$result = $matchMethod->invoke($router, '/vehicle_management/api/v1/test', '/vehicle_management/api/v1/other');
assert_true($result === false, 'Router rejects non-matching path');

// Test that /items doesn't match /items/42
$result = $matchMethod->invoke($router, '/vehicle_management/api/v1/items', '/vehicle_management/api/v1/items/42');
assert_true($result === false, 'Router: /items does NOT match /items/42');

// ─── Test 9: Full Routes Registration ────────────────────────────
section('9. Routes Configuration / إعداد المسارات');

// Load routes into a fresh router
$router = new Router('/vehicle_management');
require $BASE_DIR . '/config/routes.php';

$ref2 = new ReflectionClass($router);
$rp2 = $ref2->getProperty('routes');
$rp2->setAccessible(true);
$registeredRoutes = $rp2->getValue($router);

assert_true(count($registeredRoutes) > 0, 'Routes loaded from routes.php');

// Check specific routes exist
$routePatterns = array_map(fn($r) => $r['method'] . ' ' . $r['pattern'], $registeredRoutes);

$expectedRoutes = [
    'POST /vehicle_management/api/v1/auth/login',
    'GET /vehicle_management/api/v1/auth/check',
    'POST /vehicle_management/api/v1/auth/logout',
    'GET /vehicle_management/api/v1/roles',
    'GET /vehicle_management/api/v1/roles/public',
    'GET /vehicle_management/api/v1/roles/{id}',
    'POST /vehicle_management/api/v1/roles',
    'PUT /vehicle_management/api/v1/roles/{id}',
    'DELETE /vehicle_management/api/v1/roles/{id}',
    'GET /vehicle_management/api/v1/permissions',
    'GET /vehicle_management/api/v1/permissions/my',
    'POST /vehicle_management/api/v1/permissions',
];

foreach ($expectedRoutes as $expected) {
    assert_true(in_array($expected, $routePatterns), "Route registered: {$expected}");
}

echo "\nTotal routes registered: " . count($registeredRoutes) . "\n";

// ═══════════════════════════════════════════════════════════════════
// PART 2: HTTP INTEGRATION TESTS (requires running server)
// ═══════════════════════════════════════════════════════════════════

if ($httpBase) {
    section('10. HTTP Integration Tests / اختبارات التكامل عبر HTTP');
    echo "  Server: {$httpBase}\n\n";

    // Test 10a: Public roles endpoint (no auth needed)
    $resp = http_request('GET', "{$httpBase}/api/v1/roles/public");
    assert_equals(200, $resp['status'], 'GET /api/v1/roles/public → 200');
    assert_true($resp['json']['success'] === true, 'Public roles: success=true');
    assert_true(is_array($resp['json']['data']), 'Public roles: data is array');
    assert_true(count($resp['json']['data']) >= 5, 'Public roles: has 5+ roles');

    // Test 10b: Auth required endpoints return 401
    $resp = http_request('GET', "{$httpBase}/api/v1/roles");
    assert_equals(401, $resp['status'], 'GET /api/v1/roles without auth → 401');
    assert_true($resp['json']['success'] === false, 'Unauthenticated: success=false');

    // Test 10c: Login
    $resp = http_request('POST', "{$httpBase}/api/v1/auth/login", [
        'username' => 'admin',
        'password' => 'admin123',
    ]);
    assert_equals(200, $resp['status'], 'POST /api/v1/auth/login → 200');
    assert_true($resp['json']['success'] === true, 'Login: success=true');
    assert_true(!empty($resp['json']['token']), 'Login: token returned');
    assert_equals(1, $resp['json']['user']['role_id'], 'Login: user role_id=1 (superadmin)');

    $authToken = $resp['json']['token'] ?? '';

    if ($authToken) {
        // Test 10d: Session check with token
        $resp = http_request('GET', "{$httpBase}/api/v1/auth/check", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'GET /api/v1/auth/check → 200');
        assert_true($resp['json']['success'] === true, 'Session check: success=true');
        assert_true($resp['json']['isLoggedIn'] === true, 'Session check: isLoggedIn=true');

        // Test 10e: List roles (admin only)
        $resp = http_request('GET', "{$httpBase}/api/v1/roles", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'GET /api/v1/roles with auth → 200');
        assert_true($resp['json']['success'] === true, 'List roles: success=true');
        assert_true(is_array($resp['json']['data']), 'List roles: data is array');

        // Test 10f: Get single role
        $resp = http_request('GET', "{$httpBase}/api/v1/roles/1", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'GET /api/v1/roles/1 → 200');
        assert_true($resp['json']['success'] === true, 'Get role: success=true');
        assert_equals('superadmin', $resp['json']['data']['key_name'], 'Get role: key_name=superadmin');

        // Test 10g: List permissions
        $resp = http_request('GET', "{$httpBase}/api/v1/permissions", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'GET /api/v1/permissions → 200');
        assert_true($resp['json']['success'] === true, 'List permissions: success=true');
        assert_true(count($resp['json']['data']) >= 20, 'Has 20+ permissions');

        // Test 10h: My permissions
        $resp = http_request('GET', "{$httpBase}/api/v1/permissions/my", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'GET /api/v1/permissions/my → 200');
        assert_true($resp['json']['success'] === true, 'My permissions: success=true');
        assert_true(isset($resp['json']['data']['permissions']), 'My permissions: has permissions');

        // Test 10i: Permission modules
        $resp = http_request('GET', "{$httpBase}/api/v1/permissions/modules", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'GET /api/v1/permissions/modules → 200');
        assert_true(is_array($resp['json']['data']), 'Modules: data is array');

        // Test 10j: 404 for unknown route
        $resp = http_request('GET', "{$httpBase}/api/v1/nonexistent");
        assert_equals(404, $resp['status'], 'GET /api/v1/nonexistent → 404');

        // Test 10k: Login with wrong password
        $resp = http_request('POST', "{$httpBase}/api/v1/auth/login", [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);
        assert_equals(401, $resp['status'], 'Login wrong password → 401');

        // Test 10l: Logout
        $resp = http_request('POST', "{$httpBase}/api/v1/auth/logout", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_equals(200, $resp['status'], 'POST /api/v1/auth/logout → 200');

        // Test 10m: Token no longer works after logout
        $resp = http_request('GET', "{$httpBase}/api/v1/auth/check", [], [
            'Authorization' => "Bearer {$authToken}",
        ]);
        assert_true($resp['json']['isLoggedIn'] === false || $resp['json']['success'] === false, 'Token invalid after logout');
        
        // Test 10n: Non-admin user cannot access admin endpoints
        $resp = http_request('POST', "{$httpBase}/api/v1/auth/login", [
            'username' => 'user1',
            'password' => 'admin123',
        ]);
        $userToken = $resp['json']['token'] ?? '';
        
        if ($userToken) {
            $resp = http_request('GET', "{$httpBase}/api/v1/roles", [], [
                'Authorization' => "Bearer {$userToken}",
            ]);
            assert_equals(403, $resp['status'], 'Non-admin GET /api/v1/roles → 403');
            
            // Cleanup
            http_request('POST', "{$httpBase}/api/v1/auth/logout", [], [
                'Authorization' => "Bearer {$userToken}",
            ]);
        }
    }
} else {
    echo "\n";
    echo "  ℹ️  HTTP integration tests skipped.\n";
    echo "     To run them, start the server and add --http flag:\n";
    echo "     php tests/test_backend.php --http http://localhost:8080\n";
}

// ═══════════════════════════════════════════════════════════════════
// RESULTS SUMMARY
// ═══════════════════════════════════════════════════════════════════
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   TEST RESULTS / نتائج الاختبارات                           ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf("║   ✅ Passed: %-3d                                            ║\n", $passed);
printf("║   ❌ Failed: %-3d                                            ║\n", $failed);
printf("║   📊 Total:  %-3d                                            ║\n", $passed + $failed);
echo "╚══════════════════════════════════════════════════════════════╝\n";

if (!empty($errors)) {
    echo "\nFailed tests:\n";
    foreach ($errors as $err) {
        echo $err . "\n";
    }
}

echo "\n";
exit($failed > 0 ? 1 : 0);

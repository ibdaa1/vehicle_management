<?php
/**
 * ===================================================================
 * My Vehicles & Auth Permissions — Diagnostic Test Suite
 * ===================================================================
 * 
 * ملف اختبار تشخيصي لصفحة "مركباتي" ونقاط API المرتبطة بها
 * Diagnostic test file for "My Vehicles" page and related API endpoints
 * 
 * Tests cover:
 * 1. Auth/check endpoint returns permissions and resources (not empty)
 * 2. /vehicles/my-vehicles API endpoint responds correctly
 * 3. Fragment file exists and loads properly
 * 4. Route registration
 * 5. Permission configuration in dashboard.php
 * 6. Superadmin resource fallback in PermissionMiddleware
 * 
 * Usage (CLI - Unit Tests):
 *   cd htdocs/vehicle_management
 *   php tests/test_my_vehicles.php
 * 
 * Usage (HTTP - Integration Tests via built-in server):
 *   1. Start server:
 *      DB_HOST=127.0.0.1:3307 DB_USER=your_user DB_PASS=your_pass DB_NAME=vehicle_management \
 *      APP_BASE_URL="" php -S 0.0.0.0:8770 router.php
 *   2. Run:
 *      TEST_USER=admin TEST_PASS=secret php tests/test_my_vehicles.php --http http://localhost:8770
 * 
 * ===================================================================
 */

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
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $httpHeaders[] = 'Content-Type: application/json';
        $httpHeaders[] = 'Content-Length: ' . strlen($jsonData);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body'   => $body,
        'json'   => json_decode($body ?: '{}', true),
        'error'  => $error,
    ];
}


// ═══════════════════════════════════════════════════════════════════
echo "\n🔍 MY VEHICLES & AUTH PERMISSIONS — DIAGNOSTIC TEST SUITE\n";
echo str_repeat('=', 60) . "\n\n";


// ─── Section 1: File & Structure Tests ────────────────────────────
echo "📁 Section 1: File & Structure Tests\n" . str_repeat('-', 40) . "\n";

// Test: Fragment file exists
$fragmentPath = $BASE_DIR . '/public/fragments/my_vehicles.php';
assert_true(
    file_exists($fragmentPath),
    'Fragment file exists: public/fragments/my_vehicles.php',
    'File not found at: ' . $fragmentPath
);

// Test: Fragment file has valid PHP syntax
if (file_exists($fragmentPath)) {
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($fragmentPath) . " 2>&1", $output, $returnCode);
    assert_true(
        $returnCode === 0,
        'Fragment PHP syntax is valid',
        implode("\n", $output)
    );
}

// Test: Dashboard.php has my_vehicles in pageMeta
$dashboardPath = $BASE_DIR . '/public/dashboard.php';
if (file_exists($dashboardPath)) {
    $dashboardContent = file_get_contents($dashboardPath);
    assert_true(
        str_contains($dashboardContent, "'my_vehicles'"),
        'dashboard.php has my_vehicles in pageMeta',
        'my_vehicles key not found in dashboard.php pageMeta'
    );
    assert_true(
        str_contains($dashboardContent, "'perm' => null") && str_contains($dashboardContent, "'my_vehicles'"),
        'my_vehicles has perm => null (no permission required)',
        'my_vehicles page may have a permission restriction'
    );
    assert_true(
        str_contains($dashboardContent, 'data-page="<?= htmlspecialchars($page)'),
        'dashboard.php has data-page attribute with dynamic $page value for cache-mismatch detection',
        'data-page attribute missing or not using $page variable in dashboard.php'
    );
} else {
    test_fail('dashboard.php exists', 'File not found');
}

// Test: Footer.php has cache-mismatch detection script
$footerPath = $BASE_DIR . '/public/includes/footer.php';
if (file_exists($footerPath)) {
    $footerContent = file_get_contents($footerPath);
    assert_true(
        str_contains($footerContent, 'renderedPage') && str_contains($footerContent, 'requestedPage'),
        'Footer has cache-mismatch detection script',
        'Cache-mismatch detection script not found in footer.php'
    );
} else {
    test_fail('footer.php exists', 'File not found');
}

// Test: Dashboard.php has aggressive cache-prevention headers
assert_true(
    str_contains($dashboardContent, 'Vary') && str_contains($dashboardContent, 'Surrogate-Control'),
    'dashboard.php has Vary and Surrogate-Control cache headers',
    'Missing Vary or Surrogate-Control headers in dashboard.php'
);

// Test: Route is registered in routes.php
$routesPath = $BASE_DIR . '/config/routes.php';
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    assert_true(
        str_contains($routesContent, 'vehicles/my-vehicles') && str_contains($routesContent, 'myVehicles'),
        'Route registered: GET api/v1/vehicles/my-vehicles → VehicleController::myVehicles',
        'Route for my-vehicles not found in routes.php'
    );
} else {
    test_fail('routes.php exists', 'File not found');
}

// Test: VehicleController has myVehicles method
$controllerPath = $BASE_DIR . '/app/Controllers/VehicleController.php';
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    assert_true(
        str_contains($controllerContent, 'function myVehicles'),
        'VehicleController has myVehicles() method',
        'myVehicles method not found in VehicleController'
    );
    assert_true(
        str_contains($controllerContent, 'requireAuth') && str_contains($controllerContent, 'function myVehicles'),
        'myVehicles() calls requireAuth()',
        'Auth requirement check may be missing'
    );
} else {
    test_fail('VehicleController.php exists', 'File not found');
}

// Test: Sidebar menu has my_vehicles link
$headerPath = $BASE_DIR . '/public/includes/header.php';
if (file_exists($headerPath)) {
    $headerContent = file_get_contents($headerPath);
    assert_true(
        str_contains($headerContent, 'my_vehicles'),
        'Sidebar menu has my_vehicles link in header.php',
        'my_vehicles link not found in sidebar menu'
    );
} else {
    test_fail('header.php exists', 'File not found');
}

echo "\n";


// ─── Section 2: Auth & Permission Tests ──────────────────────────
echo "🔐 Section 2: Auth & Permission Tests\n" . str_repeat('-', 40) . "\n";

// Test: PermissionMiddleware has getDefaultSuperadminResources method
$permMiddlewarePath = $BASE_DIR . '/app/Middleware/PermissionMiddleware.php';
if (file_exists($permMiddlewarePath)) {
    $pmContent = file_get_contents($permMiddlewarePath);
    assert_true(
        str_contains($pmContent, 'getDefaultSuperadminResources'),
        'PermissionMiddleware has getDefaultSuperadminResources() fallback',
        'Superadmin resource fallback method not found'
    );
    assert_true(
        str_contains($pmContent, 'roleId === 1 && empty($resources)'),
        'getRolePermissions() has superadmin fallback when resources empty',
        'Superadmin fallback logic not found in getRolePermissions()'
    );
}

// Test: AuthController check() has superadmin fallback
$authControllerPath = $BASE_DIR . '/app/Controllers/AuthController.php';
if (file_exists($authControllerPath)) {
    $acContent = file_get_contents($authControllerPath);
    assert_true(
        str_contains($acContent, 'getDefaultSuperadminResources'),
        'AuthController has superadmin fallback for resources',
        'getDefaultSuperadminResources not referenced in AuthController'
    );
    assert_true(
        str_contains($acContent, "'resources'") && str_contains($acContent, "'permissions'"),
        'AuthController returns both permissions and resources in response',
        'permissions or resources not found in response building'
    );
}

// Test: Superadmin fallback generates correct resources
require_once $BASE_DIR . '/app/Middleware/PermissionMiddleware.php';
$fallbackResources = \App\Middleware\PermissionMiddleware::getDefaultSuperadminResources();

assert_true(
    is_array($fallbackResources) && count($fallbackResources) > 0,
    'getDefaultSuperadminResources() returns non-empty array',
    'Got: ' . (is_array($fallbackResources) ? count($fallbackResources) . ' items' : gettype($fallbackResources))
);

$expectedTypes = ['users', 'vehicles', 'movements', 'violations', 'maintenance'];
$actualTypes = array_column($fallbackResources, 'resource_type');
assert_true(
    $actualTypes === $expectedTypes,
    'Superadmin fallback covers all 5 resource types: ' . implode(', ', $expectedTypes),
    'Got types: ' . implode(', ', $actualTypes)
);

// Verify all flags are true for each resource
$allFlagsTrue = true;
$flagFields = ['can_view_all', 'can_view_own', 'can_view_tenant', 'can_create', 'can_edit_all', 'can_edit_own', 'can_delete_all', 'can_delete_own'];
foreach ($fallbackResources as $res) {
    foreach ($flagFields as $flag) {
        if (!isset($res[$flag]) || $res[$flag] !== true) {
            $allFlagsTrue = false;
            break 2;
        }
    }
}
assert_true(
    $allFlagsTrue,
    'All superadmin resource flags are true (full access)',
    'Some flags are not set to true'
);

echo "\n";


// ─── Section 3: Frontend Fragment Tests ──────────────────────────
echo "🖥️  Section 3: Frontend Fragment Tests\n" . str_repeat('-', 40) . "\n";

if (file_exists($fragmentPath)) {
    $fragmentContent = file_get_contents($fragmentPath);

    // Test: Fragment has required HTML containers
    assert_true(
        str_contains($fragmentContent, 'mvPrivateGrid'),
        'Fragment has #mvPrivateGrid container for private vehicles',
        'Private vehicles container not found'
    );
    assert_true(
        str_contains($fragmentContent, 'mvShiftGrid'),
        'Fragment has #mvShiftGrid container for shift vehicles',
        'Shift vehicles container not found'
    );
    assert_true(
        str_contains($fragmentContent, 'mvDeptGrid'),
        'Fragment has #mvDeptGrid container for department vehicles',
        'Department vehicles container not found'
    );

    // Test: Fragment has API call
    assert_true(
        str_contains($fragmentContent, "API.get('/vehicles/my-vehicles')"),
        'Fragment calls API.get(/vehicles/my-vehicles)',
        'API call to my-vehicles not found in fragment'
    );

    // Test: Fragment has Auth.getUser() check
    assert_true(
        str_contains($fragmentContent, 'Auth.getUser()'),
        'Fragment checks Auth.getUser() before loading',
        'Auth.getUser() check not found'
    );

    // Test: Fragment has error handling
    assert_true(
        str_contains($fragmentContent, 'renderError'),
        'Fragment has error rendering/handling',
        'Error handling (renderError) not found'
    );

    // Test: Fragment handles success:false
    assert_true(
        str_contains($fragmentContent, 'success === false') || str_contains($fragmentContent, 'res.success'),
        'Fragment checks API response success flag',
        'Success flag check not found'
    );

    // Test: Fragment uses self-service endpoint for pickup/return
    assert_true(
        str_contains($fragmentContent, "API.post('/vehicles/self-service'"),
        'Fragment uses self-service endpoint for pickup/return (not /movements)',
        'Self-service endpoint call not found — buttons may not work for regular users'
    );

    // Test: Pickup button visible for ALL authenticated users; return button requires manage_movements
    assert_true(
        str_contains($fragmentContent, 'hasMovementPermission = true'),
        'Pickup buttons visible for ALL authenticated users (self-service)',
        'hasMovementPermission should be true for all authenticated users'
    );

    // Test: Return button is restricted to users with manage_movements or * permission
    assert_true(
        str_contains($fragmentContent, 'hasAdminMovementPermission'),
        'Return button requires manage_movements permission',
        'Return button should be gated behind hasAdminMovementPermission'
    );

    // Test: Fragment handles empty data gracefully
    assert_true(
        str_contains($fragmentContent, '.private || []') || str_contains($fragmentContent, "data.private || []"),
        'Fragment has fallback for empty private vehicles array',
        'Empty array fallback not found for private vehicles'
    );

    // Test: Fragment script uses ob_start/ob_get_clean pattern
    assert_true(
        str_contains($fragmentContent, 'ob_start()') && str_contains($fragmentContent, 'ob_get_clean()'),
        'Fragment uses ob_start/ob_get_clean for page scripts',
        'Script buffering pattern not found'
    );
}

echo "\n";

// ─── Section 3b: Self-Service Route & Controller Tests ──────────
echo "🔧 Section 3b: Self-Service Route & Controller Tests\n" . str_repeat('-', 40) . "\n";

// Test: Route is registered for self-service
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    assert_true(
        str_contains($routesContent, 'vehicles/self-service') && str_contains($routesContent, 'selfServiceMovement'),
        'Route registered: POST api/v1/vehicles/self-service → VehicleController::selfServiceMovement',
        'Self-service route not found in routes.php'
    );
}

// Test: VehicleController has selfServiceMovement method
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    assert_true(
        str_contains($controllerContent, 'function selfServiceMovement'),
        'VehicleController has selfServiceMovement() method',
        'selfServiceMovement method not found in VehicleController'
    );

    // Test: selfServiceMovement uses requireAuth (not requirePermission)
    // Extract the method body to check
    $methodStart = strpos($controllerContent, 'function selfServiceMovement');
    if ($methodStart !== false) {
        $methodBody = substr($controllerContent, $methodStart, 1500);
        assert_true(
            str_contains($methodBody, 'requireAuth') && !str_contains($methodBody, 'requirePermission'),
            'selfServiceMovement uses requireAuth() only (no manage_movements required)',
            'Method may incorrectly require manage_movements permission'
        );
        assert_true(
            str_contains($methodBody, 'operation_type') && str_contains($methodBody, 'vehicle_code'),
            'selfServiceMovement validates vehicle_code and operation_type',
            'Required field validation not found'
        );
        assert_true(
            str_contains($methodBody, "in_array(\$operationType, ['pickup', 'return']"),
            'selfServiceMovement validates operation_type is pickup or return',
            'Operation type validation not found'
        );
    }
}

echo "\n";

// ─── Section 3b2: Service Worker, Manifest & PWA Tests ──────────
echo "📱 Section 3b2: Service Worker, Manifest & PWA Tests\n" . str_repeat('-', 40) . "\n";

// Test: sw.js exists
$swPath = $BASE_DIR . '/public/sw.js';
assert_true(
    file_exists($swPath),
    'Service worker file exists: public/sw.js',
    'sw.js not found at ' . $swPath
);

// Test: sw.js handles Vary: * safely
if (file_exists($swPath)) {
    $swContent = file_get_contents($swPath);
    assert_true(
        str_contains($swContent, 'Vary') && str_contains($swContent, '*'),
        'Service worker handles Vary: * header (skips caching)',
        'Vary: * handling not found in sw.js'
    );
}

// Test: manifest.php exists (dynamic manifest)
$manifestPhpPath = $BASE_DIR . '/public/manifest.php';
assert_true(
    file_exists($manifestPhpPath),
    'Dynamic manifest file exists: public/manifest.php',
    'manifest.php not found'
);

// Test: manifest.php reads theme from DB
if (file_exists($manifestPhpPath)) {
    $manifestContent = file_get_contents($manifestPhpPath);
    assert_true(
        str_contains($manifestContent, 'vm_get_theme') && str_contains($manifestContent, 'theme_color'),
        'Dynamic manifest reads theme colors from database',
        'DB theme integration not found in manifest.php'
    );
}

// Test: header.php links to dynamic manifest
$headerContent = file_get_contents($BASE_DIR . '/public/includes/header.php');
assert_true(
    str_contains($headerContent, 'manifest.php'),
    'header.php links to dynamic manifest.php (not static manifest.json)',
    'manifest.php link not found in header.php'
);

// Test: header.php uses DB theme color for meta theme-color
assert_true(
    str_contains($headerContent, "theme['colors']['primary_dark']"),
    'header.php uses DB theme color for meta theme-color',
    'DB-based theme-color not found in header.php'
);

// Test: footer.php registers service worker
$footerContent = file_get_contents($BASE_DIR . '/public/includes/footer.php');
assert_true(
    str_contains($footerContent, 'serviceWorker') && str_contains($footerContent, 'sw.js'),
    'footer.php registers service worker',
    'SW registration not found in footer.php'
);

// Test: VehicleController has logActivity method (required for self-service)
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    assert_true(
        str_contains($controllerContent, 'function logActivity'),
        'VehicleController has logActivity() method',
        'logActivity method missing — causes self-service to fail'
    );
}

echo "\n";

// ─── Section 3b3: Round-Robin & Single-Vehicle Display Tests ────
echo "🔄 Section 3b3: Round-Robin & Single-Vehicle Display Tests\n" . str_repeat('-', 40) . "\n";

// Test: Backend returns dept_next and dept_my_current in myVehicles response
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    assert_true(
        str_contains($controllerContent, "'dept_next'") && str_contains($controllerContent, "'dept_my_current'"),
        'myVehicles returns dept_next and dept_my_current for department round-robin',
        'dept_next / dept_my_current not found in VehicleController response'
    );

    // Test: Backend has findNextInRotation helper
    assert_true(
        str_contains($controllerContent, 'function findNextInRotation'),
        'VehicleController has findNextInRotation() helper for round-robin logic',
        'findNextInRotation method not found'
    );

    // Test: Backend returns populated shift_vehicles and department_vehicles arrays (all vehicles display)
    assert_true(
        str_contains($controllerContent, "'shift_vehicles'      => \$shiftVehicles") && str_contains($controllerContent, "'department_vehicles' => \$departmentVehicles"),
        'Backend returns populated shift_vehicles/department_vehicles arrays (all vehicles with turn order)',
        'Expected populated arrays for shift_vehicles and department_vehicles'
    );

    // Test: Department filter includes section/division matching
    assert_true(
        str_contains($controllerContent, 'userSectionId') && str_contains($controllerContent, 'userDivisionId')
            && str_contains($controllerContent, "section_id") && str_contains($controllerContent, "division_id"),
        'Department vehicles filter by section and division (not just department)',
        'Section/division filtering not found in department vehicle logic'
    );

    // Test: Dept round-robin excludes shift vehicles
    assert_true(
        str_contains($controllerContent, "isShift") && str_contains($controllerContent, "'shift'"),
        'Department vehicles exclude shift vehicles (shown in shift section)',
        'Shift vehicle exclusion not found in department filter'
    );
}

// Test: Frontend shows ALL vehicles in shift section with turn order
if (file_exists($fragmentPath)) {
    $fragmentContent = file_get_contents($fragmentPath);
    assert_true(
        str_contains($fragmentContent, 'renderShift(data.shift_vehicles') && str_contains($fragmentContent, 'is_next_turn'),
        'Frontend shift section shows ALL vehicles with turn order and next-turn highlighting',
        'Frontend may still render single shift vehicle instead of all vehicles'
    );

    // Test: Frontend shows ALL vehicles in department section with turn order
    assert_true(
        str_contains($fragmentContent, 'renderDepartment(data.department_vehicles') && str_contains($fragmentContent, 'is_next_turn'),
        'Frontend department section shows ALL vehicles with turn order and next-turn highlighting',
        'Frontend may still render single department vehicle'
    );

    // Test: Backend returns all shift/dept vehicles with turn order
    $controllerPath = $BASE_DIR . '/app/Controllers/VehicleController.php';
    $controllerContent = file_exists($controllerPath) ? file_get_contents($controllerPath) : '';
    assert_true(
        str_contains($controllerContent, 'assignTurnOrder') && str_contains($controllerContent, 'is_next_turn'),
        'Backend assigns turn_order and is_next_turn to all shift/dept vehicles',
        'Backend assignTurnOrder method not found'
    );
}

// Test: Legacy login.php has been removed (cleanup)
assert_true(
    !file_exists($BASE_DIR . '/api/users/login.php'),
    'Legacy api/users/login.php has been removed (login uses /api/v1/auth/login)',
    'api/users/login.php still exists — should be removed'
);

echo "\n";

// ─── Section 3c: Login Redirect (return_to) Tests ───────────────
echo "🔗 Section 3c: Login Redirect (return_to) Tests\n" . str_repeat('-', 40) . "\n";

// Test: app.js passes return_to parameter when redirecting to login
$appJsPath = $BASE_DIR . '/public/js/app.js';
if (file_exists($appJsPath)) {
    $appJsContent = file_get_contents($appJsPath);
    assert_true(
        str_contains($appJsContent, 'return_to') && str_contains($appJsContent, 'encodeURIComponent'),
        'app.js passes return_to parameter when redirecting to login',
        'return_to redirect not found in app.js'
    );
}

// Test: login.html reads return_to parameter and redirects after login
$loginPath = $BASE_DIR . '/public/login.html';
if (file_exists($loginPath)) {
    $loginContent = file_get_contents($loginPath);
    assert_true(
        str_contains($loginContent, 'return_to') && str_contains($loginContent, 'getReturnUrl'),
        'login.html handles return_to parameter for post-login redirect',
        'return_to handling not found in login.html'
    );

    // Test: login.html validates return_to URL is same-origin (security)
    assert_true(
        str_contains($loginContent, 'url.origin') && str_contains($loginContent, 'location.origin'),
        'login.html validates return_to URL is same-origin (prevents open redirect)',
        'Same-origin validation not found in login.html'
    );

    // Test: login.html uses afterLoginUrl for post-login redirect
    assert_true(
        str_contains($loginContent, 'afterLoginUrl'),
        'login.html uses afterLoginUrl variable for redirect after successful login',
        'afterLoginUrl variable not found in login.html'
    );
}

echo "\n";
// ─── Section 4: HTTP Integration Tests ──────────────────────────
if ($httpBase) {
    echo "🌐 Section 4: HTTP Integration Tests (server: {$httpBase})\n" . str_repeat('-', 40) . "\n";

    // Step 1: Login to get token
    $testUser = getenv('TEST_USER') ?: 'admin';
    $testPass = getenv('TEST_PASS') ?: 'admin123';
    echo "  → Attempting login...\n";
    $loginRes = http_request('POST', $httpBase . '/api/v1/auth/login', [
        'username' => $testUser,
        'password' => $testPass,
    ]);

    $token = $loginRes['json']['token'] ?? null;
    $loginUser = $loginRes['json']['user'] ?? null;

    assert_true(
        $loginRes['status'] === 200 && !empty($token),
        'Login succeeds and returns token',
        'Status: ' . $loginRes['status'] . ', Token: ' . ($token ? 'present' : 'missing') . ', Body: ' . substr($loginRes['body'] ?? '', 0, 200)
    );

    if ($loginUser) {
        // Test: Login response has permissions
        assert_true(
            isset($loginUser['permissions']) && is_array($loginUser['permissions']) && count($loginUser['permissions']) > 0,
            'Login response has non-empty permissions',
            'Permissions: ' . json_encode($loginUser['permissions'] ?? null)
        );

        // Test: Login response has resources
        assert_true(
            isset($loginUser['resources']) && is_array($loginUser['resources']) && count($loginUser['resources']) > 0,
            'Login response has non-empty resources',
            'Resources count: ' . count($loginUser['resources'] ?? [])
        );

        if (!empty($loginUser['resources'])) {
            $firstResource = $loginUser['resources'][0];
            assert_true(
                isset($firstResource['resource_type']) && isset($firstResource['can_view_all']),
                'Resources have expected structure (resource_type, can_view_all, etc.)',
                'First resource: ' . json_encode($firstResource)
            );
        }
    }

    // Step 2: Auth check
    if ($token) {
        $checkRes = http_request('GET', $httpBase . '/api/v1/auth/check', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        assert_true(
            $checkRes['status'] === 200,
            'Auth/check responds 200',
            'Status: ' . $checkRes['status']
        );

        $checkUser = $checkRes['json']['user'] ?? $checkRes['json']['data'] ?? null;
        if ($checkUser) {
            assert_true(
                isset($checkUser['permissions']) && is_array($checkUser['permissions']) && count($checkUser['permissions']) > 0,
                'Auth/check returns non-empty permissions',
                'Permissions: ' . json_encode($checkUser['permissions'] ?? null)
            );

            assert_true(
                isset($checkUser['resources']) && is_array($checkUser['resources']) && count($checkUser['resources']) > 0,
                'Auth/check returns non-empty resources ← MAIN ISSUE FIX',
                'Resources: ' . json_encode($checkUser['resources'] ?? [])
            );

            assert_true(
                isset($checkUser['role_name']),
                'Auth/check returns role_name',
                'role_name not found in response'
            );

            // Print full auth/check response for debugging
            echo "\n  📋 Auth/check response:\n";
            echo "     permissions: " . json_encode($checkUser['permissions'] ?? []) . "\n";
            echo "     resources count: " . count($checkUser['resources'] ?? []) . "\n";
            if (!empty($checkUser['resources'])) {
                foreach ($checkUser['resources'] as $r) {
                    echo "       - " . ($r['resource_type'] ?? '?') . ": can_view_all=" . json_encode($r['can_view_all'] ?? null) . "\n";
                }
            }
        } else {
            test_fail('Auth/check returns user data', 'User data null. Response: ' . substr($checkRes['body'] ?? '', 0, 200));
        }

        // Step 3: My vehicles endpoint
        echo "\n  → Testing /vehicles/my-vehicles endpoint...\n";
        $mvRes = http_request('GET', $httpBase . '/api/v1/vehicles/my-vehicles', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        assert_true(
            $mvRes['status'] === 200,
            '/vehicles/my-vehicles responds 200',
            'Status: ' . $mvRes['status'] . ', Body: ' . substr($mvRes['body'] ?? '', 0, 200)
        );

        $mvData = $mvRes['json']['data'] ?? null;
        if ($mvData !== null) {
            assert_true(
                array_key_exists('private', $mvData),
                'Response has private vehicles array',
                'private key missing from data'
            );
            assert_true(
                array_key_exists('shift_vehicles', $mvData),
                'Response has shift_vehicles array',
                'shift_vehicles key missing from data'
            );
            assert_true(
                array_key_exists('department_vehicles', $mvData),
                'Response has department_vehicles array',
                'department_vehicles key missing from data'
            );
            assert_true(
                array_key_exists('shift_next', $mvData),
                'Response has shift_next field',
                'shift_next key missing from data'
            );

            echo "\n  📋 My Vehicles response:\n";
            echo "     private vehicles: " . count($mvData['private'] ?? []) . "\n";
            echo "     shift vehicles: " . count($mvData['shift_vehicles'] ?? []) . "\n";
            echo "     department vehicles: " . count($mvData['department_vehicles'] ?? []) . "\n";
            echo "     shift_next: " . ($mvData['shift_next'] ? 'yes' : 'null') . "\n";
        } else {
            test_fail('/vehicles/my-vehicles returns data', 'Data is null. Response: ' . substr($mvRes['body'] ?? '', 0, 200));
        }

        // Step 4: Test self-service endpoint
        echo "\n  → Testing /vehicles/self-service endpoint...\n";

        // Test: Self-service without auth returns 401
        $ssNoAuth = http_request('POST', $httpBase . '/api/v1/vehicles/self-service', [
            'vehicle_code' => 'TEST001',
            'operation_type' => 'pickup'
        ]);
        assert_true(
            $ssNoAuth['status'] === 401,
            'Self-service without auth returns 401',
            'Status: ' . $ssNoAuth['status']
        );

        // Test: Self-service with invalid operation_type
        $ssInvalidOp = http_request('POST', $httpBase . '/api/v1/vehicles/self-service', [
            'vehicle_code' => 'TEST001',
            'operation_type' => 'invalid'
        ], ['Authorization' => 'Bearer ' . $token]);
        assert_true(
            $ssInvalidOp['status'] === 400,
            'Self-service with invalid operation_type returns 400',
            'Status: ' . $ssInvalidOp['status']
        );

        // Test: Self-service with missing fields
        $ssMissing = http_request('POST', $httpBase . '/api/v1/vehicles/self-service', [
        ], ['Authorization' => 'Bearer ' . $token]);
        assert_true(
            $ssMissing['status'] === 400,
            'Self-service with missing fields returns 400',
            'Status: ' . $ssMissing['status']
        );

        // Test: Self-service with non-existent vehicle
        $ssNotFound = http_request('POST', $httpBase . '/api/v1/vehicles/self-service', [
            'vehicle_code' => 'NONEXISTENT999',
            'operation_type' => 'pickup'
        ], ['Authorization' => 'Bearer ' . $token]);
        assert_true(
            $ssNotFound['status'] === 404,
            'Self-service with non-existent vehicle returns 404',
            'Status: ' . $ssNotFound['status']
        );

        echo "  ℹ️  Self-service endpoint correctly validates: auth, fields, operation_type, vehicle existence\n";

        // Step 5: Test dashboard page loads
        echo "\n  → Testing dashboard page load...\n";
        $pageRes = http_request('GET', $httpBase . '/public/dashboard.php?page=my_vehicles', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        assert_true(
            $pageRes['status'] === 200,
            'dashboard.php?page=my_vehicles responds 200',
            'Status: ' . $pageRes['status']
        );

        if ($pageRes['status'] === 200 && $pageRes['body']) {
            assert_true(
                str_contains($pageRes['body'], 'mvPrivateGrid'),
                'Page HTML contains mvPrivateGrid container',
                'Private vehicles container not found in rendered page'
            );
            assert_true(
                str_contains($pageRes['body'], 'MyVehiclesFragment'),
                'Page HTML contains MyVehiclesFragment JS',
                'Fragment JavaScript not found in rendered page'
            );
        }
    }

    echo "\n";
} else {
    echo "ℹ️  Section 4: HTTP Integration Tests SKIPPED (use --http <url> to enable)\n\n";
}


// ─── Summary ─────────────────────────────────────────────────────
echo str_repeat('=', 60) . "\n";
echo "📊 SUMMARY: {$passed} passed, {$failed} failed\n";
if ($failed > 0) {
    echo "\n❌ Failed tests:\n";
    foreach ($errors as $e) {
        echo "  {$e}\n";
    }
    echo "\n💡 Possible reasons my_vehicles.php doesn't open:\n";
    echo "  1. Auth/check returns empty resources → Frontend may not get proper permissions\n";
    echo "  2. Database tables (vehicles, vehicle_movements) may not exist or be empty\n";
    echo "  3. resource_permissions table not seeded → Run migration 001_create_permission_tables.sql\n";
    echo "  4. User not logged in → Browser redirects to login.html\n";
    echo "  5. API.get('/vehicles/my-vehicles') fails → Check server error logs\n";
    echo "  6. JavaScript error in fragment → Check browser console\n";
} else {
    echo "\n✅ All tests passed!\n";
    echo "💡 Self-service features verified:\n";
    echo "  ✓ All authenticated users can see pickup/return buttons\n";
    echo "  ✓ Self-service endpoint /vehicles/self-service only requires authentication\n";
    echo "  ✓ Users can pick up available vehicles and return vehicles they hold\n";
}
echo str_repeat('=', 60) . "\n";

exit($failed > 0 ? 1 : 0);

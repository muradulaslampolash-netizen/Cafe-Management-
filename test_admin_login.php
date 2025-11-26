<?php
/**
 * Test Admin Login Page
 * This file tests if admin_login.php is working correctly
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; padding: 10px; background: #f0f0f0; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffe0e0; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e0e0ff; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Admin Login Test</h1>";

// Test 1: Check if file exists
echo "<h2>Test 1: File Existence</h2>";
if (file_exists(__DIR__ . '/admin_login.php')) {
    echo "<p class='success'>✓ admin_login.php exists</p>";
} else {
    echo "<p class='error'>✗ admin_login.php not found</p>";
    echo "</body></html>";
    exit;
}

// Test 2: Check if config.php exists
echo "<h2>Test 2: Config File</h2>";
if (file_exists(__DIR__ . '/config.php')) {
    echo "<p class='success'>✓ config.php exists</p>";
} else {
    echo "<p class='error'>✗ config.php not found</p>";
}

// Test 3: Check if helpers.php exists
echo "<h2>Test 3: Helpers File</h2>";
if (file_exists(__DIR__ . '/inc/helpers.php')) {
    echo "<p class='success'>✓ inc/helpers.php exists</p>";
} else {
    echo "<p class='error'>✗ inc/helpers.php not found</p>";
}

// Test 4: Check if header.php exists
echo "<h2>Test 4: Header File</h2>";
if (file_exists(__DIR__ . '/inc/header.php')) {
    echo "<p class='success'>✓ inc/header.php exists</p>";
} else {
    echo "<p class='error'>✗ inc/header.php not found</p>";
}

// Test 5: Try to include config.php
echo "<h2>Test 5: Include Config</h2>";
try {
    require_once __DIR__ . '/config.php';
    echo "<p class='success'>✓ config.php loaded successfully</p>";
    echo "<p class='info'>BASE_URL: " . BASE_URL . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Try to include helpers.php
echo "<h2>Test 6: Include Helpers</h2>";
try {
    require_once __DIR__ . '/inc/helpers.php';
    echo "<p class='success'>✓ helpers.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading helpers.php: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 7: Check session
echo "<h2>Test 7: Session Check</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p class='success'>✓ Session is active</p>";
} else {
    echo "<p class='info'>ℹ Session is not active (will be started by config.php)</p>";
}

// Test 8: Check database connection
echo "<h2>Test 8: Database Connection</h2>";
try {
    $pdo = get_pdo();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE email = ? AND role = ?');
    $stmt->execute(['admin@local.test', 'admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✓ Admin user exists (ID: " . $admin['id'] . ")</p>";
    } else {
        echo "<p class='error'>✗ Admin user not found. Run setup_admin.php first.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 9: Check file syntax
echo "<h2>Test 9: PHP Syntax Check</h2>";
$output = [];
$returnVar = 0;
exec('php -l ' . escapeshellarg(__DIR__ . '/admin_login.php'), $output, $returnVar);
if ($returnVar === 0) {
    echo "<p class='success'>✓ admin_login.php syntax is valid</p>";
} else {
    echo "<p class='error'>✗ Syntax errors found:</p>";
    echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If all tests pass, try accessing: <a href='admin_login.php'>admin_login.php</a></li>";
echo "<li>If there are errors, fix them and try again</li>";
echo "<li>If admin user doesn't exist, run <a href='setup_admin.php'>setup_admin.php</a></li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Check PHP error logs in XAMPP</li>";
echo "</ol>";

echo "</body></html>";


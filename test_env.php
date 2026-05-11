<?php
/**
 * test_env.php
 * Test if environment variables are loaded correctly
 * DELETE THIS FILE after testing!
 */

require 'config.php';

echo "<h1>Environment Configuration Test</h1>";
echo "<hr>";

// Test 1: Check if .env file exists
echo "<h2>1. File Check</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<p style='color:green'>✅ .env file exists</p>";
} else {
    echo "<p style='color:red'>❌ .env file NOT found</p>";
}

if (file_exists(__DIR__ . '/config.php')) {
    echo "<p style='color:green'>✅ config.php exists</p>";
} else {
    echo "<p style='color:red'>❌ config.php NOT found</p>";
}

// Test 2: Load and display environment variables
echo "<h2>2. Environment Variables</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

$vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_ENV', 'APP_DEBUG'];

foreach ($vars as $var) {
    $value = env($var, 'NOT SET');
    $display_value = ($var === 'DB_PASS') ? str_repeat('*', strlen($value)) : htmlspecialchars($value);
    $status = ($value !== 'NOT SET' && $value !== '') ? 
        "<span style='color:green'>✅ OK</span>" : 
        "<span style='color:red'>❌ Missing</span>";
    
    echo "<tr>";
    echo "<td><strong>$var</strong></td>";
    echo "<td>$display_value</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Test 3: Database Connection Test
echo "<h2>3. Database Connection Test</h2>";
try {
    require 'db.php';
    echo "<p style='color:green'>✅ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>✅ Database query executed successfully!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Security Check
echo "<h2>4. Security Check</h2>";

// Check if .htaccess exists
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<p style='color:green'>✅ .htaccess file exists (protects .env)</p>";
} else {
    echo "<p style='color:orange'>⚠️ No .htaccess - .env might be accessible via web</p>";
}

// Check if .gitignore exists
if (file_exists(__DIR__ . '/.gitignore')) {
    echo "<p style='color:green'>✅ .gitignore exists (prevents .env commit)</p>";
} else {
    echo "<p style='color:orange'>⚠️ No .gitignore - risk of committing .env</p>";
}

// Test 5: APP_DEBUG setting
echo "<h2>5. Application Settings</h2>";
$debug = env('APP_DEBUG', false);
if ($debug === false || $debug === 'false') {
    echo "<p style='color:green'>✅ APP_DEBUG is disabled (secure for production)</p>";
} else {
    echo "<p style='color:orange'>⚠️ APP_DEBUG is enabled (okay for development, disable for production)</p>";
}

echo "<hr>";
echo "<h2>✅ Test Complete</h2>";
echo "<p><strong style='color:red;'>IMPORTANT: Delete this file after testing!</strong></p>";
echo "<p><code>rm test_env.php</code></p>";

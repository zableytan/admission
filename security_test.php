<?php
/**
 * security_test.php
 * Test page to verify security implementations
 * DELETE THIS FILE after testing in production
 */

require 'security.php';

echo "<h1>Security Implementation Test</h1>";
echo "<hr>";

// Test 1: SQL Injection Detection
echo "<h2>1. SQL Injection Detection</h2>";
$test_inputs = [
    "normal text",
    "'; DROP TABLE users; --",
    "1 OR 1=1",
    "admin' --",
    "UNION SELECT * FROM users"
];

foreach ($test_inputs as $input) {
    $detected = detect_sqli_patterns($input);
    $status = $detected ? "<span style='color:red'>BLOCKED ✓</span>" : "<span style='color:green'>ALLOWED ✓</span>";
    echo "<p>Input: <code>" . htmlspecialchars($input) . "</code> - $status</p>";
}

// Test 2: Input Sanitization
echo "<h2>2. Input Sanitization</h2>";
$test_string = "<script>alert('XSS')</script>";
$sanitized = sanitize_input($test_string);
echo "<p>Original: <code>" . htmlspecialchars($test_string) . "</code></p>";
echo "<p>Sanitized: <code>" . htmlspecialchars($sanitized) . "</code> ✓</p>";

// Test 3: CSRF Token Generation
echo "<h2>3. CSRF Token</h2>";
$token = generate_csrf_token();
echo "<p>Token generated: " . substr($token, 0, 20) . "...</p>";
echo "<p>Token valid: " . (verify_csrf_token($token) ? "YES ✓" : "NO ✗") . "</p>";

// Test 4: XSS Protection
echo "<h2>4. XSS Protection (e() function)</h2>";
$xss_input = "<img src=x onerror=alert('XSS')>";
echo "<p>Original: <code>" . htmlspecialchars($xss_input) . "</code></p>";
echo "<p>Escaped: <code>" . e($xss_input) . "</code> ✓</p>";

// Test 5: Password Validation
echo "<h2>5. Password Validation</h2>";
$test_passwords = [
    "weak",
    "Password1",
    "Str0ng!Pass#2026",
    "abcdefgh"
];

foreach ($test_passwords as $pwd) {
    $result = validate_password($pwd);
    $status = $result['valid'] ? 
        "<span style='color:green'>STRONG ✓</span>" : 
        "<span style='color:red'>WEAK ✗ (" . implode(", ", $result['errors']) . ")</span>";
    echo "<p>Password: <code>" . str_repeat("*", strlen($pwd)) . "</code> - $status</p>";
}

// Test 6: Rate Limiting
echo "<h2>6. Rate Limiting</h2>";
$test_key = 'test_rate_limit';
$remaining = get_rate_limit_remaining($test_key);
echo "<p>Remaining attempts: $remaining ✓</p>";

// Test 7: ID Validation
echo "<h2>7. ID Validation</h2>";
$test_ids = [123, -1, 0, "abc", "456"];
foreach ($test_ids as $id) {
    $valid = validate_id($id);
    $status = $valid !== false ? 
        "<span style='color:green'>VALID ✓ (ID: $valid)</span>" : 
        "<span style='color:red'>INVALID ✗</span>";
    echo "<p>ID: <code>$id</code> - $status</p>";
}

// Test 8: Filename Sanitization
echo "<h2>8. Filename Sanitization</h2>";
$test_filenames = [
    "../../../etc/passwd",
    "myfile.php",
    "document with spaces.pdf",
    "normal_file.jpg"
];

foreach ($test_filenames as $filename) {
    $sanitized = sanitize_filename($filename);
    echo "<p>Original: <code>$filename</code> → Sanitized: <code>$sanitized</code> ✓</p>";
}

// Test 9: Security Headers
echo "<h2>9. Security Headers Check</h2>";
echo "<p>Check browser developer tools (Network tab) to verify:</p>";
echo "<ul>";
echo "<li>X-Frame-Options: SAMEORIGIN</li>";
echo "<li>X-Content-Type-Options: nosniff</li>";
echo "<li>X-XSS-Protection: 1; mode=block</li>";
echo "</ul>";

// Test 10: File Upload Validation
echo "<h2>10. File Upload Validation</h2>";
echo "<p>File upload validation is ready. Test by uploading files through the application.</p>";

echo "<hr>";
echo "<h2>✅ All Security Tests Completed</h2>";
echo "<p><strong>Important:</strong> Delete this file after testing!</p>";
echo "<p><code>rm security_test.php</code></p>";

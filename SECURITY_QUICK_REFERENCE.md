# 🔒 Security Quick Reference Card

## Most Common Security Functions

### Input Sanitization
```php
require 'security.php';

// String (default)
$name = sanitize_input($_POST['name'], 'string');

// Email
$email = sanitize_input($_POST['email'], 'email');

// Integer
$age = sanitize_input($_POST['age'], 'int');

// Float/Decimal
$amount = sanitize_input($_POST['amount'], 'float');

// URL
$website = sanitize_input($_POST['website'], 'url');

// HTML (escaped)
$html = sanitize_input($_POST['comment'], 'html');
```

### Output Escaping (XSS Prevention)
```php
// Short function (recommended)
<?= e($user_input) ?>

// Instead of
<?= $user_input ?>  // ❌ DANGEROUS
```

### CSRF Protection
```php
// 1. Add to form
<form method="POST">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>

// 2. Verify on submit
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $error = "Invalid security token";
} else {
    // Process form
}
```

### ID Validation
```php
$app_id = validate_id($_GET['app_id']);
if (!$app_id) {
    die("Invalid ID");
}
```

### Password Handling
```php
// Validate password strength
$result = validate_password($password);
if (!$result['valid']) {
    print_r($result['errors']);
}

// Hash password (already used in admin_manage.php)
$hash = hash_password($password);

// Verify password (already used in admin_login.php)
if (verify_password($input, $stored_hash)) {
    // Login successful
}
```

### File Upload Validation
```php
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$max_size = 5242880; // 5MB

$result = validate_file_upload($_FILES['document'], $allowed_types, $max_size);
if (!$result['valid']) {
    die($result['error']);
}

// Sanitize filename
$filename = sanitize_filename($_FILES['document']['name']);
```

### Security Logging
```php
// Log different severity levels
log_security_event("User logged in", 'info');
log_security_event("Failed login attempt", 'warning');
log_security_event("SQL injection attempt", 'critical');
log_security_event("Unauthorized access", 'error');
```

### Rate Limiting
```php
// Check rate limit (5 attempts per 5 minutes)
$key = 'action_' . $_SERVER['REMOTE_ADDR'];
if (!check_rate_limit($key, 5, 300)) {
    die("Too many attempts. Try again later.");
}
```

---

## Security Headers (Already Added)
```php
// These are already in place - no action needed
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
```

---

## Pattern to Follow for Every Form

```php
<?php
session_start();
require 'db.php';
require 'security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token";
    } else {
        // 2. Sanitize all inputs
        $field1 = sanitize_input($_POST['field1'], 'string');
        $field2 = sanitize_input($_POST['field2'], 'email');
        
        // 3. Process with prepared statements
        $stmt = $pdo->prepare("INSERT INTO table (col1, col2) VALUES (?, ?)");
        $stmt->execute([$field1, $field2]);
        
        // 4. Log if needed
        log_security_event("Form submitted successfully", 'info');
    }
}
?>

<form method="POST">
    <?= csrf_field() ?>
    <input type="text" name="field1">
    <input type="email" name="field2">
    <button type="submit">Submit</button>
</form>
```

---

## DO's and DON'Ts

### ✅ DO
- Use `sanitize_input()` for all user input
- Use `e()` for all output
- Add `csrf_field()` to all forms
- Use prepared statements (already in place)
- Log security events
- Validate file uploads
- Check rate limits

### ❌ DON'T
- Use `$_POST` or `$_GET` directly in queries
- Echo user input without escaping
- Skip CSRF verification
- Concatenate SQL queries
- Display error details to users
- Allow unrestricted file uploads
- Ignore security logs

---

## Files to Remember

| File | Purpose |
|------|---------|
| [security.php](file:///c:/xampp/htdocs/admission/security.php) | All security functions |
| [db.php](file:///c:/xampp/htdocs/admission/db.php) | Database connection |
| [.htaccess](file:///c:/xampp/htdocs/admission/.htaccess) | Server security |
| [SECURITY_OPTIMIZATIONS.md](file:///c:/xampp/htdocs/admission/SECURITY_OPTIMIZATIONS.md) | Full documentation |
| `/logs/security.log` | Security event logs |

---

## Emergency Contacts

**Found a vulnerability?**
1. Stop testing immediately
2. Document what you found
3. Report to system administrator
4. Check security logs

---

**Print this card and keep it at your desk!** 🖨️

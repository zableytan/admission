# Security Optimizations - DMSF Admission System

## Overview
This document outlines the comprehensive security optimizations implemented to protect the DMSF Admission System from SQL injection, XSS, CSRF, and other common web vulnerabilities.

---

## 1. SQL Injection Prevention ✅

### PDO Prepared Statements
**Status**: Already implemented throughout the codebase

The system uses PDO (PHP Data Objects) with prepared statements, which is the most effective defense against SQL injection:

```php
// Example of secure query (already in use)
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$app_id]);
```

### Enhanced PDO Configuration (db.php)
- `PDO::ATTR_EMULATE_PREPARES = false`: Uses native prepared statements
- `PDO::ATTR_STRINGIFY_FETCHES = false`: Prevents type confusion attacks
- Strict SQL mode enabled for data validation
- Error messages logged instead of displayed to users

### Additional SQL Injection Detection (security.php)
- Pattern-based detection for common SQL injection attempts
- Automatic blocking and logging of suspicious requests
- Monitors GET, POST, and COOKIE data

---

## 2. Cross-Site Scripting (XSS) Prevention ✅

### Input Sanitization
All user inputs are sanitized using:
- `filter_input()` with appropriate filters
- `htmlspecialchars()` with ENT_QUOTES flag
- Custom `sanitize_input()` function in security.php

### Output Escaping
- Use `e()` helper function for all output: `<?= e($user_input) ?>`
- `htmlspecialchars()` with UTF-8 encoding
- Content-Type headers set to prevent MIME sniffing

### Security Headers
```
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Content-Security-Policy: (configured in .htaccess)
```

---

## 3. Cross-Site Request Forgery (CSRF) Protection ✅

### CSRF Token System
Implemented in `security.php`:
- `generate_csrf_token()`: Creates unique token per session
- `verify_csrf_token()`: Validates tokens with timing attack protection
- `csrf_field()`: Generates hidden form field

### Implementation Example
```php
// In forms
<form method="POST">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>

// In form processing
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Invalid security token");
}
```

### Current Implementation
- ✅ admin_login.php
- ⚠️ Other forms: Add `csrf_field()` to POST forms as needed

---

## 4. Rate Limiting & Brute Force Protection ✅

### Login Rate Limiting
- **Limit**: 5 attempts per 5 minutes per IP address
- **Function**: `check_rate_limit()` in security.php
- **Logging**: All attempts logged with IP and timestamp
- **User Feedback**: Shows remaining lockout time

### Implementation (admin_login.php)
```php
$ip_address = $_SERVER['REMOTE_ADDR'];
$rate_key = 'login_ip_' . $ip_address;

if (!check_rate_limit($rate_key, 5, 300)) {
    $error = "Too many login attempts. Try again later.";
    log_security_event("Rate limit exceeded", 'warning');
}
```

---

## 5. File Upload Security ✅

### Upload Directory Protection (.htaccess)
Located in `/uploads/.htaccess`:
- Prevents PHP execution in uploads directory
- Disables directory listing
- Forces download for document files

### File Validation Functions
- `validate_file_upload()`: Checks MIME type and file size
- `sanitize_filename()`: Removes dangerous characters
- Allowed MIME types validation
- File size limits enforced

### Best Practices
```php
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$max_size = 5242880; // 5MB

$result = validate_file_upload($_FILES['document'], $allowed_types, $max_size);
if (!$result['valid']) {
    die($result['error']);
}
```

---

## 6. Session Security ✅

### Session Configuration
```php
ini_set('session.cookie_httponly', 1);        // Prevent JavaScript access
ini_set('session.cookie_secure', 0);          // Set to 1 for HTTPS
ini_set('session.use_only_cookies', 1);       // Cookies only
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
```

### Implemented In
- ✅ admin_dashboard.php
- ✅ admin_manage.php
- ✅ All admin-facing pages

---

## 7. Security Headers ✅

### HTTP Security Headers
Configured in `.htaccess` and individual PHP files:

| Header | Value | Purpose |
|--------|-------|---------|
| X-Frame-Options | SAMEORIGIN | Prevents clickjacking |
| X-Content-Type-Options | nosniff | Prevents MIME sniffing |
| X-XSS-Protection | 1; mode=block | Enables browser XSS filter |
| Referrer-Policy | strict-origin-when-cross-origin | Controls referrer info |
| Content-Security-Policy | (configured) | Prevents XSS and injection |

---

## 8. Apache/.htaccess Security ✅

### Root .htaccess Protection
- ✅ Directory browsing disabled
- ✅ Server signature hidden
- ✅ Sensitive files blocked (.sql, .env, .log, etc.)
- ✅ PHP execution prevented in /uploads, /scratch, /sql
- ✅ File upload size limited (10MB)
- ✅ Security headers configured
- ✅ Vendor directory protected

### Files Protected
```apache
<FilesMatch "\.(sql|env|ini|conf|bak|old)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 9. Security Logging ✅

### Event Logging
Function: `log_security_event($event, $level)`

**Logged Events**:
- Failed login attempts
- Rate limit violations
- SQL injection attempts
- Suspicious user agents
- Unauthorized access attempts

**Log Location**: `/logs/security.log`

**Log Format**:
```
[2026-05-11 10:30:45] [warning] [IP: 192.168.1.100] Failed login attempt for: admin
```

---

## 10. Input Validation ✅

### Validation Functions (security.php)

| Function | Purpose |
|----------|---------|
| `sanitize_input()` | General input sanitization |
| `sanitize_for_db()` | Database input preparation |
| `validate_id()` | Integer ID validation |
| `validate_password()` | Password strength checking |
| `detect_sqli_patterns()` | SQL injection pattern detection |

### Usage Examples
```php
// Sanitize string input
$name = sanitize_input($_POST['name'], 'string');

// Validate email
$email = sanitize_input($_POST['email'], 'email');

// Validate integer
$age = sanitize_input($_POST['age'], 'int');

// Validate ID
$app_id = validate_id($_GET['app_id']);
if (!$app_id) {
    die("Invalid application ID");
}
```

---

## Implementation Checklist

### ✅ Completed
- [x] Centralized security helper file (security.php)
- [x] Enhanced PDO security configuration
- [x] CSRF token system
- [x] Rate limiting for login
- [x] Security headers
- [x] .htaccess protection
- [x] Upload directory security
- [x] Session security hardening
- [x] Security logging system
- [x] Input validation functions
- [x] XSS protection helpers

### ⚠️ Recommended Next Steps
- [ ] Add CSRF tokens to all POST forms (apply.php, personal_data.php, etc.)
- [ ] Implement CAPTCHA for login after 3 failed attempts
- [ ] Add HTTPS/SSL certificate
- [ ] Enable HTTPS-only cookies
- [ ] Implement password expiration policy
- [ ] Add two-factor authentication (2FA)
- [ ] Regular security audit schedule
- [ ] Database backup encryption
- [ ] IP whitelisting for admin access

---

## Quick Reference

### For Developers

**Adding CSRF to a form:**
```php
<form method="POST">
    <?= csrf_field() ?>
    <!-- your form -->
</form>
```

**Verifying CSRF token:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid security token");
    }
    // Process form
}
```

**Sanitizing input:**
```php
require 'security.php';
$name = sanitize_input($_POST['name'], 'string');
$email = sanitize_input($_POST['email'], 'email');
```

**Escaping output:**
```php
<?= e($user_data) ?>
```

**Logging security events:**
```php
log_security_event("User accessed restricted area", 'warning');
```

---

## Security Best Practices

1. **Never trust user input** - Always validate and sanitize
2. **Use prepared statements** - Never concatenate SQL queries
3. **Escape all output** - Use `e()` function for display
4. **Implement CSRF protection** - Add tokens to all forms
5. **Keep dependencies updated** - Regular composer updates
6. **Monitor security logs** - Check `/logs/security.log` regularly
7. **Use strong passwords** - Enforce password complexity
8. **Limit file uploads** - Validate type, size, and content
9. **Use HTTPS** - Encrypt all data in transit
10. **Regular backups** - Encrypted and off-site storage

---

## Emergency Contacts

If you discover a security vulnerability:
1. Document the issue
2. Do NOT exploit it further
3. Report to system administrator immediately
4. Check security logs for related activity

---

**Last Updated**: May 11, 2026  
**Version**: 1.0  
**System**: DMSF Admission System

# CSRF Token Implementation Guide

## What is CSRF Protection?

Cross-Site Request Forgery (CSRF) attacks trick users into performing unwanted actions on websites where they're authenticated. CSRF tokens prevent this by ensuring the form submission comes from your actual website.

---

## How to Add CSRF Protection to Forms

### Step 1: Include Security File

At the top of your PHP file, add:
```php
require 'security.php';
```

### Step 2: Add Token to Form

Inside every `<form method="POST">`, add the CSRF field:

```php
<form method="POST" action="process.php">
    <?= csrf_field() ?>  <!-- Add this line -->
    
    <!-- Your existing form fields -->
    <input type="text" name="username">
    <button type="submit">Submit</button>
</form>
```

### Step 3: Verify Token on Submission

In your form processing code:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token first
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please refresh and try again.";
        // Optionally log the attempt
        log_security_event("CSRF token validation failed", 'warning');
    } else {
        // Token is valid - process the form
        $username = sanitize_input($_POST['username'], 'string');
        // ... rest of your processing code
    }
}
```

---

## Complete Example

### Before (No CSRF Protection)
```php
// apply.php - Form
<form method="POST">
    <input type="text" name="family_name">
    <input type="text" name="given_name">
    <button type="submit">Submit Application</button>
</form>

// Processing code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $family_name = $_POST['family_name'];
    $given_name = $_POST['given_name'];
    // Process...
}
```

### After (With CSRF Protection)
```php
// apply.php - At the top
require 'security.php';

// Form
<form method="POST">
    <?= csrf_field() ?>  <!-- CSRF token added -->
    <input type="text" name="family_name">
    <input type="text" name="given_name">
    <button type="submit">Submit Application</button>
</form>

// Processing code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please refresh and try again.";
    } else {
        // Sanitize inputs
        $family_name = sanitize_input($_POST['family_name'], 'string');
        $given_name = sanitize_input($_POST['given_name'], 'string');
        // Process...
    }
}
```

---

## Files That Need CSRF Protection

### ✅ Already Protected
- [x] admin_login.php

### ⚠️ Needs Protection (Add CSRF tokens)
- [ ] apply.php
- [ ] personal_data.php
- [ ] family_background.php
- [ ] educational_intent.php
- [ ] upload_docs.php
- [ ] admin_dashboard.php (status updates, interview scheduling)
- [ ] admin_manage.php (admin creation/editing)
- [ ] registrar_dashboard.php
- [ ] view_application.php

---

## Implementation Checklist for Each File

For each form in your application:

1. **Add at the top**:
   ```php
   require 'security.php';
   ```

2. **Add to every POST form**:
   ```php
   <?= csrf_field() ?>
   ```

3. **Add to POST processing**:
   ```php
   if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
       $error = "Invalid security token. Please refresh and try again.";
   } else {
       // Process form
   }
   ```

4. **Test the form**:
   - Submit form normally - should work
   - Try submitting without token - should fail
   - Try submitting with invalid token - should fail

---

## Advanced Usage

### Custom Token Lifetime

Default token expires after 1 hour. To change:

```php
// Verify with custom lifetime (e.g., 30 minutes)
if (!verify_csrf_token($_POST['csrf_token'], 1800)) {
    $error = "Session expired. Please refresh and try again.";
}
```

### AJAX/JavaScript Forms

If using AJAX to submit forms:

```javascript
// Get CSRF token from meta tag or hidden field
const csrfToken = document.querySelector('input[name="csrf_token"]').value;

// Include in AJAX request
fetch('process.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        'csrf_token': csrfToken,
        'field1': value1,
        'field2': value2
    })
});
```

### Multiple Forms on Same Page

Each form can use the same token (it's session-bound):

```php
<form method="POST" action="action1.php">
    <?= csrf_field() ?>
    <!-- Form 1 fields -->
</form>

<form method="POST" action="action2.php">
    <?= csrf_field() ?>
    <!-- Form 2 fields -->
</form>
```

---

## Common Issues & Solutions

### Issue: "Invalid security token" error on every submit

**Solution**: Make sure `session_start()` is called before `csrf_field()`

```php
<?php
session_start();  // Must be first
require 'security.php';
?>
```

### Issue: Token expires during long forms

**Solution**: Increase token lifetime or implement auto-refresh

```php
// Increase to 2 hours
generate_csrf_token();
$_SESSION['csrf_token_time'] = time(); // Reset timer
```

### Issue: Multiple tabs causing token conflicts

**Solution**: The token is session-bound, so multiple tabs work fine. No changes needed.

---

## Testing CSRF Protection

### Manual Test

1. Load the form page
2. View page source and find the csrf_token hidden field
3. Copy the token value
4. Submit the form with a modified token
5. Should receive "Invalid security token" error

### Automated Test

Use the security test page:
```
http://localhost/admission/security_test.php
```

---

## Security Best Practices

1. ✅ Always verify CSRF token before processing
2. ✅ Use `sanitize_input()` for all user inputs
3. ✅ Log failed CSRF attempts
4. ✅ Don't disable CSRF protection in production
5. ✅ Keep security.php updated
6. ✅ Test all forms after adding CSRF protection

---

## Quick Reference Card

```php
// 1. Include
require 'security.php';

// 2. Add to form
<?= csrf_field() ?>

// 3. Verify on submit
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Invalid token");
}

// 4. Sanitize input
$data = sanitize_input($_POST['field'], 'string');

// 5. Log if needed
log_security_event("Event description", 'info');
```

---

**Need Help?**
- Review: SECURITY_OPTIMIZATIONS.md
- Check logs: /logs/security.log
- Test with: security_test.php

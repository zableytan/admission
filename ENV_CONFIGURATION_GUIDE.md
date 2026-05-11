# 🔐 Environment Configuration Guide

## Overview

Your database credentials and sensitive information are now stored in environment variables instead of being hardcoded in PHP files. This is a security best practice that:

- ✅ Hides passwords from source code
- ✅ Prevents accidental exposure in version control
- ✅ Makes it easy to use different credentials for different environments
- ✅ Follows industry standards (12-factor app methodology)

---

## 📁 Files Created

### 1. `.env` - Your actual credentials (KEEP THIS SECRET!)
Contains your real database passwords and sensitive data.
**NEVER commit this file to Git or share it publicly.**

### 2. `.env.example` - Template file (SAFE to commit)
A template showing what variables are needed, but with placeholder values.

### 3. `config.php` - Environment loader
Reads the `.env` file and makes variables available to PHP.

### 4. `.gitignore` - Protects sensitive files
Prevents `.env` and other sensitive files from being committed to Git.

---

## 🚀 How It Works

### Before (Insecure - Hardcoded):
```php
// db.php - OLD WAY
$host = 'localhost';
$user = 'aqa_admindb';
$pass = 'DMSFI-AQA2026_admindb'; // ❌ Visible in source code!
```

### After (Secure - Environment Variables):
```php
// db.php - NEW WAY
require_once __DIR__ . '/config.php';

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', ''); // ✅ Stored in .env file!
```

```env
# .env file
DB_HOST=localhost
DB_USER=aqa_admindb
DB_PASS=DMSFI-AQA2026_admindb
```

---

## 📝 Setup Instructions

### For Initial Setup:

1. **The `.env` file is already created** with your current credentials
2. **Test that everything works** - visit your application
3. **Verify the connection** - if it works, you're all set!

### For New Developers/Environments:

1. **Copy the template:**
   ```bash
   copy .env.example .env
   ```

2. **Edit `.env` with your actual credentials:**
   ```env
   DB_HOST=localhost
   DB_NAME=your_database_name
   DB_USER=your_database_user
   DB_PASS=your_database_password
   ```

3. **Save and test** - your application should now connect!

---

## 🔧 Configuration Options

### Database Settings

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database server | `localhost` or `127.0.0.1` |
| `DB_NAME` | Database name | `aqa_admission_db` |
| `DB_USER` | Database username | `aqa_admindb` |
| `DB_PASS` | Database password | `your_password` |

### Application Settings

| Variable | Description | Values |
|----------|-------------|--------|
| `APP_ENV` | Application environment | `production`, `development`, `testing` |
| `APP_DEBUG` | Show detailed errors | `true` or `false` |
| `APP_URL` | Base URL | `http://localhost/admission` |

### Security Settings

| Variable | Description | Default |
|----------|-------------|---------|
| `SESSION_LIFETIME` | Session timeout (seconds) | `3600` |
| `CSRF_TOKEN_LIFETIME` | CSRF token expiry (seconds) | `3600` |

### Mail Settings (Optional)

| Variable | Description | Example |
|----------|-------------|---------|
| `MAIL_HOST` | SMTP server | `smtp.gmail.com` |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USER` | Email address | `you@gmail.com` |
| `MAIL_PASS` | Email password | `app_password` |

---

## 🛡️ Security Benefits

### 1. **Protection from Git Exposure**
```bash
# .gitignore prevents .env from being committed
git status
# .env file won't appear in staged changes
```

### 2. **Web Access Blocked**
```apache
# .htaccess blocks direct access to .env
# http://localhost/admission/.env → 403 Forbidden
```

### 3. **Different Credentials per Environment**
```env
# .env.development
DB_PASS=dev_password123

# .env.production
DB_PASS=super_secure_production_password
```

---

## 📋 Best Practices

### ✅ DO:
- Keep `.env` file private and secure
- Use `.env.example` as a template
- Set `APP_DEBUG=false` in production
- Use strong, unique passwords
- Backup `.env` securely
- Rotate passwords regularly

### ❌ DON'T:
- Commit `.env` to Git
- Share `.env` via email/chat
- Use same password for dev and production
- Set `APP_DEBUG=true` in production
- Hardcode credentials in PHP files
- Store `.env` in cloud storage publicly

---

## 🔍 Testing Your Setup

### Test 1: Check if .env is loaded
Create a test file:
```php
<?php
require 'config.php';
echo "DB Host: " . env('DB_HOST') . "<br>";
echo "DB Name: " . env('DB_NAME') . "<br>";
// Delete this file after testing!
```

### Test 2: Verify web access is blocked
Try accessing: `http://localhost/admission/.env`
Should show: **403 Forbidden**

### Test 3: Check Git ignores .env
```bash
git status
# .env should NOT appear in the list
```

---

## 🚨 Troubleshooting

### Issue: "Database connection failed"

**Solution 1:** Check if `.env` file exists
```bash
ls -la .env
```

**Solution 2:** Verify credentials in `.env`
```env
DB_HOST=localhost
DB_NAME=aqa_admission_db
DB_USER=aqa_admindb
DB_PASS=DMSFI-AQA2026_admindb
```

**Solution 3:** Check if config.php is loaded
```php
// Add temporarily to db.php
var_dump(file_exists(__DIR__ . '/.env'));
var_dump(env('DB_HOST'));
```

### Issue: "env() function not found"

**Solution:** Make sure config.php is included first:
```php
require_once __DIR__ . '/config.php';
```

---

## 🌍 Multiple Environments

You can create different `.env` files for different environments:

```
.env.development    # Local development
.env.staging        # Staging server
.env.production     # Production server
```

Then load the appropriate one:
```php
$envFile = __DIR__ . '/.env.' . getenv('APP_ENV');
if (file_exists($envFile)) {
    loadEnv($envFile);
} else {
    loadEnv(__DIR__ . '/.env');
}
```

---

## 📚 Using Credentials in Other Files

If you need database credentials in other files:

```php
// Any PHP file
require_once __DIR__ . '/config.php';

$db_host = env('DB_HOST');
$db_name = env('DB_NAME');
```

For mail configuration:
```php
$mail_host = env('MAIL_HOST', 'smtp.gmail.com');
$mail_port = env('MAIL_PORT', 587);
$mail_user = env('MAIL_USER');
$mail_pass = env('MAIL_PASS');
```

---

## 🔐 Additional Security Tips

### 1. Set Proper File Permissions (Linux/Mac)
```bash
chmod 600 .env
chown www-data:www-data .env
```

### 2. For Windows (XAMPP)
- Right-click `.env` → Properties → Security
- Remove "Read" permission for unnecessary users
- Keep only Administrator and SYSTEM access

### 3. Backup Securely
```bash
# Create encrypted backup
tar czf - .env | gpg -c > env_backup.tar.gz.gpg
```

### 4. Use Strong Passwords
```env
# Use long, random passwords
DB_PASS=x8K#mP2$vL9nQ4wR7jT5yB3cF6hA0sD
```

---

## ✅ Checklist

- [x] `.env` file created with credentials
- [x] `config.php` loads environment variables
- [x] `db.php` uses `env()` function
- [x] `.htaccess` blocks `.env` access
- [x] `.gitignore` prevents `.env` commit
- [x] `.env.example` template created
- [ ] Test database connection
- [ ] Verify `.env` is not accessible via web
- [ ] Confirm `.env` is in `.gitignore`
- [ ] Set `APP_DEBUG=false` for production

---

## 📞 Need Help?

1. Check if `.env` file exists in project root
2. Verify credentials are correct
3. Ensure `config.php` is included before using `env()`
4. Check Apache error logs for details
5. Test with `APP_DEBUG=true` temporarily

---

**Remember**: The `.env` file is the KEY to your database. Protect it like you would protect a password! 🔑

<?php
/**
 * Security Helper Functions
 * Provides comprehensive security utilities for SQL injection prevention,
 * XSS protection, CSRF tokens, input validation, and more.
 */

// Prevent direct access
if (!defined('SECURITY_LOADED')) {
    define('SECURITY_LOADED', true);
}

/**
 * Sanitize and validate input data
 * 
 * @param mixed $data The input data to sanitize
 * @param string $type The type of sanitization (string, email, int, float, html)
 * @param bool $trim Whether to trim whitespace
 * @return mixed Sanitized data
 */
function sanitize_input($data, $type = 'string', $trim = true) {
    if (is_array($data)) {
        return array_map(function($item) use ($type, $trim) {
            return sanitize_input($item, $type, $trim);
        }, $data);
    }

    if ($trim) {
        $data = trim($data);
    }

    // Remove null bytes
    $data = str_replace(chr(0), '', $data);

    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) ?: 0;
        
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) ?: 0.0;
        
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        
        case 'html':
            // Allow safe HTML tags
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        case 'raw':
            // No sanitization, just remove null bytes
            return $data;
        
        default:
            // String sanitization
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Validate and sanitize for database queries (additional layer beyond prepared statements)
 * 
 * @param string $data The input data
 * @param int $max_length Maximum allowed length
 * @return string Sanitized string
 */
function sanitize_for_db($data, $max_length = 255) {
    $data = sanitize_input($data, 'string');
    return substr($data, 0, $max_length);
}

/**
 * Generate CSRF token
 * 
 * @return string The generated token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @param int $max_age Maximum token age in seconds (default: 1 hour)
 * @return bool Whether the token is valid
 */
function verify_csrf_token($token, $max_age = 3600) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Check token age
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time'] > $max_age)) {
        // Token expired, regenerate
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token hidden field HTML
 * 
 * @return string HTML hidden input field
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Escape output to prevent XSS
 * 
 * @param string $string The string to escape
 * @return string Escaped string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate integer ID
 * 
 * @param mixed $id The ID to validate
 * @return int|false Validated ID or false
 */
function validate_id($id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    return ($id !== false && $id > 0) ? $id : false;
}

/**
 * Rate limiting check
 * 
 * @param string $key Unique identifier for the action (e.g., 'login_ip_127.0.0.1')
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @return bool Whether the action is allowed
 */
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $rate_key = 'rate_limit_' . $key;
    
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = [
            'count' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $rate_data = $_SESSION[$rate_key];
    
    // Reset if time window has passed
    if (time() - $rate_data['first_attempt'] > $time_window) {
        $_SESSION[$rate_key] = [
            'count' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($rate_data['count'] >= $max_attempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$rate_key]['count']++;
    return true;
}

/**
 * Get remaining attempts for rate limit
 * 
 * @param string $key Unique identifier
 * @return int Remaining attempts
 */
function get_rate_limit_remaining($key) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $rate_key = 'rate_limit_' . $key;
    $max_attempts = 5;
    
    if (!isset($_SESSION[$rate_key])) {
        return $max_attempts;
    }
    
    return max(0, $max_attempts - $_SESSION[$rate_key]['count']);
}

/**
 * Secure password validation
 * 
 * @param string $password The password to validate
 * @return array Validation result ['valid' => bool, 'errors' => array]
 */
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Hash password securely
 * 
 * @param string $password The password to hash
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verify password
 * 
 * @param string $password The password to verify
 * @param string $hash The stored hash
 * @return bool Whether the password matches
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate secure random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function generate_secure_string($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate file upload
 * 
 * @param array $file The $_FILE array element
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array Validation result ['valid' => bool, 'error' => string]
 */
function validate_file_upload($file, $allowed_types = [], $max_size = 5242880) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds limit'];
    }
    
    // Check file type
    if (!empty($allowed_types)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Sanitize filename for security
 * 
 * @param string $filename The original filename
 * @return string Sanitized filename
 */
function sanitize_filename($filename) {
    // Remove any directory traversal attempts
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Prevent multiple dots
    $filename = preg_replace('/\.+/', '.', $filename);
    
    return $filename;
}

/**
 * Log security event
 * 
 * @param string $event The event description
 * @param string $level The severity level (info, warning, error, critical)
 */
function log_security_event($event, $level = 'info') {
    $log_file = __DIR__ . '/logs/security.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0750, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = "[$timestamp] [$level] [IP: $ip] $event | UA: $user_agent" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Check for SQL injection patterns in input
 * 
 * @param string $input The input to check
 * @return bool Whether SQL injection patterns detected
 */
function detect_sqli_patterns($input) {
    $patterns = [
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|EXEC)\b)/i',
        '/(--|;|\/\*|\*\/)/',
        '/(\b(OR|AND)\b\s+\d+\s*=\s*\d+)/i',
        '/(\b(OR|AND)\b\s+[\'"]\w+[\'"]\s*=\s*[\'"]\w+[\'"])/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Security middleware - check request for common attacks
 */
function security_check() {
    // Check for suspicious user agents
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($user_agent) || preg_match('/(sqlmap|nikto|nmap|masscan)/i', $user_agent)) {
        log_security_event('Suspicious user agent detected', 'warning');
        http_response_code(403);
        die('Access Denied');
    }
    
    // Check for SQL injection in GET/POST/COOKIE
    foreach ([$_GET, $_POST, $_COOKIE] as $source) {
        foreach ($source as $key => $value) {
            if (is_string($value) && detect_sqli_patterns($value)) {
                log_security_event("SQL injection attempt detected in $key", 'critical');
                http_response_code(403);
                die('Access Denied');
            }
        }
    }
}

// Auto-run security check on include
security_check();

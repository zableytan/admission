<?php
session_start();
require 'db.php';
require 'security.php';

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please refresh and try again.";
    } else {
        // Rate limiting: 5 attempts per 5 minutes per IP
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rate_key = 'login_ip_' . $ip_address;
        
        if (!check_rate_limit($rate_key, 5, 300)) {
            $remaining_time = 300 - (time() - ($_SESSION['rate_limit_' . $rate_key]['first_attempt'] ?? time()));
            $error = "Too many login attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
            log_security_event("Rate limit exceeded for login from IP: $ip_address", 'warning');
        } else {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
            $password = $_POST['password'];
            $college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_SPECIAL_CHARS);

            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Double check college if not super admin or dean
                if (!$admin['is_super_admin'] && !$admin['is_dean'] && $admin['college'] !== $college) {
                    $error = "Access denied: You are not authorized for the " . htmlspecialchars($college) . " department.";
                    log_security_event("Unauthorized college access attempt by: $username", 'warning');
                } else {
                    // Successful login - reset rate limit
                    unset($_SESSION['rate_limit_' . $rate_key]);
                    
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_college'] = ($admin['is_super_admin'] || $admin['is_dean']) ? $college : $admin['college'];
                    $_SESSION['is_super_admin'] = (bool)$admin['is_super_admin'];
                    $_SESSION['is_dean'] = (bool)$admin['is_dean'];
                    
                    log_security_event("Successful login: $username", 'info');
                    
                    header("Location: admin_dashboard.php");
                    exit;
                }
            } else {
                $error = "Invalid username or password.";
                log_security_event("Failed login attempt for: $username from IP: $ip_address", 'warning');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login - DMSF</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #1a237e; /* Deep Indigo */
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        body { 
            background: var(--bg-gradient);
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .login-card { 
            max-width: 400px; 
            width: 100%;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            background: white;
        }
        .login-header {
            background: var(--primary-color);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .login-header img {
            width: 80px;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #eee;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: none;
        }
        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #0d125a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
            color: white;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="DMSF_Logo.png" alt="DMSF Logo">
        <h4 class="mb-0">Department Portal</h4>
        <p class="small opacity-75 mb-0">Secure Administrator Access</p>
    </div>
    <div class="login-body">
        <?php if(isset($error)): ?> 
            <div class="alert alert-danger py-2 small"><?= $error ?></div> 
        <?php endif; ?>
        
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-10"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 rounded-end-10 mb-0" required placeholder="Enter username">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-10"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 rounded-end-10 mb-0" required placeholder="Enter password">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">COLLECTIVE DEPARTMENT</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-10"><i class="bi bi-building text-muted"></i></span>
                    <select name="college" class="form-select border-start-0 rounded-end-10 mb-0" required>
                        <option value="Medicine" selected>Doctor of Medicine (ALL)</option>
                        <option value="Medicine (Filipino)">Doctor of Medicine (Filipino)</option>
                        <option value="Medicine (Foreign)">Doctor of Medicine (Foreign)</option>
                        <option value="Nursing">BS in Nursing</option>
                        <option value="Dentistry">Doctor of Dental Medicine</option>
                        <option value="Midwifery">BS in Midwifery</option>
                        <option value="Biology">BS in Biology</option>
                        <option value="Master in Community Health">Master in Community Health</option>
                        <option value="Master in Health Professions Education">Master in Health Professions Education</option>
                        <option value="Master in Participatory Development">Master in Participatory Development</option>
                        <option value="Accelerated Pathway for Medicine">Accelerated Pathway for Medicine</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100 shadow-sm">
                Login to Dashboard
            </button>
        </form>
        <div class="footer-text">
            &copy; 2026 Davao Medical School Foundation, Inc.
        </div>
    </div>
</div>

</body>
</html>
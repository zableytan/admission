<?php
session_start();
require 'db.php';

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];
    $college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_SPECIAL_CHARS);

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Double check college if not super admin
        if (!$admin['is_super_admin'] && $admin['college'] !== $college) {
            $error = "Access denied: You are not authorized for the " . htmlspecialchars($college) . " department.";
        } else {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_college'] = $admin['is_super_admin'] ? $college : $admin['college'];
            $_SESSION['is_super_admin'] = (bool)$admin['is_super_admin'];
            header("Location: admin_dashboard.php");
            exit;
        }
    } else {
        $error = "Invalid username or password.";
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
            filter: brightness(0) invert(1);
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
        <img src="DMSF_logo.png" alt="DMSF Logo">
        <h4 class="mb-0">Department Portal</h4>
        <p class="small opacity-75 mb-0">Secure Administrator Access</p>
    </div>
    <div class="login-body">
        <?php if(isset($error)): ?> 
            <div class="alert alert-danger py-2 small"><?= $error ?></div> 
        <?php endif; ?>
        
        <form method="POST">
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
                        <option value="Medicine" selected>College of Medicine</option>
                        <option value="Nursing">College of Nursing</option>
                        <option value="Dentistry">College of Dentistry</option>
                        <option value="Midwifery">College of Midwifery</option>
                        <option value="Biology">College of Biology</option>
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
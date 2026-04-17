<?php
session_start();
require 'db.php';

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: registrar_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_registrar = 1");
    $stmt->execute([$username]);
    $registrar = $stmt->fetch();

    if ($registrar && password_verify($password, $registrar['password'])) {
        $_SESSION['registrar_id'] = $registrar['id'];
        $_SESSION['registrar_username'] = $registrar['username'];
        header("Location: registrar_dashboard.php");
        exit;
    } else {
        $error = "Invalid registrar credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Portal Login - DMSF</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #196199;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #d6e4f0 100%);
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
            background: #124873;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 97, 153, 0.3);
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
        <h4 class="mb-0">Registrar Portal</h4>
        <p class="small opacity-75 mb-0">Secure Verification Access</p>
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
                    <input type="text" name="username" class="form-control border-start-0 rounded-end-10 mb-0" required placeholder="Enter registrar username">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-10"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 rounded-end-10 mb-0" required placeholder="Enter password">
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100 shadow-sm">
                Login to Registrar Portal
            </button>
        </form>
        <div class="footer-text">
            &copy; 2026 Davao Medical School Foundation, Inc.
        </div>
    </div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMSF - Admission Portal</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #196199; /* Updated to DMSF Blue */
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .admission-card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            padding: 60px 40px;
            max-width: 480px;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }
        .admission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-color);
        }
        .admission-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.12);
        }
        .logo-img {
            width: 120px !important;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 8px 15px rgba(0,0,0,0.1));
        }
        h2 {
            color: #1a1a1a;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }
        .sub-text {
            color: #6c757d;
            margin-bottom: 40px;
            font-size: 1.05rem;
            line-height: 1.6;
        }
        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            margin-bottom: 10px;
            padding-left: 4px;
        }
        .form-select {
            padding: 14px 18px;
            border-radius: 12px;
            border: 2px solid #f1f3f5;
            background-color: #f8f9fa;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 30px;
            transition: all 0.2s;
        }
        .form-select:focus {
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(25, 97, 153, 0.1);
        }
        .btn-apply {
            background-color: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-apply:hover {
            background-color: #124d7a;
            box-shadow: 0 8px 20px rgba(25, 97, 153, 0.3);
            transform: scale(1.02);
            color: white;
        }
        .footer-text {
            margin-top: 40px;
            font-size: 0.8rem;
            color: #adb5bd;
            font-weight: 500;
        }
        .admin-link {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #adb5bd;
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.2s;
        }
        .admin-link:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>

<div class="admission-card text-center">
    <a href="admin_login.php" class="admin-link" title="Admin Portal">
        <i class="bi bi-shield-lock"></i>
    </a>
    
    <div class="logo-container">
        <img src="DMSF_Logo.png" alt="DMSF Logo" class="logo-img">
    </div>
    
    <h2>Welcome</h2>
    <p class="sub-text">Embark on your medical journey with Davao Medical School Foundation.</p>
    
    <form action="apply.php" method="GET">
        <div class="text-start">
            <label class="form-label">Select Your College</label>
            <select name="college" class="form-select" required>
                <option value="" disabled selected>Choose a program...</option>
                <option value="Medicine (NMD)">College of Medicine (NMD)</option>
                <option value="Medicine (IMD)">College of Medicine (IMD)</option>
                <option value="Nursing">College of Nursing</option>
                <option value="Dentistry">College of Dentistry</option>
                <option value="Midwifery">College of Midwifery</option>
                <option value="Biology">Department of Biology</option>
                <option value="All Colleges" style="font-weight: 700; color: var(--primary-color);">All Colleges (Universal Application)</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-apply w-100 shadow-sm">
            Begin Application <i class="bi bi-arrow-right"></i>
        </button>
    </form>

    <div class="footer-text">
        &copy; 2026 Davao Medical School Foundation, Inc.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/form-draft.js"></script>

</body>
</html>
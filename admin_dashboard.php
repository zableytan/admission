<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db.php';

// Security Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle College Switching for Super Admins
if (isset($_GET['college']) && isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin']) {
    $college = filter_input(INPUT_GET, 'college', FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION['admin_college'] = $college;
} else {
    $college = $_SESSION['admin_college'];
    if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] && !isset($_GET['college'])) {
        // Default super admin view to All departments
        $college = 'All';
        $_SESSION['admin_college'] = 'All';
    }
}

$msg = '';

// Handle Status Update & File Re-upload (Application Processing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    $app_id = $_POST['app_id'];
    $decision = $_POST['decision']; // Accepted or Declined
    $student_email = $_POST['student_email'];

    $signed_doc_path = null;

    // If Accepted, process the signed document upload
    if ($decision === 'Accepted') {
        if (isset($_FILES['signed_doc']) && $_FILES['signed_doc']['error'] == 0) {
            $target_dir = "uploads/signed/";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    $msg = "Error: Could not create directory '$target_dir'. Please create it manually.";
                }
            }
            
            if (empty($msg)) {
                // Ensure the directory is writable
                @chmod($target_dir, 0777);

                // Generate unique filename for the signed document
                $file_ext = pathinfo($_FILES["signed_doc"]["name"], PATHINFO_EXTENSION);
                $file_name = "SIGNED_" . $app_id . "_" . time() . "." . $file_ext;
                
                // Use absolute path for move_uploaded_file for better reliability
                $resolved_dir = realpath($target_dir);
                if (!$resolved_dir) {
                    $msg = "Error: Directory '$target_dir' does not exist or is not accessible.";
                } else {
                    $target_path = $resolved_dir . DIRECTORY_SEPARATOR . $file_name;

                    if (move_uploaded_file($_FILES["signed_doc"]["tmp_name"], $target_path)) {
                        $signed_doc_path = "uploads/signed/" . $file_name;
                    } else {
                        $msg = "Error: Failed to move uploaded file to $target_path. Check folder permissions.";
                        $signed_doc_path = null;
                    }
                }
            }
        } else {
            $upload_error = isset($_FILES['signed_doc']) ? $_FILES['signed_doc']['error'] : 'No file uploaded';
            $error_desc = [
                1 => 'File exceeds upload_max_filesize in php.ini',
                2 => 'File exceeds MAX_FILE_SIZE in HTML form',
                3 => 'File was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk',
                8 => 'A PHP extension stopped the file upload'
            ];
            $desc = is_numeric($upload_error) ? ($error_desc[$upload_error] ?? "Unknown error ($upload_error)") : $upload_error;
            $msg = "Error: Signed document upload failed ($desc).";
        }
    }

    // Only update status if signed_doc_path is set OR decision is Declined
    if ($decision === 'Declined' || ($decision === 'Accepted' && $signed_doc_path)) {
        $sql = "UPDATE applications SET status = ?, signed_document_path = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$decision, $signed_doc_path, $app_id])) {
            $msg = "Application updated to **$decision** successfully.";

            if ($decision === 'Accepted') {
                // Fetch student name and admin emails for the notification
                $app_stmt = $pdo->prepare("SELECT given_name, family_name, college FROM applications WHERE id = ?");
                $app_stmt->execute([$app_id]);
                $student_data = $app_stmt->fetch();
                $s_given = $student_data['given_name'] ?? '';
                $s_family = $student_data['family_name'] ?? '';
                $s_name = $s_given . ' ' . $s_family;
                $app_college = $student_data['college'];

                $admin_stmt = $pdo->prepare("SELECT email FROM admins WHERE college = ? OR is_super_admin = 1");
                $admin_stmt->execute([$app_college]);
                $admin_emails = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);

                // --- 2. SEND EMAIL WITH SIGNED DOCUMENT ---
                $smtp_config = require 'mail_config.php';
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = $smtp_config['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_config['username'];
                    $mail->Password = $smtp_config['password'];
                    $mail->SMTPSecure = $smtp_config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $smtp_config['port'];

                    $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);

                    // To Student
                    $mail->addAddress($student_email, $s_name);

                    // CC Admins for copy
                    foreach ($admin_emails as $admin_email_list) {
                        $emails = explode(',', $admin_email_list);
                        foreach ($emails as $email) {
                            $email = trim($email);
                            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $mail->addCC($email);
                            }
                        }
                    }

                    // Attach the signed document if it's not too large
                    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);
                    $signed_doc_url = $base_url . '/' . $signed_doc_path;
                    
                    if ($signed_doc_path) {
                        $abs_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $signed_doc_path);
                        if (file_exists($abs_path)) {
                            // Gmail limit is 25MB. We'll use 20MB as a safe threshold.
                            if (filesize($abs_path) < 20 * 1024 * 1024) {
                                $mail->addAttachment($abs_path, "Signed_Admission_Form_" . str_replace(' ', '_', $s_name) . ".pdf");
                                $attachment_status = "attached to this email";
                            } else {
                                $attachment_status = "available for download via the link below (file too large to attach)";
                            }
                        }
                    }

                    // Performance optimization
                    $mail->SMTPKeepAlive = true; 

                    $mail->isHTML(true);
                    $mail->Subject = "Admission Accepted: $s_name - " . $app_college;
                    $mail->Body = "
<div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
    <h2 style='color: #1a237e;'>Congratulations!</h2>
    <p>Dear <strong>$s_name</strong>,</p>
    <p>We are pleased to inform you that your application for admission to the <strong>$app_college</strong> at Davao
        Medical School Foundation, Inc. has been <strong>ACCEPTED</strong>.</p>
    <p>Your <strong>Signed Admission Form</strong> is $attachment_status. Please keep this for your records and
        follow the next steps as advised by the registrar.</p>
    <p><a href='$signed_doc_url' style='background-color: #1a237e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Download Signed Form</a></p>
    <br>
    <p>Best Regards,</p>
    <p><strong>DMSF Admissions Office</strong><br>Davao Medical School Foundation, Inc.</p>
</div>";

                    $mail->send();
                    $msg .= " Notification email with signed form sent to student and admins.";
                } catch (Exception $e) {
                    $msg .= " (Warning: Email failed to send. " . $mail->ErrorInfo . ")";
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                }
            }

            // Set flash message and redirect
            $_SESSION['flash_msg'] = $msg;
            header("Location: admin_dashboard.php");
            exit;
        }
    } else if ($decision === 'Accepted' && !$signed_doc_path && empty($msg)) {
        $msg = "Action failed: Signed document must be uploaded to accept the application.";
    }
}

// Check for flash message
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Fetch Applications
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] && ($college === 'All' || $college === '' || $college === null)) {
    // Super Admin viewing all departments
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC");
    $applications = $stmt->fetchAll();
} else {
    // Department-specific view
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE college = ? ORDER BY created_at DESC");
    $stmt->execute([$college]);
    $applications = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $college ?> Department Portal - DMSF</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3f51b5;
            --bg-light: #f8f9fa;
            --status-pending-bg: #fff3cd;
            --status-pending-text: #856404;
            --status-accepted-bg: #d4edda;
            --status-accepted-text: #155724;
            --status-declined-bg: #f8d7da;
            --status-declined-text: #721c24;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .navbar {
            background: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .dashboard-container {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px;
            border-radius: 15px 15px 0 0 !important;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #fcfcfc;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c757d;
            border-top: none;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background: var(--status-pending-bg);
            color: var(--status-pending-text);
        }

        .status-accepted {
            background: var(--status-accepted-bg);
            color: var(--status-accepted-text);
        }

        .status-declined {
            background: var(--status-declined-bg);
            color: var(--status-declined-text);
        }

        .action-form {
            background: #fdfdfd;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            padding: 15px;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
            border: none;
            font-weight: 600;
        }

        .btn-view:hover {
            background: #1976d2;
            color: white;
        }

        .applicant-name {
            font-weight: 600;
            color: #2c3e50;
            display: block;
        }

        .applicant-email {
            font-size: 0.8rem;
            color: #95a5a6;
        }

        .score-pill {
            background: #eee;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Loading Animation Styles */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            margin-top: 15px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="DMSF_Logo.png" alt="DMSF Logo" height="40" class="me-2"
                    style="filter: brightness(0) invert(1);">
                <div>
                    <span class="fw-bold d-block" style="line-height: 1.2;">DMSF</span>
                    <span class="small opacity-75" style="font-size: 0.7rem;"><?= $college ?> Portal</span>
                </div>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <?php if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin']): ?>
                    <form action="admin_dashboard.php" method="GET" class="me-3">
                        <select name="college" class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3"
                            onchange="this.form.submit()">
                            <option value="All" <?= $college === 'All' ? 'selected' : '' ?>>All</option>
                            <option value="Medicine" <?= $college === 'Medicine' ? 'selected' : '' ?>>Medicine</option>
                            <option value="Nursing" <?= $college === 'Nursing' ? 'selected' : '' ?>>Nursing</option>
                            <option value="Dentistry" <?= $college === 'Dentistry' ? 'selected' : '' ?>>Dentistry</option>
                            <option value="Midwifery" <?= $college === 'Midwifery' ? 'selected' : '' ?>>Midwifery</option>
                            <option value="Biology" <?= $college === 'Biology' ? 'selected' : '' ?>>Biology</option>
                        </select>
                    </form>
                    <a href="admin_manage.php" class="btn btn-outline-light btn-sm rounded-pill px-3 me-3">
                        <i class="bi bi-people-fill me-1"></i> Manage Admins
                    </a>
                <?php endif; ?>
                <span class="text-white opacity-75 me-3 small">Welcome, Admin</span>
                <a href="admin_login.php?logout=true" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Applications Overview</h3>
                    <p class="text-muted mb-0">Manage and process student admission applications</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-white text-dark shadow-sm p-2 px-3 border">
                        <i class="bi bi-calendar3 me-2"></i> <?= date('F d, Y') ?>
                    </span>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> <?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0 fw-bold">Recent Applications</h5>
                        </div>
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control bg-light border-start-0"
                                    placeholder="Search applicants...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Applicant</th>
                                    <th>College/Score</th>
                                    <th>Status</th>
                                    <th>Documents</th>
                                    <th class="pe-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span
                                                class="applicant-name"><?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?></span>
                                            <span class="applicant-email"><?= $app['email'] ?></span>
                                            <div class="mt-1 small text-muted">ID:
                                                #<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                        </td>
                                        <td>
                                            <div class="mb-1 fw-semibold small text-primary"><?= $app['college'] ?></div>
                                            <span class="score-pill"><?= $app['score_value'] ?>
                                                (<?= $app['score_type'] ?>)</span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                                <?= $app['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <?php 
                                                $doc_icons = [
                                                    'tor_path' => 'file-earmark-text',
                                                    'birth_cert_path' => 'person-badge',
                                                    'nmat_path' => 'journal-check',
                                                    'diploma_path' => 'award',
                                                    'gwa_cert_path' => 'calculator',
                                                    'entrance_exam_path' => 'pencil-square',
                                                    'receipt_path' => 'receipt',
                                                    'good_moral_path' => 'shield-check'
                                                ];
                                                foreach($doc_icons as $field => $icon):
                                                    if(!empty($app[$field])):
                                                ?>
                                                    <i class="bi bi-<?= $icon ?> text-success" title="<?= str_replace('_', ' ', strtoupper($field)) ?>"></i>
                                                <?php endif; endforeach; ?>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="view_application.php?id=<?= $app['id'] ?>"
                                                    class="btn btn-view btn-sm rounded-pill px-3">
                                                    <i class="bi bi-eye me-1"></i> View Docs
                                                </a>
                                                <?php if ($app['status'] == 'Accepted' && !empty($app['signed_document_path'])): ?>
                                                    <a href="<?= $app['signed_document_path'] ?>" target="_blank"
                                                        class="btn btn-sm btn-success py-1 text-white">
                                                        <i class="bi bi-file-earmark-check me-1"></i> Signed Form
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="pe-4">
                                            <?php if ($app['status'] == 'Pending'): ?>
                                                <form method="POST" enctype="multipart/form-data" class="action-form">
                                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                                    <input type="hidden" name="student_email" value="<?= $app['email'] ?>">

                                                    <div class="mb-2">
                                                        <label class="form-label small fw-bold mb-1">UPLOAD SIGNED FORM</label>
                                                        <input type="file" name="signed_doc"
                                                            class="form-control form-control-sm border-dashed">
                                                    </div>

                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <button type="submit" name="decision" value="Accepted"
                                                                class="btn btn-success btn-sm w-100 fw-bold">
                                                                ACCEPT
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button type="submit" name="decision" value="Declined"
                                                                class="btn btn-outline-danger btn-sm w-100 fw-bold">
                                                                DECLINE
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <span class="text-muted small italic">Processed on
                                                        <?= date('M d, Y', strtotime($app['updated_at'] ?? 'now')) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($applications)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                            No applications found for this college.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Processing Application...</div>
        <p class="text-muted small mt-2">Sending notification emails, please wait.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Use event delegation for the multiple possible action forms
        document.body.addEventListener('submit', function (e) {
            if (e.target.classList.contains('action-form')) {
                // Show loading overlay
                document.getElementById('loadingOverlay').style.display = 'flex';

                // Create hidden input to ensure the button's value is sent
                if (e.submitter && e.submitter.name) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = e.submitter.name;
                    input.value = e.submitter.value;
                    e.target.appendChild(input);
                }

                // Disable the specific buttons in this form
                const buttons = e.target.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    if (e.submitter && btn === e.submitter) {
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + btn.innerText;
                    }
                });
            }
        });
    </script>
</body>

</html>

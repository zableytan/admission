<?php
// session_start(); // Commented out for screen recording
require 'db.php';

// Security Check - Bypassed for screen recording
$college = isset($_GET['college']) ? $_GET['college'] : 'Medicine';
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
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            // Generate unique filename for the signed document
            $file_name = "SIGNED_" . $app_id . "_" . time() . "_" . basename($_FILES["signed_doc"]["name"]);
            $signed_doc_path = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["signed_doc"]["tmp_name"], $signed_doc_path)) {
                // File moved successfully
            } else {
                $msg = "Error uploading signed document.";
                $signed_doc_path = null; // Revert path if upload fails
            }
        } else {
             $msg = "Error: Signed document upload is required for Acceptance.";
        }
    }

    // Only update status if signed_doc_path is set OR decision is Declined
    if ($decision === 'Declined' || ($decision === 'Accepted' && $signed_doc_path)) {
        $sql = "UPDATE applications SET status = ?, signed_document_path = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$decision, $signed_doc_path, $app_id])) {
            $msg = "Application updated to **$decision** successfully.";
            if ($decision === 'Accepted') {
                $msg .= " An email with the signed document has been sent to $student_email.";
            }
            
            // --- EMAIL NOTIFICATION TO STUDENT (Placeholder) ---
            // In a real system, you would attach $signed_doc_path file if accepted.
            // mail($student_email, "Admission Decision", "Your application status is: $decision");

            // Redirect back to avoid re-post with college parameter
            header("Location: admin_dashboard.php?college=" . urlencode($college));
            exit;
        }
    } else if ($decision === 'Accepted' && !$signed_doc_path) {
         // This handles the edge case where they click Accept but the file upload failed or was missing
         $msg = "Action failed: Signed document must be uploaded to accept the application.";
    }
}

// Fetch Applications for this Specific College ONLY
$stmt = $pdo->prepare("SELECT * FROM applications WHERE college = ? ORDER BY created_at DESC");
$stmt->execute([$college]);
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $college ?> Department Portal - DMSF</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dashboard-container {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
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
        .status-pending { background: var(--status-pending-bg); color: var(--status-pending-text); }
        .status-accepted { background: var(--status-accepted-bg); color: var(--status-accepted-text); }
        .status-declined { background: var(--status-declined-bg); color: var(--status-declined-text); }
        
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
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="DMSF_logo.png" alt="DMSF Logo" height="40" class="me-2" style="filter: brightness(0) invert(1);">
            <div>
                <span class="fw-bold d-block" style="line-height: 1.2;">DMSF</span>
                <span class="small opacity-75" style="font-size: 0.7rem;"><?= $college ?> Portal</span>
            </div>
        </a>
        <div class="ms-auto d-flex align-items-center">
            <form action="admin_dashboard.php" method="GET" class="me-3">
                <select name="college" class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3" onchange="this.form.submit()">
                    <option value="Medicine" <?= $college === 'Medicine' ? 'selected' : '' ?>>Medicine</option>
                    <option value="Nursing" <?= $college === 'Nursing' ? 'selected' : '' ?>>Nursing</option>
                    <option value="Dentistry" <?= $college === 'Dentistry' ? 'selected' : '' ?>>Dentistry</option>
                    <option value="Midwifery" <?= $college === 'Midwifery' ? 'selected' : '' ?>>Midwifery</option>
                    <option value="Biology" <?= $college === 'Biology' ? 'selected' : '' ?>>Biology</option>
                </select>
            </form>
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
        
        <?php if($msg): ?> 
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
                            <input type="text" class="form-control bg-light border-start-0" placeholder="Search applicants...">
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
                                    <span class="applicant-name"><?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?></span>
                                    <span class="applicant-email"><?= $app['email'] ?></span>
                                    <div class="mt-1 small text-muted">ID: #<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td>
                                    <div class="mb-1 fw-semibold small text-primary"><?= $app['college'] ?></div>
                                    <span class="score-pill"><?= $app['score_value'] ?> (<?= $app['score_type'] ?>)</span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                        <?= $app['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="view_application.php?id=<?= $app['id'] ?>" class="btn btn-view btn-sm rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i> View Docs
                                        </a>
                                        <?php if($app['status'] == 'Accepted' && !empty($app['signed_document_path'])): ?>
                                            <a href="<?= $app['signed_document_path'] ?>" target="_blank" class="btn btn-sm btn-success py-1 text-white">
                                                <i class="bi bi-file-earmark-check me-1"></i> Signed Form
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <?php if($app['status'] == 'Pending'): ?>
                                        <form method="POST" enctype="multipart/form-data" class="action-form">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="student_email" value="<?= $app['email'] ?>">
                                            
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold mb-1">UPLOAD SIGNED FORM</label>
                                                <input type="file" name="signed_doc" class="form-control form-control-sm border-dashed">
                                            </div>
                                            
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <button type="submit" name="decision" value="Accepted" class="btn btn-success btn-sm w-100 fw-bold">
                                                        ACCEPT
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="submit" name="decision" value="Declined" class="btn btn-outline-danger btn-sm w-100 fw-bold">
                                                        DECLINE
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <span class="text-muted small italic">Processed on <?= date('M d, Y', strtotime($app['updated_at'] ?? 'now')) ?></span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
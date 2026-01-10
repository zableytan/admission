<?php
// admin_demo.php - Hardcoded version for screen recording
$msg = $_GET['msg'] ?? '';
$status = $_GET['status'] ?? 'Pending';
$signed_doc_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'];
    if ($decision === 'Accepted') {
        $status = 'Accepted';
        $m = "Application updated to **Accepted** successfully. An email with the signed document has been sent to " . $app['email'] . ".";
    } else {
        $status = 'Declined';
        $m = "Application updated to **Declined** successfully.";
    }
    // Redirect to GET to allow clean refresh (Post/Redirect/Get pattern)
    header("Location: admin_demo.php?msg=" . urlencode($m) . "&status=" . $status);
    exit;
}

// Sample Data
$app = [
    'id' => 101,
    'given_name' => 'John',
    'family_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'score_value' => '92.5',
    'score_type' => 'NMAT',
    'college' => 'Medicine',
    'attachment_path' => '#'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Department Portal - Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
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
        .table thead th {
            background: #fcfcfc;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c757d;
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
                <span class="small opacity-75" style="font-size: 0.7rem;">Medicine Portal (DEMO)</span>
            </div>
        </a>
        <div class="ms-auto">
            <a href="admin_login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
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
                <p class="text-muted mb-0">DEMO MODE: Interactive preview of application processing</p>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-dark shadow-sm p-2 px-3 border">
                    <i class="bi bi-calendar3 me-2"></i> <?= date('F d, Y') ?>
                </span>
            </div>
        </div>
        
        <?php if($msg): ?> 
            <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div><i class="bi bi-info-circle-fill me-2"></i> <?= $msg ?></div>
                    <a href="admin_demo.php" class="btn btn-sm btn-primary">Reset Demo</a>
                </div>
            </div> 
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">Recent Applications</h5>
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
                            <tr>
                                <td class="ps-4">
                                    <span class="applicant-name"><?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?></span>
                                    <span class="applicant-email"><?= $app['email'] ?></span>
                                    <div class="mt-1 small text-muted">ID: #<?= $app['id'] ?></div>
                                </td>
                                <td>
                                    <div class="mb-1 fw-semibold small text-primary"><?= $app['college'] ?></div>
                                    <span class="score-pill"><?= $app['score_value'] ?> (<?= $app['score_type'] ?>)</span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($status) ?>">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <button class="btn btn-sm btn-light py-1">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> View Docs
                                        </button>
                                        <?php if($status == 'Accepted'): ?>
                                            <button class="btn btn-sm btn-success py-1 text-white">
                                                <i class="bi bi-file-earmark-check me-1"></i> Signed Form
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <?php if($status == 'Pending'): ?>
                                        <form method="POST" class="action-form">
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold mb-1">UPLOAD SIGNED FORM</label>
                                                <input type="file" class="form-control form-control-sm border-dashed">
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <button type="submit" name="decision" value="Accepted" class="btn btn-success btn-sm w-100 fw-bold">ACCEPT</button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="submit" name="decision" value="Declined" class="btn btn-outline-danger btn-sm w-100 fw-bold">DECLINE</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <span class="text-muted small italic">Action Completed</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
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

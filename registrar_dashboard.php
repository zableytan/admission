<?php
session_start();
require 'db.php';

$msg = '';

// Handle Acknowledgement Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    $app_id = $_POST['app_id'];
    $action = $_POST['action']; // 'acknowledge' or 'undo'
    
    $status = ($action === 'acknowledge') ? 1 : 0;
    
    $sql = "UPDATE applications SET registrar_acknowledged = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$status, $app_id])) {
        $actionText = $status ? "Acknowledged" : "Un-acknowledged";
        $_SESSION['flash_msg'] = "Application #$app_id marked as $actionText.";
        header("Location: registrar_dashboard.php");
        exit;
    } else {
        $msg = "Failed to update record.";
    }
}

if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Fetch only Accepted Applications
$stmt = $pdo->query("SELECT * FROM applications WHERE status = 'Accepted' ORDER BY updated_at DESC");
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - DMSF</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #196199;
            --bg-light: #f8f9fa;
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

        .table thead th {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c757d;
            border-top: none;
            background: #fcfcfc;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            font-size: 0.9rem;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="DMSF_Logo.png" alt="DMSF Logo" height="40" class="me-2" style="filter: brightness(0) invert(1);">
                <div>
                    <span class="fw-bold d-block" style="line-height: 1.2;">DMSF</span>
                    <span class="small opacity-75" style="font-size: 0.7rem;">Registrar Portal</span>
                </div>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white opacity-75 me-3 small">Welcome, Registrar</span>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Registrar Dashboard</h3>
                    <p class="text-muted mb-0">Track accepted students visiting the registrar office</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-white text-dark shadow-sm p-2 px-3 border">
                        <i class="bi bi-calendar3 me-2"></i> <?= date('F d, Y') ?>
                    </span>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0 fw-bold">Accepted Students Queue</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Applicant</th>
                                    <th>College details</th>
                                    <th>Status</th>
                                    <th class="pe-4 text-end">Registrar Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="applicant-name"><?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?></span>
                                            <span class="applicant-email"><?= htmlspecialchars($app['email']) ?></span>
                                            <div class="mt-1 small text-muted">ID: #<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                        </td>
                                        <td>
                                            <div class="mb-1 fw-semibold small text-primary">
                                                <?= htmlspecialchars($app['college']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (isset($app['registrar_acknowledged']) && $app['registrar_acknowledged']): ?>
                                                <span class="badge bg-success rounded-pill px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Acknowledged</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="bi bi-clock me-1"></i> Waiting</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                                <?php if (isset($app['registrar_acknowledged']) && $app['registrar_acknowledged']): ?>
                                                    <button type="submit" name="action" value="undo" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Undo
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="acknowledge" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                                        <i class="bi bi-check2 me-1"></i> Mark as Acknowledged
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($applications)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                            No accepted applications available to review.
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

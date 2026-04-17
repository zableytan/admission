<?php
session_start();
require 'db.php';

// Security Check - Only Registrars can access this page
if (!isset($_SESSION['registrar_id'])) {
    header("Location: registrar_login.php");
    exit;
}

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
$stmt = $pdo->query("SELECT * FROM applications WHERE status = 'Accepted' ORDER BY college ASC, updated_at DESC");
$applications = $stmt->fetchAll();

// Group by College
$grouped_apps = [];
foreach ($applications as $app) {
    $grouped_apps[$app['college']][] = $app;
}
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
                <img src="DMSF_Logo.png" alt="DMSF Logo" height="40" class="me-2">
                <div>
                    <span class="fw-bold d-block" style="line-height: 1.2;">DMSF</span>
                    <span class="small opacity-75" style="font-size: 0.7rem;">Registrar Portal</span>
                </div>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white opacity-75 me-3 small">Welcome, <?= htmlspecialchars($_SESSION['registrar_username']) ?></span>
                <a href="registrar_login.php?logout=1" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-7">
                    <h3 class="fw-bold mb-1">Registrar Dashboard</h3>
                    <p class="text-muted mb-0">Track accepted students visiting the registrar office</p>
                </div>
                <div class="col-md-5 text-end">
                    <div class="input-group shadow-sm mb-2">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
                        <input type="text" id="registrarSearch" class="form-control border-start-0 py-2" placeholder="Search across all colleges by name or email...">
                    </div>
                    <span class="badge bg-white text-dark shadow-sm p-2 px-3 border rounded-pill">
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

            <?php if (empty($grouped_apps)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                        No accepted applications available to review.
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($grouped_apps as $college_name => $apps): ?>
                <div class="college-section mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-3 p-2 me-3">
                            <i class="bi bi-mortarboard-fill h4 mb-0"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($college_name) ?></h4>
                            <span class="text-muted small fw-semibold text-uppercase"><?= count($apps) ?> Accepted Student(s)</span>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm overflow-hidden">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Applicant</th>
                                            <th class="text-center">Status</th>
                                            <th>Requirements & Docs</th>
                                            <th class="pe-4 text-end">Registrar Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($apps as $app): ?>
                                            <tr class="applicant-row">
                                                <td class="ps-4">
                                                    <span class="applicant-name"><?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?></span>
                                                    <span class="applicant-email"><?= htmlspecialchars($app['email']) ?></span>
                                                    <div class="mt-1 small text-muted">ID: #<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (isset($app['registrar_acknowledged']) && $app['registrar_acknowledged']): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                                            <i class="bi bi-check-circle-fill me-1"></i> Acknowledged
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold">
                                                            <i class="bi bi-clock me-1"></i> Waiting
                                                        </span>
                                                    <?php endif; ?>
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
                                                            'good_moral_path' => 'shield-check'
                                                        ];
                                                        foreach ($doc_icons as $field => $icon):
                                                            if (!empty($app[$field])):
                                                                ?>
                                                                <i class="bi bi-<?= $icon ?> text-success" style="font-size: 1.1rem;"
                                                                    title="<?= str_replace('_', ' ', strtoupper($field)) ?>"></i>
                                                            <?php endif; endforeach; ?>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <a href="view_application.php?id=<?= $app['id'] ?>" target="_blank"
                                                            class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-bold">
                                                            <i class="bi bi-eye me-1"></i> View Requirements
                                                        </a>
                                                        <?php if (!empty($app['signed_document_path'])): ?>
                                                            <a href="<?= $app['signed_document_path'] ?>" target="_blank"
                                                                class="btn btn-sm btn-success rounded-pill py-1 px-3 text-white fw-bold shadow-sm">
                                                                <i class="bi bi-file-earmark-check me-1"></i> NOA / Signed Form
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <?php if (isset($app['registrar_acknowledged']) && $app['registrar_acknowledged']): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" 
                                                                onclick="confirmRegistrarAction(<?= $app['id'] ?>, 'undo', '<?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?>')">
                                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Undo
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"
                                                                onclick="confirmRegistrarAction(<?= $app['id'] ?>, 'acknowledge', '<?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?>')">
                                                            <i class="bi bi-check2 me-1"></i> Mark as Acknowledged
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-5 text-center">
                    <div class="mb-4">
                        <i id="confirmIcon" class="bi bi-question-circle text-primary display-1"></i>
                    </div>
                    <h3 class="fw-bold mb-3" id="confirmTitle">Confirm Action</h3>
                    <p class="text-muted mb-4" id="confirmMessage">Are you sure you want to perform this action?</p>
                    
                    <form method="POST" id="confirmForm">
                        <input type="hidden" name="app_id" id="confirmAppId">
                        <input type="hidden" name="action" id="confirmAction">
                        
                        <div class="d-flex gap-3 justify-content-center">
                            <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="confirmBtn" class="btn btn-primary rounded-pill px-5 py-2 fw-bold">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        function confirmRegistrarAction(appId, action, studentName) {
            const title = document.getElementById('confirmTitle');
            const message = document.getElementById('confirmMessage');
            const icon = document.getElementById('confirmIcon');
            const btn = document.getElementById('confirmBtn');
            const appIdInput = document.getElementById('confirmAppId');
            const actionInput = document.getElementById('confirmAction');

            appIdInput.value = appId;
            actionInput.value = action;

            if (action === 'acknowledge') {
                title.innerText = "Acknowledge Student";
                message.innerHTML = `Are you sure you want to mark <strong>${studentName}</strong> as acknowledged? This signifies they have visited the registrar office.`;
                icon.className = "bi bi-check-circle-fill text-success display-1";
                btn.className = "btn btn-success rounded-pill px-5 py-2 fw-bold";
                btn.innerText = "Acknowledge Now";
            } else {
                title.innerText = "Undo Acknowledgement";
                message.innerHTML = `Are you sure you want to undo the acknowledgement for <strong>${studentName}</strong>?`;
                icon.className = "bi bi-arrow-counterclockwise text-secondary display-1";
                btn.className = "btn btn-secondary rounded-pill px-5 py-2 fw-bold";
                btn.innerText = "Undo Action";
            }

            confirmModal.show();
        }

        document.getElementById('registrarSearch').addEventListener('input', function() {
            const searchText = this.value.toLowerCase().trim();
            const sections = document.querySelectorAll('.college-section');
            
            sections.forEach(section => {
                const rows = section.querySelectorAll('.applicant-row');
                let visibleRowsInSection = 0;
                
                rows.forEach(row => {
                    const name = row.querySelector('.applicant-name').innerText.toLowerCase();
                    const email = row.querySelector('.applicant-email').innerText.toLowerCase();
                    if (name.includes(searchText) || email.includes(searchText)) {
                        row.style.display = '';
                        visibleRowsInSection++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Hide the whole section if no rows match
                if (visibleRowsInSection === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = '';
                }
            });
        });
    </script>
</body>
</html>

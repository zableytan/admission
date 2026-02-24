<?php
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$app_id = $_GET['id'];

// Fetch application data
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$app_id]);
$app = $stmt->fetch();

if (!$app) {
    header("Location: admin_dashboard.php");
    exit;
}

// Helper for file status
function getFileLink($path, $label) {
    if (!$path) {
        return '<span class="text-muted small"><i>Not Uploaded</i></span>';
    }
    if (file_exists($path)) {
        return '<a href="' . htmlspecialchars($path) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-arrow-down me-1"></i> View ' . $label . '</a>';
    }
    return '<span class="text-danger small"><i>File missing on server (' . htmlspecialchars($path) . ')</i></span>';
}

$student_name = htmlspecialchars($app['given_name'] . ' ' . ($app['middle_name'] ? $app['middle_name'] . ' ' : '') . $app['family_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - <?= $student_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .detail-card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border: none; }
        .card-header { background: #fff; border-bottom: 1px solid #eee; font-weight: 700; color: #2c3e50; padding: 20px; border-radius: 15px 15px 0 0 !important; }
        .label { font-size: 0.75rem; color: #95a5a6; text-transform: uppercase; font-weight: 700; margin-bottom: 3px; }
        .value { font-weight: 600; color: #2d3436; margin-bottom: 15px; }
        .section-title { color: #dc3545; border-left: 4px solid #dc3545; padding-left: 10px; margin: 30px 0 20px 0; font-weight: 800; }
        .doc-item { background: #fcfcfc; border: 1px solid #f0f0f0; border-radius: 10px; padding: 15px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="admin_dashboard.php" class="btn btn-link text-decoration-none p-0 mb-2">← Back to Dashboard</a>
            <h2 class="fw-bold mb-0"><?= $student_name ?></h2>
            <p class="text-muted">Application ID: #<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?> | <?= $app['college'] ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-<?= $app['status'] == 'Accepted' ? 'success' : ($app['status'] == 'Declined' ? 'danger' : 'warning') ?> fs-6 px-3 py-2 rounded-pill">
                <?= strtoupper($app['status']) ?>
            </span>
        </div>
    </div>

    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8">
            <!-- Admission Record PDF (First) -->
            <?php if($app['record_pdf_path']): ?>
            <div class="detail-card card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-file-pdf me-2"></i>Generated Admission Record
                </div>
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">Official Admission Record PDF Summary</div>
                        <div class="small text-muted">Comprehensive summary of the entire application process.</div>
                    </div>
                    <a href="<?= htmlspecialchars($app['record_pdf_path']) ?>" target="_blank" class="btn btn-danger btn-lg shadow-sm">
                        <i class="bi bi-file-pdf me-1"></i> Download PDF Summary
                    </a>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <a href="generate_full_pdf.php?id=<?= $app['id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-printer me-1"></i> Generate Full PDF with Attachments (for Printing)
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Application Details -->
            <div class="detail-card card mb-4">
                <div class="card-header">Application Overview</div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="label">Full Name</div>
                            <div class="value"><?= $student_name ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">College Applied</div>
                            <div class="value text-primary"><?= $app['college'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Email Address</div>
                            <div class="value"><?= $app['email'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Mobile Number</div>
                            <div class="value"><?= $app['mobile_no'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Score (<?= $app['score_type'] ?>)</div>
                            <div class="value"><?= $app['score_value'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uploaded Credentials (Bottom) -->
            <div class="detail-card card">
                <div class="card-header">Uploaded Documents & Credentials</div>
                <div class="card-body p-4">
                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Transcript of Records (TOR)</div>
                            <div class="small text-muted">Primary academic requirement</div>
                        </div>
                        <?= getFileLink($app['tor_path'], 'TOR') ?>
                    </div>
                    
                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Birth Certificate (PSA)</div>
                            <div class="small text-muted">Identification & Citizenship proof</div>
                        </div>
                        <?= getFileLink($app['birth_cert_path'], 'Birth Cert') ?>
                    </div>

                    <?php if($app['college'] === 'Medicine'): ?>
                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">NMAT Result</div>
                            <div class="small text-muted">Percentile Rank proof</div>
                        </div>
                        <?= getFileLink($app['nmat_path'], 'NMAT') ?>
                    </div>
                    <?php endif; ?>

                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Diploma</div>
                            <div class="small text-muted">Proof of graduation</div>
                        </div>
                        <?= getFileLink($app['diploma_path'], 'Diploma') ?>
                    </div>

                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">GWA Certificate</div>
                            <div class="small text-muted">General Weighted Average</div>
                        </div>
                        <?= getFileLink($app['gwa_cert_path'], 'GWA') ?>
                    </div>

                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Entrance Exam Result</div>
                            <div class="small text-muted">DMSF Entrance Exam</div>
                        </div>
                        <?= getFileLink($app['entrance_exam_path'], 'Exam Result') ?>
                    </div>

                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Application Fee Receipt</div>
                            <div class="small text-muted">Proof of payment</div>
                        </div>
                        <?= getFileLink($app['receipt_path'], 'Receipt') ?>
                    </div>

                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Good Moral Character</div>
                            <div class="small text-muted">Certificate of Good Moral</div>
                        </div>
                        <?= getFileLink($app['good_moral_path'], 'Good Moral') ?>
                    </div>

                    <?php if($app['other_docs_paths']): ?>
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Other Documents</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $others = explode(',', $app['other_docs_paths']);
                            foreach($others as $idx => $path): 
                                if(file_exists($path)):
                            ?>
                                <a href="<?= htmlspecialchars($path) ?>" target="_blank" class="btn btn-sm btn-light border">
                                    <i class="bi bi-paperclip me-1"></i> Doc <?= $idx + 1 ?>
                                </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="detail-card card">
                <div class="card-header">Quick Stats</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="label">Submitted On</div>
                        <div class="fw-bold small"><?= date('F d, Y h:i A', strtotime($app['created_at'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="label">Last Updated</div>
                        <div class="fw-bold small"><?= date('F d, Y h:i A', strtotime($app['updated_at'])) ?></div>
                    </div>
                    <hr>
                    <div class="label mb-2">Decision Actions</div>
                    <?php if($app['status'] == 'Pending'): ?>
                        <div class="alert alert-info small py-2">Waiting for admin decision.</div>
                    <?php else: ?>
                        <div class="alert alert-<?= $app['status'] == 'Accepted' ? 'success' : 'danger' ?> small py-2">
                            This application has been marked as <strong><?= $app['status'] ?></strong>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

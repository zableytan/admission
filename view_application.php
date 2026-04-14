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
            <p class="text-muted">Application ID: #<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?> | 
                <span class="<?= $app['college'] === 'All Colleges' ? 'badge bg-primary rounded-pill px-2' : '' ?>">
                    <?= $app['college'] ?>
                </span>
            </p>
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
                <div class="card-header">1. Application Overview</div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="label">Full Name</div>
                            <div class="value"><?= $student_name ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">College Applied</div>
                            <div class="value text-primary font-bold"><?= $app['college'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Email Address</div>
                            <div class="value"><?= $app['email'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Mobile Number</div>
                            <div class="value"><?= $app['mobile_no'] ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="label">Score (<?= $app['score_type'] ?>)</div>
                            <div class="value badge bg-light text-dark fs-6"><?= $app['score_value'] ?></div>
                        </div>
                        <?php if(strpos($app['college'], 'Medicine') !== false): ?>
                        <div class="col-md-4">
                            <div class="label">NMAT Percentile</div>
                            <div class="value text-danger fs-5"><?= $app['score_value'] ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="label">NMAT Date</div>
                            <div class="value"><?= $app['nmat_date'] ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Detailed Personal Data -->
            <div class="detail-card card mb-4">
                <div class="card-header">2. Personal & Medical Data</div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="label">Age</div>
                            <div class="value"><?= $app['age'] ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="label">Sex</div>
                            <div class="value"><?= $app['sex'] ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="label">Civil Status</div>
                            <div class="value"><?= $app['civil_status'] ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="label">Religion</div>
                            <div class="value"><?= $app['religion'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Date of Birth</div>
                            <div class="value"><?= date('F d, Y', strtotime($app['date_of_birth'])) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Place of Birth</div>
                            <div class="value"><?= $app['place_of_birth'] ?></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="label">Medical History</div>
                            <div class="value border-start border-danger ps-3 py-1 bg-light rounded small"><?= nl2br(htmlspecialchars($app['medical_history'])) ?></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="label">Vaccination Status</div>
                            <div class="value small">
                                <strong><?= $app['vax_status'] ?></strong>
                                <?php if($app['vax_status'] == 'Yes'): ?>
                                    <ul class="mb-0 x-small text-muted mt-1">
                                        <li>1st Dose: <?= $app['vax_dose1'] ?></li>
                                        <li>2nd Dose: <?= $app['vax_dose2'] ?></li>
                                        <li>Booster: <?= $app['vax_booster'] ?></li>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="label">Chronic Medical Condition</div>
                            <div class="value small">
                                <strong><?= $app['chronic_condition_flag'] ? 'Yes' : 'No' ?></strong>
                                <?php if($app['chronic_condition_flag']): ?>
                                    <div class="x-small text-muted mt-1"><?= $app['chronic_condition_details'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="label">Counselling History</div>
                            <div class="value small"><?= $app['counselling_history'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Future Plans & Support -->
            <div class="detail-card card mb-4">
                <div class="card-header">3. Future Plans & Support</div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="label mb-2">Motivations / Influences</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $motivations = [];
                            if($app['motivation_parents']) $motivations[] = 'Parents';
                            if($app['motivation_siblings']) $motivations[] = 'Siblings';
                            if($app['motivation_relatives']) $motivations[] = 'Other Relatives';
                            if($app['motivation_friends']) $motivations[] = 'Friends';
                            if($app['motivation_illness']) $motivations[] = 'Illness in family';
                            if($app['motivation_prestige']) $motivations[] = 'Prestige/Status';
                            if($app['motivation_health_awareness']) $motivations[] = 'Health Awareness';
                            if($app['motivation_community_needs']) $motivations[] = 'Community Needs';
                            
                            foreach($motivations as $m): ?>
                                <span class="badge bg-outline-primary border border-primary text-primary px-2 py-1"><?= $m ?></span>
                            <?php endforeach; ?>
                            <?php if($app['motivation_others']): ?>
                                <span class="badge bg-light text-dark border px-2 py-1">Others: <?= $app['motivation_others'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="label">Financial Support</div>
                            <div class="value small">
                                <?php if($app['support_scholarship_flag']): ?>
                                    <div class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Scholarship: <?= $app['support_scholarship_name'] ?></div>
                                    <div class="text-muted small ps-4">Status: <?= $app['support_status'] ?></div>
                                <?php endif; ?>
                                <?php if($app['support_parents']) echo '<div>Parents/Family</div>'; ?>
                                <?php if($app['support_veteran_benefit']) echo '<div>Phil Veteran Benefit</div>'; ?>
                                <?php if($app['support_others']) echo '<div>' . $app['support_others'] . '</div>'; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Stay in Davao</div>
                            <div class="value"><?= $app['staying_place'] == 'Others' ? $app['staying_place_others'] : $app['staying_place'] ?></div>
                        </div>
                    </div>

                    <div>
                        <div class="label">Source of Information about DMSF</div>
                        <div class="small text-muted">
                            <?php 
                            $sources = [];
                            if($app['info_parents']) $sources[] = 'Parents';
                            if($app['info_family_friends']) $sources[] = 'Family Friends';
                            if($app['info_student_friends']) $sources[] = 'DMSF Students';
                            if($app['info_siblings']) $sources[] = 'Brother/Sister';
                            if($app['info_teachers']) $sources[] = 'College Teachers';
                            if($app['info_newspaper']) $sources[] = 'Newspaper';
                            if($app['info_convocation']) $sources[] = 'Convocation';
                            if($app['info_internet']) $sources[] = 'Internet';
                            if($app['info_own_effort']) $sources[] = 'Own Effort';
                            
                            echo implode(', ', $sources);
                            if($app['info_others']) echo ', Others: ' . $app['info_others'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campus Engagement & Learning Profile -->
            <div class="detail-card card mb-4">
                <div class="card-header">4. Campus Engagement & Learning Profile</div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="label">Learning Style Preference</div>
                            <div class="value small"><?= $app['learning_style'] ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="label">Extracurricular Involvement</div>
                            <div class="value small"><?= $app['extracurricular_involvement'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Student Stress Profile</div>
                            <div class="value small">
                                <div>Level: <strong><?= $app['stress_level'] ?> / 5</strong></div>
                                <div class="x-small text-muted mt-1">Source: <?= $app['stress_source'] ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Coping Style</div>
                            <div class="value small"><?= $app['coping_style'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tertiary Background (Medicine Only) -->
            <?php if(isset($app['tertiary_name']) && $app['tertiary_name']): ?>
            <div class="detail-card card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <span>5. Tertiary Academic Background</span>
                    <?php if(isset($app['self_rating'])): ?>
                        <span class="badge bg-info text-white">Self-Rating: <?= $app['self_rating'] ?>/5</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="label">School & Degree</div>
                            <div class="value"><?= $app['tertiary_name'] ?> (<?= $app['tertiary_degree'] ?>)</div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Region & Address</div>
                            <div class="value text-muted small"><?= $app['tertiary_region'] ?>, <?= $app['tertiary_address'] ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="label">School Type</div>
                            <div class="value"><?= $app['tertiary_school_type'] ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="label">Course Type</div>
                            <div class="value"><?= $app['tertiary_course_type'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">General Weighted Average (GWA)</div>
                            <div class="value fw-bold text-success"><?= $app['tertiary_gwa'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="label">Academic Honors</div>
                            <div class="value"><?= $app['tertiary_honors'] ?: 'None' ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                            <div class="fw-bold text-dark">Form 137</div>
                            <div class="small text-muted">Secondary academic record</div>
                        </div>
                        <?= getFileLink($app['form137_path'], 'Form 137') ?>
                    </div>
                    
                    <div class="doc-item">
                        <div>
                            <div class="fw-bold text-dark">Birth Certificate (PSA)</div>
                            <div class="small text-muted">Identification & Citizenship proof</div>
                        </div>
                        <?= getFileLink($app['birth_cert_path'], 'Birth Cert') ?>
                    </div>

                    <?php if(strpos($app['college'], 'Medicine') !== false): ?>
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

<?php
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Application ID.");
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    die("Application not found.");
}

// College Access Check
if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    if ($app['college'] !== $_SESSION['admin_college']) {
        die("Unauthorized access: You do not have permission to view this department's applications.");
    }
}

// Helper function for boolean display
function showBool($val) {
    return $val ? 'YES' : 'NO';
}

// Helper for comma-separated lists
function listItems($app, $prefix, $fields) {
    $items = [];
    foreach ($fields as $field => $label) {
        if (!empty($app[$prefix . $field])) {
            $items[] = $label;
        }
    }
    return !empty($items) ? implode(', ', $items) : 'None';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Application Record - <?= htmlspecialchars($app['given_name'] . ' ' . $app['family_name']) ?></title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #333; }
        .document-page { background: white; max-width: 1000px; margin: 40px auto; padding: 50px; box-shadow: 0 0 30px rgba(0,0,0,0.1); border-top: 12px solid #1a237e; border-radius: 8px; }
        .doc-header { border-bottom: 2px solid #f0f0f0; padding-bottom: 25px; margin-bottom: 35px; display: flex; align-items: center; }
        .doc-header img { height: 90px; margin-right: 25px; }
        .doc-title h2 { margin: 0; color: #1a237e; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-header { background: #1a237e; padding: 8px 15px; font-weight: 700; color: white; margin: 30px 0 15px 0; text-transform: uppercase; font-size: 0.85rem; border-radius: 4px; display: flex; justify-content: space-between; }
        .info-row { display: flex; border-bottom: 1px solid #f8f9fa; padding: 10px 0; }
        .info-label { width: 40%; font-weight: 600; color: #666; font-size: 0.8rem; text-transform: uppercase; }
        .info-value { width: 60%; font-weight: 500; color: #111; font-size: 0.9rem; }
        .status-badge { font-size: 0.75rem; padding: 6px 16px; border-radius: 50px; font-weight: 800; letter-spacing: 1px; }
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 0 40px; }
        .full-width { grid-column: span 2; }
        .doc-item { display: flex; align-items: center; padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border: 1px solid #eee; transition: all 0.2s; text-decoration: none; color: inherit; }
        .doc-item:hover { background: #eef2ff; border-color: #1a237e; transform: translateY(-2px); }
        .doc-icon { font-size: 1.5rem; margin-right: 15px; color: #1a237e; }
        .doc-info { flex-grow: 1; }
        .doc-name { font-weight: 600; font-size: 0.85rem; display: block; }
        .doc-meta { font-size: 0.7rem; color: #666; }
        .doc-card-wrapper { display: flex; flex-direction: column; }
        .img-preview-box { transition: all 0.3s; border: 1px solid #ddd; }
        .img-preview-box:hover { border-color: #1a237e; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .img-preview-box img { max-width: 100%; height: auto; object-fit: contain; }
        @media print {
            body { background: white; }
            .document-page { margin: 0; box-shadow: none; padding: 20px; max-width: 100%; border-radius: 0; }
            .no-print { display: none; }
            .section-header { background: #eee !important; color: #000 !important; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

<div class="container no-print mt-4 text-center">
    <a href="admin_dashboard.php?college=<?= urlencode($app['college']) ?>" class="btn btn-dark rounded-pill px-4 me-2">
        <i class="bi bi-arrow-left me-2"></i> Dashboard
    </a>
    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-printer me-2"></i> Print Full Record
    </button>
</div>

<div class="document-page">
    <div class="doc-header">
        <img src="DMSF_Logo.png" alt="DMSF Logo">
        <div class="doc-title">
            <h2>Official Admission Record</h2>
            <p class="text-muted mb-0">Davao Medical School Foundation, Inc. | Registrar's Office</p>
        </div>
        <div class="text-end ms-auto">
            <div class="small text-muted text-uppercase fw-bold">App ID</div>
            <div class="h4 fw-bold text-primary mb-1">#<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></div>
            <span class="status-badge bg-<?= $app['status'] == 'Pending' ? 'warning' : ($app['status'] == 'Accepted' ? 'success' : 'danger') ?> text-white">
                <?= strtoupper($app['status']) ?>
            </span>
        </div>
    </div>

    <!-- 1. BASIC IDENTIFICATION -->
    <div class="section-header"><span>Application Overview</span></div>
    <div class="grid-container">
        <div class="info-row"><div class="info-label">Full Name</div><div class="info-value"><?= htmlspecialchars($app['given_name'] . ' ' . $app['middle_name'] . ' ' . $app['family_name']) ?></div></div>
        <div class="info-row"><div class="info-label">College Applied</div><div class="info-value"><?= htmlspecialchars($app['college']) ?></div></div>
        <div class="info-row"><div class="info-label">Email Address</div><div class="info-value"><?= htmlspecialchars($app['email']) ?></div></div>
        <div class="info-row"><div class="info-label">Mobile Number</div><div class="info-value"><?= htmlspecialchars($app['mobile_no']) ?></div></div>
        <div class="info-row"><div class="info-label">Score Type/Value</div><div class="info-value"><?= $app['score_type'] ?>: <?= $app['score_value'] ?></div></div>
        <div class="info-row"><div class="info-label">Submitted On</div><div class="info-value"><?= date('F d, Y h:i A', strtotime($app['created_at'])) ?></div></div>
        <div class="info-row full-width"><div class="info-label">Mailing Address</div><div class="info-value"><?= htmlspecialchars($app['mailing_address']) ?></div></div>
        <div class="info-row full-width"><div class="info-label">Home Address</div><div class="info-value"><?= htmlspecialchars($app['home_address']) ?></div></div>
    </div>

    <!-- 2. PERSONAL DATA -->
    <div class="section-header"><span>Personal & Physical Data</span></div>
    <div class="grid-container">
        <div class="info-row"><div class="info-label">Date of Birth</div><div class="info-value"><?= htmlspecialchars($app['date_of_birth']) ?></div></div>
        <div class="info-row"><div class="info-label">Place of Birth</div><div class="info-value"><?= htmlspecialchars($app['place_of_birth']) ?></div></div>
        <div class="info-row"><div class="info-label">Age / Sex</div><div class="info-value"><?= $app['age'] ?> / <?= $app['sex'] ?></div></div>
        <div class="info-row"><div class="info-label">Civil Status</div><div class="info-value"><?= $app['civil_status'] ?></div></div>
        <div class="info-row"><div class="info-label">Religion</div><div class="info-value"><?= htmlspecialchars($app['religion']) ?></div></div>
        <div class="info-row"><div class="info-label">Citizenship</div><div class="info-value"><?= htmlspecialchars($app['citizenship']) ?></div></div>
        <div class="info-row"><div class="info-label">Height</div><div class="info-value"><?= $app['height_ft'] ?>'<?= $app['height_in'] ?>"</div></div>
        <div class="info-row"><div class="info-label">Weight (Now)</div><div class="info-value"><?= $app['weight_kilos_now'] ?> kg</div></div>
        <div class="info-row full-width"><div class="info-label">Medical History</div><div class="info-value"><?= nl2br(htmlspecialchars($app['medical_history'] ?: 'None declared')) ?></div></div>
        <div class="info-row"><div class="info-label">Disability?</div><div class="info-value"><?= showBool($app['physical_disability_flag']) ?> (<?= htmlspecialchars($app['physical_disability_details'] ?: 'N/A') ?>)</div></div>
        <div class="info-row"><div class="info-label">Criminal Record?</div><div class="info-value"><?= showBool($app['convicted_flag']) ?> (<?= htmlspecialchars($app['convicted_explanation'] ?: 'N/A') ?>)</div></div>
    </div>

    <!-- 3. FAMILY BACKGROUND -->
    <div class="section-header"><span>Family & Financial Background</span></div>
    <div class="grid-container">
        <div class="info-row"><div class="info-label">Father's Name</div><div class="info-value"><?= htmlspecialchars($app['father_name']) ?></div></div>
        <div class="info-row"><div class="info-label">Father's Occupation</div><div class="info-value"><?= htmlspecialchars($app['father_occupation']) ?></div></div>
        <div class="info-row"><div class="info-label">Mother's Name</div><div class="info-value"><?= htmlspecialchars($app['mother_name']) ?></div></div>
        <div class="info-row"><div class="info-label">Mother's Occupation</div><div class="info-value"><?= htmlspecialchars($app['mother_occupation']) ?></div></div>
        <div class="info-row"><div class="info-label">Family Income</div><div class="info-value">PHP <?= number_format($app['total_family_income'], 2) ?></div></div>
        <div class="info-row"><div class="info-label">Income Sources</div><div class="info-value">
            <?= listItems($app, 'income_', ['salaries'=>'Salaries', 'farm'=>'Farm', 'commissions'=>'Commissions', 'rentals'=>'Rentals', 'pension'=>'Pension', 'business'=>'Business']) ?>
        </div></div>
        <div class="info-row"><div class="info-label">DMSF Alumni Parent?</div><div class="info-value"><?= showBool($app['parent_dmsf_grad_flag']) ?> (<?= htmlspecialchars($app['parent_dmsf_course_year'] ?: 'N/A') ?>)</div></div>
        <div class="info-row"><div class="info-label">DMSF Faculty Parent?</div><div class="info-value"><?= showBool($app['parent_dmsf_teaching_flag']) ?> (<?= $app['parent_dmsf_teaching_years'] ?: 0 ?> years)</div></div>
        <div class="info-row"><div class="info-label">Siblings</div><div class="info-value"><?= $app['num_brothers'] ?> Brothers / <?= $app['num_sisters'] ?> Sisters</div></div>
        <div class="info-row"><div class="info-label">Siblings in DMSF?</div><div class="info-value"><?= showBool($app['sibling_dmsf_flag']) ?> (<?= htmlspecialchars($app['sibling_dmsf_details'] ?: 'N/A') ?>)</div></div>
    </div>

    <!-- 4. EDUCATIONAL BACKGROUND -->
    <div class="section-header"><span>Educational Background</span></div>
    <div class="grid-container">
        <div class="info-row full-width"><div class="info-label">Primary School</div><div class="info-value"><?= htmlspecialchars($app['primary_school']) ?> (<?= htmlspecialchars($app['primary_dates']) ?>)</div></div>
        <div class="info-row full-width"><div class="info-label">Secondary School</div><div class="info-value"><?= htmlspecialchars($app['secondary_school']) ?> (<?= htmlspecialchars($app['secondary_dates']) ?>)</div></div>
        <div class="info-row"><div class="info-label">High School Honors</div><div class="info-value"><?= showBool($app['hs_honors_flag']) ?> (<?= htmlspecialchars($app['hs_honor_type'] ?: 'N/A') ?>)</div></div>
        <div class="info-row full-width"><div class="info-label">College Degree</div><div class="info-value"><?= htmlspecialchars($app['degree_obtained']) ?> from <?= htmlspecialchars($app['college_name_address']) ?> (Grad: <?= $app['date_of_graduation'] ?>)</div></div>
        <div class="info-row"><div class="info-label">College Honors</div><div class="info-value"><?= showBool($app['college_honors_flag']) ?> (<?= htmlspecialchars($app['college_honors_list'] ?: 'N/A') ?>)</div></div>
        <div class="info-row"><div class="info-label">Board Exam</div><div class="info-value"><?= htmlspecialchars($app['board_profession'] ?: 'None') ?> (Rating: <?= $app['board_rating'] ?>%)</div></div>
    </div>

    <!-- 5. INTENT & ACTIVITIES -->
    <div class="section-header"><span>Intent & Organizational Interests</span></div>
    <div class="grid-container">
        <div class="info-row full-width"><div class="info-label">Post-Grad Activity</div><div class="info-value"><?= htmlspecialchars($app['post_grad_activity']) ?> <?= $app['employee_work'] ? "at " . htmlspecialchars($app['employee_work']) : "" ?></div></div>
        <div class="info-row full-width"><div class="info-label">Interests/Skills</div><div class="info-value">
            <?= listItems($app, 'interest_', ['school_orgs'=>'School Orgs', 'religious'=>'Religious', 'sociocivic'=>'Socio-Civic', 'sports'=>'Sports', 'music_vocal'=>'Music/Vocal', 'dance'=>'Dance', 'creative_writing'=>'Creative Writing']) ?>
        </div></div>
        <div class="info-row"><div class="info-label">First Time Applicant?</div><div class="info-value"><?= showBool($app['first_time_md_flag']) ?></div></div>
        <div class="info-row"><div class="info-label">Staying Place</div><div class="info-value"><?= htmlspecialchars($app['staying_place']) ?></div></div>
        <div class="info-row full-width"><div class="info-label">Motivations</div><div class="info-value">
            <?= listItems($app, 'motivation_', ['parents'=>'Parents', 'siblings'=>'Siblings', 'relatives'=>'Relatives', 'friends'=>'Friends', 'illness'=>'Personal Illness', 'prestige'=>'Prestige', 'health_awareness'=>'Health Awareness', 'community_needs'=>'Community Needs']) ?>
        </div></div>
        <div class="info-row full-width"><div class="info-label">Info Source</div><div class="info-value">
            <?= listItems($app, 'info_', ['parents'=>'Parents', 'family_friends'=>'Family Friends', 'student_friends'=>'Student Friends', 'siblings'=>'Siblings', 'teachers'=>'Teachers', 'newspaper'=>'Newspaper', 'internet'=>'Internet']) ?>
        </div></div>
    </div>

    <!-- 6. UPLOADED DOCUMENTS -->
    <div class="section-header no-print"><span>Uploaded Documents & Credentials</span></div>
    <div class="grid-container no-print">
        <?php
        $docs = [
            'record_pdf_path' => ['label' => 'Admission Record (Generated Summary)', 'icon' => 'bi-file-pdf-fill', 'color' => '#dc3545'],
            'tor_path' => ['label' => 'Transcript of Records (TOR)', 'icon' => 'bi-file-earmark-text'],
            'birth_cert_path' => ['label' => 'Birth Certificate (PSA)', 'icon' => 'bi-person-badge'],
            'nmat_path' => ['label' => 'NMAT Result', 'icon' => 'bi-journal-check'],
            'diploma_path' => ['label' => 'College Diploma', 'icon' => 'bi-award'],
            'gwa_cert_path' => ['label' => 'GWA Certification', 'icon' => 'bi-calculator'],
            'entrance_exam_path' => ['label' => 'Entrance Exam Result', 'icon' => 'bi-pencil-square'],
            'receipt_path' => ['label' => 'Payment Receipt', 'icon' => 'bi-receipt'],
            'good_moral_path' => ['label' => 'Good Moral Certificate', 'icon' => 'bi-shield-check'],
            'signed_document_path' => ['label' => 'Signed Admission Form', 'icon' => 'bi-file-earmark-check-fill', 'color' => '#198754']
        ];

        $has_docs = false;
        foreach ($docs as $path_key => $info):
            if (!empty($app[$path_key])):
                $has_docs = true;
                $file_path = $app[$path_key];
                $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                $icon_color = isset($info['color']) ? $info['color'] : '#1a237e';
                $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        ?>
            <div class="doc-card-wrapper">
                <a href="<?= htmlspecialchars($file_path) ?>" target="_blank" class="doc-item mb-2">
                    <i class="bi <?= $info['icon'] ?> doc-icon" style="color: <?= $icon_color ?>;"></i>
                    <div class="doc-info">
                        <span class="doc-name"><?= $info['label'] ?></span>
                        <span class="doc-meta"><?= strtoupper($file_ext) ?> File | Click to view full</span>
                    </div>
                    <i class="bi bi-box-arrow-up-right text-muted small"></i>
                </a>
                <?php if ($is_image): ?>
                    <div class="img-preview-box text-center p-2 bg-light rounded border mb-3">
                        <img src="<?= htmlspecialchars($file_path) ?>" class="img-fluid rounded" style="max-height: 150px; cursor: pointer;" onclick="window.open('<?= htmlspecialchars($file_path) ?>', '_blank')">
                    </div>
                <?php endif; ?>
            </div>
        <?php 
            endif;
        endforeach;

        // Handle "Other" documents if they exist
        if (!empty($app['other_docs_paths'])):
            $other_docs = explode(',', $app['other_docs_paths']);
            foreach ($other_docs as $idx => $path):
                $has_docs = true;
                $file_ext = strtoupper(pathinfo($path, PATHINFO_EXTENSION));
        ?>
            <a href="<?= htmlspecialchars(trim($path)) ?>" target="_blank" class="doc-item">
                <i class="bi bi-file-earmark-plus doc-icon"></i>
                <div class="doc-info">
                    <span class="doc-name">Additional Document #<?= $idx + 1 ?></span>
                    <span class="doc-meta"><?= $file_ext ?> File | Click to view</span>
                </div>
                <i class="bi bi-box-arrow-up-right text-muted small"></i>
            </a>
        <?php 
            endforeach;
        endif;

        if (!$has_docs): ?>
            <div class="full-width py-4 text-center text-muted">
                <i class="bi bi-folder-x display-6 d-block mb-2"></i>
                No documents have been uploaded for this application yet.
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER SIGNATURES -->
    <div class="mt-5 pt-5 text-center" style="border-top: 1px dashed #eee;">
        <div class="row">
            <div class="col-4">
                <div style="border-bottom: 1px solid #333; width: 180px; margin: 0 auto 10px auto;"></div>
                <div class="small text-muted text-uppercase">Applicant's Signature</div>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #333; width: 180px; margin: 0 auto 10px auto;"></div>
                <div class="small text-muted text-uppercase">Admissions Officer</div>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #333; width: 180px; margin: 0 auto 10px auto;"></div>
                <div class="small text-muted text-uppercase">Date Processed</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
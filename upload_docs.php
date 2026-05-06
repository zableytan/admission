<?php
// upload_docs.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

require 'vendor/autoload.php';
require 'db.php';

session_start();

// Helper functions for PDF generation
function getBoolText($val)
{
    return $val ? 'YES' : 'NO';
}
function getListText($app, $prefix, $fields)
{
    $items = [];
    foreach ($fields as $field => $label) {
        if (!empty($app[$prefix . $field])) {
            $items[] = $label;
        }
    }
    return !empty($items) ? implode(', ', $items) : 'None';
}

$message = '';

// 1. GET LOGIC: Validate Application ID
if (!isset($_GET['app_id']) || !is_numeric($_GET['app_id'])) {
    header("Location: apply.php");
    exit;
}

$app_id = $_GET['app_id'];

// Fetch application basics
$stmt = $pdo->prepare("SELECT family_name, given_name, college FROM applications WHERE id = ?");
$stmt->execute([$app_id]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: apply.php");
    exit;
}

$student_name = htmlspecialchars($application['given_name'] . ' ' . $application['family_name']);
$college_applied = trim($application['college'] ?? '');
// Check for legacy (IMD) or new (Foreign) designation
$is_imd = (strpos($college_applied, '(IMD)') !== false) || (strpos($college_applied, '(Foreign)') !== false);
$is_dentistry = (strpos($college_applied, 'Dentistry') !== false);
$is_nursing = (strpos($college_applied, 'Nursing') !== false);

// 2. POST LOGIC: Process file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = 'uploads/';
    $photo_path = null;
    $tor_path = null;
    $birth_cert_path = null;
    $nmat_path = null;
    $good_moral_path = null;
    $other_paths = [];

    // Helper function for file uploads
    function handleUpload($file_key, $upload_dir, $app_id)
    {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
            $filename = $file_key . "_" . $app_id . "_" . time() . "." . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target)) {
                return $target;
            }
        }
        return null;
    }

    $photo_path = handleUpload('photo_file', $upload_dir, $app_id);
    $tor_path = handleUpload('tor_file', $upload_dir, $app_id);
    $form137_path = handleUpload('form137_file', $upload_dir, $app_id);
    $birth_cert_path = handleUpload('birth_cert_file', $upload_dir, $app_id);
    $nmat_path = handleUpload('nmat_file', $upload_dir, $app_id);
    $diploma_path = handleUpload('diploma_file', $upload_dir, $app_id); // Optional
    $gwa_path = handleUpload('gwa_file', $upload_dir, $app_id);
    $good_moral_path = handleUpload('good_moral_file', $upload_dir, $app_id);
    $passport_path = $is_imd ? handleUpload('passport_file', $upload_dir, $app_id) : null;

    // "To be followed" flags for optional documents
    $tbf_tor       = isset($_POST['tbf_tor'])       ? 1 : 0;
    $tbf_form137   = isset($_POST['tbf_form137'])   ? 1 : 0;
    $tbf_diploma   = isset($_POST['tbf_diploma'])   ? 1 : 0;
    $tbf_gwa       = isset($_POST['tbf_gwa'])       ? 1 : 0;
    $tbf_good_moral = isset($_POST['tbf_good_moral']) ? 1 : 0;

    // Handle multiple "other" docs
    if (isset($_FILES['other_docs'])) {
        foreach ($_FILES['other_docs']['name'] as $key => $name) {
            if ($_FILES['other_docs']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $filename = "other_" . $app_id . "_" . $key . "_" . time() . "." . $ext;
                $target = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['other_docs']['tmp_name'][$key], $target)) {
                    $other_paths[] = $target;
                }
            }
        }
    }

    $other_docs_paths = !empty($other_paths) ? implode(',', $other_paths) : null;

    // Update the database with file paths and TBF flags
    try {
        $sql = "UPDATE applications SET
            photo_path=?, tor_path=?, tbf_tor=?, form137_path=?, tbf_form137=?, birth_cert_path=?, nmat_path=?,
            diploma_path=?, tbf_diploma=?, gwa_cert_path=?, tbf_gwa=?, entrance_exam_path=?, receipt_path=?,
            good_moral_path=?, tbf_good_moral=?, passport_path=?, other_docs_paths=?, is_submitted=1
            WHERE id = ?";
        
        $execute_params = [
            $photo_path, $tor_path, $tbf_tor, $form137_path, $tbf_form137, $birth_cert_path, $nmat_path,
            $diploma_path, $tbf_diploma, $gwa_path, $tbf_gwa, null, null,
            $good_moral_path, $tbf_good_moral, $passport_path, $other_docs_paths, $app_id
        ];

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($execute_params);
        } catch (PDOException $e) {
            // Self-healing: if column tbf_gwa is missing, add it
            if (strpos($e->getMessage(), 'Unknown column \'tbf_gwa\'') !== false) {
                $pdo->exec("ALTER TABLE applications ADD COLUMN tbf_gwa TINYINT(1) NOT NULL DEFAULT 0 AFTER gwa_cert_path");
                // Retry
                $stmt = $pdo->prepare($sql);
                $stmt->execute($execute_params);
            } else {
                throw $e;
            }
        }
    } catch (PDOException $e) {
        error_log("Database Error in Step 5 initial update: " . $e->getMessage());
    }

    // 3. EMAIL NOTIFICATION TO ADMINS
    $college = $application['college'];
    if ($college === 'All Colleges') {
        // Notify ALL admins for a universal application
        $admin_stmt = $pdo->query("SELECT email FROM admins");
    } else {
        $admin_stmt = $pdo->prepare("SELECT email FROM admins WHERE college = ? OR is_super_admin = 1");
        $admin_stmt->execute([$college]);
    }
    $admin_emails = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch COMPLETE application data for the report
    $full_stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $full_stmt->execute([$app_id]);
    $app_data = $full_stmt->fetch(PDO::FETCH_ASSOC);

    // --- 1. PREPARE LOGO FOR PDF ---
    $logo_path = 'DMSF_Logo.png';
    $logo_data = '';
    if (file_exists($logo_path)) {
        $type = pathinfo($logo_path, PATHINFO_EXTENSION);
        $data = file_get_contents($logo_path);
        $logo_data = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    // --- 2. PREPARE APPLICANT PHOTO FOR PDF ---
    $photo_data = '';
    if (!empty($app_data['photo_path'])) {
        $real_photo_path = realpath(__DIR__ . DIRECTORY_SEPARATOR . $app_data['photo_path']);
        if ($real_photo_path && file_exists($real_photo_path)) {
            $type = pathinfo($real_photo_path, PATHINFO_EXTENSION);
            $img_blob = file_get_contents($real_photo_path);
            $photo_data = 'data:image/' . $type . ';base64,' . base64_encode($img_blob);
        }
    }

    // --- 2. GENERATE PDF RECORD ---
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');
    $dompdf = new Dompdf($options);

    // Build HTML for PDF (Exact Match to User Image)
    $html = "
    <html>
    <head>
        <style>
            @page { margin: 20px; }
            body { margin: 0; padding: 0; background-color: #fff; font-family: 'Helvetica', sans-serif; color: #333; }
            .document-page { background: white; width: 100%; padding: 10px; }
            
            /* Header Styling */
            .header-table { width: 100%; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; }
            .logo-cell { width: 70px; vertical-align: middle; }
            .title-cell { vertical-align: middle; padding-left: 10px; }
            .title-cell h1 { margin: 0; color: #1a237e; font-size: 20px; text-transform: uppercase; font-weight: 900; letter-spacing: -0.5px; }
            .subtitle { margin: 0; padding-top: 1px; color: #7f8c8d; font-size: 8px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.3px; }
            .id-cell { text-align: right; vertical-align: middle; padding-right: 15px; }
            .id-cell .label { font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; }
            .id-cell .value { font-size: 20px; font-weight: bold; color: #3498db; margin: 0; line-height: 1; }
            .status-badge { background: #ffc107; color: white; padding: 3px 15px; border-radius: 50px; font-size: 10px; font-weight: bold; display: inline-block; }
            
            /* Photo Styling */
            .photo-cell { width: 100px; padding: 0; vertical-align: top; text-align: right; }
            .applicant-photo-box {
                width: 100px;
                height: 100px;
                border: 1px solid #ddd;
                background: #f9f9f9;
                text-align: center;
                overflow: hidden;
                display: block;
            }
            .applicant-photo-box img {
                width: 100px;
                height: 100px;
                object-fit: cover;
            }
            .no-photo-text {
                font-size: 8px;
                color: #999;
                padding-top: 40px;
                display: block;
            }

            /* Section Styling */
            .section-header { background: #1a237e; padding: 7px 15px; font-weight: bold; color: white; margin: 20px 0 5px 0; text-transform: uppercase; font-size: 11px; border-radius: 3px; }
            
            /* Grid Table Styling */
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .info-table td { border-bottom: 1px solid #f2f2f2; padding: 8px 5px; vertical-align: top; }
            .row-label { width: 18%; font-size: 8.5px; color: #666; text-transform: uppercase; font-weight: 500; }
            .row-value { width: 32%; font-size: 10.5px; font-weight: 700; color: #111; }
            .full-width-label { width: 18%; font-size: 8.5px; color: #666; text-transform: uppercase; font-weight: 500; }
            .full-width-value { width: 82%; font-size: 10.5px; font-weight: 700; color: #111; }

            /* Footer Styling */
            .footer { margin-top: 50px; border-top: 1px dashed #ddd; padding-top: 30px; }
            .sig-table { width: 100%; }
            .sig-cell { width: 33.33%; text-align: center; }
            .sig-line { border-bottom: 1px solid #333; width: 160px; margin: 0 auto 5px auto; }
            .sig-label { font-size: 8.5px; color: #666; text-transform: uppercase; }
        </style>
    </head>
    <body>
        <div class='document-page'>
            <table class='header-table'>
                <tr>
                    <td class='logo-cell'><img src='{$logo_data}' style='height: 65px;'></td>
                    <td class='title-cell'>
                        <h1>OFFICIAL ADMISSION RECORD</h1>
                        <div class='subtitle'>Davao Medical School Foundation, Inc. | Registrar's Office</div>
                    </td>
                    <td class='id-cell' style='width: 120px;'>
                        <div class='label'>APP ID</div>
                        <div class='value'>#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . "</div>
                        <div class='status-badge'>PENDING</div>
                    </td>
                    <td class='photo-cell'>
                        <div class='applicant-photo-box'>
                            " . ($photo_data ? "<img src='{$photo_data}'>" : "<span class='no-photo-text'>PASSPORT PHOTO</span>") . "
                        </div>
                    </td>
                </tr>
            </table>

            <!-- I. APPLICATION OVERVIEW -->
            <div class='section-header'>APPLICATION OVERVIEW</div>
            <table class='info-table'>
                <tr>
                    <td class='row-label'>Full Name</td><td class='row-value'>" . htmlspecialchars($app_data['given_name'] . ' ' . $app_data['middle_name'] . ' ' . $app_data['family_name']) . "</td>
                    <td class='row-label'>College Applied</td><td class='row-value'>" . $app_data['college'] . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Email Address</td><td class='row-value'>" . $app_data['email'] . "</td>
                    <td class='row-label'>Mobile Number</td><td class='row-value'>" . $app_data['mobile_no'] . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Score Type/Value</td><td class='row-value'>" . $app_data['score_type'] . ": " . $app_data['score_value'] . "</td>
                    <td class='row-label'>Social Media</td><td class='row-value'>" . htmlspecialchars($app_data['social_media'] ?: 'None') . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Submitted On</td><td class='row-value' colspan='3'>" . date('F d, Y h:i A') . "</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Mailing Address</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['mailing_address']) . "</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Home Address</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['home_address']) . "</td>
                </tr>
            </table>

            <!-- II. PERSONAL & PHYSICAL DATA -->
            <div class='section-header'>PERSONAL & PHYSICAL DATA</div>
            <table class='info-table'>
                <tr>
                    <td class='row-label'>Date of Birth</td><td class='row-value'>" . $app_data['date_of_birth'] . "</td>
                    <td class='row-label'>Place of Birth</td><td class='row-value'>" . htmlspecialchars($app_data['place_of_birth']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Age / Sex</td><td class='row-value'>" . $app_data['age'] . " / " . $app_data['sex'] . "</td>
                    <td class='row-label'>Civil Status</td><td class='row-value'>" . $app_data['civil_status'] . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Religion</td><td class='row-value'>" . htmlspecialchars($app_data['religion']) . "</td>
                    <td class='row-label'>Citizenship</td><td class='row-value'>" . htmlspecialchars($app_data['citizenship']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Height</td><td class='row-value'>" . $app_data['height_ft'] . "' " . $app_data['height_in'] . "\"</td>
                    <td class='row-label'>Weight (Now)</td><td class='row-value'>" . $app_data['weight_kilos_now'] . " kg</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Medical History</td><td class='full-width-value' colspan='3'>" . nl2br(htmlspecialchars(html_entity_decode($app_data['medical_history'] ?: 'None declared', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Disability?</td><td class='row-value' colspan='3'>" . getBoolText($app_data['physical_disability_flag']) . " (" . htmlspecialchars(html_entity_decode($app_data['physical_disability_details'] ?: 'N/A', ENT_QUOTES | ENT_HTML5, 'UTF-8')) . ")</td>
                </tr>
            </table>

            <!-- III. FAMILY & FINANCIAL BACKGROUND -->
            <div class='section-header'>FAMILY & FINANCIAL BACKGROUND</div>
            <table class='info-table'>
                <tr>
                    <td class='row-label'>Father's Name</td>
                    <td class='row-value'>" . ($app_data['father_deceased'] ? 'DECEASED' : htmlspecialchars($app_data['father_first_name'] . ' ' . $app_data['father_middle_name'] . ' ' . $app_data['father_last_name'])) . "</td>
                    <td class='row-label'>Father's Occupation</td>
                    <td class='row-value'>" . ($app_data['father_deceased'] ? 'N/A' : htmlspecialchars($app_data['father_occupation'])) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Mother's Name</td>
                    <td class='row-value'>" . ($app_data['mother_deceased'] ? 'DECEASED' : htmlspecialchars($app_data['mother_first_name'] . ' ' . $app_data['mother_middle_name'] . ' ' . $app_data['mother_last_name'])) . "</td>
                    <td class='row-label'>Mother's Occupation</td>
                    <td class='row-value'>" . ($app_data['mother_deceased'] ? 'N/A' : htmlspecialchars($app_data['mother_occupation'])) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Family Income (Gross)</td>
                    <td class='row-value'>" . htmlspecialchars($app_data['total_family_income']) . "</td>
                    <td class='row-label'>Parents' Marriage Status</td>
                    <td class='row-value'>" . htmlspecialchars($app_data['parents_marriage_status'] ?: 'N/A') . "</td>
                </tr>
                <tr>
                    <td class='row-label'>DMSF Alumni Parent?</td><td class='row-value'>" . getBoolText($app_data['parent_dmsf_grad_flag'] ?? 0) . " (" . htmlspecialchars($app_data['parent_dmsf_course_year'] ?? 'N/A') . ")</td>
                    <td class='row-label'>DMSF Affiliated Parent?</td><td class='row-value'>" . getBoolText($app_data['parent_dmsf_teaching_flag'] ?? 0) . " (" . ($app_data['parent_dmsf_teaching_years'] ?? 0) . " years)</td>
                </tr>
                <tr>
                    <td class='row-label'>Income Sources</td>
                    <td class='row-value' colspan='3'>" . getListText($app_data, 'income_', ['salaries' => 'Salaries', 'farm' => 'Farm', 'commissions' => 'Commissions', 'rentals' => 'Rentals', 'pension' => 'Pension', 'business' => 'Business']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Siblings</td><td class='row-value'>" . ($app_data['num_brothers'] ?? 0) . " Brothers / " . ($app_data['num_sisters'] ?? 0) . " Sisters</td>
                    <td class='row-label'>Siblings in DMSF?</td><td class='row-value'>" . getBoolText($app_data['sibling_dmsf_flag'] ?? 0) . " (" . htmlspecialchars($app_data['sibling_dmsf_details'] ?? 'N/A') . ")</td>
                </tr>
            </table>

            <!-- IV. EDUCATIONAL BACKGROUND -->
            <div class='section-header'>EDUCATIONAL BACKGROUND</div>
            <table class='info-table'>
                <tr>
                    <td class='full-width-label'>Primary School</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['primary_school'] ?? '') . " (" . htmlspecialchars($app_data['primary_dates'] ?? '') . ")</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Secondary School</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['secondary_school'] ?? '') . " (" . htmlspecialchars($app_data['secondary_dates'] ?? '') . ")</td>
                </tr>
                " . (!empty($app_data['tertiary_name']) ? "
                <tr>
                    <td class='full-width-label'>Tertiary Background</td><td class='full-width-value' colspan='3'>
                        <strong>" . htmlspecialchars($app_data['tertiary_name']) . "</strong> (" . htmlspecialchars($app_data['tertiary_region'] ?: 'N/A') . ")<br>
                        Address: " . htmlspecialchars($app_data['tertiary_address'] ?: 'N/A') . " | Type: " . $app_data['tertiary_school_type'] . "<br>
                        Course: " . htmlspecialchars($app_data['tertiary_degree'] ?: $app_data['tertiary_course_type']) . " | GWA: " . $app_data['tertiary_gwa'] . "<br>
                        Honors: " . htmlspecialchars(html_entity_decode($app_data['tertiary_honors'] ?: 'None', ENT_QUOTES | ENT_HTML5, 'UTF-8')) . " | <strong>Self-Rating: " . ($app_data['self_rating'] ?: 'N/A') . "/5</strong>
                    </td>
                </tr>
                " : "") . "
                <tr>
                    <td class='row-label'>High School Honors</td><td class='row-value' colspan='3'>" . getBoolText($app_data['hs_honors_flag'] ?? 0) . " (" . htmlspecialchars($app_data['hs_honor_type'] ?? 'N/A') . ")</td>
                </tr>
                <tr>
                    <td class='full-width-label'>College Degree</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['degree_obtained'] ?? '') . " from " . htmlspecialchars($app_data['college_name_address'] ?? '') . " (Grad: " . ($app_data['date_of_graduation'] ?? 'N/A') . ")</td>
                </tr>
                <tr>
                    <td class='row-label'>College Honors</td><td class='row-value'>" . getBoolText($app_data['college_honors_flag'] ?? 0) . " (" . htmlspecialchars(html_entity_decode($app_data['college_honors_list'] ?? 'N/A', ENT_QUOTES | ENT_HTML5, 'UTF-8')) . ")</td>
                    <td class='row-label'>Board Exam</td><td class='row-value'>" . htmlspecialchars($app_data['board_profession'] ?: 'None') . " (Rating: " . ($app_data['board_rating'] ?? 0) . "%)</td>
                </tr>
            </table>

            <!-- V. INTENT & ORGANIZATIONAL INTERESTS -->
            <div class='section-header'>INTENT & ORGANIZATIONAL INTERESTS</div>
            <table class='info-table'>
                <tr>
                    <td class='full-width-label'>Post-Grad Activity</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['post_grad_activity'] ?? 'None') . " " . ($app_data['employee_work'] ? "at " . htmlspecialchars($app_data['employee_work']) : "") . "</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Interests/Skills</td><td class='full-width-value' colspan='3'>" . getListText($app_data, 'interest_', ['school_orgs' => 'School Orgs', 'religious' => 'Religious', 'sociocivic' => 'Socio-Civic', 'sports' => 'Sports', 'music_vocal' => 'Music/Vocal', 'dance' => 'Dance', 'creative_writing' => 'Creative Writing']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>First Time Applicant?</td><td class='row-value'>" . getBoolText($app_data['first_time_md_flag'] ?? 1) . "</td>
                    <td class='row-label'>Staying Place</td><td class='row-value'>" . htmlspecialchars($app_data['staying_place'] ?? 'N/A') . "</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Motivations</td><td class='full-width-value' colspan='3'>" . getListText($app_data, 'motivation_', ['parents' => 'Parents', 'siblings' => 'Siblings', 'relatives' => 'Relatives', 'friends' => 'Friends', 'illness' => 'Personal Illness', 'prestige' => 'Prestige', 'health_awareness' => 'Health Awareness', 'community_needs' => 'Community Needs']) . "</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Info Source</td><td class='full-width-value' colspan='3'>" . getListText($app_data, 'info_', ['parents' => 'Parents', 'family_friends' => 'Family Friends', 'student_friends' => 'Student Friends', 'siblings' => 'Siblings', 'teachers' => 'Teachers', 'newspaper' => 'Newspaper', 'internet' => 'Internet']) . "</td>
                </tr>
                <tr>
                    <td class='full-width-label'>Personal Essay</td>
                    <td class='full-width-value' colspan='3'>" . nl2br(htmlspecialchars(html_entity_decode($app_data['application_essay'] ?: 'None', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) . "</td>
                </tr>
            </table>

            <!-- VI. ATTACHED DOCUMENTS CHECKLIST -->
            <div class='section-header'>ATTACHED DOCUMENTS CHECKLIST</div>
            <table class='info-table'>
                <tr>
                    <td class='row-label'>Transcript (TOR)</td><td class='row-value'>" . ($tor_path ? '✓ Provided' : ($tbf_tor ? '📋 To be followed' : '✗ Missing')) . "</td>
                    <td class='row-label'>Form 138 (Report Card)</td><td class='row-value'>" . ($form137_path ? '✓ Provided' : ($tbf_form137 ? '📋 To be followed' : '✗ Missing')) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Birth Cert (PSA)</td><td class='row-value'>" . ($birth_cert_path ? '✓ Provided' : '✗ Missing') . "</td>
                    " . (strpos($app_data['college'], 'Medicine') !== false && strpos($app_data['college'], 'Accelerated Pathway') === false ? "<td class='row-label'>NMAT Result</td><td class='row-value'>" . ($nmat_path ? '✓ Provided' : '✗ Missing') . "</td>" : "<td class='row-label'></td><td class='row-value'></td>") . "
                </tr>
                <tr>
                    <td class='row-label'>Diploma</td><td class='row-value'>" . ($diploma_path ? '✓ Provided' : ($tbf_diploma ? '📋 To be followed' : '✗ Missing')) . "</td>
                    <td class='row-label'>GWA Certificate</td><td class='row-value'>" . ($gwa_path ? '✓ Provided' : '✗ Missing') . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Good Moral</td><td class='row-value'>" . ($good_moral_path ? '✓ Provided' : ($tbf_good_moral ? '📋 To be followed' : '✗ Missing')) . "</td>
                    " . ($is_imd ? "<td class='row-label'>Passport Copy</td><td class='row-value'>" . ($passport_path ? '✓ Provided' : '✗ Missing') . "</td>" : "<td class='row-label'></td><td class='row-value'></td>") . "
                </tr>
            </table>

            <div class='footer'>
                <table class='sig-table'>
                    <tr>
                        <td class='sig-cell'><div class='sig-line'></div><div class='sig-label'>Applicant's Signature</div></td>
                        <td class='sig-cell'><div class='sig-line'></div><div class='sig-label'>Admissions Officer</div></td>
                        <td class='sig-cell'><div class='sig-line'></div><div class='sig-label'>Date Processed</div></td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
    </html>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf_output = $dompdf->output();

    // Save the generated PDF summary to disk
    $pdf_filename = "Admission_Record_" . $app_id . "_" . time() . ".pdf";
    $pdf_path = 'uploads/' . $pdf_filename;
    if (file_put_contents($pdf_path, $pdf_output) === false) {
        error_log("Failed to save PDF to $pdf_path. Check permissions for 'uploads/' directory.");
    }

    // Update the database with the PDF record path
    try {
        $stmt = $pdo->prepare("UPDATE applications SET record_pdf_path = ? WHERE id = ?");
        $stmt->execute([$pdf_path, $app_id]);
    } catch (PDOException $e) {
        error_log("Database Error updating record_pdf_path: " . $e->getMessage());
        // We continue even if this fails, as the email might still be sent
    }

    // --- 2. SEND EMAIL WITH PDF ATTACHMENT ---
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

        $hasRecipient = false;
        foreach ($admin_emails as $admin_email_list) {
            $emails = explode(',', $admin_email_list);
            foreach ($emails as $email) {
                $email = trim($email);
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($email);
                    $hasRecipient = true;
                }
            }
        }

        // If no admin emails found, add the sender as a fallback or a default admin email
        if (!$hasRecipient) {
            // Use the from_email as a fallback recipient if no admins are configured
            $mail->addAddress($smtp_config['from_email']);
        }

        $mail->addReplyTo($app_data['email'], $student_name);

        // --- ATTACHMENT STRATEGY: Links for large files ---
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);

        // Always attach the generated PDF Summary (usually small)
        $mail->addStringAttachment($pdf_output, "Admission_Record_#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . ".pdf");

        // Map for file links
        $file_links_html = "<ul>";
        $total_attachment_size = strlen($pdf_output);

        $file_map = [
            'Applicant Photo' => $app_data['photo_path'],
            'TOR' => $tor_path,
            'Form 138 (Report Card)' => $form137_path,
            'Birth Cert' => $birth_cert_path
        ];

        if (strpos($college, 'Medicine') !== false && strpos($college, 'Accelerated Pathway') === false) {
            $file_map['NMAT'] = $nmat_path;
        }

        $file_map += [
            'Diploma' => $diploma_path,
            'GWA Cert' => $gwa_path,
            'Entrance Exam' => $entrance_path,
            'Receipt' => $receipt_path,
            'Good Moral' => $good_moral_path
        ];

        foreach ($file_map as $label => $path) {
            if ($path) {
                $abs_path = __DIR__ . DIRECTORY_SEPARATOR . $path;
                if (file_exists($abs_path)) {
                    $fsize = filesize($abs_path);
                    $file_url = $base_url . '/' . $path;
                    $file_links_html .= "<li><a href='$file_url'>$label</a> (" . round($fsize / 1024 / 1024, 2) . " MB)</li>";

                    // Only attach if we are well under Gmail's 25MB limit (total)
                    if (($total_attachment_size + $fsize) < 20 * 1024 * 1024) {
                        $mail->addAttachment($abs_path, str_replace(' ', '_', $label) . "_" . basename($path));
                        $total_attachment_size += $fsize;
                    }
                }
            }
        }

        // Also handle any "other" documents
        if (!empty($other_paths)) {
            foreach ($other_paths as $idx => $path) {
                $abs_path = __DIR__ . DIRECTORY_SEPARATOR . $path;
                if (file_exists($abs_path)) {
                    $fsize = filesize($abs_path);
                    $file_url = $base_url . '/' . $path;
                    $label = "Other Doc " . ($idx + 1);
                    $file_links_html .= "<li><a href='$file_url'>$label</a> (" . round($fsize / 1024 / 1024, 2) . " MB)</li>";

                    if (($total_attachment_size + $fsize) < 20 * 1024 * 1024) {
                        $mail->addAttachment($abs_path, str_replace(' ', '_', $label) . "_" . basename($path));
                        $total_attachment_size += $fsize;
                    }
                }
            }
        }
        $file_links_html .= "</ul>";

        $mail->isHTML(true);
        $display_college = preg_replace('/\s*\(.*?\)/', '', $college);
        $mail->Subject = "Admission Submission: " . $student_name . " (" . $display_college . ")";

        $mail->Body = "<h3>New Admission Application Received</h3>
                           <p>A new application has been submitted by <strong>$student_name</strong> (#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . ") for the <strong>$display_college</strong>.</p>
                           <p><strong>Documents & Credentials:</strong></p>
                           $file_links_html
                           <p><em>Note: Large files are provided as links to avoid email size limits. Small files are attached directly.</em></p>
                           <p>The complete <strong>Official Admission Record PDF</strong> summary is also attached for your reference.</p>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }


    header("Location: application_complete.php?app_id=$app_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 5: Upload Documents | Admission</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #196199;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --accent-color: #f8f9fa;
            --border-radius: 12px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7fe;
            color: #2d3436;
            line-height: 1.6;
        }

        .form-card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header-custom {
            background: var(--primary-color);
            padding: 30px;
            color: white;
            border: none;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f2f6;
            display: flex;
            align-items: center;
        }

        .section-title::after {
            content: "";
            flex: 1;
            margin-left: 15px;
            height: 1px;
            background: #eee;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.95rem;
            color: #2d3436;
            margin-bottom: 8px;
        }

        .file-upload-wrapper {
            background: #fcfcfc;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
            margin-bottom: 20px;
        }

        .file-upload-wrapper:hover {
            border-color: var(--primary-color);
            background: #f4f7fe;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            background: white;
        }

        .applicant-info {
            background: #f4f7fe;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
        }

        .btn-submit {
            padding: 18px 30px;
            font-weight: 700;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--primary-color);
            border: none;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 97, 153, 0.2);
            background: #124873;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            width: 120px !important;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.1));
        }

        .privacy-statement {
            background-color: #f8f9fa;
            border-left: 5px solid #0d6efd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            line-height: 1.5;
            color: #495057;
        }

        .privacy-title {
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .upload-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 15px;
        }

        .required-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
            background: #fee2e2;
            color: #dc2626;
            text-transform: uppercase;
            font-weight: 700;
            margin-left: 10px;
        }

        .optional-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
            background: #e0f2fe;
            color: #0369a1;
            text-transform: uppercase;
            font-weight: 700;
            margin-left: 10px;
        }

        .btn-tbf {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            border: 2px solid #f59e0b;
            background: white;
            color: #b45309;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-tbf.active {
            background: #fef3c7;
            border-color: #d97706;
            color: #92400e;
        }
        .tbf-file-area {
            transition: all 0.3s;
        }
        .tbf-file-area.disabled-upload {
            opacity: 0.4;
            pointer-events: none;
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
            width: 60px;
            height: 60px;
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
            margin-top: 20px;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 1px;
        }
    </style>
</head>

<body>

<?php include 'contact_modal.php'; ?>

    <div class="container py-5">
        <div class="logo-container">
            <img src="DMSF_Logo.png" alt="DMSF Logo" class="logo-img">
            <h2 class="fw-bold">Davao Medical School Foundation</h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card shadow">
                    <div class="card-header-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1 fw-bold">Step 5 of 5</h3>
                                <p class="mb-0 opacity-75">Upload Credentials & Documents</p>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <i class="bi bi-cloud-arrow-up-fill display-6"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="applicant-info">
                            <p class="mb-0 text-dark">Application for
                                **<?= htmlspecialchars($application['college']) ?>** | Applicant:
                                **<?= $student_name ?>**</p>
                        </div>

                        <form method="POST" enctype="multipart/form-data" autocomplete="off">
                            <h5 class="section-title">Required Documents</h5>
                            <p class="text-muted small mb-4">Please upload clear scans or photos of the following (PDF,
                                JPG, or PNG formats only). Maximum file size: 5MB per file.</p>

                            <!-- Document: Applicant Photo -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person-bounding-box upload-icon"></i>
                                    <label class="form-label mb-0">1. Applicant Passport Size Photo <span
                                            class="required-badge">Required</span></label>
                                </div>
                                <input type="file" name="photo_file" class="form-control" accept="image/*" required>
                            </div>

                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark-text upload-icon"></i>
                                    <label class="form-label mb-0">2. Transcript of Records (TOR)
                                        <?php if ($is_dentistry): ?>
                                            <span class="required-badge">Required</span>
                                        <?php else: ?>
                                            <span class="optional-badge">Optional</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!$is_dentistry): ?>
                                        <button type="button" class="btn-tbf ms-auto" id="tbf-tor-btn" onclick="toggleTBF('tor')">📋 To be followed</button>
                                        <input type="hidden" name="tbf_tor" id="tbf_tor" value="">
                                    <?php endif; ?>
                                </div>
                                <div class="tbf-file-area" id="tbf-tor-area">
                                    <input type="file" name="tor_file" class="form-control" id="tor_file" <?= $is_dentistry ? 'required' : '' ?>>
                                </div>
                            </div>

                            <!-- Document: Form 138 (Report Card) -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark-spreadsheet upload-icon"></i>
                                    <label class="form-label mb-0">3. Form 138 (Report Card)
                                        <?php if ($is_nursing): ?>
                                            <span class="required-badge">Required</span>
                                        <?php else: ?>
                                            <span class="optional-badge">Optional</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!$is_nursing): ?>
                                        <button type="button" class="btn-tbf ms-auto" id="tbf-form137-btn" onclick="toggleTBF('form137')">📋 To be followed</button>
                                        <input type="hidden" name="tbf_form137" id="tbf_form137" value="">
                                    <?php endif; ?>
                                </div>
                                <div class="tbf-file-area" id="tbf-form137-area">
                                    <input type="file" name="form137_file" class="form-control" id="form137_file" <?= $is_nursing ? 'required' : '' ?>>
                                </div>
                            </div>

                            <!-- Document: Birth Certificate -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person-badge upload-icon"></i>
                                    <label class="form-label mb-0">4. Birth Certificate (PSA) <span
                                            class="required-badge">Required</span></label>
                                </div>
                                <input type="file" name="birth_cert_file" class="form-control" required>
                            </div>

                            <!-- Document 2: NMAT (Conditional) -->
                            <?php if (strpos($application['college'], 'Medicine') !== false && strpos($application['college'], 'Accelerated Pathway') === false): ?>
                                <div class="file-upload-wrapper">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-journal-check upload-icon"></i>
                                        <label class="form-label mb-0">5. Copy of NMAT Result <span
                                                class="required-badge">Required</span></label>
                                    </div>
                                    <input type="file" name="nmat_file" class="form-control" required>
                                </div>
                            <?php endif; ?>

                            <!-- Document 3: Diploma -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-award upload-icon"></i>
                                    <label class="form-label mb-0">6. Copy of Diploma
                                        <span class="optional-badge">Optional</span></label>
                                    <button type="button" class="btn-tbf ms-auto" id="tbf-diploma-btn" onclick="toggleTBF('diploma')">📋 To be followed</button>
                                    <input type="hidden" name="tbf_diploma" id="tbf_diploma" value="">
                                </div>
                                <div class="tbf-file-area" id="tbf-diploma-area">
                                    <input type="file" name="diploma_file" class="form-control" id="diploma_file">
                                </div>
                            </div>

                            <!-- Document 4: GWA -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calculator upload-icon"></i>
                                    <label class="form-label mb-0">7. General Weighted Average (GWA)
                                        <?php if ($is_nursing): ?>
                                            <span class="required-badge">Required</span>
                                        <?php else: ?>
                                            <span class="optional-badge">Optional</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!$is_nursing): ?>
                                        <button type="button" class="btn-tbf ms-auto" id="tbf-gwa-btn" onclick="toggleTBF('gwa')">📋 To be followed</button>
                                        <input type="hidden" name="tbf_gwa" id="tbf_gwa" value="">
                                    <?php endif; ?>
                                </div>
                                <div class="tbf-file-area" id="tbf-gwa-area">
                                    <input type="file" name="gwa_file" class="form-control" id="gwa_file" <?= $is_nursing ? 'required' : '' ?>>
                                </div>
                            </div>

                            <!-- Document: Good Moral -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-shield-check upload-icon"></i>
                                    <label class="form-label mb-0">8. Certificate of Good Moral Character
                                        <?php if ($is_dentistry): ?>
                                            <span class="required-badge">Required</span>
                                        <?php else: ?>
                                            <span class="optional-badge">Optional</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!$is_dentistry): ?>
                                        <button type="button" class="btn-tbf ms-auto" id="tbf-good_moral-btn" onclick="toggleTBF('good_moral')">📋 To be followed</button>
                                        <input type="hidden" name="tbf_good_moral" id="tbf_good_moral" value="">
                                    <?php endif; ?>
                                </div>
                                <div class="tbf-file-area" id="tbf-good_moral-area">
                                    <input type="file" name="good_moral_file" class="form-control" id="good_moral_file" <?= $is_dentistry ? 'required' : '' ?>>
                                </div>
                            </div>

                            <!-- Document: Passport Copy (Foreign Applicants ONLY) -->
                            <?php if ($is_imd): ?>
                                <div class="file-upload-wrapper">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-passport upload-icon"></i>
                                        <label class="form-label mb-0">10. Passport Copy <span
                                                class="required-badge">Required</span></label>
                                    </div>
                                    <input type="file" name="passport_file" class="form-control"
                                        accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="helper-text mt-2 small text-muted">Required for Foreign applicants.</div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-5 text-center">
                                <button type="button" class="btn btn-submit w-100 shadow-sm text-white py-3"
                                    onclick="showPrivacyModal()">
                                    <i class="bi bi-check2-circle me-2"></i> Complete Final Submission
                                </button>
                                <p class="text-center mt-3 small text-muted">
                                    Finalizing your application will generate your admission record.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy & Consent Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-center">
                    <div class="text-center">
                        <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                        <h4 class="modal-title fw-bold mt-2" id="privacyModalLabel">Data Privacy Statement</h4>
                    </div>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="text-muted mb-0" style="font-size: 0.95rem; line-height: 1.6;">
                        By submitting this application, you consent to the collection, processing, storage, and use of
                        your personal data by Davao Medical School Foundation, Inc. (DMSFI) solely for admission and
                        other legitimate academic-related purposes. All information shall be handled with strict
                        confidentiality in accordance with the Data Privacy Act of 2012 (RA 10173) and its Implementing
                        Rules and Regulations.
                    </p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold" onclick="finalSubmitApplication()"
                        style="border-radius: 12px;">
                        I CONSENT AND SUBMIT
                    </button>
                    <button type="button" class="btn btn-link text-decoration-none text-muted small"
                        data-bs-dismiss="modal">
                        Review Application
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">PROCESSING SUBMISSION...</div>
        <p class="text-muted small mt-2">Please do not refresh or close this page.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const applicationForm = document.querySelector('form');
        const privacyModal = new bootstrap.Modal(document.getElementById('privacyModal'));

        // TBF toggle logic
        const tbfDocs = ['tor', 'form137', 'diploma', 'gwa', 'good_moral'];
        const tbfState = {};
        tbfDocs.forEach(key => tbfState[key] = false);

        function toggleTBF(key) {
            tbfState[key] = !tbfState[key];
            const btn = document.getElementById('tbf-' + key + '-btn');
            const area = document.getElementById('tbf-' + key + '-area');
            const hidden = document.getElementById('tbf_' + key);
            const fileInput = document.getElementById(key === 'good_moral' ? 'good_moral_file' : key + '_file');

            if (tbfState[key]) {
                btn.classList.add('active');
                btn.textContent = '✅ Marked as To be followed';
                area.classList.add('disabled-upload');
                hidden.value = '1';
                if (fileInput) fileInput.value = ''; // clear any selected file
            } else {
                btn.classList.remove('active');
                btn.textContent = '📋 To be followed';
                area.classList.remove('disabled-upload');
                hidden.value = '';
            }
        }

        function showPrivacyModal() {
            if (applicationForm.checkValidity()) {
                privacyModal.show();
            } else {
                applicationForm.reportValidity();
            }
        }

        function finalSubmitApplication() {
            privacyModal.hide();
            // Trigger the loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Disable original button if needed, though form is about to submit
            const mainBtn = document.querySelector('.btn-submit');
            mainBtn.disabled = true;
            mainBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting...';

            // Submit form
            applicationForm.submit();
        }

        applicationForm.addEventListener('submit', function (e) {
            // This is a safety catch in case the form is submitted via Enter key
            if (!document.getElementById('privacyModal').classList.contains('show')) {
                e.preventDefault();
                showPrivacyModal();
                return false;
            }

            // If modal IS showing, let the finalSubmitApplication handle it or let it pass
            document.getElementById('loadingOverlay').style.display = 'flex';
        });
    </script>
</body>

</html>
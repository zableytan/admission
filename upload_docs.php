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

// 2. POST LOGIC: Process file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = 'uploads/';
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

    $tor_path = handleUpload('tor_file', $upload_dir, $app_id);
    $nmat_path = handleUpload('nmat_file', $upload_dir, $app_id);
    $diploma_path = handleUpload('diploma_file', $upload_dir, $app_id); // Optional
    $gwa_path = handleUpload('gwa_file', $upload_dir, $app_id);
    $entrance_path = handleUpload('entrance_exam_file', $upload_dir, $app_id);
    $receipt_path = handleUpload('receipt_file', $upload_dir, $app_id);

    // Handle multiple "other" docs (removed from form but keeping logic just in case or for cleanup)
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

    // Update the database with file paths
// Note: Database schema must be updated to include these new columns: diploma_path, gwa_cert_path, entrance_exam_path, receipt_path
    $sql = "UPDATE applications SET
tor_path=?, nmat_path=?, diploma_path=?, gwa_cert_path=?, entrance_exam_path=?, receipt_path=?, other_docs_paths=?
WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $tor_path,
        $nmat_path,
        $diploma_path,
        $gwa_path,
        $entrance_path,
        $receipt_path,
        $other_docs_paths,
        $app_id
    ]);

    // 3. EMAIL NOTIFICATION TO ADMINS
    $college = $application['college'];
    $admin_stmt = $pdo->prepare("SELECT email FROM admins WHERE college = ? OR is_super_admin = 1");
    $admin_stmt->execute([$college]);
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
            .title-cell h1 { margin: 0; color: #1a237e; font-size: 24px; text-transform: uppercase; font-weight: 900; }
            .title-cell p { margin: 0; color: #666; font-size: 11px; }
            .id-cell { text-align: right; vertical-align: middle; }
            .id-cell .label { font-size: 10px; color: #666; font-weight: bold; }
            .id-cell .value { font-size: 22px; font-weight: bold; color: #0d6efd; margin: 2px 0; }
            .status-badge { background: #ffc107; color: white; padding: 3px 15px; border-radius: 50px; font-size: 10px; font-weight: bold; display: inline-block; }

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
                        <p>Davao Medical School Foundation, Inc. | Registrar's Office</p>
                    </td>
                    <td class='id-cell'>
                        <div class='label'>APP ID</div>
                        <div class='value'>#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . "</div>
                        <div class='status-badge'>PENDING</div>
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
                    <td class='row-label'>Submitted On</td><td class='row-value'>" . date('F d, Y h:i A') . "</td>
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
                    <td class='full-width-label'>Medical History</td><td class='full-width-value' colspan='3'>" . nl2br(htmlspecialchars($app_data['medical_history'] ?: 'None declared')) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Disability?</td><td class='row-value'>" . getBoolText($app_data['physical_disability_flag']) . " (" . htmlspecialchars($app_data['physical_disability_details'] ?: 'N/A') . ")</td>
                    <td class='row-label'>Criminal Record?</td><td class='row-value'>" . getBoolText($app_data['convicted_flag']) . " (" . htmlspecialchars($app_data['convicted_explanation'] ?: 'N/A') . ")</td>
                </tr>
            </table>

            <!-- III. FAMILY & FINANCIAL BACKGROUND -->
            <div class='section-header'>FAMILY & FINANCIAL BACKGROUND</div>
            <table class='info-table'>
                <tr>
                    <td class='row-label'>Father's Name</td><td class='row-value'>" . htmlspecialchars($app_data['father_name']) . "</td>
                    <td class='row-label'>Father's Occupation</td><td class='row-value'>" . htmlspecialchars($app_data['father_occupation']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Mother's Name</td><td class='row-value'>" . htmlspecialchars($app_data['mother_name']) . "</td>
                    <td class='row-label'>Mother's Occupation</td><td class='row-value'>" . htmlspecialchars($app_data['mother_occupation']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>Family Income</td><td class='row-value'>PHP " . number_format($app_data['total_family_income'] ?? 0, 2) . "</td>
                    <td class='row-label'>Income Sources</td><td class='row-value'>" . getListText($app_data, 'income_', ['salaries' => 'Salaries', 'farm' => 'Farm', 'commissions' => 'Commissions', 'rentals' => 'Rentals', 'pension' => 'Pension', 'business' => 'Business']) . "</td>
                </tr>
                <tr>
                    <td class='row-label'>DMSF Alumni Parent?</td><td class='row-value'>" . getBoolText($app_data['parent_dmsf_grad_flag'] ?? 0) . " (" . htmlspecialchars($app_data['parent_dmsf_course_year'] ?? 'N/A') . ")</td>
                    <td class='row-label'>DMSF Faculty Parent?</td><td class='row-value'>" . getBoolText($app_data['parent_dmsf_teaching_flag'] ?? 0) . " (" . ($app_data['parent_dmsf_teaching_years'] ?? 0) . " years)</td>
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
                <tr>
                    <td class='row-label'>High School Honors</td><td class='row-value' colspan='3'>" . getBoolText($app_data['hs_honors_flag'] ?? 0) . " (" . htmlspecialchars($app_data['hs_honor_type'] ?? 'N/A') . ")</td>
                </tr>
                <tr>
                    <td class='full-width-label'>College Degree</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['degree_obtained'] ?? '') . " from " . htmlspecialchars($app_data['college_name_address'] ?? '') . " (Grad: " . ($app_data['date_of_graduation'] ?? 'N/A') . ")</td>
                </tr>
                <tr>
                    <td class='row-label'>College Honors</td><td class='row-value'>" . getBoolText($app_data['college_honors_flag'] ?? 0) . " (" . htmlspecialchars($app_data['college_honors_list'] ?? 'N/A') . ")</td>
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
        foreach ($admin_emails as $admin_email_list) {
            $emails = explode(',', $admin_email_list);
            foreach ($emails as $email) {
                $email = trim($email);
                if (!empty($email))
                    $mail->addAddress($email);
            }
        }

        $mail->addReplyTo($app_data['email'], $student_name);

        // Attach the generated PDF Summary
        $mail->addStringAttachment($pdf_output, "Admission_Record_#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . ".pdf");

        // Attach original uploaded files
        $file_map = ['TOR' => $tor_path, 'NMAT' => $nmat_path, 'Diploma' => $diploma_path, 'GWA_Cert' => $gwa_path, 'Entrance_Exam' => $entrance_path, 'Receipt' => $receipt_path];
        foreach ($file_map as $label => $path) {
            if ($path && file_exists($path)) {
                $mail->addAttachment($path, $label . "_" . basename($path));
            }
        }

        $mail->isHTML(true);
        $mail->Subject = "Admission Submission: " . $student_name . " (" . $college . ")";
        $mail->Body = "<h3>New Admission Application</h3>
                           <p>Please find attached the official <strong>Admission Record PDF</strong> and the original documents for <strong>$student_name</strong> (#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . ").</p>";

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
            --primary-color: #6610f2;
            /* Indigo for Step 5 */
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
            background: #f8f5ff;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            background: white;
        }

        .applicant-info {
            background: #f5f0ff;
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
            box-shadow: 0 10px 20px rgba(102, 16, 242, 0.2);
            background: #520dc2;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            width: 100px;
            height: auto;
            margin-bottom: 15px;
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

    <div class="container py-5">
        <div class="logo-container">
            <img src="DMSF_logo.png" alt="DMSF Logo">
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
                            <i class="bi bi-cloud-arrow-up-fill display-6"></i>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="applicant-info">
                            <p class="mb-0 text-dark">Application for
                                **<?= htmlspecialchars($application['college']) ?>** | Applicant:
                                **<?= $student_name ?>**</p>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <h5 class="section-title">Required Documents</h5>
                            <p class="text-muted small mb-4">Please upload clear scans or photos of the following (PDF,
                                JPG, or PNG formats only). Maximum file size: 5MB per file.</p>

                            <!-- Document 1: TOR -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark-text upload-icon"></i>
                                    <label class="form-label mb-0">1. Transcript of Records (TOR) <span
                                            class="required-badge">Required</span></label>
                                </div>
                                <input type="file" name="tor_file" class="form-control" required>
                            </div>

                            <!-- Document 2: NMAT (Conditional) -->
                            <?php if ($application['college'] === 'Medicine'): ?>
                                <div class="file-upload-wrapper">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-journal-check upload-icon"></i>
                                        <label class="form-label mb-0">2. Copy of NMAT Result <span
                                                class="required-badge">Required</span></label>
                                    </div>
                                    <input type="file" name="nmat_file" class="form-control" required>
                                </div>
                            <?php endif; ?>

                            <!-- Document 3: Diploma -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-award upload-icon"></i>
                                    <label class="form-label mb-0">3. Copy of Diploma <span
                                            class="text-muted small fw-normal ms-2">(If available)</span></label>
                                </div>
                                <input type="file" name="diploma_file" class="form-control">
                            </div>

                            <!-- Document 4: GWA -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calculator upload-icon"></i>
                                    <label class="form-label mb-0">4. General Weighted Average in College <span
                                            class="required-badge">Required</span></label>
                                </div>
                                <input type="file" name="gwa_file" class="form-control" required>
                            </div>

                            <!-- Document 5: Entrance Exam -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-pencil-square upload-icon"></i>
                                    <label class="form-label mb-0">5. Result of Entrance Exam <span
                                            class="required-badge">Required</span></label>
                                </div>
                                <input type="file" name="entrance_exam_file" class="form-control" required>
                            </div>

                            <!-- Document 6: Receipt -->
                            <div class="file-upload-wrapper">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-receipt upload-icon"></i>
                                    <label class="form-label mb-0">6. Receipt of Application Fee <span
                                            class="required-badge">Required</span></label>
                                </div>
                                <input type="file" name="receipt_file" class="form-control" required>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary btn-submit w-100 shadow-sm text-white">
                                    <i class="bi bi-check2-circle me-2"></i> Complete Final Submission
                                </button>
                                <p class="text-center mt-3 small text-muted">
                                    By clicking submit, you certify that all uploaded documents are authentic and
                                    original copies.
                                </p>
                            </div>
                        </form>
                    </div>
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
        document.querySelector('form').addEventListener('submit', function (e) {
            // Show loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Disable submit button
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
        });
    </script>
</body>

</html>
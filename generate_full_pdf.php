<?php
// generate_full_pdf.php
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;

require 'vendor/autoload.php';
require 'db.php';

// --- ROBUST LIBRARY LOADING ---
// 1. Force load FPDF (The base class)
$fpdf_path = __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
if (file_exists($fpdf_path)) {
    require_once $fpdf_path;
} else {
    // If not in vendor/setasign, try the standard vendor/fpdf/fpdf.php path
    $fpdf_alt = __DIR__ . '/vendor/fpdf/fpdf.php';
    if (file_exists($fpdf_alt)) {
        require_once $fpdf_alt;
    }
}

// 2. Load FPDI (The PDF importer)
if (!class_exists('setasign\Fpdi\Fpdi')) {
    $fpdi_autoload = __DIR__ . '/vendor/setasign/fpdi/src/autoload.php';
    if (file_exists($fpdi_autoload)) {
        require_once $fpdi_autoload;
    }
}

// Check if both are now loaded
if (!class_exists('FPDF')) {
    die("Critical Error: FPDF library not found. Please ensure 'vendor/setasign/fpdf/' is fully uploaded to your server.");
}
if (!class_exists('setasign\Fpdi\Fpdi')) {
    die("Critical Error: FPDI library not found. Please ensure 'vendor/setasign/fpdi/' is fully uploaded to your server.");
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security Check
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access. Please log in again.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Application ID.");
}

$app_id = $_GET['id'];

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

// Fetch complete application data
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$app_id]);
$app_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app_data) {
    die("Application not found.");
}

// --- 1. GENERATE SUMMARY PDF (Using Dompdf) ---
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

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

$html = "
<html>
<head>
    <style>
        @page { margin: 20px; }
        body { margin: 0; padding: 0; background-color: #fff; font-family: 'Helvetica', sans-serif; color: #333; }
        .document-page { background: white; width: 100%; padding: 10px; }
        .header-table { width: 100%; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-cell { width: 70px; vertical-align: middle; }
        .title-cell { vertical-align: middle; padding-left: 10px; }
        .title-cell h1 { margin: 0; color: #1a237e; font-size: 20px; text-transform: uppercase; font-weight: 900; letter-spacing: -0.5px; }
        .subtitle { margin: 0; padding-top: 1px; color: #7f8c8d; font-size: 8px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.3px; }
        .id-cell { text-align: right; vertical-align: middle; padding-right: 15px; width: 120px; }
        .id-cell .label { font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; }
        .id-cell .value { font-size: 20px; font-weight: bold; color: #3498db; margin: 0; line-height: 1; }
        
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
        .section-header { background: #1a237e; padding: 7px 15px; font-weight: bold; color: white; margin: 20px 0 5px 0; text-transform: uppercase; font-size: 11px; border-radius: 3px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { border-bottom: 1px solid #f2f2f2; padding: 8px 5px; vertical-align: top; }
        .row-label { width: 18%; font-size: 8.5px; color: #666; text-transform: uppercase; font-weight: 500; }
        .row-value { width: 32%; font-size: 10.5px; font-weight: 700; color: #111; }
        .full-width-label { width: 18%; font-size: 8.5px; color: #666; text-transform: uppercase; font-weight: 500; }
        .full-width-value { width: 82%; font-size: 10.5px; font-weight: 700; color: #111; }
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
            <td class='id-cell'>
                <div class='label'>APP ID</div>
                <div class='value'>#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . "</div>
                <div style='background: #ffc107; color: white; padding: 2px 10px; border-radius: 50px; font-size: 8px; font-weight: bold; display: inline-block; margin-top: 5px;'>PENDING</div>
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
                <td class='row-label'>Score Type/Value</td><td class='row-value'>" . $app_data['score_type'] . ": " . $app_data['score_value'] . ($app_data['gwa_value'] ? " (GWA: " . $app_data['gwa_value'] . ")" : "") . "</td>
                <td class='row-label'>Social Media</td><td class='row-value'>" . htmlspecialchars($app_data['social_media'] ?: 'None') . "</td>
            </tr>
            <tr>
                <td class='row-label'>Submitted On</td><td class='row-value' colspan='3'>" . date('F d, Y h:i A', strtotime($app_data['created_at'])) . "</td>
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
                <td class='row-label'>Disability?</td><td class='row-value' colspan='3'>" . ($app_data['physical_disability_flag'] ? 'YES' : 'NO') . " (" . htmlspecialchars($app_data['physical_disability_details'] ?: 'N/A') . ")</td>
            </tr>
        </table>

        <!-- III. FAMILY & FINANCIAL BACKGROUND -->
        <div class='section-header'>FAMILY & FINANCIAL BACKGROUND</div>
        <table class='info-table'>
            <tr>
                <td class='row-label'>Mother's Name</td>
                <td class='row-value'>" . ($app_data['mother_deceased'] ? 'DECEASED' : htmlspecialchars($app_data['mother_first_name'] . ' ' . $app_data['mother_middle_name'] . ' ' . $app_data['mother_last_name'])) . " (" . ($app_data['mother_age'] ?: 'N/A') . ")</td>
                <td class='row-label'>Mother's Occupation</td>
                <td class='row-value'>" . ($app_data['mother_deceased'] ? 'N/A' : htmlspecialchars($app_data['mother_occupation'])) . "</td>
            </tr>
            <tr>
                <td class='row-label'>Father's Name</td>
                <td class='row-value'>" . ($app_data['father_deceased'] ? 'DECEASED' : htmlspecialchars($app_data['father_first_name'] . ' ' . $app_data['father_middle_name'] . ' ' . $app_data['father_last_name'])) . " (" . ($app_data['father_age'] ?: 'N/A') . ")</td>
                <td class='row-label'>Father's Occupation</td>
                <td class='row-value'>" . ($app_data['father_deceased'] ? 'N/A' : htmlspecialchars($app_data['father_occupation'])) . "</td>
            </tr>
            <tr>
                <td class='row-label'>Family Income (Gross)</td>
                <td class='row-value'>" . htmlspecialchars($app_data['total_family_income']) . "</td>
                <td class='row-label'>Parents' Marriage Status</td>
                <td class='row-value'>" . htmlspecialchars($app_data['parents_marriage_status'] ?: 'N/A') . "</td>
            </tr>
            <tr>
                <td class='row-label'>DMSF Alumni Parent?</td>
                <td class='row-value'>" . ($app_data['parent_dmsf_grad_flag'] ? 'YES' : 'NO') . " (" . htmlspecialchars(($app_data['parent_dmsf_course'] ?? '') . ' ' . ($app_data['parent_dmsf_year'] ?? '')) . ")</td>
                <td class='row-label'>DMSF Affiliated Parent?</td>
                <td class='row-value'>" . ($app_data['parent_dmsf_teaching_flag'] ? 'YES' : 'NO') . " (" . ($app_data['parent_dmsf_teaching_years'] ?? 0) . " years)</td>
            </tr>
            <tr>
                <td class='row-label'>Income Sources</td>
                <td class='row-value' colspan='3'>" . getListText($app_data, 'income_', ['salaries' => 'Salaries', 'farm' => 'Farm', 'commissions' => 'Commissions', 'rentals' => 'Rentals', 'pension' => 'Pension', 'business' => 'Business']) . "</td>
            </tr>
            <tr>
                <td class='row-label'>Siblings</td><td class='row-value'>" . ($app_data['num_brothers'] ?? 0) . " Brothers / " . ($app_data['num_sisters'] ?? 0) . " Sisters</td>
                <td class='row-label'>Siblings in DMSF?</td><td class='row-value'>" . ($app_data['sibling_dmsf_flag'] ? 'YES' : 'NO') . " (" . htmlspecialchars($app_data['sibling_dmsf_details'] ?? 'N/A') . ")</td>
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
                <td class='row-label'>High School Honors</td><td class='row-value' colspan='3'>" . ($app_data['hs_honors_flag'] ? 'YES' : 'NO') . " (" . htmlspecialchars($app_data['hs_honor_type'] ?? 'N/A') . ")</td>
            </tr>
            <tr>
                <td class='full-width-label'>College Degree</td><td class='full-width-value' colspan='3'>" . htmlspecialchars($app_data['degree_obtained'] ?? '') . " from " . htmlspecialchars($app_data['college_name_address'] ?? '') . " (Grad: " . ($app_data['date_of_graduation'] ?? 'N/A') . ")</td>
            </tr>
            <tr>
                <td class='row-label'>College Honors</td><td class='row-value'>" . ($app_data['college_honors_flag'] ? 'YES' : 'NO') . " (" . htmlspecialchars($app_data['college_honors_list'] ?? 'N/A') . ")</td>
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
                <td class='row-label'>First Time Applicant?</td><td class='row-value'>" . ($app_data['first_time_md_flag'] ? 'YES' : 'NO') . "</td>
                <td class='row-label'>Staying Place</td><td class='row-value'>" . htmlspecialchars($app_data['staying_place'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td class='row-label'>Support Source</td>
                <td class='row-value'>" . getListText($app_data, 'support_', ['parents' => 'Parents', 'veteran_benefit' => 'Veteran', 'scholarship_flag' => 'Scholarship']) . ($app_data['support_others'] ? ", " . htmlspecialchars($app_data['support_others']) : "") . "</td>
                <td class='row-label'>Support Status</td>
                <td class='row-value'>" . htmlspecialchars($app_data['support_status'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td class='full-width-label'>Personal Essay</td>
                <td class='full-width-value' colspan='3'>" . nl2br(htmlspecialchars($app_data['application_essay'] ?: 'None')) . "</td>
            </tr>
        </table>

        <!-- VI. ATTACHED DOCUMENTS CHECKLIST -->
        <div class='section-header'>ATTACHED DOCUMENTS CHECKLIST</div>
        <table class='info-table'>
            <tr>
                <td class='row-label'>Transcript (TOR)</td><td class='row-value'>" . (!empty($app_data['tor_path']) ? '✓ Provided' : '✗ Missing') . "</td>
                <td class='row-label'>Birth Cert (PSA)</td><td class='row-value'>" . (!empty($app_data['birth_cert_path']) ? '✓ Provided' : '✗ Missing') . "</td>
            </tr>
            <tr>
                <td class='row-label'>NMAT Result</td><td class='row-value'>" . (!empty($app_data['nmat_path']) ? '✓ Provided' : '✗ Missing') . "</td>
                <td class='row-label'>Diploma</td><td class='row-value'>" . (!empty($app_data['diploma_path']) ? '✓ Provided' : '✗ Missing') . "</td>
            </tr>
            <tr>
                <td class='row-label'>GWA Certificate</td><td class='row-value'>" . (!empty($app_data['gwa_cert_path']) ? '✓ Provided' : '✗ Missing') . "</td>
                <td class='row-label'>Good Moral</td><td class='row-value'>" . (!empty($app_data['good_moral_path']) ? '✓ Provided' : '✗ Missing') . "</td>
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
$summary_pdf_content = $dompdf->output();

// Save summary to temporary file for FPDI
$temp_summary = tempnam(sys_get_temp_dir(), 'summary');
file_put_contents($temp_summary, $summary_pdf_content);

// --- 2. MERGE WITH ATTACHMENTS (Using FPDI) ---
$pdf = new Fpdi();

// Add Summary Pages
$pageCount = $pdf->setSourceFile($temp_summary);
for ($n = 1; $pageCount >= $n; $n++) {
    $tplIdx = $pdf->importPage($n);
    $pdf->addPage();
    $pdf->useTemplate($tplIdx, 0, 0, 210, 297); // A4 size in mm
}
unlink($temp_summary);

// Attachments
$file_fields = [
    'Applicant Passport Photo' => $app_data['photo_path'],
    'Transcript of Records (TOR)' => $app_data['tor_path'],
    'Form 137' => $app_data['form137_path'],
    'Birth Certificate (PSA)' => $app_data['birth_cert_path'],
    'NMAT Result' => $app_data['nmat_path'],
    'Diploma' => $app_data['diploma_path'],
    'GWA Certificate' => $app_data['gwa_cert_path'],
    'Good Moral Character' => $app_data['good_moral_path']
];

foreach ($file_fields as $label => $path) {
    if ($path && file_exists($path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            try {
                $pageCount = $pdf->setSourceFile($path);
                for ($n = 1; $pageCount >= $n; $n++) {
                    $tplIdx = $pdf->importPage($n);
                    $pdf->addPage();
                    // Import with label header
                    $pdf->SetFont('Helvetica', 'B', 14);
                    $pdf->SetTextColor(26, 35, 126);
                    $pdf->Cell(0, 10, "$label - Page $n", 0, 1, 'L');
                    $pdf->Ln(5);

                    // Fit the imported page into the remaining A4 space
                    $pdf->useTemplate($tplIdx, 10, 25, 190);
                }
            } catch (Exception $e) {
                // Skip if PDF is too new version or encrypted
                $pdf->addPage();
                $pdf->SetFont('Helvetica', 'B', 12);
                $pdf->Cell(0, 10, "Error importing PDF: $label", 0, 1);
            }
        } else if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $pdf->addPage();
            $pdf->SetFont('Helvetica', 'B', 14);
            $pdf->SetTextColor(26, 35, 126);
            $pdf->Cell(0, 10, $label, 0, 1, 'L');
            $pdf->Ln(10);

            // Image fitting logic
            $img_info = getimagesize($path);
            if ($img_info) {
                $w = $img_info[0];
                $h = $img_info[1];
                $ratio = $w / $h;

                if ($label === 'Applicant Passport Photo') {
                    // Render passport photo at a smaller, standard size (60mm width)
                    $pdf->Image($path, 10, 30, 60);
                } else if ($ratio > 1) { // Landscape image
                    $pdf->Image($path, 10, 30, 190);
                } else { // Portrait image
                    $pdf->Image($path, 10, 30, 0, 240);
                }
            }
        }
    }
}

// Handle "other" documents
if (!empty($app_data['other_docs_paths'])) {
    $others = explode(',', $app_data['other_docs_paths']);
    foreach ($others as $idx => $path) {
        if ($path && file_exists($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $label = "Other Document " . ($idx + 1);

            if ($ext === 'pdf') {
                try {
                    $pageCount = $pdf->setSourceFile($path);
                    for ($n = 1; $pageCount >= $n; $n++) {
                        $tplIdx = $pdf->importPage($n);
                        $pdf->addPage();
                        $pdf->SetFont('Helvetica', 'B', 14);
                        $pdf->Cell(0, 10, "$label - Page $n", 0, 1, 'L');
                        $pdf->useTemplate($tplIdx, 10, 20, 190);
                    }
                } catch (Exception $e) {
                }
            } else if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $pdf->addPage();
                $pdf->SetFont('Helvetica', 'B', 14);
                $pdf->Cell(0, 10, $label, 0, 1, 'L');
                $pdf->Image($path, 10, 25, 190);
            }
        }
    }
}

$filename = "Full_Package_#" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . "_" . time() . ".pdf";
$pdf->Output('I', $filename);
exit;

<?php
session_start();
require 'db.php';

// Security Check - Only Super Admins and Deans can export
$is_high_level = (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin']) || (isset($_SESSION['is_dean']) && $_SESSION['is_dean']);
if (!isset($_SESSION['admin_id']) || !$is_high_level) {
    die("Unauthorized access.");
}

// Get Filters
$college = filter_input(INPUT_GET, 'college', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'All';
$submission_filter = filter_input(INPUT_GET, 'submission_type', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'All';

// Fetch Data (Same logic as dashboard)
if ($college === 'All' || $college === '' || $college === null) {
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC");
    $applications = $stmt->fetchAll();
} else {
    if ($college === 'Medicine') {
        // Show all Medicine sub-colleges but carefully separate from Accelerated Pathway
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE (college LIKE 'Medicine%' OR college LIKE '%, Medicine%' OR college LIKE '%All Colleges%') ORDER BY created_at DESC");
        $stmt->execute([]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE (college LIKE ? OR college LIKE '%All Colleges%') ORDER BY created_at DESC");
        $stmt->execute(["%$college%"]);
    }
    $applications = $stmt->fetchAll();
}

// Apply Submission Filter in PHP (matches dashboard logic)
if ($submission_filter !== 'All') {
    $applications = array_filter($applications, function($app) use ($submission_filter) {
        $is_submitted = (isset($app['is_submitted']) && $app['is_submitted']) || !empty($app['record_pdf_path']);
        if ($submission_filter === 'Submitted') return $is_submitted;
        if ($submission_filter === 'Draft') return !$is_submitted;
        return true;
    });
}

// Set headers for CSV download
$filename = "DMSF_Applications_Export_" . ($college === 'All' ? 'All_Depts' : str_replace(' ', '_', $college)) . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column Headers
fputcsv($output, [
    'ID', 
    'Family Name', 
    'Given Name', 
    'Middle Name', 
    'Email', 
    'College Applied', 
    'Status', 
    'Submission Type',
    'Score Type', 
    'Score Value', 
    'GWA Value',
    'Mobile No.', 
    'Created At'
]);

// Rows
foreach ($applications as $app) {
    $is_submitted = (isset($app['is_submitted']) && $app['is_submitted']) || !empty($app['record_pdf_path']);
    fputcsv($output, [
        $app['id'],
        $app['family_name'],
        $app['given_name'],
        $app['middle_name'],
        $app['email'],
        $app['college'],
        $app['status'],
        $is_submitted ? 'Submitted' : 'Draft',
        $app['score_type'],
        $app['score_value'],
        $app['gwa_value'] ?? 'N/A',
        $app['mobile_no'],
        $app['created_at']
    ]);
}

fclose($output);
exit;

<?php
// upload_docs.php
// session_start(); // Commented out for screen recording
require 'db.php'; 

$message = '';

// 1. GET LOGIC: Validate Application ID
if (!isset($_GET['app_id']) || !is_numeric($_GET['app_id'])) {
    // For screen recording, provide dummy data if app_id is missing
    $app_id = 0; // A dummy ID
    $application = [
        'family_name' => 'Applicant',
        'given_name' => 'Dummy',
        'college' => 'Medicine' 
    ];
    $student_name = "Dummy Applicant";
} else {
    $app_id = $_GET['app_id'];

    // Fetch application basics
    $stmt = $pdo->prepare("SELECT family_name, given_name, college FROM applications WHERE id = ?");
    $stmt->execute([$app_id]);
    $application = $stmt->fetch();

    if (!$application) {
        $application = [
            'family_name' => 'Applicant',
            'given_name' => 'Dummy',
            'college' => 'Medicine'
        ];
        $student_name = "Dummy Applicant";
    } else {
        $student_name = htmlspecialchars($application['given_name'] . ' ' . $application['family_name']);
    }
}

// 2. POST LOGIC: Process file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For screen recording, we'll just redirect to a completion page
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #6610f2; /* Indigo for Step 5 */
            --secondary-color: #6c757d;
            --success-color: #198754;
            --accent-color: #f8f9fa;
            --border-radius: 12px;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
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
                        <p class="mb-0 text-dark">Application for **<?= htmlspecialchars($application['college']) ?>** | Applicant: **<?= $student_name ?>**</p>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <h5 class="section-title">Required Documents</h5>
                        <p class="text-muted small mb-4">Please upload clear scans or photos of the following (PDF, JPG, or PNG formats only). Maximum file size: 5MB per file.</p>
                        
                        <!-- Document 1 -->
                        <div class="file-upload-wrapper">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-file-earmark-text upload-icon"></i>
                                <label class="form-label mb-0">1. Transcript of Records (TOR) <span class="required-badge">Required</span></label>
                            </div>
                            <input type="file" name="tor_file" class="form-control" required>
                        </div>
                        
                        <!-- Document 2 -->
                        <div class="file-upload-wrapper">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-badge upload-icon"></i>
                                <label class="form-label mb-0">2. Birth Certificate (PSA) <span class="required-badge">Required</span></label>
                            </div>
                            <input type="file" name="birth_cert_file" class="form-control" required>
                        </div>

                        <!-- Document 3 (Conditional) -->
                        <?php if ($application['college'] === 'Medicine'): ?>
                        <div class="file-upload-wrapper">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-journal-check upload-icon"></i>
                                <label class="form-label mb-0">3. NMAT Result <span class="required-badge">Required</span></label>
                            </div>
                            <input type="file" name="nmat_file" class="form-control" required>
                        </div>
                        <?php endif; ?>

                        <!-- Document 4 -->
                        <div class="file-upload-wrapper">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-shield-check upload-icon"></i>
                                <label class="form-label mb-0">4. Certificate of Good Moral Character <span class="required-badge">Required</span></label>
                            </div>
                            <input type="file" name="good_moral_file" class="form-control" required>
                        </div>

                        <!-- Document 5 -->
                        <div class="file-upload-wrapper">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-plus-circle upload-icon"></i>
                                <label class="form-label mb-0">5. Other Credentials <span class="text-muted small fw-normal ms-2">(Optional)</span></label>
                            </div>
                            <input type="file" name="other_docs[]" class="form-control" multiple>
                            <div class="form-text small mt-2">You can select multiple files if needed.</div>
                        </div>

                        <div class="mt-5">
                            <button type="submit" class="btn btn-primary btn-submit w-100 shadow-sm text-white">
                                <i class="bi bi-check2-circle me-2"></i> Complete Final Submission
                            </button>
                            <p class="text-center mt-3 small text-muted">
                                By clicking submit, you certify that all uploaded documents are authentic and original copies.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

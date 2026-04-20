<?php
/**
 * apply.php
 * * Handles the dynamic student application form (STEP 1).
 * Saves basic demographics and academic scores, then redirects to STEP 2 (personal_data.php).
 * ***FILE UPLOAD LOGIC AND FIELD HAVE BEEN REMOVED.***
 */

// Include the database connection script
require 'db.php';

// --- 1. INITIAL SETUP & GET LOGIC ---
if (!isset($_GET['college']) && !isset($_POST['college'])) {
    // If no college is selected (neither GET nor POST), redirect to the index page
    header("Location: index.php");
    exit;
}

// Determine the college from the GET parameter (URL) or POST submission (hidden field)
$college_input = isset($_GET['college']) ? $_GET['college'] : (isset($_POST['college']) ? $_POST['college'] : null);
$applicant_type = isset($_GET['applicant_type']) ? $_GET['applicant_type'] : (isset($_POST['applicant_type']) ? $_POST['applicant_type'] : null);

// If college is an array (from checkboxes), join it into a string
if (is_array($college_input)) {
    $processed_colleges = [];
    foreach ($college_input as $c) {
        if ($applicant_type) {
            $processed_colleges[] = "$c ($applicant_type)";
        } else {
            $processed_colleges[] = $c;
        }
    }
    $college = implode(', ', $processed_colleges);
    $is_multiple = (count($college_input) > 1);
} else {
    // If it's a single string with an applicant_type available, append it (unless it already has it)
    if ($applicant_type && strpos($college_input, '(') === false) {
        $college = "$college_input ($applicant_type)";
    } else {
        $college = $college_input;
    }
    $is_multiple = false;
}

// Determine if this is a Medicine application (NMD or IMD)
// OR if multiple colleges are selected (per user request)
// Note: "Accelerated Pathway for Medicine" does NOT require NMAT fields.
$is_medicine = (strpos($college, 'Medicine') !== false && strpos($college, 'Accelerated Pathway for Medicine') === false) || $is_multiple;

// Determine fields and logic based on College selection
if ($is_medicine) {
    $score_label = "NMAT Score";
    $score_field_name = "nmat_score";
    $score_placeholder = "Enter NMAT Percentile Rank (e.g., 90)";
} else {
    $score_label = ($college === 'All Colleges') ? "General Weighted Average (GWA) / NMAT Score" : "General Weighted Average (GWA)";
    $score_field_name = "gwa_score";
    $score_placeholder = ($college === 'All Colleges') ? "Enter your highest score (GWA or NMAT)" : "Enter General Weighted Average (e.g., 1.75)";
}

$message = '';
$success = false; // Flag to control form display after success

// --- 2. POST SUBMISSION PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize all fields
    $family_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $given_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $middle_name = filter_input(INPUT_POST, 'middle_initial', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $posted_college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_SPECIAL_CHARS);

    $mailing_address = filter_input(INPUT_POST, 'mailing_address', FILTER_SANITIZE_SPECIAL_CHARS);
    $mobile_no = filter_input(INPUT_POST, 'mobile_no', FILTER_SANITIZE_SPECIAL_CHARS);
    $tel_no_mailing = filter_input(INPUT_POST, 'tel_no_mailing', FILTER_SANITIZE_SPECIAL_CHARS);
    $home_address = filter_input(INPUT_POST, 'home_address', FILTER_SANITIZE_SPECIAL_CHARS);
    $tel_no_home = filter_input(INPUT_POST, 'tel_no_home', FILTER_SANITIZE_SPECIAL_CHARS);

    // Collect multiple social media selections
    $socmed_array = isset($_POST['social_media']) ? $_POST['social_media'] : [];
    $social_media = implode(', ', array_map(function ($val) {
        return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
    }, $socmed_array));

    // Conditional data collection
    $nmat_date = $is_medicine ? filter_input(INPUT_POST, 'nmat_date', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $board_rating = filter_input(INPUT_POST, 'board_rating', FILTER_VALIDATE_FLOAT);
    if ($board_rating === false)
        $board_rating = null;

    // Set score type and value based on college selection
    $score_type = $is_medicine ? 'NMAT' : 'GWA';
    $score_val = $is_medicine ? filter_input(INPUT_POST, 'nmat_score', FILTER_VALIDATE_FLOAT) : filter_input(INPUT_POST, 'gwa_score', FILTER_VALIDATE_FLOAT);
    if ($score_val === false)
        $score_val = null;

    // Collect Medicine-specific GWA if applicable
    $gwa_val = $is_medicine ? filter_input(INPUT_POST, 'medicine_gwa', FILTER_VALIDATE_FLOAT) : null;
    if ($gwa_val === false)
        $gwa_val = null;

    // Check if the required email validation passed
    if (!$email) {
        $message = "Invalid email format.";
    } elseif ($is_medicine && $score_val < 40) {
        $message = "Warning: NMAT Score is below the 40 percentile requirement. Please ensure you meet the admission criteria.";
    } else {
        // --- PREVENT DUPLICATE DRAFTS ---
        // Check for existing un-submitted application for this email
        // We look for is_submitted = 0. If it exists, we resume that record ID.
        try {
            // First check if 'is_submitted' column exists to avoid errors
            $check_stmt = $pdo->prepare("SELECT id FROM applications WHERE email = ? AND (is_submitted = 0 OR is_submitted IS NULL) ORDER BY created_at DESC LIMIT 1");
            $check_stmt->execute([$email]);
            $existing_app = $check_stmt->fetch();
        } catch (PDOException $e) {
            // Fallback if column check fails (unlikely given our previous investigation)
            $existing_app = null;
        }

        if ($existing_app) {
            $app_id = $existing_app['id'];
            // Update the existing draft
            $sql = "UPDATE applications SET 
                family_name = ?, given_name = ?, middle_name = ?, college = ?, score_type = ?, score_value = ?, gwa_value = ?, nmat_date = ?, board_rating = ?, 
                mailing_address = ?, mobile_no = ?, tel_no_mailing = ?, home_address = ?, tel_no_home = ?, social_media = ?
                WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $family_name,
                $given_name,
                $middle_name,
                $posted_college,
                $score_type,
                $score_val,
                $gwa_val,
                $nmat_date,
                $board_rating,
                $mailing_address,
                $mobile_no,
                $tel_no_mailing,
                $home_address,
                $tel_no_home,
                $social_media,
                $app_id
            ]);
        } else {
            // Prepare the comprehensive INSERT statement for a new applicant.
            $sql = "INSERT INTO applications 
                (family_name, given_name, middle_name, email, college, score_type, score_value, gwa_value, nmat_date, board_rating, 
                mailing_address, mobile_no, tel_no_mailing, home_address, tel_no_home, social_media) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $family_name,
                $given_name,
                $middle_name,
                $email,
                $posted_college,
                $score_type,
                $score_val,
                $gwa_val,
                $nmat_date,
                $board_rating,
                $mailing_address,
                $mobile_no,
                $tel_no_mailing,
                $home_address,
                $tel_no_home,
                $social_media
            ]);
            if ($success) {
                $app_id = $pdo->lastInsertId();
            }
        }

        if ($success) {
            // Store current app_id in session for persistence across steps
            $_SESSION['active_app_id'] = $app_id;
            header("Location: personal_data.php?app_id=$app_id");
            exit;
        } else {
            $message = "Database error: Could not save application data. Please check your data types and connection.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 1: Basic Application - <?= $is_multiple ? 'Multiple Colleges' : htmlspecialchars($college) ?></title>
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
            background-color: #f4f7fe;
            font-family: 'Inter', sans-serif;
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
            background-color: var(--primary-color) !important;
            padding: 30px;
            color: white;
            border: none;
        }

        .card-body {
            padding: 40px;
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
            font-size: 0.9rem;
            color: #4b4b4b;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            transition: all 0.2s;
            background-color: #fcfcfc;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(25, 97, 153, 0.25);
        }

        .btn-step {
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #124873;
            color: white;
        }

        .helper-text {
            font-size: 0.85rem;
            color: #6c757d;
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
            <div class="col-lg-10">
                <div class="form-card shadow">
                    <div class="card-header-custom text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0 fw-bold">Step 1 of 5: Basic Application</h3>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-white text-primary px-3 py-2">
                                    <?php if ($is_multiple): ?>
                                        Multiple Colleges (Universal)
                                    <?php else: ?>
                                        College of <?= htmlspecialchars($college) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 p-md-5">

                        <?php if ($message): ?>
                            <div class="alert alert-danger rounded-3 shadow-sm border-0 mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off" id="admissionStep1">
                            <input type="hidden" name="college" value="<?= htmlspecialchars($college) ?>">
                            <?php if ($applicant_type): ?>
                                <input type="hidden" name="applicant_type" value="<?= htmlspecialchars($applicant_type) ?>">
                            <?php endif; ?>

                            <h5 class="section-title">Personal Information</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Family Name *</label>
                                    <input type="text" name="last_name" class="form-control" required
                                        placeholder="Last Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Given Name *</label>
                                    <input type="text" name="first_name" class="form-control" required
                                        placeholder="First Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Initial</label>
                                    <input type="text" name="middle_initial" class="form-control"
                                        placeholder="Optional">
                                </div>
                            </div>

                            <h5 class="section-title mt-2">Academic Information</h5>
                            <div class="row g-4 mb-4">
                                <?php if ($is_medicine): ?>
                                    <div class="col-md-4">
                                        <label class="form-label">NMAT Score *</label>
                                        <input type="number" step="1" name="nmat_score" class="form-control" required
                                            placeholder="Percentile Rank (e.g. 90)">
                                        <div class="helper-text mt-2 mx-1">Required percentile rank.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date Taken (NMAT) *</label>
                                        <input type="date" name="nmat_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">College GWA *</label>
                                        <input type="number" step="0.01" name="medicine_gwa" class="form-control" required
                                            placeholder="Enter GWA (e.g. 1.75)">
                                        <div class="helper-text mt-2 mx-1">Your general weighted average.</div>
                                    </div>
                                <?php else: ?>
                                    <div class="col-md-6">
                                        <label class="form-label"><?= $score_label ?> *</label>
                                        <input type="number" step="0.01" name="gwa_score" class="form-control" required
                                            placeholder="<?= $score_placeholder ?>">
                                        <div class="helper-text mt-2 mx-1">
                                            <?= ($college === 'All Colleges') ? 'Highest score between GWA or NMAT.' : 'General Weighted Average.' ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label class="form-label">Board Rating (if applicable)</label>
                                    <input type="number" step="0.01" name="board_rating" class="form-control"
                                        placeholder="e.g. 85.50">
                                </div>
                            </div>

                            <h5 class="section-title mt-2">Contact Details</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required
                                        placeholder="yourname@example.com">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Mailing Address *</label>
                                    <input type="text" name="mailing_address" class="form-control" required
                                        placeholder="Street, Barangay, City, Province">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mobile No. *</label>
                                    <input type="text" name="mobile_no" class="form-control" required
                                        placeholder="09XX XXX XXXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tel. No. (Mailing)</label>
                                    <input type="text" name="tel_no_mailing" class="form-control"
                                        placeholder="Optional">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Home Address *</label>
                                    <input type="text" name="home_address" class="form-control" required
                                        placeholder="Permanent Residence Address">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tel. No. (Home)</label>
                                    <input type="text" name="tel_no_home" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block mb-3">Social Media Accounts (Select all that
                                        apply)</label>
                                    <div class="row g-2">
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="social_media[]"
                                                    value="Facebook" id="smFB">
                                                <label class="form-check-label small" for="smFB">Facebook</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="social_media[]"
                                                    value="Instagram" id="smIG">
                                                <label class="form-check-label small" for="smIG">Instagram</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="social_media[]"
                                                    value="Twitter (X)" id="smTW">
                                                <label class="form-check-label small" for="smTW">Twitter (X)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="social_media[]"
                                                    value="LinkedIn" id="smLI">
                                                <label class="form-check-label small" for="smLI">LinkedIn</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="social_media[]"
                                                    value="TikTok" id="smTK">
                                                <label class="form-check-label small" for="smTK">TikTok</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="social_media[]"
                                                    value="YouTube" id="smYT">
                                                <label class="form-check-label small" for="smYT">YouTube</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">Other</span>
                                                <input type="text" name="social_media[]" class="form-control"
                                                    placeholder="Specify platform...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-step w-100 shadow-sm">
                                    Proceed to Step 2: Personal Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-link link-secondary text-decoration-none">← Choose a different
                        college</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- BROWSER AUTO-SAVE FEATURE ---
        // This script saves form data to localStorage so it's not lost on refresh
        const formId = 'admissionStep1';
        const form = document.getElementById(formId);

        // 1. Restore data on page load
        window.addEventListener('load', () => {
            const savedData = localStorage.getItem(formId);
            if (savedData) {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const field = form.elements[key];
                    if (field && field.type !== 'file' && field.type !== 'password') {
                        if (field.type === 'checkbox') {
                            field.checked = data[key] === field.value;
                        } else {
                            field.value = data[key];
                        }
                    }
                });
            }
        });

        // 2. Save data on input
        form.addEventListener('input', () => {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (!(value instanceof File)) {
                    data[key] = value;
                }
            });
            localStorage.setItem(formId, JSON.stringify(data));
        });

        // 3. Clear storage if user successfully moves to Step 2
        <?php if (isset($_GET['clear_storage'])): ?>
            localStorage.removeItem(formId);
        <?php endif; ?>

        // Real-time validation for NMAT Score and Expiry
        document.addEventListener('DOMContentLoaded', function () {
            // We only run this if the nmat_score input exists (i.e., College of Medicine)
            const nmatInput = document.querySelector('input[name="nmat_score"]');
            const nmatDateInput = document.querySelector('input[name="nmat_date"]');

            if (nmatInput) {
                // 1. NMAT Score Warning
                const warningMsg = document.createElement('div');
                warningMsg.style.color = '#dc3545';
                warningMsg.style.fontSize = '0.875rem';
                warningMsg.style.marginTop = '0.5rem';
                warningMsg.style.fontWeight = '600';
                warningMsg.innerText = 'Warning: Score is below the 40 percentile requirement.';
                warningMsg.style.display = 'none';

                if (nmatInput.nextElementSibling) {
                    let existingHelper = nmatInput.nextElementSibling;
                    if (existingHelper && existingHelper.classList.contains('helper-text')) {
                        existingHelper.parentNode.insertBefore(warningMsg, existingHelper.nextSibling);
                    } else {
                        nmatInput.parentNode.insertBefore(warningMsg, nmatInput.nextElementSibling);
                    }
                } else {
                    nmatInput.parentNode.appendChild(warningMsg);
                }

                nmatInput.addEventListener('input', function () {
                    const val = parseFloat(this.value);
                    if (this.value && val < 40) {
                        warningMsg.style.display = 'block';
                        this.style.borderColor = '#dc3545';
                    } else {
                        warningMsg.style.display = 'none';
                        this.style.borderColor = '';
                    }
                });
            }

            if (nmatDateInput) {
                // 2. NMAT Expiry Warning
                const expiryMsg = document.createElement('div');
                expiryMsg.style.color = '#dc3545';
                expiryMsg.style.fontSize = '0.875rem';
                expiryMsg.style.marginTop = '0.5rem';
                expiryMsg.style.fontWeight = '600';
                expiryMsg.innerText = 'Warning: Your NMAT has expired or you need to retake it (valid for 2 years only).';
                expiryMsg.style.display = 'none';
                nmatDateInput.parentNode.appendChild(expiryMsg);

                nmatDateInput.addEventListener('change', function () {
                    if (this.value) {
                        const takenDate = new Date(this.value);
                        const today = new Date();
                        const twoYearsAgo = new Date();
                        twoYearsAgo.setFullYear(today.getFullYear() - 2);

                        if (takenDate < twoYearsAgo) {
                            expiryMsg.style.display = 'block';
                            this.style.borderColor = '#dc3545';
                        } else {
                            expiryMsg.style.display = 'none';
                            this.style.borderColor = '';
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>
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
$college = isset($_GET['college']) ? $_GET['college'] : $_POST['college'];

// Determine fields and logic based on College selection
if ($college === 'Medicine') {
    $score_label = "NMAT Score";
    $score_field_name = "nmat_score";
    $is_medicine = true;
    $score_placeholder = "Enter NMAT Percentile Rank (e.g., 90)";
} else {
    $score_label = "College GWA";
    $score_field_name = "gwa_score";
    $is_medicine = false;
    $score_placeholder = "Enter General Weighted Average (e.g., 1.75)";
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
    $social_media = filter_input(INPUT_POST, 'social_media', FILTER_SANITIZE_SPECIAL_CHARS);

    // Conditional data collection
    $nmat_date = $is_medicine ? filter_input(INPUT_POST, 'nmat_date', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $board_rating = filter_input(INPUT_POST, 'board_rating', FILTER_VALIDATE_FLOAT);
    if ($board_rating === false) $board_rating = null;

    // Set score type and value based on college selection
    $score_type = $is_medicine ? 'NMAT' : 'GWA';
    $score_val = $is_medicine ? filter_input(INPUT_POST, 'nmat_score', FILTER_VALIDATE_FLOAT) : filter_input(INPUT_POST, 'gwa_score', FILTER_VALIDATE_FLOAT);
    if ($score_val === false) $score_val = null;

    // Default attachment path to NULL since we are skipping upload for now
    $target_file = null;

    // Check if the required email validation passed
    if (!$email) {
        $message = "Invalid email format.";
    } elseif ($is_medicine && $score_val < 40) {
        $message = "Warning: NMAT Score is below the 40 percentile requirement. Please ensure you meet the admission criteria.";
    } else {

        // --- FILE UPLOAD LOGIC REMOVED ---

        // Prepare the comprehensive INSERT statement.
        $sql = "INSERT INTO applications 
            (family_name, given_name, middle_name, email, college, score_type, score_value, nmat_date, board_rating, 
            mailing_address, mobile_no, tel_no_mailing, home_address, tel_no_home, social_media) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        // Execute the statement with the collected values
        if (
            $stmt->execute([
                $family_name,
                $given_name,
                $middle_name,
                $email,
                $posted_college,
                $score_type,
                $score_val,
                $nmat_date,
                $board_rating,
                $mailing_address,
                $mobile_no,
                $tel_no_mailing,
                $home_address,
                $tel_no_home,
                $social_media
            ])
        ) {

            // Get ID and Redirect to Step 2
            $app_id = $pdo->lastInsertId();
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
    <title>Step 1: Basic Application - <?= htmlspecialchars($college) ?></title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
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
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
            background-color: #0b5ed7;
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
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.1));
        }
    </style>
</head>

<body>

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
                            <span class="badge bg-white text-primary px-3 py-2">College of
                                <?= htmlspecialchars($college) ?></span>
                        </div>
                    </div>
                    <div class="card-body p-4 p-md-5">

                        <?php if ($message): ?>
                            <div class="alert alert-danger rounded-3 shadow-sm border-0 mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="college" value="<?= htmlspecialchars($college) ?>">

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
                                <div class="col-md-6">
                                    <label class="form-label"><?= $score_label ?> *</label>
                                    <input type="number" step="<?= $is_medicine ? '1' : '0.01' ?>"
                                        name="<?= $score_field_name ?>" class="form-control" required
                                        placeholder="<?= $score_placeholder ?>">
                                    <div class="helper-text mt-2 mx-1">
                                        <?= $is_medicine ? 'Medicine requires NMAT Percentile Rank.' : 'Other colleges require General Weighted Average.' ?>
                                    </div>
                                </div>

                                <?php if ($is_medicine): ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Date Taken (NMAT) *</label>
                                        <input type="date" name="nmat_date" class="form-control" required>
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
                                <div class="col-md-6">
                                    <label class="form-label">Social Media Accounts</label>
                                    <input type="text" name="social_media" class="form-control"
                                        placeholder="FB / LinkedIn / Twitter">
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
    <script src="js/form-draft.js"></script>

    <script>
        // Real-time validation for NMAT Score
        document.addEventListener('DOMContentLoaded', function () {
            // We only run this if the nmat_score input exists (i.e., College of Medicine)
            const nmatInput = document.querySelector('input[name="nmat_score"]');

            if (nmatInput) {
                // Create a warning element but hide it initially
                const warningMsg = document.createElement('div');
                warningMsg.style.color = '#dc3545'; // Bootstrap error color
                warningMsg.style.fontSize = '0.875rem';
                warningMsg.style.marginTop = '0.5rem';
                warningMsg.style.fontWeight = '600';
                warningMsg.innerText = 'Warning: Score is below the 40 percentile requirement.';
                warningMsg.style.display = 'none';

                // Insert after the helper text or the input
                if (nmatInput.nextElementSibling) {
                    // If the next sibling is the helper text div, insert after it
                    // We check if next element is a DIV with class helper-text
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

                    // Check if value is entered and is less than 40
                    if (this.value && val < 40) {
                        warningMsg.style.display = 'block';
                        this.style.borderColor = '#dc3545'; // Highlight input border
                    } else {
                        warningMsg.style.display = 'none';
                        this.style.borderColor = ''; // Reset border
                    }
                });
            }
        });
    </script>

</body>

</html>
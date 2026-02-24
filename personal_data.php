<?php
/**
 * personal_data.php
 * Handles the collection of detailed personal, medical, and legal history (Step 2).
 * Requires an app_id via GET parameter and updates the existing application record.
 */
session_start();
require 'db.php';

$message = '';

// 1. GET LOGIC: Validate Application ID
if (!isset($_GET['app_id']) || !is_numeric($_GET['app_id'])) {
    header("Location: apply.php");
    exit;
}

$app_id = $_GET['app_id'];

// Check if application exists and fetch basic info for display
$stmt = $pdo->prepare("SELECT family_name, given_name, college FROM applications WHERE id = ?");
$stmt->execute([$app_id]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: apply.php");
    exit;
}

$student_name = htmlspecialchars($application['given_name'] . ' ' . $application['family_name']);


// 2. POST LOGIC: Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize all fields
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    if ($age === false) $age = null;

    $dob = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_SPECIAL_CHARS);
    $pob = filter_input(INPUT_POST, 'place_of_birth', FILTER_SANITIZE_SPECIAL_CHARS);
    $sex = filter_input(INPUT_POST, 'sex', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'civil_status', FILTER_SANITIZE_SPECIAL_CHARS);
    $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_SPECIAL_CHARS);
    $citizenship = filter_input(INPUT_POST, 'citizenship', FILTER_SANITIZE_SPECIAL_CHARS);

    $height_ft = filter_input(INPUT_POST, 'height_ft', FILTER_VALIDATE_INT);
    if ($height_ft === false) $height_ft = null;

    $height_in = filter_input(INPUT_POST, 'height_in', FILTER_VALIDATE_INT);
    if ($height_in === false) $height_in = null;

    $weight_initial = filter_input(INPUT_POST, 'weight_kilos_initial', FILTER_VALIDATE_FLOAT);
    if ($weight_initial === false) $weight_initial = null;

    $weight_now = filter_input(INPUT_POST, 'weight_kilos_now', FILTER_VALIDATE_FLOAT);
    if ($weight_now === false) $weight_now = null;

    $med_history = filter_input(INPUT_POST, 'medical_history', FILTER_SANITIZE_SPECIAL_CHARS);

    // Boolean fields (convert 'YES'/'NO' or checkbox presence)
    $disability_flag = (isset($_POST['disability_flag']) && $_POST['disability_flag'] == 'YES') ? 1 : 0;
    $disability_details = filter_input(INPUT_POST, 'physical_disability_details', FILTER_SANITIZE_SPECIAL_CHARS);

    $convicted_flag = (isset($_POST['convicted_flag']) && $_POST['convicted_flag'] == 'YES') ? 1 : 0;
    $convicted_explanation = filter_input(INPUT_POST, 'convicted_explanation', FILTER_SANITIZE_SPECIAL_CHARS);


    // Prepare the UPDATE statement
    $sql = "UPDATE applications SET 
            age=?, date_of_birth=?, place_of_birth=?, sex=?, civil_status=?, religion=?, citizenship=?, 
            height_ft=?, height_in=?, weight_kilos_initial=?, weight_kilos_now=?, medical_history=?, 
            physical_disability_flag=?, physical_disability_details=?, convicted_flag=?, convicted_explanation=?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    // Execute the statement
    if (
        $stmt->execute([
            $age,
            $dob,
            $pob,
            $sex,
            $status,
            $religion,
            $citizenship,
            $height_ft,
            $height_in,
            $weight_initial,
            $weight_now,
            $med_history,
            $disability_flag,
            $disability_details,
            $convicted_flag,
            $convicted_explanation,
            $app_id
        ])
    ) {
        // Successful update! Redirect to Step 3: Family Background
        header("Location: family_background.php?app_id=$app_id");
        exit;
    } else {
        $message = "Error: Could not save personal data. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2: Personal Data | Admission</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #ffc107;
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
            color: #212529;
            /* Dark text for yellow bg */
            border: none;
        }

        .card-body {
            padding: 40px;
        }

        .section-title {
            color: #d39e00;
            /* Darker yellow for text visibility */
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

        .form-control,
        .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            transition: all 0.2s;
            background-color: #fcfcfc;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        .btn-step {
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            background-color: var(--primary-color);
            color: #212529;
            border: none;
        }

        .btn-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #ffcd39;
        }

        .applicant-info {
            background: #fff8e1;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid #ffc107;
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
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="logo-container">
            <img src="DMSF_Logo.png" alt="DMSF Logo">
            <h2 class="fw-bold">Davao Medical School Foundation</h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-card mx-auto shadow">
                    <div class="card-header-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0 fw-bold">Step 2 of 5: Personal & Medical Data</h3>
                            <span class="badge bg-white text-dark px-3 py-2">Admission Process</span>
                        </div>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <div class="applicant-info">
                            <p class="mb-0 text-dark">Application for
                                **<?= htmlspecialchars($application['college']) ?>** | Applicant:
                                **<?= $student_name ?>**</p>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-danger rounded-3 shadow-sm border-0 mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <h5 class="section-title">A. Detailed Personal Data</h5>

                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Age *</label>
                                    <input type="number" name="age" class="form-control" required placeholder="Age">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Date of Birth *</label>
                                    <input type="date" name="date_of_birth" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Place of Birth *</label>
                                    <input type="text" name="place_of_birth" class="form-control" required
                                        placeholder="City/Province">
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Sex *</label>
                                    <select name="sex" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Civil Status *</label>
                                    <select name="civil_status" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Religion *</label>
                                    <input type="text" name="religion" class="form-control" required
                                        placeholder="e.g. Catholic">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Citizenship *</label>
                                    <input type="text" name="citizenship" class="form-control" required
                                        placeholder="at birth">
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Height (feet)</label>
                                    <input type="number" name="height_ft" class="form-control" min="3" max="7"
                                        placeholder="Feet">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Height (inches)</label>
                                    <input type="number" name="height_in" class="form-control" min="0" max="11"
                                        placeholder="Inches">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Weight (Kilos - Initial)</label>
                                    <input type="number" step="0.1" name="weight_kilos_initial" class="form-control"
                                        placeholder="Initial">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Weight (Kilos - Now)</label>
                                    <input type="number" step="0.1" name="weight_kilos_now" class="form-control"
                                        placeholder="Current">
                                </div>
                            </div>

                            <h5 class="section-title mt-2">B. Medical History</h5>

                            <div class="mb-4">
                                <label class="form-label">Medical History (last 5 years)</label>
                                <textarea name="medical_history" class="form-control" rows="3"
                                    placeholder="List any physical or mental illnesses..."></textarea>
                            </div>

                            <div class="mb-4 border-0 bg-light p-4 rounded-3 text-secondary">
                                <label class="form-label fw-bold d-block mb-3 text-dark">Do you have any physical
                                    disability which might interfere with the practice of medicine?</label>
                                <div class="form-check form-check-inline me-4">
                                    <input class="form-check-input" type="radio" name="disability_flag"
                                        id="disabilityYes" value="YES"
                                        onclick="document.getElementById('disabilityDetails').style.display='block'">
                                    <label class="form-check-label" for="disabilityYes">YES</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="disability_flag"
                                        id="disabilityNo" value="NO" checked
                                        onclick="document.getElementById('disabilityDetails').style.display='none'">
                                    <label class="form-check-label" for="disabilityNo">NO</label>
                                </div>
                                <div class="mt-3" id="disabilityDetails" style="display: none;">
                                    <textarea name="physical_disability_details" class="form-control" rows="2"
                                        placeholder="If YES, please state details."></textarea>
                                </div>
                            </div>

                            <h5 class="section-title mt-2">C. Legal History</h5>

                            <div class="mb-4 border-0 bg-light p-4 rounded-3 text-secondary">
                                <label class="form-label fw-bold d-block mb-3 text-dark">Have you been convicted in
                                    court of any offense?</label>
                                <div class="form-check form-check-inline me-4">
                                    <input class="form-check-input" type="radio" name="convicted_flag" id="convictedYes"
                                        value="YES"
                                        onclick="document.getElementById('convictedExplanation').style.display='block'">
                                    <label class="form-check-label" for="convictedYes">YES</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="convicted_flag" id="convictedNo"
                                        value="NO" checked
                                        onclick="document.getElementById('convictedExplanation').style.display='none'">
                                    <label class="form-check-label" for="convictedNo">NO</label>
                                </div>
                                <div class="mt-3" id="convictedExplanation" style="display: none;">
                                    <textarea name="convicted_explanation" class="form-control" rows="3"
                                        placeholder="If YES, please explain..."></textarea>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-step w-100 shadow-sm">
                                    Proceed to Step 3: Family Background <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/form-draft.js"></script>
</body>

</html>
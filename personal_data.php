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
    if ($age === false)
        $age = null;

    $dob = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_SPECIAL_CHARS);
    $pob = filter_input(INPUT_POST, 'place_of_birth', FILTER_SANITIZE_SPECIAL_CHARS);
    $sex = filter_input(INPUT_POST, 'sex', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'civil_status', FILTER_SANITIZE_SPECIAL_CHARS);
    $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_SPECIAL_CHARS);
    $citizenship = filter_input(INPUT_POST, 'citizenship', FILTER_SANITIZE_SPECIAL_CHARS);

    $height_ft = filter_input(INPUT_POST, 'height_ft', FILTER_VALIDATE_INT);
    if ($height_ft === false)
        $height_ft = null;

    $height_in = filter_input(INPUT_POST, 'height_in', FILTER_VALIDATE_INT);
    if ($height_in === false)
        $height_in = null;

    $weight_initial = filter_input(INPUT_POST, 'weight_kilos_initial', FILTER_VALIDATE_FLOAT);
    if ($weight_initial === false)
        $weight_initial = null;

    $weight_now = filter_input(INPUT_POST, 'weight_kilos_now', FILTER_VALIDATE_FLOAT);
    if ($weight_now === false)
        $weight_now = null;

    $med_history = filter_input(INPUT_POST, 'medical_history', FILTER_DEFAULT);

    // Boolean fields (convert 'YES'/'NO' or checkbox presence)
    $disability_flag = (isset($_POST['disability_flag']) && $_POST['disability_flag'] == 'YES') ? 1 : 0;
    $disability_details = filter_input(INPUT_POST, 'physical_disability_details', FILTER_DEFAULT);

    // --- Vaccination Status ---
    $vax_status = filter_input(INPUT_POST, 'vax_status', FILTER_SANITIZE_SPECIAL_CHARS);
    $vax_dose1 = ($vax_status == 'Yes') ? filter_input(INPUT_POST, 'vax_dose1', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $vax_dose2 = ($vax_status == 'Yes') ? filter_input(INPUT_POST, 'vax_dose2', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $vax_booster = ($vax_status == 'Yes') ? filter_input(INPUT_POST, 'vax_booster', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Health & Well-being ---
    $chronic_condition_flag = (isset($_POST['chronic_condition_flag']) && $_POST['chronic_condition_flag'] == 'YES') ? 1 : 0;
    $chronic_condition_details = filter_input(INPUT_POST, 'chronic_condition_details', FILTER_DEFAULT);
    $counselling_history = filter_input(INPUT_POST, 'counselling_history', FILTER_DEFAULT);

    // --- Motivation ---
    $motivation_parents = isset($_POST['motivation_parents']) ? 1 : 0;
    $motivation_siblings = isset($_POST['motivation_siblings']) ? 1 : 0;
    $motivation_relatives = isset($_POST['motivation_relatives']) ? 1 : 0;
    $motivation_friends = isset($_POST['motivation_friends']) ? 1 : 0;
    $motivation_illness = isset($_POST['motivation_illness']) ? 1 : 0;
    $motivation_prestige = isset($_POST['motivation_prestige']) ? 1 : 0;
    $motivation_health_awareness = isset($_POST['motivation_health_awareness']) ? 1 : 0;
    $motivation_community_needs = isset($_POST['motivation_community_needs']) ? 1 : 0;
    $motivation_others = filter_input(INPUT_POST, 'motivation_others', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Support ---
    $support_parents = isset($_POST['support_parents']) ? 1 : 0;
    $support_veteran_benefit = isset($_POST['support_veteran_benefit']) ? 1 : 0;
    $support_scholarship_flag = isset($_POST['support_scholarship_flag']) ? 1 : 0;
    $support_scholarship_name = $support_scholarship_flag ? filter_input(INPUT_POST, 'support_scholarship_name', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $support_others = filter_input(INPUT_POST, 'support_others', FILTER_SANITIZE_SPECIAL_CHARS);
    $support_status = filter_input(INPUT_POST, 'support_status', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Information Source ---
    $info_parents = isset($_POST['info_parents']) ? 1 : 0;
    $info_family_friends = isset($_POST['info_family_friends']) ? 1 : 0;
    $info_student_friends = isset($_POST['info_student_friends']) ? 1 : 0;
    $info_siblings = isset($_POST['info_siblings']) ? 1 : 0;
    $info_teachers = isset($_POST['info_teachers']) ? 1 : 0;
    $info_newspaper = isset($_POST['info_newspaper']) ? 1 : 0;
    $info_convocation = isset($_POST['info_convocation']) ? 1 : 0;
    $info_internet = isset($_POST['info_internet']) ? 1 : 0;
    $info_own_effort = isset($_POST['info_own_effort']) ? 1 : 0;
    $info_others = filter_input(INPUT_POST, 'info_others', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Staying Place ---
    $staying_place = filter_input(INPUT_POST, 'staying_place', FILTER_SANITIZE_SPECIAL_CHARS);
    $staying_place_others = ($staying_place == 'Others') ? filter_input(INPUT_POST, 'staying_place_others', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // Legal Case Involvement (Section E) removed

    // Prepare the UPDATE statement
    $sql = "UPDATE applications SET 
            age=?, date_of_birth=?, place_of_birth=?, sex=?, civil_status=?, religion=?, citizenship=?, 
            height_ft=?, height_in=?, weight_kilos_initial=?, weight_kilos_now=?, medical_history=?, 
            physical_disability_flag=?, physical_disability_details=?,
            vax_status=?, vax_dose1=?, vax_dose2=?, vax_booster=?,
            chronic_condition_flag=?, chronic_condition_details=?, counselling_history=?,
            motivation_parents=?, motivation_siblings=?, motivation_relatives=?, motivation_friends=?, 
            motivation_illness=?, motivation_prestige=?, motivation_health_awareness=?, motivation_community_needs=?, motivation_others=?,
            support_parents=?, support_veteran_benefit=?, support_scholarship_flag=?, support_scholarship_name=?, support_others=?, support_status=?,
            info_parents=?, info_family_friends=?, info_student_friends=?, info_siblings=?, info_teachers=?, 
            info_newspaper=?, info_convocation=?, info_internet=?, info_own_effort=?, info_others=?,
            staying_place=?, staying_place_others=?
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
            $vax_status,
            $vax_dose1,
            $vax_dose2,
            $vax_booster,
            $chronic_condition_flag,
            $chronic_condition_details,
            $counselling_history,
            $motivation_parents, $motivation_siblings, $motivation_relatives, $motivation_friends,
            $motivation_illness, $motivation_prestige, $motivation_health_awareness, $motivation_community_needs, $motivation_others,
            $support_parents, $support_veteran_benefit, $support_scholarship_flag, $support_scholarship_name, $support_others, $support_status,
            $info_parents, $info_family_friends, $info_student_friends, $info_siblings, $info_teachers,
            $info_newspaper, $info_convocation, $info_internet, $info_own_effort, $info_others,
            $staying_place, $staying_place_others,
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

        .applicant-info {
            background: #f4f7fe;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
        }

        .x-small {
            font-size: 0.75rem;
            text-transform: none;
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
                <div class="form-card mx-auto shadow">
                    <div class="card-header-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0 fw-bold">Step 2 of 5: Personal & Medical Data</h3>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-white text-dark px-3 py-2">Admission Process</span>
                            </div>
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

                        <form method="POST" autocomplete="off" id="admissionStep2">
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

                            <h5 class="section-title mt-2">B. Health & Medical Profile</h5>

                            <div class="mb-4">
                                <label class="form-label">General Medical History</label>
                                <textarea name="medical_history" class="form-control" rows="3"
                                    placeholder="List any physical or mental illnesses, surgeries, or major health events..."></textarea>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Vaccination Status <span class="fw-normal text-muted small">(COVID-19 or Hepatitis)</span></label>
                                        <div class="d-flex gap-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="vax_status" id="vaxYes" value="Yes" onclick="document.getElementById('vaxDetails').style.display='block'">
                                                <label class="form-check-label px-2" for="vaxYes font-bold">YES</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="vax_status" id="vaxNo" value="No" checked onclick="document.getElementById('vaxDetails').style.display='none'">
                                                <label class="form-check-label px-2" for="vaxNo font-bold">NO</label>
                                            </div>
                                        </div>
                                        <div id="vaxDetails" style="display: none;" class="mt-3 border-top pt-3">
                                            <div class="mb-2">
                                                <label class="form-label x-small mb-1">1st Dose (Brand):</label>
                                                <input type="text" name="vax_dose1" class="form-control form-control-sm" placeholder="Brand name...">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label x-small mb-1">2nd Dose (Brand / N/A):</label>
                                                <input type="text" name="vax_dose2" class="form-control form-control-sm" placeholder="Brand name or N/A...">
                                            </div>
                                            <div>
                                                <label class="form-label x-small mb-1">Booster (Brand / N/A):</label>
                                                <input type="text" name="vax_booster" class="form-control form-control-sm" placeholder="Brand name or N/A...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Chronic Medical Condition</label>
                                        <div class="d-flex gap-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="chronic_condition_flag" id="chronicYes" value="YES" onclick="document.getElementById('chronicDetails').style.display='block'">
                                                <label class="form-check-label px-2" for="chronicYes font-bold">YES</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="chronic_condition_flag" id="chronicNo" value="NO" checked onclick="document.getElementById('chronicDetails').style.display='none'">
                                                <label class="form-check-label px-2" for="chronicNo font-bold">NO</label>
                                            </div>
                                        </div>
                                        <div id="chronicDetails" style="display: none;" class="mt-2">
                                            <textarea name="chronic_condition_details" class="form-control form-control-sm" rows="3" placeholder="Please state details..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Physical Disability</label>
                                        <p class="x-small text-muted mb-3">Do you have any disability which might interfere with the practice of your chosen course?</p>
                                        <div class="d-flex gap-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="disability_flag" id="disabilityYes" value="YES" onclick="document.getElementById('disabilityDetails').style.display='block'">
                                                <label class="form-check-label px-2" for="disabilityYes font-bold">YES</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="disability_flag" id="disabilityNo" value="NO" checked onclick="document.getElementById('disabilityDetails').style.display='none'">
                                                <label class="form-check-label px-2" for="disabilityNo font-bold">NO</label>
                                            </div>
                                        </div>
                                        <div id="disabilityDetails" style="display: none;" class="mt-2 text-danger">
                                            <textarea name="physical_disability_details" class="form-control form-control-sm" rows="2" placeholder="If YES, please state details."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">History of Professional Counselling</label>
                                        <div class="mb-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="counselling_history" id="counselYes" value="Yes">
                                                <label class="form-check-label px-2" for="counselYes">Yes</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="counselling_history" id="counselNo" value="No" checked>
                                                <label class="form-check-label px-2" for="counselNo">No</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="counselling_history" id="counselPrefer" value="Prefer not to say">
                                                <label class="form-check-label px-2" for="counselPrefer">Prefer not to say</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title mt-5 mb-4">C. Future Plans & Support</h5>

                            <!-- Influence Group -->
                            <div class="mb-5">
                                <label class="form-label fw-bold d-block mb-3">What influence you greatly in taking up this course? <span class="fw-normal text-muted small">(Check all that apply)</span></label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="motivation_parents"> <label class="form-check-label small">Parents</label></div>
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="motivation_siblings"> <label class="form-check-label small">Siblings</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="motivation_relatives"> <label class="form-check-label small">Other Relatives</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="motivation_friends"> <label class="form-check-label small">Friends</label></div>
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="motivation_illness"> <label class="form-check-label small">Illness in family</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="motivation_prestige"> <label class="form-check-label small">Prestige/Social Standing</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="motivation_health_awareness"> <label class="form-check-label small">Health Awareness</label></div>
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="motivation_community_needs"> <label class="form-check-label small">Community Needs</label></div>
                                        <div class="mt-3">
                                            <label class="form-label x-small text-muted mb-1">Others (specify):</label>
                                            <input type="text" name="motivation_others" class="form-control form-control-sm" placeholder="Please specify...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Support Group -->
                            <div class="mb-5">
                                <label class="form-label fw-bold d-block mb-3">How will your education be supported?</label>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="support_parents"> <label class="form-check-label small">Parents/Family</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="support_veteran_benefit"> <label class="form-check-label small">Phil Veteran Benefit</label></div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="support_scholarship_flag" id="scholarshipCheck" onclick="document.getElementById('scholarshipDetail').style.display=this.checked?'block':'none'">
                                            <label class="form-check-label small fw-semibold" for="scholarshipCheck">I have a Scholarship</label>
                                        </div>
                                        <div id="scholarshipDetail" class="ms-4 p-3 bg-light rounded-3 border" style="display: none;">
                                            <div class="row g-2">
                                                <div class="col-md-7">
                                                    <label class="form-label x-small mb-1">Name of Scholarship:</label>
                                                    <input type="text" name="support_scholarship_name" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label x-small mb-1">Status:</label>
                                                    <select name="support_status" class="form-select form-select-sm">
                                                        <option value="">Select status...</option>
                                                        <option value="Approved">Approved</option>
                                                        <option value="Processing">Processing</option>
                                                        <option value="Planning to apply">Planning to apply</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="form-label x-small text-muted mb-1">Other Support:</label>
                                            <input type="text" name="support_others" class="form-control form-control-sm" placeholder="Specify other financial support...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sources Group -->
                            <div class="mb-5">
                                <label class="form-label fw-bold d-block mb-3">What are your sources of information about DMSF?</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="info_parents"> <label class="form-check-label small">Parents</label></div>
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="info_family_friends"> <label class="form-check-label small">Family Friends</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="info_student_friends"> <label class="form-check-label small">DMSF Students</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="info_siblings"> <label class="form-check-label small">Brother/Sister</label></div>
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="info_teachers"> <label class="form-check-label small">College Teachers</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="info_newspaper"> <label class="form-check-label small">Newspaper ad</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="info_convocation"> <label class="form-check-label small">Convocation</label></div>
                                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="info_internet"> <label class="form-check-label small">Internet</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="info_own_effort"> <label class="form-check-label small">Own Effort</label></div>
                                        <div class="mt-3">
                                            <label class="form-label x-small text-muted mb-1">Others (please specify):</label>
                                            <input type="text" name="info_others" class="form-control form-control-sm" placeholder="Others...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Staying Place Group -->
                            <div class="mb-4">
                                <label class="form-label fw-bold d-block mb-2">If you will be studying here in Davao City, where will you most likely be staying?</label>
                                <select name="staying_place" class="form-select" onchange="this.value=='Others'?document.getElementById('stayingOther').style.display='block':document.getElementById('stayingOther').style.display='none'">
                                    <option value="">Select...</option>
                                    <option value="With Parents">With Parents</option>
                                    <option value="Boarding house/dormitory">Boarding house/dormitory</option>
                                    <option value="Apartment with relatives">Apartment with relatives</option>
                                    <option value="house of relatives">House of relatives</option>
                                    <option value="Others">Others</option>
                                </select>
                                <div id="stayingOther" class="mt-3 shadow-sm p-3 bg-white rounded-3 border" style="display: none;">
                                    <label class="form-label x-small mb-1">Specify staying place:</label>
                                    <input type="text" name="staying_place_others" class="form-control" placeholder="Please specify...">
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
    <script>
        // --- BROWSER AUTO-SAVE FEATURE ---
        const formId = 'admissionStep2';
        const form = document.getElementById(formId);
        
        window.addEventListener('load', () => {
            const savedData = localStorage.getItem(formId);
            if (savedData) {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const field = form.elements[key];
                    if (field) {
                        if (field.type === 'radio' || field.type === 'checkbox') {
                            if (field.value === data[key]) field.checked = true;
                        } else {
                            field.value = data[key];
                        }
                    }
                });
                // Trigger visibility updates for conditional fields
                if(document.getElementById('vaxYes').checked) document.getElementById('vaxDetails').style.display='block';
                if(document.getElementById('chronicYes').checked) document.getElementById('chronicDetails').style.display='block';
                if(document.getElementById('disabilityYes').checked) document.getElementById('disabilityDetails').style.display='block';
                if(document.getElementById('scholarshipCheck').checked) document.getElementById('scholarshipDetail').style.display='block';
                if(form.elements['staying_place'].value === 'Others') document.getElementById('stayingOther').style.display='block';
            }
        });

        form.addEventListener('input', () => {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (!(value instanceof File)) data[key] = value;
            });
            localStorage.setItem(formId, JSON.stringify(data));
        });
    </script>
</body>

</html>
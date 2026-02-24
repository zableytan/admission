<?php
session_start();
require 'db.php';

$message = '';

// 1. GET LOGIC: Validate Application ID
if (!isset($_GET['app_id']) || !is_numeric($_GET['app_id'])) {
    header("Location: apply.php");
    exit;
}

$app_id = $_GET['app_id'];

// Fetch application basics to display the applicant's name
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
    // --- Collect Family Info ---
    $father_name = filter_input(INPUT_POST, 'father_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $mother_name = filter_input(INPUT_POST, 'mother_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $father_occupation = filter_input(INPUT_POST, 'father_occupation', FILTER_SANITIZE_SPECIAL_CHARS);
    $mother_occupation = filter_input(INPUT_POST, 'mother_occupation', FILTER_SANITIZE_SPECIAL_CHARS);
    $family_address = filter_input(INPUT_POST, 'family_address', FILTER_SANITIZE_SPECIAL_CHARS);
    $family_contact_no = filter_input(INPUT_POST, 'family_contact_no', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Income Sources (Convert checkbox state to boolean) ---
    $income_salaries = isset($_POST['income_salaries']) ? 1 : 0;
    $income_farm = isset($_POST['income_farm']) ? 1 : 0;
    $income_commissions = isset($_POST['income_commissions']) ? 1 : 0;
    $income_rentals = isset($_POST['income_rentals']) ? 1 : 0;
    $income_pension = isset($_POST['income_pension']) ? 1 : 0;
    $income_business = isset($_POST['income_business']) ? 1 : 0;
    $income_others = filter_input(INPUT_POST, 'income_others', FILTER_SANITIZE_SPECIAL_CHARS);

    $total_family_income = filter_input(INPUT_POST, 'total_family_income', FILTER_VALIDATE_FLOAT);
    if ($total_family_income === false) $total_family_income = null;

    $family_assets = filter_input(INPUT_POST, 'family_assets', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- DMSF Affiliation ---
    $parent_dmsf_grad_flag = (isset($_POST['parent_dmsf_grad_flag']) && $_POST['parent_dmsf_grad_flag'] == 'YES') ? 1 : 0;
    $parent_dmsf_course_year = filter_input(INPUT_POST, 'parent_dmsf_course_year', FILTER_SANITIZE_SPECIAL_CHARS);
    $parent_dmsf_teaching_flag = (isset($_POST['parent_dmsf_teaching_flag']) && $_POST['parent_dmsf_teaching_flag'] == 'YES') ? 1 : 0;
    $parent_dmsf_teaching_years = filter_input(INPUT_POST, 'parent_dmsf_teaching_years', FILTER_VALIDATE_INT);
    if ($parent_dmsf_teaching_years === false) $parent_dmsf_teaching_years = null;

    // --- Siblings ---
    $num_brothers = filter_input(INPUT_POST, 'num_brothers', FILTER_VALIDATE_INT);
    if ($num_brothers === false) $num_brothers = null;

    $num_sisters = filter_input(INPUT_POST, 'num_sisters', FILTER_VALIDATE_INT);
    if ($num_sisters === false) $num_sisters = null;

    $brothers_hs = filter_input(INPUT_POST, 'brothers_hs', FILTER_VALIDATE_INT);
    if ($brothers_hs === false) $brothers_hs = null;

    $sisters_hs = filter_input(INPUT_POST, 'sisters_hs', FILTER_VALIDATE_INT);
    if ($sisters_hs === false) $sisters_hs = null;

    $brothers_college = filter_input(INPUT_POST, 'brothers_college', FILTER_VALIDATE_INT);
    if ($brothers_college === false) $brothers_college = null;

    $sisters_college = filter_input(INPUT_POST, 'sisters_college', FILTER_VALIDATE_INT);
    if ($sisters_college === false) $sisters_college = null;

    $siblings_middle_school = filter_input(INPUT_POST, 'siblings_middle_school', FILTER_VALIDATE_INT);
    if ($siblings_middle_school === false) $siblings_middle_school = null;

    $brothers_courses = filter_input(INPUT_POST, 'brothers_courses', FILTER_SANITIZE_SPECIAL_CHARS);
    $sisters_courses = filter_input(INPUT_POST, 'sisters_courses', FILTER_SANITIZE_SPECIAL_CHARS);

    $sibling_dmsf_flag = (isset($_POST['sibling_dmsf_flag']) && $_POST['sibling_dmsf_flag'] == 'YES') ? 1 : 0;
    $sibling_dmsf_details = filter_input(INPUT_POST, 'sibling_dmsf_details', FILTER_SANITIZE_SPECIAL_CHARS);

    // Prepare the comprehensive UPDATE statement
    $sql = "UPDATE applications SET 
            father_name=?, mother_name=?, father_occupation=?, mother_occupation=?, family_address=?, family_contact_no=?, 
            income_salaries=?, income_farm=?, income_commissions=?, income_rentals=?, income_pension=?, income_business=?, 
            income_others=?, total_family_income=?, family_assets=?, parent_dmsf_grad_flag=?, parent_dmsf_course_year=?, 
            parent_dmsf_teaching_flag=?, parent_dmsf_teaching_years=?, num_brothers=?, num_sisters=?, brothers_hs=?, 
            sisters_hs=?, brothers_college=?, sisters_college=?, brothers_courses=?, sisters_courses=?, siblings_middle_school=?,
            sibling_dmsf_flag=?, sibling_dmsf_details=? 
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    // Execute the update
    if (
        $stmt->execute([
            $father_name,
            $mother_name,
            $father_occupation,
            $mother_occupation,
            $family_address,
            $family_contact_no,
            $income_salaries,
            $income_farm,
            $income_commissions,
            $income_rentals,
            $income_pension,
            $income_business,
            $income_others,
            $total_family_income,
            $family_assets,
            $parent_dmsf_grad_flag,
            $parent_dmsf_course_year,
            $parent_dmsf_teaching_flag,
            $parent_dmsf_teaching_years,
            $num_brothers,
            $num_sisters,
            $brothers_hs,
            $sisters_hs,
            $brothers_college,
            $sisters_college,
            $brothers_courses,
            $sisters_courses,
            $siblings_middle_school,
            $sibling_dmsf_flag,
            $sibling_dmsf_details,
            $app_id
        ])
    ) {
        // Successful update! Redirect to Step 4: Educational Background and Intent
        header("Location: educational_intent.php?app_id=$app_id");
        exit;
    } else {
        $message = "Error: Could not save family data. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 3: Family Background | Admission</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #198754;
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
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
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
            background-color: #157347;
            color: white;
        }

        .applicant-info {
            background: #e8f5e9;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid #198754;
        }

        .income-checkbox-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
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
                            <h3 class="mb-0 fw-bold">Step 3 of 5: Family Background</h3>
                            <span class="badge bg-white text-success px-3 py-2">Admission Process</span>
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

                            <h5 class="section-title">A. Parent Information</h5>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Father's Name</label>
                                    <input type="text" name="father_name" class="form-control" placeholder="Full Name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mother's Name</label>
                                    <input type="text" name="mother_name" class="form-control" placeholder="Full Name">
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Father's Occupation</label>
                                    <input type="text" name="father_occupation" class="form-control"
                                        placeholder="Occupation">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mother's Occupation</label>
                                    <input type="text" name="mother_occupation" class="form-control"
                                        placeholder="Occupation">
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label">Family Address</label>
                                    <input type="text" name="family_address" class="form-control"
                                        placeholder="Current Residence">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contact No.</label>
                                    <input type="text" name="family_contact_no" class="form-control"
                                        placeholder="Tel / Cellphone">
                                </div>
                            </div>

                            <h5 class="section-title mt-2">B. Family Income Sources</h5>
                            <p class="text-muted mb-3 small">What is/are their source(s) of income? (Check all that
                                apply)</p>
                            <div class="income-checkbox-group">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="income_salaries"> <label class="form-check-label">Salaries</label>
                                        </div>
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="income_commissions"> <label
                                                class="form-check-label">Commissions</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox"
                                                name="income_pension"> <label class="form-check-label">Pension</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="income_farm"> <label class="form-check-label">Income from
                                                farm</label></div>
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="income_rentals"> <label class="form-check-label">Income from
                                                rentals</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox"
                                                name="income_business"> <label class="form-check-label">Income from
                                                business</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Others:</label>
                                        <input type="text" name="income_others" class="form-control"
                                            placeholder="Specify...">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Approximate total income of the Family</label>
                                <input type="number" step="0.01" name="total_family_income" class="form-control"
                                    placeholder="Combined annual income">
                                <div class="text-muted small mt-2">Include parents, unmarried siblings, and family
                                    enterprises.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Family Assets</label>
                                <textarea name="family_assets" class="form-control" rows="2"
                                    placeholder="e.g., House, car, land, etc."></textarea>
                            </div>

                            <h5 class="section-title mt-2">C. DMSF Affiliation</h5>

                            <div class="mb-4 border-0 bg-light p-4 rounded-3 text-secondary">
                                <label class="form-label fw-bold d-block mb-3 text-dark">Is your parent a graduate of
                                    DMSF?</label>
                                <div class="form-check form-check-inline me-4">
                                    <input class="form-check-input" type="radio" name="parent_dmsf_grad_flag"
                                        id="parentGradYes" value="YES"
                                        onclick="document.getElementById('parentGradDetails').style.display='block'">
                                    <label class="form-check-label" for="parentGradYes">YES</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="parent_dmsf_grad_flag"
                                        id="parentGradNo" value="NO" checked
                                        onclick="document.getElementById('parentGradDetails').style.display='none'">
                                    <label class="form-check-label" for="parentGradNo">NO</label>
                                </div>
                                <div class="mt-3" id="parentGradDetails" style="display: none;">
                                    <input type="text" name="parent_dmsf_course_year" class="form-control"
                                        placeholder="What course and year graduated?">
                                </div>
                            </div>

                            <div class="mb-4 border-0 bg-light p-4 rounded-3 text-secondary">
                                <label class="form-label fw-bold d-block mb-3 text-dark">Is your parent teaching in
                                    DMSF?</label>
                                <div class="form-check form-check-inline me-4">
                                    <input class="form-check-input" type="radio" name="parent_dmsf_teaching_flag"
                                        id="parentTeachYes" value="YES"
                                        onclick="document.getElementById('parentTeachDetails').style.display='block'">
                                    <label class="form-check-label" for="parentTeachYes">YES</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="parent_dmsf_teaching_flag"
                                        id="parentTeachNo" value="NO" checked
                                        onclick="document.getElementById('parentTeachDetails').style.display='none'">
                                    <label class="form-check-label" for="parentTeachNo">NO</label>
                                </div>
                                <div class="mt-3" id="parentTeachDetails" style="display: none;">
                                    <input type="number" name="parent_dmsf_teaching_years" class="form-control"
                                        placeholder="How many years?">
                                </div>
                            </div>

                            <h5 class="section-title mt-2">D. Sibling Information</h5>

                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">No. of Brothers</label>
                                    <input type="number" name="num_brothers" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">No. of Sisters</label>
                                    <input type="number" name="num_sisters" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">In Middle School</label>
                                    <input type="number" name="siblings_middle_school" class="form-control"
                                        placeholder="0">
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Brothers in High School</label>
                                    <input type="number" name="brothers_hs" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sisters in High School</label>
                                    <input type="number" name="sisters_hs" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Brothers in College</label>
                                    <input type="number" name="brothers_college" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sisters in College</label>
                                    <input type="number" name="sisters_college" class="form-control" placeholder="0">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Courses of Brother/s</label>
                                <textarea name="brothers_courses" class="form-control" rows="2"
                                    placeholder="List course and status (e.g., BS Biology, Graduated)"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Courses of Sister/s</label>
                                <textarea name="sisters_courses" class="form-control" rows="2"
                                    placeholder="List course and status (e.g., BS Nursing, 3rd Year)"></textarea>
                            </div>

                            <div class="mb-4 border-0 bg-light p-4 rounded-3 text-secondary">
                                <label class="form-label fw-bold d-block mb-3 text-dark">Sibling(s) enrolled in
                                    DMSF?</label>
                                <div class="form-check form-check-inline me-4">
                                    <input class="form-check-input" type="radio" name="sibling_dmsf_flag"
                                        id="siblingDmsfYes" value="YES"
                                        onclick="document.getElementById('siblingDmsfDetails').style.display='block'">
                                    <label class="form-check-label" for="siblingDmsfYes">YES</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="sibling_dmsf_flag"
                                        id="siblingDmsfNo" value="NO" checked
                                        onclick="document.getElementById('siblingDmsfDetails').style.display='none'">
                                    <label class="form-check-label" for="siblingDmsfNo">NO</label>
                                </div>
                                <div class="mt-3" id="siblingDmsfDetails" style="display: none;">
                                    <textarea name="sibling_dmsf_details" class="form-control" rows="2"
                                        placeholder="Write their Names and Year Level..."></textarea>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-step w-100 shadow-sm">
                                    Proceed to Step 4: Educational Background
                                </button>
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
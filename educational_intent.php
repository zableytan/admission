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

// Determine if this is a Medicine application OR if multiple colleges are selected
$is_multiple = (strpos($application['college'], ',') !== false);
$is_medicine = (strpos($application['college'], 'Medicine') !== false) || $is_multiple;

// Dynamic labels based on college selection
$program_label = $is_medicine ? "Medicine" : $application['college'];
$degree_label = $is_medicine ? "medical degree" : "degree in " . $application['college'];
$school_label = $is_medicine ? "Medical School" : "College";


// 2. POST LOGIC: Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Education & Honors ---
    $primary_school = filter_input(INPUT_POST, 'primary_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $primary_location = filter_input(INPUT_POST, 'primary_location', FILTER_SANITIZE_SPECIAL_CHARS);
    $primary_dates = filter_input(INPUT_POST, 'primary_dates', FILTER_SANITIZE_SPECIAL_CHARS);
    $secondary_school = filter_input(INPUT_POST, 'secondary_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $secondary_location = filter_input(INPUT_POST, 'secondary_location', FILTER_SANITIZE_SPECIAL_CHARS);
    $secondary_dates = filter_input(INPUT_POST, 'secondary_dates', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Tertiary Background (New) ---
    $tertiary_name = filter_input(INPUT_POST, 'tertiary_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_region = filter_input(INPUT_POST, 'tertiary_region', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_address = filter_input(INPUT_POST, 'tertiary_address', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_school_type = filter_input(INPUT_POST, 'tertiary_school_type', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_course_type = filter_input(INPUT_POST, 'tertiary_course_type', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_degree = filter_input(INPUT_POST, 'tertiary_degree', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_gwa = filter_input(INPUT_POST, 'tertiary_gwa', FILTER_SANITIZE_SPECIAL_CHARS);
    $tertiary_honors = filter_input(INPUT_POST, 'tertiary_honors', FILTER_SANITIZE_SPECIAL_CHARS);
    $self_rating = filter_input(INPUT_POST, 'self_rating', FILTER_VALIDATE_INT);
    if ($self_rating === false) $self_rating = null;

    $hs_honors_flag = (isset($_POST['hs_honors_flag']) && $_POST['hs_honors_flag'] == 'YES') ? 1 : 0;
    $hs_honor_type = $hs_honors_flag ? filter_input(INPUT_POST, 'hs_honor_type', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    if ($hs_honor_type == 'Others') {
        $hs_honor_type = filter_input(INPUT_POST, 'hs_honor_type_other', FILTER_SANITIZE_SPECIAL_CHARS);
    }

    $college_name_address = filter_input(INPUT_POST, 'college_name_address', FILTER_SANITIZE_SPECIAL_CHARS);
    $degree_obtained = filter_input(INPUT_POST, 'degree_obtained', FILTER_SANITIZE_SPECIAL_CHARS);
    $date_of_graduation = filter_input(INPUT_POST, 'date_of_graduation', FILTER_SANITIZE_SPECIAL_CHARS);
    $college_honors_flag = (isset($_POST['college_honors_flag']) && $_POST['college_honors_flag'] == 'YES') ? 1 : 0;
    $college_honors_list = $college_honors_flag ? filter_input(INPUT_POST, 'college_honors_list', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Board Exam ---
    $board_profession = filter_input(INPUT_POST, 'board_profession', FILTER_SANITIZE_SPECIAL_CHARS);
    $board_exam_date = filter_input(INPUT_POST, 'board_exam_date', FILTER_SANITIZE_SPECIAL_CHARS);
    $board_rating = filter_input(INPUT_POST, 'board_rating', FILTER_VALIDATE_FLOAT);
    if ($board_rating === false)
        $board_rating = null;

    // --- Post-Grad & Gap Activity ---
    $post_grad_course = filter_input(INPUT_POST, 'post_grad_course', FILTER_SANITIZE_SPECIAL_CHARS);
    $post_grad_school = filter_input(INPUT_POST, 'post_grad_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $post_grad_date = filter_input(INPUT_POST, 'post_grad_date', FILTER_SANITIZE_SPECIAL_CHARS);

    $post_grad_activity = filter_input(INPUT_POST, 'post_grad_activity', FILTER_SANITIZE_SPECIAL_CHARS); // Radio button value
    $activity_took_another_course = ($post_grad_activity == 'Took another course') ? filter_input(INPUT_POST, 'activity_took_another_course', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Employee Details ---
    $employee_work = ($post_grad_activity == 'Worked as employee') ? filter_input(INPUT_POST, 'employee_work', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $employee_position = ($post_grad_activity == 'Worked as employee') ? filter_input(INPUT_POST, 'employee_position', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $employee_years = ($post_grad_activity == 'Worked as employee') ? filter_input(INPUT_POST, 'employee_years', FILTER_VALIDATE_INT) : null;
    if ($employee_years === false)
        $employee_years = null;

    $trainings_seminars = ($post_grad_activity == 'Worked as employee') ? filter_input(INPUT_POST, 'trainings_seminars', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Interests and Skills ---
    $interest_school_orgs = isset($_POST['interest_school_orgs']) ? 1 : 0;
    $interest_religious = isset($_POST['interest_religious']) ? 1 : 0;
    $interest_sociocivic = isset($_POST['interest_sociocivic']) ? 1 : 0;
    $interest_sports = isset($_POST['interest_sports']) ? 1 : 0;
    $interest_music_vocal = isset($_POST['interest_music_vocal']) ? 1 : 0;
    $interest_dance = isset($_POST['interest_dance']) ? 1 : 0;
    $interest_creative_writing = isset($_POST['interest_creative_writing']) ? 1 : 0;
    $interest_philately = isset($_POST['interest_philately']) ? 1 : 0;
    $interest_others = filter_input(INPUT_POST, 'interest_others', FILTER_SANITIZE_SPECIAL_CHARS);
    $other_skills_work_exp = filter_input(INPUT_POST, 'other_skills_work_exp', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Learning & Behavior ---
    $learning_style = filter_input(INPUT_POST, 'learning_style', FILTER_SANITIZE_SPECIAL_CHARS);
    $stress_level = filter_input(INPUT_POST, 'stress_level', FILTER_VALIDATE_INT);
    $stress_source = filter_input(INPUT_POST, 'stress_source', FILTER_SANITIZE_SPECIAL_CHARS);
    $coping_style = filter_input(INPUT_POST, 'coping_style', FILTER_SANITIZE_SPECIAL_CHARS);
    $extracurricular_involvement = filter_input(INPUT_POST, 'extracurricular_involvement', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- Admission History ---
    $first_time_md_flag = (isset($_POST['first_time_md_flag']) && $_POST['first_time_md_flag'] == 'YES') ? 1 : 0;
    $prev_app_status = !$first_time_md_flag ? filter_input(INPUT_POST, 'prev_app_status', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $prev_med_school_name = ($prev_app_status == 'Accepted and enrolled at' || $prev_app_status == 'Accepted but did not enroll at') ? filter_input(INPUT_POST, 'prev_med_school_name', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    $staying_place_others = null; // Moved to personal_data.php

    // --- Preferences ---
    $pref_first_med_school = filter_input(INPUT_POST, 'pref_first_med_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $pref_second_med_school = filter_input(INPUT_POST, 'pref_second_med_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $pref_third_med_school = filter_input(INPUT_POST, 'pref_third_med_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $pref_other_med_schools = filter_input(INPUT_POST, 'pref_other_med_schools', FILTER_SANITIZE_SPECIAL_CHARS);

    $application_essay = filter_input(INPUT_POST, 'application_essay', FILTER_SANITIZE_SPECIAL_CHARS);


    // Prepare the massive UPDATE statement
    // (Due to the sheer number of fields, this SQL is split for readability, but must be run as one string)
    $sql = "UPDATE applications SET 
        primary_school=?, primary_location=?, primary_dates=?, secondary_school=?, secondary_location=?, secondary_dates=?, 
        tertiary_name=?, tertiary_region=?, tertiary_address=?, tertiary_school_type=?, tertiary_course_type=?, tertiary_degree=?, tertiary_gwa=?, tertiary_honors=?, self_rating=?,
        hs_honors_flag=?, hs_honor_type=?, college_name_address=?, degree_obtained=?, date_of_graduation=?, 
        college_honors_flag=?, college_honors_list=?, board_profession=?, board_exam_date=?, board_rating=?, 
        post_grad_course=?, post_grad_school=?, post_grad_date=?, post_grad_activity=?, activity_took_another_course=?, 
        employee_work=?, employee_position=?, employee_years=?, trainings_seminars=?, interest_school_orgs=?, 
        interest_religious=?, interest_sociocivic=?, interest_sports=?, interest_music_vocal=?, interest_dance=?, 
        interest_creative_writing=?, interest_philately=?, interest_others=?, other_skills_work_exp=?, 
        learning_style=?, stress_level=?, stress_source=?, coping_style=?, extracurricular_involvement=?,
        first_time_md_flag=?, 
        prev_app_status=?, prev_med_school_name=?, 
        pref_first_med_school=?, pref_second_med_school=?, pref_third_med_school=?, 
        pref_other_med_schools=?, application_essay=?
        WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    // Execute the update
    if (
        $stmt->execute([
            $primary_school,
            $primary_location,
            $primary_dates,
            $secondary_school,
            $secondary_location,
            $secondary_dates,
            $tertiary_name,
            $tertiary_region,
            $tertiary_address,
            $tertiary_school_type,
            $tertiary_course_type,
            $tertiary_degree,
            $tertiary_gwa,
            $tertiary_honors,
            $self_rating,
            $hs_honors_flag,
            $hs_honor_type,
            $college_name_address,
            $degree_obtained,
            $date_of_graduation,
            $college_honors_flag,
            $college_honors_list,
            $board_profession,
            $board_exam_date,
            $board_rating,
            $post_grad_course,
            $post_grad_school,
            $post_grad_date,
            $post_grad_activity,
            $activity_took_another_course,
            $employee_work,
            $employee_position,
            $employee_years,
            $trainings_seminars,
            $interest_school_orgs,
            $interest_religious,
            $interest_sociocivic,
            $interest_sports,
            $interest_music_vocal,
            $interest_dance,
            $interest_creative_writing,
            $interest_philately,
            $interest_others,
            $other_skills_work_exp,
            $learning_style,
            $stress_level,
            $stress_source,
            $coping_style,
            $extracurricular_involvement,
            $first_time_md_flag,
            $prev_app_status,
            $prev_med_school_name,
            $pref_first_med_school,
            $pref_second_med_school,
            $pref_third_med_school,
            $pref_other_med_schools,
            $application_essay,
            $app_id
        ])
    ) {
        // Successful update! Redirect to Step 5: Upload Documents
        header("Location: upload_docs.php?app_id=$app_id");
        exit;
    } else {
        $message = "Error: Could not save educational and intent data. Please try again.";
        // You might log $stmt->errorInfo() here for detailed debugging
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4: Educational Background & Intent | Admission</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            box-shadow: var-card-shadow;
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
            font-size: 0.9rem;
            color: #4b4b4b;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            transition: all 0.2s;
            background-color: #fcfcfc;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(25, 97, 153, 0.25);
        }

        .applicant-info {
            background: #f4f7fe;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
        }

        .btn-step {
            padding: 15px 30px;
            font-weight: 700;
            border-radius: 10px;
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

        .info-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .table-custom {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .table-custom th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #6c757d;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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

<!-- Contact Button & Modal -->
<button type="button" class="btn btn-primary rounded-circle shadow" data-bs-toggle="modal" data-bs-target="#contactModal" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1050; background-color: #196199; border: none; display: flex; align-items: center; justify-content: center;">
    <i class="bi bi-chat-dots-fill fs-3"></i>
</button>

<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #196199;">
        <h5 class="modal-title fw-bold" id="contactModalLabel"><i class="bi bi-envelope-fill me-2"></i>Contact Admissions</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-4 small">If there are any concerns or need of improvement for this tool, please email us at the appropriate department below.</p>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <strong>Medicine</strong>
                <a href="mailto:admission.med@dmsf.edu.ph" class="text-decoration-none rounded px-2 py-1 bg-light small"><i class="bi bi-envelope me-1"></i> admission.med@dmsf.edu.ph</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <strong>Nursing</strong>
                <a href="mailto:admission.nursing@dmsf.edu.ph" class="text-decoration-none rounded px-2 py-1 bg-light small"><i class="bi bi-envelope me-1"></i> admission.nursing@dmsf.edu.ph</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <strong>Dentistry</strong>
                <a href="mailto:admission.dentistry@dmsf.edu.ph" class="text-decoration-none rounded px-2 py-1 bg-light small"><i class="bi bi-envelope me-1"></i> admission.dentistry@dmsf.edu.ph</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <strong>Midwifery</strong>
                <a href="mailto:admission.midwifery@dmsf.edu.ph" class="text-decoration-none rounded px-2 py-1 bg-light small"><i class="bi bi-envelope me-1"></i> admission.midwifery@dmsf.edu.ph</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                <strong>Biology</strong>
                <a href="mailto:admission.biology@dmsf.edu.ph" class="text-decoration-none rounded px-2 py-1 bg-light small"><i class="bi bi-envelope me-1"></i> admission.biology@dmsf.edu.ph</a>
            </li>
        </ul>
      </div>
    </div>
  </div>
</div>

    <div class="container py-5">
        <div class="logo-container">
            <img src="DMSF_Logo.png" alt="DMSF Logo" class="logo-img">
            <h2 class="fw-bold">Davao Medical School Foundation</h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-card shadow">
                    <div class="card-header-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0 fw-bold">Step 4 of 5: Educational Background & Intent</h3>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-white text-danger px-3 py-2">Admission Process</span>
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
                            <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">

                            <h5 class="section-title">A. Educational Background</h5>

                            <div class="table-responsive mb-4">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 20%">Level</th>
                                            <th style="width: 35%">School Attended</th>
                                            <th style="width: 25%">Location</th>
                                            <th style="width: 20%">Dates (e.g., 2005-2011)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted small">ELEMENTARY</td>
                                            <td><input type="text" name="primary_school" class="form-control"
                                                    placeholder="Name of school"></td>
                                            <td><input type="text" name="primary_location" class="form-control"
                                                    placeholder="City/Province"></td>
                                            <td><input type="text" name="primary_dates" class="form-control"
                                                    placeholder="Year Range"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted small">SECONDARY</td>
                                            <td><input type="text" name="secondary_school" class="form-control"
                                                    placeholder="Name of school"></td>
                                            <td><input type="text" name="secondary_location" class="form-control"
                                                    placeholder="City/Province"></td>
                                            <td><input type="text" name="secondary_dates" class="form-control"
                                                    placeholder="Year Range"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($is_medicine): ?>
                                <h5 class="section-title mt-4">Tertiary Background</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label">Previous College Name</label>
                                        <input type="text" name="tertiary_name" class="form-control" placeholder="Enter Full School Name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Previous College Region</label>
                                        <input type="text" name="tertiary_region" class="form-control" placeholder="Region">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Previous College Address</label>
                                        <input type="text" name="tertiary_address" class="form-control" placeholder="Complete Address">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Previous School Type</label>
                                        <select name="tertiary_school_type" class="form-select">
                                            <option value="">Select Type...</option>
                                            <option value="Government">Government</option>
                                            <option value="Private">Private</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type of Course</label>
                                        <select name="tertiary_course_type" class="form-select">
                                            <option value="">Select Category...</option>
                                            <option value="Medical">Medical</option>
                                            <option value="Non-medical">Non-medical</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Course/Degree Taken</label>
                                        <input type="text" name="tertiary_degree" class="form-control" placeholder="e.g. BS Biology">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">General Weighted Average (GWA)</label>
                                        <input type="text" name="tertiary_gwa" class="form-control" placeholder="e.g. 1.75">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Academic Honors (College)</label>
                                        <input type="text" name="tertiary_honors" class="form-control" placeholder="Honors received">
                                    </div>
                                </div>

                                <h5 class="section-title mt-4">Self-Assessment</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Self-Rating of Academic Performance</label>
                                        <p class="small text-muted mb-2">How would you rate your overall academic performance so far? (1 = Poor, 5 = Excellent)</p>
                                        <div class="d-flex justify-content-between px-2" style="max-width: 400px;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <div class="form-check form-check-inline text-center">
                                                    <input class="form-check-input d-block mx-auto mb-1" type="radio" name="self_rating" id="rating<?= $i ?>" value="<?= $i ?>" required>
                                                    <label class="form-check-label small" for="rating<?= $i ?>"><?= $i ?></label>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="d-flex justify-content-between px-2 mt-1" style="max-width: 400px;">
                                            <span class="small text-muted italic">Poor</span>
                                            <span class="small text-muted italic">Excellent</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Have you earned academic honors in high
                                    school?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honors_flag" id="hsYes"
                                            value="YES"
                                            onclick="document.getElementById('hsHonorType').style.display='block'">
                                        <label class="form-check-label" for="hsYes">Yes, I have</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honors_flag" id="hsNo"
                                            value="NO" checked
                                            onclick="document.getElementById('hsHonorType').style.display='none'; toggleHsOther(false)">
                                        <label class="form-check-label" for="hsNo">No, I haven't</label>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 bg-white rounded border" id="hsHonorType" style="display: none;">
                                    <label class="form-label small text-muted mb-2">If YES, please specify your
                                        rank:</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="hs_honor_type"
                                                value="Valedictorian" onclick="toggleHsOther(false)"> Valedictorian
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="hs_honor_type"
                                                value="Salutatorian" onclick="toggleHsOther(false)"> Salutatorian
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="hs_honor_type"
                                                value="First Honor" onclick="toggleHsOther(false)"> First Honor
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="hs_honor_type"
                                                value="Second Honor" onclick="toggleHsOther(false)"> Second Honor
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="hs_honor_type"
                                                value="Others" onclick="toggleHsOther(true)"> Others
                                        </div>
                                    </div>
                                    <div id="hsOtherSpec" class="mt-3" style="display: none;">
                                        <input type="text" name="hs_honor_type_other" class="form-control" placeholder="Please specify...">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Name and Address of the School Granting the Degree in
                                        College:</label>
                                    <input type="text" name="college_name_address" class="form-control"
                                        placeholder="Full name and location of university">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Degree Obtained:</label>
                                    <input type="text" name="degree_obtained" class="form-control"
                                        placeholder="e.g. BS Biology">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Graduation:</label>
                                    <input type="date" name="date_of_graduation" class="form-control">
                                </div>
                            </div>

                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Have you earned academic honors in
                                    college?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="college_honors_flag"
                                            id="collegeYes" value="YES"
                                            onclick="document.getElementById('collegeHonorsList').style.display='block'">
                                        <label class="form-check-label" for="collegeYes">Yes, I have</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="college_honors_flag"
                                            id="collegeNo" value="NO" checked
                                            onclick="document.getElementById('collegeHonorsList').style.display='none'">
                                        <label class="form-check-label" for="collegeNo">No, I haven't</label>
                                    </div>
                                </div>
                                <div class="mt-3" id="collegeHonorsList" style="display: none;">
                                    <textarea name="college_honors_list" class="form-control" rows="2"
                                        placeholder="List your honors (e.g., Cum Laude, Dean's Lister, etc.)"></textarea>
                                </div>
                            </div>

                            <h5 class="section-title">Board Examination <span
                                    class="text-muted lowercase fw-normal ms-2">(if applicable)</span></h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Profession:</label>
                                    <input type="text" name="board_profession" class="form-control"
                                        placeholder="e.g. Registered Nurse">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Examination Date:</label>
                                    <input type="date" name="board_exam_date" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Board Rating (%):</label>
                                    <input type="number" step="0.01" name="board_rating" class="form-control"
                                        placeholder="00.00">
                                </div>
                            </div>

                            <h5 class="section-title">Post Graduate Studies <span
                                    class="text-muted lowercase fw-normal ms-2">(if applicable)</span></h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Course:</label>
                                    <input type="text" name="post_grad_course" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">School:</label>
                                    <input type="text" name="post_grad_school" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Graduation:</label>
                                    <input type="date" name="post_grad_date" class="form-control">
                                </div>
                            </div>
                            <h5 class="section-title">B. Gap Activity / Employment <span
                                    class="text-muted lowercase fw-normal ms-2">(if applicable)</span></h5>
                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Activities between college graduation and
                                    this application:</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="post_grad_activity"
                                                id="actNone" value="None" checked onclick="toggleGapDetails('none')">
                                            <label class="form-check-label" for="actNone">None (Proceeded
                                                directly)</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="post_grad_activity"
                                                id="actCourse" value="Took another course"
                                                onclick="toggleGapDetails('course')">
                                            <label class="form-check-label" for="actCourse">Took another course</label>
                                        </div>
                                        <div id="courseInput" class="ms-4 mt-2" style="display: none;">
                                            <input type="text" name="activity_took_another_course"
                                                class="form-control form-control-sm"
                                                placeholder="Please specify course...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="post_grad_activity"
                                                id="actEmployee" value="Worked as employee"
                                                onclick="toggleGapDetails('employee')">
                                            <label class="form-check-label" for="actEmployee">Worked as employee</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="post_grad_activity"
                                                id="actOther" value="Other" onclick="toggleGapDetails('none')">
                                            <label class="form-check-label" for="actOther">Other activities</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="employeeDetails" class="mt-4 p-3 bg-white rounded border"
                                    style="display: none;">
                                    <h6 class="fw-bold mb-3 small text-danger">Employment Details</h6>
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label small">Company/Employer:</label>
                                            <input type="text" name="employee_work" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Position:</label>
                                            <input type="text" name="employee_position" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Years/Months:</label>
                                            <input type="text" name="employee_years" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Special Trainings/Seminars Attended:</label>
                                            <textarea name="trainings_seminars" class="form-control" rows="2"
                                                placeholder="List relevant professional development..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title">C. Interests and Skills</h5>
                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Extracurricular Interests:</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="interest_school_orgs"> <label
                                                class="form-check-label small">School Organizations</label></div>
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="interest_religious"> <label
                                                class="form-check-label small">Religious Activities</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox"
                                                name="interest_sociocivic"> <label
                                                class="form-check-label small">Socio-Civic Work</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="interest_sports"> <label
                                                class="form-check-label small">Sports</label></div>
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="interest_music_vocal"> <label
                                                class="form-check-label small">Music/Vocal</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox"
                                                name="interest_dance"> <label
                                                class="form-check-label small">Dance</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="interest_creative_writing"> <label
                                                class="form-check-label small">Creative Writing</label></div>
                                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
                                                name="interest_philately"> <label
                                                class="form-check-label small">Arts/Photography</label></div>
                                        <div class="mt-2">
                                            <input type="text" name="interest_others"
                                                class="form-control form-control-sm" placeholder="Others (specify)...">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="form-label">Other Skills and Work Experiences:</label>
                                    <textarea name="other_skills_work_exp" class="form-control" rows="2"
                                        placeholder="Tell us about any other unique skills..."></textarea>
                                </div>
                            </div>

                            <h5 class="section-title">D. Campus Engagement & Learning Profile</h5>
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Learning Style Preference</label>
                                        <div class="row g-2">
                                            <?php 
                                            $styles = ['Visual', 'Auditory', 'Kinesthetic', 'Reading/Writing', 'Mixed'];
                                            foreach($styles as $style): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="learning_style" id="style<?= $style ?>" value="<?= $style ?>">
                                                    <label class="form-check-label x-small" for="style<?= $style ?>"><?= $style ?></label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Extracurricular Involvement</label>
                                        <div class="mb-3">
                                            <?php 
                                            $levels = ['High (Leadership Role)', 'Moderate', 'Low', 'None'];
                                            foreach($levels as $level): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="extracurricular_involvement" id="level<?= str_replace(' ', '', $level) ?>" value="<?= $level ?>">
                                                <label class="form-check-label x-small" for="level<?= str_replace(' ', '', $level) ?>"><?= $level ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Student Stress Profile</label>
                                        <div class="mb-4">
                                            <label class="form-label x-small mb-3">Overall Stress Level (1-5):</label>
                                            <div class="d-flex justify-content-between px-2">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                <div class="text-center">
                                                    <input class="form-check-input d-block mx-auto mb-1" type="radio" name="stress_level" value="<?= $i ?>" id="stress<?= $i ?>">
                                                    <label class="x-small text-muted" for="stress<?= $i ?>"><?= $i ?></label>
                                                </div>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="d-flex justify-content-between x-small text-muted mt-1">
                                                <span>Minimal</span>
                                                <span>Extremely High</span>
                                            </div>
                                        </div>
                                        <div class="border-top pt-3">
                                            <label class="form-label x-small mb-2">Primary Source of Academic Stress:</label>
                                            <select name="stress_source" class="form-select form-select-sm">
                                                <option value="">Select source...</option>
                                                <option value="Time Management/Procrastination">Time Management/Procrastination</option>
                                                <option value="Exam Anxiety">Exam Anxiety</option>
                                                <option value="Financial Worries">Financial Worries</option>
                                                <option value="Course Load / Difficulty">Course Load / Difficulty</option>
                                                <option value="Family Issues">Family Issues</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold d-block mb-3">Learning & Behaviour (Coping style)</label>
                                        <div class="mb-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="coping_style" id="copingProblem" value="Problem-Focused Coping">
                                                <label class="form-check-label small d-block" for="copingProblem">
                                                    <strong>Problem-Focused Coping</strong>
                                                    <span class="d-block x-small text-muted">Making a plan, Seeking solutions, Time management, Taking action.</span>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="coping_style" id="copingEmotion" value="Emotion-Focused Coping">
                                                <label class="form-check-label small d-block" for="copingEmotion">
                                                    <strong>Emotion-Focused Coping</strong>
                                                    <span class="d-block x-small text-muted">Talking to someone, Practicing relaxation, Journaling, Acceptance.</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title">E. Admission History</h5>
                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Is this your first time applying for a
                                    <?= $degree_label ?>?</label>
                                <div class="d-flex gap-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="first_time_md_flag"
                                            id="mdFirstYes" value="YES" checked
                                            onclick="document.getElementById('prevMdDetails').style.display='none'">
                                        <label class="form-check-label" for="mdFirstYes">Yes, first time</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="first_time_md_flag"
                                            id="mdFirstNo" value="NO"
                                            onclick="document.getElementById('prevMdDetails').style.display='block'">
                                        <label class="form-check-label" for="mdFirstNo">No, I've applied before</label>
                                    </div>
                                </div>
                                <div id="prevMdDetails" class="p-3 bg-white rounded border" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small">Previous Application Status:</label>
                                            <select name="prev_app_status" class="form-select"
                                                onchange="toggleMedSchoolName(this.value)">
                                                <option value="">Select status...</option>
                                                <option value="Accepted and enrolled at">Accepted and enrolled</option>
                                                <option value="Accepted but did not enroll at">Accepted but did not
                                                    enroll</option>
                                                <option value="Denied admission at">Denied admission</option>
                                                <option value="Pending">Pending</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6" id="prevMedSchoolContainer" style="display: none;">
                                            <label class="form-label small">Name of <?= $school_label ?>:</label>
                                            <input type="text" name="prev_med_school_name" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title">F. Post-Graduation Plans</h5>
                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Plans after graduation from <?= $school_label ?>:</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="future_plan"
                                                id="planPractice" value="Engage in private practice" checked
                                                onclick="togglePlanDetails('none')">
                                            <label class="form-check-label" for="planPractice">Engage in private
                                                practice</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="future_plan"
                                                id="planCourse" value="Pursue another Post-graduate course"
                                                onclick="togglePlanDetails('course')">
                                            <label class="form-check-label" for="planCourse">Pursue another
                                                Post-graduate course</label>
                                        </div>
                                        <div id="planCourseInput" class="ms-4 mt-2" style="display: none;">
                                            <input type="text" name="future_plan_other_postgrad"
                                                class="form-control form-control-sm" placeholder="Specify course...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="future_plan" id="planGov"
                                                value="Seek employment in a Government agency"
                                                onclick="togglePlanDetails('none')">
                                            <label class="form-check-label" for="planGov">Seek employment in
                                                Government</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="future_plan"
                                                id="planOther" value="Others" onclick="togglePlanDetails('other')">
                                            <label class="form-check-label" for="planOther">Others</label>
                                        </div>
                                        <div id="planOtherInput" class="ms-4 mt-2" style="display: none;">
                                            <input type="text" name="future_plan_others"
                                                class="form-control form-control-sm" placeholder="Specify plans...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title">G. Personal Essay</h5>
                            <div class="info-group mb-4">
                                <label class="form-label fw-bold d-block mb-3">Why did you choose this course or college? <span class="text-danger">*</span></label>
                                <textarea name="application_essay" class="form-control" rows="6" required
                                    placeholder="Please share your reasons for choosing DMSF and your specific program..."></textarea>
                                <div class="text-muted small mt-2">Maximum of 500 words.</div>
                            </div>

                            <h5 class="section-title"><?= $school_label ?> Preferences</h5>
                            <div class="info-group mb-4">
                                <p class="small text-muted mb-3">List in order of preference other <?= strtolower($school_label) ?>s where
                                    you have applied/plan to apply:</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">1st Preference:</label>
                                        <input type="text" name="pref_first_med_school" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">2nd Preference:</label>
                                        <input type="text" name="pref_second_med_school" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">3rd Preference:</label>
                                        <input type="text" name="pref_third_med_school" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Others:</label>
                                        <input type="text" name="pref_other_med_schools" class="form-control"
                                            placeholder="Comma separated list...">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-step w-100 shadow-sm">
                                    Proceed to Step 5: Upload Documents
                                </button>
                                <p class="text-center mt-3 small text-muted">Please review all information before
                                    proceeding.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleHsOther(show) {
            document.getElementById('hsOtherSpec').style.display = show ? 'block' : 'none';
        }

        function toggleGapDetails(type) {
            document.getElementById('courseInput').style.display = (type === 'course') ? 'block' : 'none';
            document.getElementById('employeeDetails').style.display = (type === 'employee') ? 'block' : 'none';
        }

        function togglePlanDetails(type) {
            document.getElementById('planCourseInput').style.display = (type === 'course') ? 'block' : 'none';
            document.getElementById('planOtherInput').style.display = (type === 'other') ? 'block' : 'none';
        }

        function toggleMedSchoolName(value) {
            const container = document.getElementById('prevMedSchoolContainer');
            if (value === 'Accepted and enrolled at' || value === 'Accepted but did not enroll at') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }
    </script>

    <style>
        .x-small {
            font-size: 0.75rem;
        }

        .lowercase {
            text-transform: none !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
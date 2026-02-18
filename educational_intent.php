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
    // --- Education & Honors ---
    $primary_school = filter_input(INPUT_POST, 'primary_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $primary_location = filter_input(INPUT_POST, 'primary_location', FILTER_SANITIZE_SPECIAL_CHARS);
    $primary_dates = filter_input(INPUT_POST, 'primary_dates', FILTER_SANITIZE_SPECIAL_CHARS);
    $secondary_school = filter_input(INPUT_POST, 'secondary_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $secondary_location = filter_input(INPUT_POST, 'secondary_location', FILTER_SANITIZE_SPECIAL_CHARS);
    $secondary_dates = filter_input(INPUT_POST, 'secondary_dates', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $hs_honors_flag = isset($_POST['hs_honors_flag']) ? ($_POST['hs_honors_flag'] == 'YES') : false;
    $hs_honor_type = $hs_honors_flag ? filter_input(INPUT_POST, 'hs_honor_type', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    
    $college_name_address = filter_input(INPUT_POST, 'college_name_address', FILTER_SANITIZE_SPECIAL_CHARS);
    $degree_obtained = filter_input(INPUT_POST, 'degree_obtained', FILTER_SANITIZE_SPECIAL_CHARS);
    $date_of_graduation = filter_input(INPUT_POST, 'date_of_graduation', FILTER_SANITIZE_SPECIAL_CHARS);
    $college_honors_flag = isset($_POST['college_honors_flag']) ? ($_POST['college_honors_flag'] == 'YES') : false;
    $college_honors_list = $college_honors_flag ? filter_input(INPUT_POST, 'college_honors_list', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Board Exam ---
    $board_profession = filter_input(INPUT_POST, 'board_profession', FILTER_SANITIZE_SPECIAL_CHARS);
    $board_exam_date = filter_input(INPUT_POST, 'board_exam_date', FILTER_SANITIZE_SPECIAL_CHARS);
    $board_rating = filter_input(INPUT_POST, 'board_rating', FILTER_VALIDATE_FLOAT);

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
    $trainings_seminars = ($post_grad_activity == 'Worked as employee') ? filter_input(INPUT_POST, 'trainings_seminars', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Interests and Skills ---
    $interest_school_orgs = isset($_POST['interest_school_orgs']);
    $interest_religious = isset($_POST['interest_religious']);
    $interest_sociocivic = isset($_POST['interest_sociocivic']);
    $interest_sports = isset($_POST['interest_sports']);
    $interest_music_vocal = isset($_POST['interest_music_vocal']);
    $interest_dance = isset($_POST['interest_dance']);
    $interest_creative_writing = isset($_POST['interest_creative_writing']);
    $interest_philately = isset($_POST['interest_philately']);
    $interest_others = filter_input(INPUT_POST, 'interest_others', FILTER_SANITIZE_SPECIAL_CHARS);
    $other_skills_work_exp = filter_input(INPUT_POST, 'other_skills_work_exp', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // --- Admission History ---
    $first_time_md_flag = isset($_POST['first_time_md_flag']) ? ($_POST['first_time_md_flag'] == 'YES') : false;
    $prev_app_status = !$first_time_md_flag ? filter_input(INPUT_POST, 'prev_app_status', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $prev_med_school_name = ($prev_app_status == 'Accepted and enrolled at' || $prev_app_status == 'Accepted but did not enroll at') ? filter_input(INPUT_POST, 'prev_med_school_name', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // --- Motivation ---
    $motivation_parents = isset($_POST['motivation_parents']);
    $motivation_siblings = isset($_POST['motivation_siblings']);
    $motivation_relatives = isset($_POST['motivation_relatives']);
    $motivation_friends = isset($_POST['motivation_friends']);
    $motivation_illness = isset($_POST['motivation_illness']);
    $motivation_prestige = isset($_POST['motivation_prestige']);
    $motivation_health_awareness = isset($_POST['motivation_health_awareness']);
    $motivation_community_needs = isset($_POST['motivation_community_needs']);
    $motivation_others = filter_input(INPUT_POST, 'motivation_others', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // --- Future Plan & Support ---
    $future_plan = filter_input(INPUT_POST, 'future_plan', FILTER_SANITIZE_SPECIAL_CHARS);
    $future_plan_other_postgrad = ($future_plan == 'Pursue another Post-graduate course') ? filter_input(INPUT_POST, 'future_plan_other_postgrad', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $future_plan_others = ($future_plan == 'Others') ? filter_input(INPUT_POST, 'future_plan_others', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    $support_parents = isset($_POST['support_parents']);
    $support_veteran_benefit = isset($_POST['support_veteran_benefit']);
    $support_scholarship_flag = isset($_POST['support_scholarship_flag']);
    $support_scholarship_name = $support_scholarship_flag ? filter_input(INPUT_POST, 'support_scholarship_name', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $support_status = filter_input(INPUT_POST, 'support_status', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // --- Info Source & Staying Place ---
    $info_parents = isset($_POST['info_parents']);
    $info_family_friends = isset($_POST['info_family_friends']);
    $info_student_friends = isset($_POST['info_student_friends']);
    $info_siblings = isset($_POST['info_siblings']);
    $info_teachers = isset($_POST['info_teachers']);
    $info_newspaper = isset($_POST['info_newspaper']);
    $info_convocation = isset($_POST['info_convocation']);
    $info_internet = isset($_POST['info_internet']);
    $info_own_effort = isset($_POST['info_own_effort']);
    $info_others = filter_input(INPUT_POST, 'info_others', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $staying_place = filter_input(INPUT_POST, 'staying_place', FILTER_SANITIZE_SPECIAL_CHARS);
    $staying_place_others = ($staying_place == 'Others') ? filter_input(INPUT_POST, 'staying_place_others', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    
    // --- Preferences ---
    $pref_first_med_school = filter_input(INPUT_POST, 'pref_first_med_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $pref_second_med_school = filter_input(INPUT_POST, 'pref_second_med_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $pref_third_med_school = filter_input(INPUT_POST, 'pref_third_med_school', FILTER_SANITIZE_SPECIAL_CHARS);
    $pref_other_med_schools = filter_input(INPUT_POST, 'pref_other_med_schools', FILTER_SANITIZE_SPECIAL_CHARS);


    // Prepare the massive UPDATE statement
    // (Due to the sheer number of fields, this SQL is split for readability, but must be run as one string)
    $sql = "UPDATE applications SET 
        primary_school=?, primary_location=?, primary_dates=?, secondary_school=?, secondary_location=?, secondary_dates=?, 
        hs_honors_flag=?, hs_honor_type=?, college_name_address=?, degree_obtained=?, date_of_graduation=?, 
        college_honors_flag=?, college_honors_list=?, board_profession=?, board_exam_date=?, board_rating=?, 
        post_grad_course=?, post_grad_school=?, post_grad_date=?, post_grad_activity=?, activity_took_another_course=?, 
        employee_work=?, employee_position=?, employee_years=?, trainings_seminars=?, interest_school_orgs=?, 
        interest_religious=?, interest_sociocivic=?, interest_sports=?, interest_music_vocal=?, interest_dance=?, 
        interest_creative_writing=?, interest_philately=?, interest_others=?, other_skills_work_exp=?, first_time_md_flag=?, 
        prev_app_status=?, prev_med_school_name=?, motivation_parents=?, motivation_siblings=?, motivation_relatives=?, 
        motivation_friends=?, motivation_illness=?, motivation_prestige=?, motivation_health_awareness=?, 
        motivation_community_needs=?, motivation_others=?, future_plan=?, future_plan_other_postgrad=?, future_plan_others=?, 
        support_parents=?, support_veteran_benefit=?, support_scholarship_flag=?, support_scholarship_name=?, 
        support_status=?, info_parents=?, info_family_friends=?, info_student_friends=?, info_siblings=?, info_teachers=?, 
        info_newspaper=?, info_convocation=?, info_internet=?, info_own_effort=?, info_others=?, staying_place=?, 
        staying_place_others=?, pref_first_med_school=?, pref_second_med_school=?, pref_third_med_school=?, 
        pref_other_med_schools=?
        WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    
    // Execute the update
    if ($stmt->execute([
        $primary_school, $primary_location, $primary_dates, $secondary_school, $secondary_location, $secondary_dates, 
        $hs_honors_flag, $hs_honor_type, $college_name_address, $degree_obtained, $date_of_graduation, 
        $college_honors_flag, $college_honors_list, $board_profession, $board_exam_date, $board_rating, 
        $post_grad_course, $post_grad_school, $post_grad_date, $post_grad_activity, $activity_took_another_course, 
        $employee_work, $employee_position, $employee_years, $trainings_seminars, $interest_school_orgs, 
        $interest_religious, $interest_sociocivic, $interest_sports, $interest_music_vocal, $interest_dance, 
        $interest_creative_writing, $interest_philately, $interest_others, $other_skills_work_exp, $first_time_md_flag, 
        $prev_app_status, $prev_med_school_name, $motivation_parents, $motivation_siblings, $motivation_relatives, 
        $motivation_friends, $motivation_illness, $motivation_prestige, $motivation_health_awareness, 
        $motivation_community_needs, $motivation_others, $future_plan, $future_plan_other_postgrad, $future_plan_others, 
        $support_parents, $support_veteran_benefit, $support_scholarship_flag, $support_scholarship_name, 
        $support_status, $info_parents, $info_family_friends, $info_student_friends, $info_siblings, $info_teachers, 
        $info_newspaper, $info_convocation, $info_internet, $info_own_effort, $info_others, $staying_place, 
        $staying_place_others, $pref_first_med_school, $pref_second_med_school, $pref_third_med_school, 
        $pref_other_med_schools,
        $app_id
    ])) {
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
            --primary-color: #0d6efd;
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
            box-shadow: var-card-shadow;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header-custom {
            background: #dc3545; /* Red for Step 4 */
            padding: 30px;
            color: white;
            border: none;
        }

        .section-title {
            color: #dc3545;
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

        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            transition: all 0.2s;
            background-color: #fcfcfc;
        }

        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .applicant-info {
            background: #fff5f5;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid #dc3545;
        }

        .btn-step {
            padding: 15px 30px;
            font-weight: 700;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            background-color: #dc3545;
            border-color: #dc3545;
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
        <img src="DMSF_logo.png" alt="DMSF Logo">
        <h2 class="fw-bold">Davao Medical School Foundation</h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-card shadow">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0 fw-bold">Step 4 of 5: Educational Background & Intent</h3>
                        <span class="badge bg-white text-danger px-3 py-2">Admission Process</span>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="applicant-info">
                        <p class="mb-0 text-dark">Application for **<?= htmlspecialchars($application['college']) ?>** | Applicant: **<?= $student_name ?>**</p>
                    </div>

                    <?php if($message): ?> 
                        <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $message ?>
                        </div> 
                    <?php endif; ?>

                    <form method="POST">
                        
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
                                        <td><input type="text" name="primary_school" class="form-control" placeholder="Name of school"></td>
                                        <td><input type="text" name="primary_location" class="form-control" placeholder="City/Province"></td>
                                        <td><input type="text" name="primary_dates" class="form-control" placeholder="Year Range"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted small">SECONDARY</td>
                                        <td><input type="text" name="secondary_school" class="form-control" placeholder="Name of school"></td>
                                        <td><input type="text" name="secondary_location" class="form-control" placeholder="City/Province"></td>
                                        <td><input type="text" name="secondary_dates" class="form-control" placeholder="Year Range"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">Have you earned academic honors in high school?</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="hs_honors_flag" id="hsYes" value="YES" onclick="document.getElementById('hsHonorType').style.display='block'">
                                    <label class="form-check-label" for="hsYes">Yes, I have</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="hs_honors_flag" id="hsNo" value="NO" checked onclick="document.getElementById('hsHonorType').style.display='none'">
                                    <label class="form-check-label" for="hsNo">No, I haven't</label>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-white rounded border" id="hsHonorType" style="display: none;">
                                <label class="form-label small text-muted mb-2">If YES, please specify your rank:</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honor_type" value="Valedictorian"> Valedictorian
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honor_type" value="Salutatorian"> Salutatorian
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honor_type" value="First Honor"> First Honor
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honor_type" value="Second Honor"> Second Honor
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hs_honor_type" value="Others"> Others
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12">
                                <label class="form-label">Name and Address of the School Granting the Degree in College:</label>
                                <input type="text" name="college_name_address" class="form-control" placeholder="Full name and location of university">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Degree Obtained:</label>
                                <input type="text" name="degree_obtained" class="form-control" placeholder="e.g. BS Biology">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Graduation:</label>
                                <input type="date" name="date_of_graduation" class="form-control">
                            </div>
                        </div>

                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">Have you earned academic honors in college?</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="college_honors_flag" id="collegeYes" value="YES" onclick="document.getElementById('collegeHonorsList').style.display='block'">
                                    <label class="form-check-label" for="collegeYes">Yes, I have</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="college_honors_flag" id="collegeNo" value="NO" checked onclick="document.getElementById('collegeHonorsList').style.display='none'">
                                    <label class="form-check-label" for="collegeNo">No, I haven't</label>
                                </div>
                            </div>
                            <div class="mt-3" id="collegeHonorsList" style="display: none;">
                                <textarea name="college_honors_list" class="form-control" rows="2" placeholder="List your honors (e.g., Cum Laude, Dean's Lister, etc.)"></textarea>
                            </div>
                        </div>
                        
                        <h5 class="section-title">Board Examination <span class="text-muted lowercase fw-normal ms-2">(if applicable)</span></h5>
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Profession:</label>
                                <input type="text" name="board_profession" class="form-control" placeholder="e.g. Registered Nurse">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Examination Date:</label>
                                <input type="date" name="board_exam_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Board Rating (%):</label>
                                <input type="number" step="0.01" name="board_rating" class="form-control" placeholder="00.00">
                            </div>
                        </div>

                        <h5 class="section-title">Post Graduate Studies <span class="text-muted lowercase fw-normal ms-2">(if applicable)</span></h5>
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
                        <h5 class="section-title">B. Gap Activity / Employment <span class="text-muted lowercase fw-normal ms-2">(if applicable)</span></h5>
                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">Activities between college graduation and this application:</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="post_grad_activity" id="actNone" value="None" checked onclick="toggleGapDetails('none')">
                                        <label class="form-check-label" for="actNone">None (Proceeded directly)</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="post_grad_activity" id="actCourse" value="Took another course" onclick="toggleGapDetails('course')">
                                        <label class="form-check-label" for="actCourse">Took another course</label>
                                    </div>
                                    <div id="courseInput" class="ms-4 mt-2" style="display: none;">
                                        <input type="text" name="activity_took_another_course" class="form-control form-control-sm" placeholder="Please specify course...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="post_grad_activity" id="actEmployee" value="Worked as employee" onclick="toggleGapDetails('employee')">
                                        <label class="form-check-label" for="actEmployee">Worked as employee</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="post_grad_activity" id="actOther" value="Other" onclick="toggleGapDetails('none')">
                                        <label class="form-check-label" for="actOther">Other activities</label>
                                    </div>
                                </div>
                            </div>

                            <div id="employeeDetails" class="mt-4 p-3 bg-white rounded border" style="display: none;">
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
                                        <textarea name="trainings_seminars" class="form-control" rows="2" placeholder="List relevant professional development..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="section-title">C. Interests and Skills</h5>
                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">Extracurricular Interests:</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="interest_school_orgs"> <label class="form-check-label small">School Organizations</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="interest_religious"> <label class="form-check-label small">Religious Activities</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="interest_sociocivic"> <label class="form-check-label small">Socio-Civic Work</label></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="interest_sports"> <label class="form-check-label small">Sports</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="interest_music_vocal"> <label class="form-check-label small">Music/Vocal</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="interest_dance"> <label class="form-check-label small">Dance</label></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="interest_creative_writing"> <label class="form-check-label small">Creative Writing</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="interest_philately"> <label class="form-check-label small">Arts/Photography</label></div>
                                    <div class="mt-2">
                                        <input type="text" name="interest_others" class="form-control form-control-sm" placeholder="Others (specify)...">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="form-label">Other Skills and Work Experiences:</label>
                                <textarea name="other_skills_work_exp" class="form-control" rows="2" placeholder="Tell us about any other unique skills..."></textarea>
                            </div>
                        </div>

                        <h5 class="section-title">D. Admission History</h5>
                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">Is this your first time applying for a medical degree?</label>
                            <div class="d-flex gap-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="first_time_md_flag" id="mdFirstYes" value="YES" checked onclick="document.getElementById('prevMdDetails').style.display='none'">
                                    <label class="form-check-label" for="mdFirstYes">Yes, first time</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="first_time_md_flag" id="mdFirstNo" value="NO" onclick="document.getElementById('prevMdDetails').style.display='block'">
                                    <label class="form-check-label" for="mdFirstNo">No, I've applied before</label>
                                </div>
                            </div>
                            <div id="prevMdDetails" class="p-3 bg-white rounded border" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Previous Application Status:</label>
                                        <select name="prev_app_status" class="form-select" onchange="toggleMedSchoolName(this.value)">
                                            <option value="">Select status...</option>
                                            <option value="Accepted and enrolled at">Accepted and enrolled</option>
                                            <option value="Accepted but did not enroll at">Accepted but did not enroll</option>
                                            <option value="Denied admission at">Denied admission</option>
                                            <option value="Pending">Pending</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="prevMedSchoolContainer" style="display: none;">
                                        <label class="form-label small">Name of Medical School:</label>
                                        <input type="text" name="prev_med_school_name" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="section-title">E. Motivation & Future Plans</h5>
                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">What motivated you to pursue Medicine? <span class="fw-normal text-muted">(Check all that apply)</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="motivation_parents"> <label class="form-check-label small">Parents</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="motivation_siblings"> <label class="form-check-label small">Siblings</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="motivation_relatives"> <label class="form-check-label small">Other Relatives</label></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="motivation_friends"> <label class="form-check-label small">Friends</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="motivation_illness"> <label class="form-check-label small">Illness in family</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="motivation_prestige"> <label class="form-check-label small">Prestige/Social Standing</label></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="motivation_health_awareness"> <label class="form-check-label small">Health Awareness</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="motivation_community_needs"> <label class="form-check-label small">Community Needs</label></div>
                                    <div class="mt-2">
                                        <input type="text" name="motivation_others" class="form-control form-control-sm" placeholder="Others (specify)...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">Plans after graduation from Medical School:</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="future_plan" id="planPractice" value="Engage in private practice" checked onclick="togglePlanDetails('none')">
                                        <label class="form-check-label" for="planPractice">Engage in private practice</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="future_plan" id="planCourse" value="Pursue another Post-graduate course" onclick="togglePlanDetails('course')">
                                        <label class="form-check-label" for="planCourse">Pursue another Post-graduate course</label>
                                    </div>
                                    <div id="planCourseInput" class="ms-4 mt-2" style="display: none;">
                                        <input type="text" name="future_plan_other_postgrad" class="form-control form-control-sm" placeholder="Specify course...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="future_plan" id="planGov" value="Seek employment in a Government agency" onclick="togglePlanDetails('none')">
                                        <label class="form-check-label" for="planGov">Seek employment in Government</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="future_plan" id="planOther" value="Others" onclick="togglePlanDetails('other')">
                                        <label class="form-check-label" for="planOther">Others</label>
                                    </div>
                                    <div id="planOtherInput" class="ms-4 mt-2" style="display: none;">
                                        <input type="text" name="future_plan_others" class="form-control form-control-sm" placeholder="Specify plans...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="section-title">F. Support & Information</h5>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="info-group h-100">
                                    <label class="form-label fw-bold mb-3">Who will support your medical education?</label>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="support_parents"> <label class="form-check-label small">Parents/Family</label></div>
                                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="support_veteran_benefit"> <label class="form-check-label small">Veteran Benefit</label></div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="support_scholarship_flag" id="scholarshipCheck" onclick="document.getElementById('scholarshipInput').style.display=this.checked?'block':'none'">
                                        <label class="form-check-label small">Scholarship</label>
                                    </div>
                                    <div id="scholarshipInput" class="ms-4 mb-3" style="display: none;">
                                        <input type="text" name="support_scholarship_name" class="form-control form-control-sm" placeholder="Name of scholarship...">
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label small">Support Status:</label>
                                        <select name="support_status" class="form-select form-select-sm">
                                            <option value="Assured">Assured</option>
                                            <option value="Probable">Probable</option>
                                            <option value="To be applied for">To be applied for</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group h-100">
                                    <label class="form-label fw-bold mb-3">Where did you learn about DMSF?</label>
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_parents"> <label class="form-check-label x-small">Parents</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_family_friends"> <label class="form-check-label x-small">Family Friends</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_student_friends"> <label class="form-check-label x-small">Student Friends</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_siblings"> <label class="form-check-label x-small">Siblings</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_teachers"> <label class="form-check-label x-small">Teachers</label></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_newspaper"> <label class="form-check-label x-small">Newspaper</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_convocation"> <label class="form-check-label x-small">Convocation</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_internet"> <label class="form-check-label x-small">Internet/Web</label></div>
                                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="info_own_effort"> <label class="form-check-label x-small">Own Effort</label></div>
                                            <input type="text" name="info_others" class="form-control form-control-sm mt-1" placeholder="Others...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-group mb-4">
                            <label class="form-label fw-bold d-block mb-3">While in Davao, where do you plan to stay?</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="staying_place" value="Parents/Guardians Home" checked onclick="document.getElementById('stayingOther').style.display='none'"> <label class="form-check-label small">Parents/Guardians Home</label></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="staying_place" value="Dormitory/Boarding House" onclick="document.getElementById('stayingOther').style.display='none'"> <label class="form-check-label small">Dormitory/Boarding House</label></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2"><input class="form-check-input" type="radio" name="staying_place" value="Others" onclick="document.getElementById('stayingOther').style.display='block'"> <label class="form-check-label small">Others</label></div>
                                    <div id="stayingOther" style="display: none;">
                                        <input type="text" name="staying_place_others" class="form-control form-control-sm" placeholder="Specify place...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="section-title">G. Medical School Preferences</h5>
                        <div class="info-group mb-4">
                            <p class="small text-muted mb-3">List in order of preference other medical schools where you have applied/plan to apply:</p>
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
                                    <input type="text" name="pref_other_med_schools" class="form-control" placeholder="Comma separated list...">
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <button type="submit" class="btn btn-danger btn-step w-100 shadow-sm">
                                Proceed to Step 5: Upload Documents
                            </button>
                            <p class="text-center mt-3 small text-muted">Please review all information before proceeding.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
    .x-small { font-size: 0.75rem; }
    .lowercase { text-transform: none !important; }
</style>

</body>
</html>

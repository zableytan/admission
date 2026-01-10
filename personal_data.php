<?php
/**
 * personal_data.php
 * Handles the collection of detailed personal, medical, and legal history (Step 2).
 * Requires an app_id via GET parameter and updates the existing application record.
 */
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
        'college' => 'Medicine' // Default college for display
    ];
    $student_name = "Dummy Applicant";
    $message = "Note: Displaying dummy data as Application ID is missing for screen recording.";
} else {
    $app_id = $_GET['app_id'];

    // Check if application exists and fetch basic info for display
    $stmt = $pdo->prepare("SELECT family_name, given_name, college FROM applications WHERE id = ?");
    $stmt->execute([$app_id]);
    $application = $stmt->fetch();

    if (!$application) {
        // For screen recording, provide dummy data if application not found
        $application = [
            'family_name' => 'Applicant',
            'given_name' => 'Dummy',
            'college' => 'Medicine'
        ];
        $student_name = "Dummy Applicant";
        $message = "Note: Displaying dummy data as Application not found for screen recording.";
    } else {
        $student_name = htmlspecialchars($application['given_name'] . ' ' . $application['family_name']);
    }
}


// 2. POST LOGIC: Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize all fields
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $dob = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_SPECIAL_CHARS);
    $pob = filter_input(INPUT_POST, 'place_of_birth', FILTER_SANITIZE_SPECIAL_CHARS);
    $sex = filter_input(INPUT_POST, 'sex', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'civil_status', FILTER_SANITIZE_SPECIAL_CHARS);
    $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_SPECIAL_CHARS);
    $citizenship = filter_input(INPUT_POST, 'citizenship', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $height_ft = filter_input(INPUT_POST, 'height_ft', FILTER_VALIDATE_INT);
    $height_in = filter_input(INPUT_POST, 'height_in', FILTER_VALIDATE_INT);
    $weight_initial = filter_input(INPUT_POST, 'weight_kilos_initial', FILTER_VALIDATE_FLOAT);
    $weight_now = filter_input(INPUT_POST, 'weight_kilos_now', FILTER_VALIDATE_FLOAT);
    
    $med_history = filter_input(INPUT_POST, 'medical_history', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Boolean fields (convert 'YES'/'NO' or checkbox presence)
    $disability_flag = isset($_POST['disability_flag']) ? ($_POST['disability_flag'] == 'YES') : false;
    $disability_details = filter_input(INPUT_POST, 'physical_disability_details', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $convicted_flag = isset($_POST['convicted_flag']) ? ($_POST['convicted_flag'] == 'YES') : false;
    $convicted_explanation = filter_input(INPUT_POST, 'convicted_explanation', FILTER_SANITIZE_SPECIAL_CHARS);
    

    // Prepare the UPDATE statement
    $sql = "UPDATE applications SET 
            age=?, date_of_birth=?, place_of_birth=?, sex=?, civil_status=?, religion=?, citizenship=?, 
            height_ft=?, height_in=?, weight_kilos_initial=?, weight_kilos_now=?, medical_history=?, 
            physical_disability_flag=?, physical_disability_details=?, convicted_flag=?, convicted_explanation=?
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    
    // Execute the statement
    if ($stmt->execute([
        $age, $dob, $pob, $sex, $status, $religion, $citizenship, 
        $height_ft, $height_in, $weight_initial, $weight_now, $med_history, 
        $disability_flag, $disability_details, $convicted_flag, $convicted_explanation,
        $app_id
    ])) {
        // Successful update! Redirect to Step 3: Family Background
        header("Location: family_background.php?app_id=$app_id");
        exit;
    } else {
        $message = "Error: Could not save personal data. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Step 2: Personal Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1.05rem;
        }
        .form-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header {
            background-color: #ffc107 !important;
            padding: 25px;
            border: none;
        }
        .card-header h3 {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #212529;
        }
        .card-body {
            padding: 40px;
        }
        .section-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f1f1;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #eee;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.1);
        }
        .btn-step {
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        .applicant-info {
            background: #fff8e1;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid #ffc107;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="form-card mx-auto" style="max-width: 900px;">
        <div class="card-header">
            <h3 class="mb-0">Step 2 of 5: Personal & Medical Data</h3>
        </div>
        <div class="card-body">
            <div class="applicant-info">
                <p class="mb-0 text-dark">Application for **<?= htmlspecialchars($application['college']) ?>** | Applicant: **<?= $student_name ?>**</p>
            </div>
            
            <?php if($message): ?> 
                <div class="alert alert-danger rounded-3" role="alert"><?= $message ?></div> 
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
                        <input type="text" name="place_of_birth" class="form-control" required placeholder="City/Province">
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
                        <input type="text" name="religion" class="form-control" required placeholder="e.g. Catholic">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Citizenship *</label>
                        <input type="text" name="citizenship" class="form-control" required placeholder="at birth">
                    </div>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Height (feet)</label>
                        <input type="number" name="height_ft" class="form-control" min="3" max="7" placeholder="Feet">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Height (inches)</label>
                        <input type="number" name="height_in" class="form-control" min="0" max="11" placeholder="Inches">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Weight (Kilos - Initial)</label>
                        <input type="number" step="0.1" name="weight_kilos_initial" class="form-control" placeholder="Initial">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Weight (Kilos - Now)</label>
                        <input type="number" step="0.1" name="weight_kilos_now" class="form-control" placeholder="Current">
                    </div>
                </div>

                <h5 class="section-title mt-2">B. Medical History</h5>
                
                <div class="mb-4">
                    <label class="form-label">Medical History (last 5 years)</label>
                    <textarea name="medical_history" class="form-control" rows="3" placeholder="List any physical or mental illnesses..."></textarea>
                </div>
                
                <div class="mb-4 border-0 bg-light p-4 rounded-3">
                    <label class="form-label fw-bold d-block mb-3">Do you have any physical disability which might interfere with the practice of medicine?</label>
                    <div class="form-check form-check-inline me-4">
                        <input class="form-check-input" type="radio" name="disability_flag" id="disabilityYes" value="YES" onclick="document.getElementById('disabilityDetails').style.display='block'">
                        <label class="form-check-label" for="disabilityYes">YES</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="disability_flag" id="disabilityNo" value="NO" checked onclick="document.getElementById('disabilityDetails').style.display='none'">
                        <label class="form-check-label" for="disabilityNo">NO</label>
                    </div>
                    <div class="mt-3" id="disabilityDetails" style="display: none;">
                        <textarea name="physical_disability_details" class="form-control" rows="2" placeholder="If YES, please state details."></textarea>
                    </div>
                </div>

                <h5 class="section-title mt-2">C. Legal History</h5>
                
                <div class="mb-4 border-0 bg-light p-4 rounded-3">
                    <label class="form-label fw-bold d-block mb-3">Have you been convicted in court of any offense?</label>
                    <div class="form-check form-check-inline me-4">
                        <input class="form-check-input" type="radio" name="convicted_flag" id="convictedYes" value="YES" onclick="document.getElementById('convictedExplanation').style.display='block'">
                        <label class="form-check-label" for="convictedYes">YES</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="convicted_flag" id="convictedNo" value="NO" checked onclick="document.getElementById('convictedExplanation').style.display='none'">
                        <label class="form-check-label" for="convictedNo">NO</label>
                    </div>
                    <div class="mt-3" id="convictedExplanation" style="display: none;">
                        <textarea name="convicted_explanation" class="form-control" rows="3" placeholder="If YES, please explain..."></textarea>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn btn-success btn-step w-100 shadow-sm">
                        Proceed to Step 3: Family Background
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
                
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
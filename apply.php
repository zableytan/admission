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
    
    // Set score type and value based on college selection
    $score_type = $is_medicine ? 'NMAT' : 'GWA';
    $score_val = $is_medicine ? filter_input(INPUT_POST, 'nmat_score', FILTER_VALIDATE_FLOAT) : filter_input(INPUT_POST, 'gwa_score', FILTER_VALIDATE_FLOAT);
    
    // Default attachment path to NULL since we are skipping upload for now
    $target_file = null; 
    
    // Check if the required email validation passed
    if (!$email) {
        $message = "Invalid email format.";
    } else {
        
        // --- FILE UPLOAD LOGIC REMOVED ---
        
        // Prepare the comprehensive INSERT statement. attachment_path is now set to NULL (or default in DB).
        $sql = "INSERT INTO applications 
            (family_name, given_name, middle_name, email, college, score_type, score_value, nmat_date, board_rating, 
            mailing_address, mobile_no, tel_no_mailing, home_address, tel_no_home, social_media, attachment_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        $stmt = $pdo->prepare($sql);
        
        // Execute the statement with the collected values
        if($stmt->execute([
            $family_name, $given_name, $middle_name, $email, $posted_college, $score_type, $score_val, $nmat_date, 
            $board_rating, $mailing_address, $mobile_no, $tel_no_mailing, $home_address, $tel_no_home, 
            $social_media, $target_file // $target_file is null now
        ])) {
            
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
<html>
<head>
    <title>Step 1: Basic Application - <?= htmlspecialchars($college) ?></title>
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
            background-color: #0d6efd !important;
            padding: 25px;
            border: none;
        }
        .card-header h3 {
            font-weight: 700;
            letter-spacing: -0.5px;
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
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #eee;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
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
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
        .helper-text {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    
<div class="container my-5">
    <div class="form-card mx-auto" style="max-width: 900px;">
        <div class="card-header text-white">
            <h3 class="mb-0">Step 1 of 5: Basic Application</h3>
            <p class="mb-0 opacity-75">Applying for College of <?= htmlspecialchars($college) ?></p>
        </div>
        <div class="card-body">

            <?php if($message): ?> 
                <div class="alert alert-danger rounded-3" role="alert"><?= $message ?></div> 
            <?php endif; ?>

            <form method="POST"> 
                <input type="hidden" name="college" value="<?= htmlspecialchars($college) ?>">

                <h5 class="section-title">Personal Information</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Family Name *</label>
                        <input type="text" name="last_name" class="form-control" required placeholder="Last Name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Given Name *</label>
                        <input type="text" name="first_name" class="form-control" required placeholder="First Name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Initial</label>
                        <input type="text" name="middle_initial" class="form-control" placeholder="Optional">
                    </div>
                </div>

                <h5 class="section-title mt-2">Academic Information</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label"><?= $score_label ?> *</label>
                        <input type="number" step="<?= $is_medicine ? '1' : '0.01' ?>" name="<?= $score_field_name ?>" class="form-control" required placeholder="<?= $score_placeholder ?>">
                        <div class="helper-text mt-2"><?= $is_medicine ? 'Medicine requires NMAT Percentile Rank.' : 'Other colleges require General Weighted Average.' ?></div>
                    </div>
                    
                    <?php if ($is_medicine): ?>
                        <div class="col-md-6">
                            <label class="form-label">Date Taken (NMAT) *</label>
                            <input type="date" name="nmat_date" class="form-control" required>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-6">
                        <label class="form-label">Board Rating (if applicable)</label>
                        <input type="number" step="0.01" name="board_rating" class="form-control" placeholder="e.g. 85.50">
                    </div>
                </div>
                
                <h5 class="section-title mt-2">Contact Details</h5>
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="yourname@example.com">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Mailing Address *</label>
                        <input type="text" name="mailing_address" class="form-control" required placeholder="Street, Barangay, City, Province">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Mobile No. *</label>
                        <input type="text" name="mobile_no" class="form-control" required placeholder="09XX XXX XXXX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tel. No. (Mailing)</label>
                        <input type="text" name="tel_no_mailing" class="form-control" placeholder="Optional">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Home Address *</label>
                        <input type="text" name="home_address" class="form-control" required placeholder="Permanent Residence Address">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Tel. No. (Home)</label>
                        <input type="text" name="tel_no_home" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Social Media Accounts</label>
                        <input type="text" name="social_media" class="form-control" placeholder="FB / LinkedIn / Twitter">
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn btn-warning btn-step w-100 shadow-sm">
                        Proceed to Step 2: Personal Data
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="text-center mt-3">
        <a href="index.php" class="btn btn-link">← Choose a different college</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
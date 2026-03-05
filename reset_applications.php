<?php
/**
 * reset_applications.php
 * WARNING: This script will PERMANENTLY DELETE all application records and uploaded files.
 * It will reset the auto-increment ID to start back at #00001.
 */

require 'db.php';
session_start();

// Security Check: Only logged-in super admins should be allowed to run this
// If you don't have session-based security yet, you can temporarily comment this out or add a password check
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access. You must be logged in as an admin to perform a reset.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    try {
        // 1. Delete physical files in uploads directory (except index.php or .gitignore if they exist)
        $upload_dir = 'uploads/';
        if (is_dir($upload_dir)) {
            $files = glob($upload_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Also clean up the 'signed' subfolder if it exists
            if (is_dir($upload_dir . 'signed')) {
                $signed_files = glob($upload_dir . 'signed/*');
                foreach ($signed_files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }

        // 2. Delete all rows from the applications table
        $pdo->exec("DELETE FROM applications");
        
        // 3. Reset the AUTO_INCREMENT counter
        // Note: On some restricted shared hosting, ALTER TABLE might also be restricted.
        // If this fails, the IDs will continue from where they left off, but data will be gone.
        try {
            $pdo->exec("ALTER TABLE applications AUTO_INCREMENT = 1");
        } catch (Exception $alter_e) {
            error_log("Could not reset auto-increment: " . $alter_e->getMessage());
        }

        $message = "Success! All application records and files have been deleted.";
        $status = "success";
    } catch (Exception $e) {
        $message = "Error during reset: " . $e->getMessage();
        $status = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reset | Admissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; min-height: 100vh; }
        .reset-card { max-width: 500px; margin: auto; border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card reset-card shadow-sm">
            <div class="card-body p-5 text-center">
                <i class="bi bi-exclamation-octagon text-danger display-1"></i>
                <h2 class="mt-4 fw-bold">System Reset</h2>
                <p class="text-muted">This action will permanently delete <strong>all student applications</strong> and reset the next ID to <strong>#00001</strong>.</p>
                
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= $status ?> mt-3">
                        <?= $message ?>
                    </div>
                    <a href="admin_dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
                <?php else: ?>
                    <form method="POST" class="mt-4">
                        <div class="alert alert-warning small text-start">
                            <i class="bi bi-info-circle me-2"></i> This will also delete all uploaded PDFs and images from the server.
                        </div>
                        <button type="submit" name="confirm_reset" class="btn btn-danger btn-lg w-100 fw-bold">
                            Yes, Reset Everything
                        </button>
                        <a href="admin_dashboard.php" class="btn btn-link mt-2">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

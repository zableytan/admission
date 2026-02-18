<?php
session_start();
require 'db.php';

// Security Check - Only Super Admins can access this page
if (!isset($_SESSION['admin_id']) || !$_SESSION['is_super_admin']) {
    header("Location: admin_dashboard.php");
    exit;
}

$msg = '';
$error = '';

// Handle Account Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $raw_email = $_POST['email'] ?? '';
        $college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_SPECIAL_CHARS);
        $is_super = isset($_POST['is_super']) ? 1 : 0;

        // Validate multiple emails
        $emails = array_map('trim', explode(',', $raw_email));
        $valid_emails = [];
        $email_errors = false;

        foreach ($emails as $email_item) {
            if (!empty($email_item)) {
                if (filter_var($email_item, FILTER_VALIDATE_EMAIL)) {
                    $valid_emails[] = $email_item;
                } else {
                    $email_errors = true;
                }
            }
        }
        $final_email_string = implode(',', $valid_emails);

        if ($email_errors) {
            $error = "One or more email addresses are invalid.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, college, is_super_admin) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password, $final_email_string, $college, $is_super]);
                $msg = "Admin account created successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Error: Username '" . htmlspecialchars($username) . "' is already taken by another account.";
                } else {
                    $error = "Database Error: " . $e->getMessage();
                }
            }
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $raw_email = $_POST['email'] ?? '';
        $college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_SPECIAL_CHARS);
        $is_super = isset($_POST['is_super']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        // Validate multiple emails
        $emails = array_map('trim', explode(',', $raw_email));
        $valid_emails = [];
        $email_errors = false;

        foreach ($emails as $email_item) {
            if (!empty($email_item)) {
                if (filter_var($email_item, FILTER_VALIDATE_EMAIL)) {
                    $valid_emails[] = $email_item;
                } else {
                    $email_errors = true;
                }
            }
        }
        $final_email_string = implode(',', $valid_emails);

        if ($email_errors) {
            $error = "One or more email addresses are invalid.";
        } else {
            try {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, email = ?, college = ?, is_super_admin = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $final_email_string, $college, $is_super, $hashed_password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, email = ?, college = ?, is_super_admin = ? WHERE id = ?");
                    $stmt->execute([$username, $final_email_string, $college, $is_super, $id]);
                }
                $msg = "Admin account updated successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Error: Username '" . htmlspecialchars($username) . "' is already taken by another account.";
                } else {
                    $error = "Database Error: " . $e->getMessage();
                }
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        // Don't allow deleting yourself
        if ($id == $_SESSION['admin_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Admin account deleted successfully.";
        }
    }
}

// Fetch all admins
$admins = $pdo->query("SELECT * FROM admins ORDER BY college, username ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - DMSF</title>
    <link rel="icon" type="image/png" href="DMSF_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }

        .navbar {
            background: var(--primary-color) !important;
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php">
                <i class="bi bi-shield-lock-fill me-2"></i>DMSF Admin Management
            </a>
            <div class="ms-auto">
                <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Account Form -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4">Create New Admin</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Email Address(es)</label>
                                <input type="text" name="email" class="form-control"
                                    placeholder="one@email.com, two@email.com" required>
                                <div class="form-text small">Separate multiple emails with a comma.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Department/College</label>
                                <select name="college" class="form-select" required>
                                    <option value="Medicine">Medicine</option>
                                    <option value="Nursing">Nursing</option>
                                    <option value="Dentistry">Dentistry</option>
                                    <option value="Midwifery">Midwifery</option>
                                    <option value="Biology">Biology</option>
                                    <option value="All">All (Super Admin)</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_super" id="isSuper">
                                    <label class="form-check-label fw-bold" for="isSuper">Super Admin Access</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                                <i class="bi bi-person-plus-fill me-2"></i>Create Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admin List -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4">Existing Accounts</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>College</th>
                                        <th>Type</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($admin['username']) ?></div>
                                                <div class="small text-primary">
                                                    <?= htmlspecialchars($admin['email'] ?? 'No email set') ?>
                                                </div>
                                                <small class="text-muted">Created:
                                                    <?= date('M d, Y', strtotime($admin['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= $admin['college'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($admin['is_super_admin']): ?>
                                                    <span class="badge bg-primary"><i class="bi bi-star-fill me-1"></i>Super
                                                        Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark">Staff</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-primary btn-sm me-1"
                                                    onclick="editAdmin(<?= htmlspecialchars(json_encode($admin)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">Current User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editModalLabel">Edit Admin Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Username</label>
                            <input type="text" name="username" id="editUsername" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Email Address(es)</label>
                            <input type="text" name="email" id="editEmail" class="form-control"
                                placeholder="Separate with comma" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">New Password (leave blank to keep
                                current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Department/College</label>
                            <select name="college" id="editCollege" class="form-select" required>
                                <option value="Medicine">Medicine</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Dentistry">Dentistry</option>
                                <option value="Midwifery">Midwifery</option>
                                <option value="Biology">Biology</option>
                                <option value="All">All (Super Admin)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_super" id="editIsSuper">
                                <label class="form-check-label fw-bold" for="editIsSuper">Super Admin Access</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 p-4">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));

        function editAdmin(admin) {
            document.getElementById('editId').value = admin.id;
            document.getElementById('editUsername').value = admin.username;
            document.getElementById('editEmail').value = admin.email || '';
            document.getElementById('editCollege').value = admin.college;
            document.getElementById('editIsSuper').checked = admin.is_super_admin == 1;

            editModal.show();
        }
    </script>
</body>

</html>
<?php
// contact_modal.php
// Dynamically fetches and displays admin contact information from the database.

if (!isset($pdo)) {
    require_once 'db.php';
}

// Fetch admin emails for the contact modal
try {
    $admin_stmt = $pdo->query("SELECT college, email FROM admins WHERE is_super_admin = 0 AND college != 'All' ORDER BY college ASC");
    $contact_admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contact_admins = [];
}
?>

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
            <?php if (empty($contact_admins)): ?>
                <li class="list-group-item px-0 border-bottom-0">
                    <p class="text-muted small mb-0">No contact information available at the moment.</p>
                </li>
            <?php else: ?>
                <?php foreach ($contact_admins as $idx => $admin): ?>
                    <?php 
                        $is_last = ($idx === count($contact_admins) - 1);
                        $emails = explode(',', $admin['email']);
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 <?= $is_last ? 'border-bottom-0' : '' ?>">
                        <strong><?= htmlspecialchars($admin['college']) ?></strong>
                        <div class="d-flex flex-column align-items-end gap-1">
                            <?php foreach ($emails as $email): ?>
                                <?php $email = trim($email); ?>
                                <?php if (!empty($email)): ?>
                                    <a href="mailto:<?= htmlspecialchars($email) ?>" class="text-decoration-none rounded px-2 py-1 bg-light small"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($email) ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

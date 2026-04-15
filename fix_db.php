<?php
require 'db.php';
try {
    $pdo->query("SELECT is_submitted FROM applications LIMIT 1");
    echo "Column exists";
} catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE applications ADD COLUMN is_submitted TINYINT(1) DEFAULT 0 AFTER status");
        echo "Column created successfully";
    } catch (Exception $e2) {
        echo "Error: " . $e2->getMessage();
    }
}
?>

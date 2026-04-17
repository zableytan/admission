<?php
require 'db.php';
try {
    $sql = "ALTER TABLE applications 
            ADD COLUMN interview_date DATE NULL,
            ADD COLUMN interview_time TIME NULL,
            ADD COLUMN interview_link TEXT NULL,
            ADD COLUMN interview_status VARCHAR(50) DEFAULT 'Not Scheduled'";
    $pdo->exec($sql);
    echo "Columns added successfully.";
} catch (PDOException $e) {
    echo "Error or already exists: " . $e->getMessage();
}

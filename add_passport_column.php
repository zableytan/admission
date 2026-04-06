<?php
require 'db.php';
try {
    $sql = "ALTER TABLE applications ADD COLUMN passport_path VARCHAR(255) NULL AFTER signed_document_path";
    $pdo->exec($sql);
    echo "Successfully added 'passport_path' column to 'applications' table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
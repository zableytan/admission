<?php
require 'db.php';

try {
    $sql = "ALTER TABLE applications ADD COLUMN parents_marriage_status VARCHAR(100) AFTER family_contact_no";
    $pdo->exec($sql);
    echo "Successfully added 'parents_marriage_status' column to 'applications' table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

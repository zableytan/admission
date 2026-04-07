<?php
require 'db.php';

try {
    $sql = "ALTER TABLE applications 
            ADD COLUMN legal_involved ENUM('Yes', 'No', 'Prefer not to answer') DEFAULT 'No' AFTER convicted_explanation,
            ADD COLUMN legal_status TEXT NULL AFTER legal_involved,
            ADD COLUMN legal_nature TEXT NULL AFTER legal_status,
            ADD COLUMN legal_support TEXT NULL AFTER legal_nature,
            ADD COLUMN legal_additional TEXT NULL AFTER legal_support";
    $pdo->exec($sql);
    echo "Successfully added legal involvement columns to 'applications' table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

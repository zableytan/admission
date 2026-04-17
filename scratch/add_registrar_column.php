<?php
require 'db.php';

try {
    // Add is_registrar column if it doesn't exist
    $pdo->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS is_registrar TINYINT(1) DEFAULT 0 AFTER is_dean");
    echo "Column 'is_registrar' added successfully or already exists.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

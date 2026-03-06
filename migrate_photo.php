<?php
/**
 * Database Migration: Add Photo Path to Applications
 * Run this script once on your hosting environment to update the database schema.
 */

require 'db.php';

try {
    // Check if the column already exists first to avoid errors
    $check = $pdo->query("SHOW COLUMNS FROM applications LIKE 'photo_path'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL AFTER tor_path");
        echo "✅ Column 'photo_path' added successfully to 'applications' table.\n";
    } else {
        echo "ℹ️ Column 'photo_path' already exists.\n";
    }
} catch (PDOException $e) {
    echo "❌ Error migrating database: " . $e->getMessage() . "\n";
}
?>
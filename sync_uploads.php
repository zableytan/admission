<?php
/**
 * sync_uploads.php
 * Scans the uploads/ directory and matches existing files to application records in the database.
 * Use this to recover "lost" file links if the database columns were missing during upload.
 */

require 'db.php';

$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    die("Uploads directory not found.");
}

$files = scandir($upload_dir);
$sync_count = 0;

// Mapping of filename prefix to database column name
$mapping = [
    'tor_file'           => 'tor_path',
    'birth_cert_file'    => 'birth_cert_path',
    'nmat_file'          => 'nmat_path',
    'diploma_file'       => 'diploma_path',
    'gwa_file'           => 'gwa_cert_path',
    'entrance_exam_file' => 'entrance_exam_path',
    'receipt_file'       => 'receipt_path',
    'good_moral_file'    => 'good_moral_path'
];

echo "Starting synchronization...\n\n";

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    // Filenames are usually: {prefix}_{app_id}_{timestamp}.{ext}
    // Example: diploma_file_3_1770165525.pdf
    
    foreach ($mapping as $prefix => $column) {
        if (strpos($file, $prefix . '_') === 0) {
            // Extract the app_id
            // Split by underscore and get the part after the prefix parts
            // For 'diploma_file', prefix has 2 parts. app_id is at index 2.
            // For 'tor_file', prefix has 2 parts. app_id is at index 2.
            $parts = explode('_', $file);
            
            // The structure is {prefix_part1}_{prefix_part2}_{app_id}_...
            // So app_id is usually the 3rd part (index 2) or 4th part if prefix has 3 parts.
            // Let's count prefix parts:
            $prefix_parts_count = count(explode('_', $prefix));
            $app_id = $parts[$prefix_parts_count] ?? null;

            if ($app_id && is_numeric($app_id)) {
                $path = $upload_dir . $file;
                
                // Update the database if the column is currently empty
                $stmt = $pdo->prepare("UPDATE applications SET $column = ? WHERE id = ? AND ($column IS NULL OR $column = '')");
                $stmt->execute([$path, $app_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo "Synced: $file -> Application #$app_id ($column)\n";
                    $sync_count++;
                }
            }
            break;
        }
    }
}

echo "\nSynchronization complete. Total records updated: $sync_count\n";
?>

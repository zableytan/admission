<?php
require 'db.php';
try {
    echo "--- admins table ---\n";
    $stmt = $pdo->query('DESCRIBE admins');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }

    echo "\n--- applications table ---\n";
    $stmt = $pdo->query('DESCRIBE applications');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

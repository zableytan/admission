<?php
require 'db.php';
function dump_table($pdo, $table) {
    echo "Table: $table\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
}
dump_table($pdo, 'applications');
dump_table($pdo, 'admins');

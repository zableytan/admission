<?php
// setup_database.php

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server without specifying a database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to MySQL server successfully.<br>";

    // Read the SQL file
    $sqlFile = 'database.sql';
    if (!file_exists($sqlFile)) {
        die("Error: SQL file '$sqlFile' not found.");
    }

    $sql = file_get_contents($sqlFile);

    // Execute the SQL commands
    // Note: PDO::exec can execute multiple statements if the driver supports it, 
    // but splitting by semicolon is often safer for simple scripts.
    // However, CREATE DATABASE and USE need to be handled carefully or just run the whole block if possible.
    // Let's try running the whole block first.
    
    $pdo->exec($sql);
    
    echo "Database setup completed successfully.<br>";

    // Create uploads/signed directory if it doesn't exist
    $upload_dir = 'uploads/signed';
    if (!is_dir($upload_dir)) {
        if (mkdir($upload_dir, 0777, true)) {
            echo "Directory '$upload_dir' created successfully.<br>";
        } else {
            echo "Warning: Failed to create directory '$upload_dir'. Please create it manually and ensure it is writable.<br>";
        }
    } else {
        @chmod($upload_dir, 0777);
        echo "Directory '$upload_dir' already exists and permissions have been updated.<br>";
    }
    
    echo "You can now <a href='index.php'>go to the homepage</a>.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

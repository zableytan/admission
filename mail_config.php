<?php
// mail_config.php
// Configure your SMTP settings here

return [
    'host' => 'smtp.gmail.com',         // Your SMTP server (e.g., smtp.gmail.com)
    'username' => 'aqa-files@dmsf.edu.ph', // Your SMTP username
    'password' => 'gmph mgkc hvkw mfzi',  // Your SMTP password (use App Password for Gmail)
    'port' => 587,                      // TCP port to connect to (587 or 465)
    'encryption' => 'tls',              // Enable implicit TLS encryption (ssl or tls)
    'from_email' => 'aqa-files@dmsf.edu.ph',
    'from_name' => 'DMSF Admission System'
];

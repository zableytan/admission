-- Run this SQL in your database manager (e.g., phpMyAdmin)
ALTER TABLE applications 
ADD COLUMN form137_path VARCHAR(255) NULL AFTER tor_path;

-- Davao Medical School Foundation, Inc.
-- Database Update: Add Dean Role
-- Version: 1.3

USE admission_system;

-- Add is_dean column to admins table
ALTER TABLE admins ADD COLUMN is_dean TINYINT(1) DEFAULT 0 AFTER is_super_admin;

-- Optional: Create a default dean account (Password: password123)
-- INSERT IGNORE INTO admins (username, password, email, college, is_dean) 
-- VALUES ('dean', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dean@dmsf.edu.ph', 'All', 1);

-- SQL to add the registrar role column to the admins table
-- This allows distinguishing between department admins and registrar staff

ALTER TABLE admins 
ADD COLUMN is_registrar TINYINT(1) DEFAULT 0 
AFTER is_dean;

-- Optional: If you want to see the changes
-- DESCRIBE admins;

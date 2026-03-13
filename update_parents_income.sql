-- Update script for Parent Names, Deceased Flags, and Family Income
ALTER TABLE applications 
DROP COLUMN father_name,
DROP COLUMN mother_name,
ADD COLUMN father_first_name VARCHAR(100) AFTER family_contact_no,
ADD COLUMN father_middle_name VARCHAR(100) AFTER father_first_name,
ADD COLUMN father_last_name VARCHAR(100) AFTER father_middle_name,
ADD COLUMN father_deceased TINYINT(1) DEFAULT 0 AFTER father_last_name,
ADD COLUMN mother_first_name VARCHAR(100) AFTER father_deceased,
ADD COLUMN mother_middle_name VARCHAR(100) AFTER mother_first_name,
ADD COLUMN mother_last_name VARCHAR(100) AFTER mother_middle_name,
ADD COLUMN mother_deceased TINYINT(1) DEFAULT 0 AFTER mother_last_name;

ALTER TABLE applications 
MODIFY COLUMN total_family_income VARCHAR(100);

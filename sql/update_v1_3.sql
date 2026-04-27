-- Update script for Step 1, 2, 3, 4 changes
ALTER TABLE applications 
ADD COLUMN father_age INT AFTER father_last_name,
ADD COLUMN mother_age INT AFTER mother_last_name,
ADD COLUMN parent_dmsf_course VARCHAR(100) AFTER parent_dmsf_grad_flag,
ADD COLUMN parent_dmsf_year VARCHAR(50) AFTER parent_dmsf_course,
ADD COLUMN support_others VARCHAR(255) AFTER support_scholarship_name;

-- Note: You may want to migrate existing data from parent_dmsf_course_year to the new split columns
-- UPDATE applications SET parent_dmsf_course = SUBSTRING_INDEX(parent_dmsf_course_year, ',', 1), parent_dmsf_year = SUBSTRING_INDEX(parent_dmsf_course_year, ',', -1) WHERE parent_dmsf_grad_flag = 1;

ALTER TABLE applications DROP COLUMN parent_dmsf_course_year;

-- Update schema for Vaccination, Health, and Learning/Behavior fields
ALTER TABLE applications 
-- Vaccination and Health (for Personal Data)
ADD COLUMN vax_status ENUM('Yes', 'No') DEFAULT 'No' AFTER physical_disability_details,
ADD COLUMN vax_dose1 VARCHAR(100) NULL AFTER vax_status,
ADD COLUMN vax_dose2 VARCHAR(100) NULL AFTER vax_dose1,
ADD COLUMN vax_booster VARCHAR(100) NULL AFTER vax_dose2,
ADD COLUMN chronic_condition_flag TINYINT(1) DEFAULT 0 AFTER vax_booster,
ADD COLUMN chronic_condition_details TEXT NULL AFTER chronic_condition_flag,
ADD COLUMN counselling_history ENUM('Yes', 'No', 'Prefer not to say') NULL AFTER chronic_condition_details,

-- Learning and Behavior (for Educational Intent)
ADD COLUMN learning_style ENUM('Visual', 'Auditory', 'Kinesthetic', 'Reading/Writing', 'Mixed') NULL AFTER interest_others,
ADD COLUMN stress_level INT(1) NULL AFTER learning_style,
ADD COLUMN stress_source VARCHAR(100) NULL AFTER stress_level,
ADD COLUMN coping_style ENUM('Problem-Focused Coping', 'Emotion-Focused Coping') NULL AFTER stress_source,
ADD COLUMN extracurricular_involvement ENUM('High (Leadership Role)', 'Moderate', 'Low', 'None') NULL AFTER coping_style;

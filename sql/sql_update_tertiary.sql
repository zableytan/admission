-- Update schema to add Tertiary Background fields for Medicine applicants
ALTER TABLE applications 
ADD COLUMN tertiary_name VARCHAR(255) AFTER secondary_dates,
ADD COLUMN tertiary_region VARCHAR(100) AFTER tertiary_name,
ADD COLUMN tertiary_address TEXT AFTER tertiary_region,
ADD COLUMN tertiary_school_type ENUM('Government', 'Private') AFTER tertiary_address,
ADD COLUMN tertiary_course_type ENUM('Medical', 'Non-medical') AFTER tertiary_school_type,
ADD COLUMN tertiary_degree VARCHAR(255) AFTER tertiary_course_type,
ADD COLUMN tertiary_gwa VARCHAR(50) AFTER tertiary_degree,
ADD COLUMN tertiary_honors TEXT AFTER tertiary_gwa;

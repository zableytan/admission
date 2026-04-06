ALTER TABLE applications 
ADD COLUMN diploma_path VARCHAR(255) NULL AFTER nmat_path,
ADD COLUMN gwa_cert_path VARCHAR(255) NULL AFTER diploma_path;

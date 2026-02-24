-- Fix missing columns for application tracking
ALTER TABLE applications 
ADD COLUMN birth_cert_path VARCHAR(255) NULL AFTER tor_path,
ADD COLUMN good_moral_path VARCHAR(255) NULL AFTER receipt_path,
ADD COLUMN record_pdf_path VARCHAR(255) NULL AFTER other_docs_paths;

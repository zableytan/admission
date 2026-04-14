-- Add Self-Assessment rating field
ALTER TABLE applications 
ADD COLUMN self_rating INT(1) AFTER tertiary_honors;

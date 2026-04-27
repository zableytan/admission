-- Add essay column to Step 4
ALTER TABLE applications ADD COLUMN application_essay TEXT AFTER pref_other_med_schools;

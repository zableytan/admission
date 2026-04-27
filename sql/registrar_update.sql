ALTER TABLE applications ADD COLUMN registrar_acknowledged TINYINT(1) DEFAULT 0 AFTER status;

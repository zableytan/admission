-- ============================================================
-- Migration: Add "To be followed" flag columns
-- Run this on your database to support the TBF document feature
-- Date: 2026-04-15
-- ============================================================

ALTER TABLE `applications`
    ADD COLUMN IF NOT EXISTS `tbf_tor`        TINYINT(1) NOT NULL DEFAULT 0 AFTER `tor_path`,
    ADD COLUMN IF NOT EXISTS `tbf_form137`    TINYINT(1) NOT NULL DEFAULT 0 AFTER `form137_path`,
    ADD COLUMN IF NOT EXISTS `tbf_diploma`    TINYINT(1) NOT NULL DEFAULT 0 AFTER `diploma_path`,
    ADD COLUMN IF NOT EXISTS `tbf_good_moral` TINYINT(1) NOT NULL DEFAULT 0 AFTER `good_moral_path`;

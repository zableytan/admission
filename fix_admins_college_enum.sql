-- ============================================================
-- Migration: Expand the `college` ENUM in the `admins` table
-- to include all current colleges and programs.
-- Run this on your database to fix the "Data truncated" error.
-- Date: 2026-04-15
-- ============================================================

ALTER TABLE `admins`
MODIFY COLUMN `college` ENUM(
    'Medicine',
    'Medicine (Filipino)',
    'Medicine (Foreign)',
    'Nursing',
    'Dentistry',
    'Midwifery',
    'Biology',
    'Accelerated Pathway for Medicine',
    'Master in Community Health',
    'Master in Health Professions Education',
    'Master in Participatory Development',
    'All Colleges',
    'All'
) NOT NULL;


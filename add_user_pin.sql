-- Migration: Add per-user status PIN to admin_users
-- Run this once to add the status_pin_hash column

ALTER TABLE admin_users
    ADD COLUMN IF NOT EXISTS status_pin_hash VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Hashed PIN for authorizing live trip status changes',
    ADD COLUMN IF NOT EXISTS status_pin_set_at DATETIME NULL DEFAULT NULL
        COMMENT 'When the status PIN was last changed';

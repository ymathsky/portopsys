-- ============================================================
-- Migration: New Features — Pre-booking, Priority Lane,
--            Counter Recall, Multi-window Token Transfer
-- Run once against the `queue_system` database
-- ============================================================
-- HOSTING: Select your database in phpMyAdmin first, then import.
-- Do NOT run this if you already imported the updated database.sql
-- ============================================================

-- 0. Add missing tables (vessels, schedules, announcements) if they don't exist yet
CREATE TABLE IF NOT EXISTS vessels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('cargo','roro','passenger','ferry','tugboat','other') DEFAULT 'other',
    max_capacity INT NOT NULL DEFAULT 0,
    registration_number VARCHAR(50),
    owner_name VARCHAR(100),
    contact_number VARCHAR(30),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS standard_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NULL,
    schedule_name VARCHAR(100) NOT NULL,
    trip_number_prefix VARCHAR(20),
    origin VARCHAR(100),
    destination VARCHAR(100),
    departure_time TIME,
    arrival_time TIME,
    fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monday TINYINT(1) DEFAULT 0,
    tuesday TINYINT(1) DEFAULT 0,
    wednesday TINYINT(1) DEFAULT 0,
    thursday TINYINT(1) DEFAULT 0,
    friday TINYINT(1) DEFAULT 0,
    saturday TINYINT(1) DEFAULT 0,
    sunday TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    effective_from DATE,
    effective_until DATE NULL,
    notes TEXT,
    capacity_per_trip INT NULL,
    trip_status ENUM('on_time','delayed','cancelled','departed','arrived') DEFAULT 'on_time',
    delay_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vessel_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NULL,
    trip_number VARCHAR(30),
    origin VARCHAR(100),
    destination VARCHAR(100),
    departure_time DATETIME,
    arrival_time DATETIME,
    status ENUM('scheduled','departed','arrived','cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schedule_exceptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    standard_schedule_id INT NULL,
    vessel_id INT NULL,
    exception_type ENUM('cancellation','delay','extra_trip','modification') DEFAULT 'cancellation',
    exception_date DATE NOT NULL,
    trip_number VARCHAR(30),
    origin VARCHAR(100),
    destination VARCHAR(100),
    departure_time TIME,
    arrival_time TIME,
    status VARCHAR(30),
    reason VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (standard_schedule_id) REFERENCES standard_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vessel_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NOT NULL,
    service_category_id INT NOT NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (service_category_id) REFERENCES service_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vessel_service (vessel_id, service_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('info','warning','danger','success') DEFAULT 'info',
    show_customer TINYINT(1) DEFAULT 1,
    show_display TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add missing columns to tokens table
ALTER TABLE tokens
    ADD COLUMN IF NOT EXISTS vessel_id INT NULL AFTER counter_id,
    ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER vessel_id,
    ADD COLUMN IF NOT EXISTS passenger_count INT NOT NULL DEFAULT 1 AFTER schedule_id,
    ADD COLUMN IF NOT EXISTS fare_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER passenger_count,
    ADD COLUMN IF NOT EXISTS booking_type ENUM('walkin','prebooked') NOT NULL DEFAULT 'walkin' AFTER fare_paid;

-- Add missing columns to admin_users table
ALTER TABLE admin_users
    ADD COLUMN IF NOT EXISTS status_pin_hash VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS status_pin_set_at DATETIME NULL;

-- Add audit_logs table if missing
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    record_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Fix priority_type ENUM to include customer-facing priority types
--    (DB had cargo types; customer form uses passenger types)
ALTER TABLE tokens
    MODIFY COLUMN priority_type
        ENUM('regular','senior','pwd','pregnant','student','emergency',
             'urgent','perishable','hazmat','express')
        NOT NULL DEFAULT 'regular';

-- 2. Add reservation columns for pre-booking feature
ALTER TABLE tokens
    ADD COLUMN IF NOT EXISTS reservation_code   VARCHAR(20)  NULL AFTER notes,
    ADD COLUMN IF NOT EXISTS reserved_for_date  DATE         NULL AFTER reservation_code,
    ADD COLUMN IF NOT EXISTS recall_count       TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER reserved_for_date;

-- Index for fast look-up by reservation code
CREATE INDEX IF NOT EXISTS idx_reservation_code ON tokens (reservation_code);

-- 3. Add system settings for the new features
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
    ('recall_timeout_minutes', '5',  'Minutes before a called token is auto-marked no-show if not recalled'),
    ('priority_lanes_enabled', '1',  'Enable priority lane (senior/PWD/pregnant/emergency) — 1=enabled'),
    ('prebooking_enabled',     '1',  'Allow customers to make advance reservations — 1=enabled'),
    ('prebooking_advance_days','7',  'How many days in advance customers can pre-book');

-- ============================================================
-- DONE — review the above then run:
--   mysql -u root queue_system < migrate_new_features.sql
-- ============================================================

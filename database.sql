-- Queue Management System Database Schema
-- Created: January 17, 2026
--
-- HOSTING IMPORT INSTRUCTIONS:
--   1. Create a database in cPanel → MySQL Databases (e.g. portops_queue)
--   2. Select that database in phpMyAdmin
--   3. Import this file — do NOT run CREATE DATABASE / USE here
-- ─────────────────────────────────────────────────────────────

-- Service Categories Table
CREATE TABLE IF NOT EXISTS service_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE,
    priority_level INT DEFAULT 0,
    avg_service_time INT DEFAULT 10,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    walkin_daily_limit INT NOT NULL DEFAULT 0,
    online_daily_limit INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service Counters Table
CREATE TABLE IF NOT EXISTS service_counters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    counter_number VARCHAR(10) NOT NULL UNIQUE,
    counter_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    current_status ENUM('available', 'serving', 'break', 'closed') DEFAULT 'available',
    staff_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Counter-Service Mapping (which services each counter can handle)
CREATE TABLE IF NOT EXISTS counter_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    counter_id INT NOT NULL,
    service_category_id INT NOT NULL,
    FOREIGN KEY (counter_id) REFERENCES service_counters(id) ON DELETE CASCADE,
    FOREIGN KEY (service_category_id) REFERENCES service_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_counter_service (counter_id, service_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tokens/Queue Table
CREATE TABLE IF NOT EXISTS tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_number VARCHAR(20) NOT NULL,
    service_category_id INT NOT NULL,
    customer_name VARCHAR(100),
    customer_mobile VARCHAR(20),
    customer_email VARCHAR(100),
    priority_type ENUM('regular','senior','pwd','pregnant','student','emergency','urgent','perishable','hazmat','express') DEFAULT 'regular',
    status ENUM('waiting', 'called', 'serving', 'completed', 'cancelled', 'no_show') DEFAULT 'waiting',
    queue_position INT,
    counter_id INT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    serving_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_wait_time INT,
    actual_wait_time INT,
    service_duration INT,
    notes TEXT,
    reservation_code VARCHAR(20) NULL,
    reserved_for_date DATE NULL,
    recall_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    booking_type ENUM('walkin','prebooked','online') NOT NULL DEFAULT 'walkin',
    vessel_id INT NULL,
    schedule_id INT NULL,
    passenger_count INT NOT NULL DEFAULT 1,
    passengers_json TEXT NULL,
    fare_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    customer_age TINYINT UNSIGNED NULL,
    customer_sex ENUM('male','female','other') NULL,
    customer_place VARCHAR(100) NULL,
    qr_expires_at DATETIME NULL,
    FOREIGN KEY (service_category_id) REFERENCES service_categories(id),
    FOREIGN KEY (counter_id) REFERENCES service_counters(id) ON DELETE SET NULL,
    INDEX idx_token_number (token_number),
    INDEX idx_status (status),
    INDEX idx_service_category (service_category_id),
    INDEX idx_issued_at (issued_at),
    INDEX idx_reservation_code (reservation_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Token Reset Log (used to restart per-service numbering after EOD reset)
CREATE TABLE IF NOT EXISTS token_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reset_at DATETIME NOT NULL,
    INDEX idx_reset_at (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vessels Table
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

-- Standard (recurring) Schedules Table
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

-- One-off Vessel Schedules Table
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

-- Schedule Exceptions Table (overrides/cancellations for standard schedules)
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

-- Vessel–Service Category Mapping
CREATE TABLE IF NOT EXISTS vessel_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NOT NULL,
    service_category_id INT NOT NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (service_category_id) REFERENCES service_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vessel_service (vessel_id, service_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements Table
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

-- Token History/Audit Log
CREATE TABLE IF NOT EXISTS token_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_id INT NOT NULL,
    status_from VARCHAR(20),
    status_to VARCHAR(20) NOT NULL,
    counter_id INT NULL,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_id INT NOT NULL,
    notification_type ENUM('sms', 'email', 'push') NOT NULL,
    recipient VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System Settings Table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('super_admin', 'admin', 'counter_staff') DEFAULT 'counter_staff',
    assigned_counter_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    status_pin_hash VARCHAR(255) NULL,
    status_pin_set_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_counter_id) REFERENCES service_counters(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Insert Default Service Categories (Port Management by Vessel Type)
INSERT INTO service_categories (name, code, priority_level, avg_service_time, description) VALUES
('Cargo Ship - Berthing', 'CGO-BTH', 3, 25, 'Cargo ship berthing and docking services'),
('Cargo Ship - Loading/Unloading', 'CGO-LUD', 2, 45, 'Cargo ship container loading and unloading operations'),
('Cargo Ship - Documentation', 'CGO-DOC', 2, 20, 'Cargo ship customs and documentation processing'),
('RORO - Vehicle Entry', 'ROR-ENT', 3, 15, 'Roll-on/Roll-off vehicle entry processing'),
('RORO - Vehicle Exit', 'ROR-EXT', 3, 15, 'Roll-on/Roll-off vehicle exit processing'),
('RORO - Documentation', 'ROR-DOC', 2, 18, 'RORO vessel documentation and manifests'),
('Boat - Berthing', 'BOT-BTH', 2, 10, 'Small boat and vessel berthing services'),
('Boat - Passenger Services', 'BOT-PSG', 2, 12, 'Passenger boat boarding and disembarkation'),
('Boat - Customs/Immigration', 'BOT-CUS', 3, 15, 'Boat customs and immigration clearance'),
('General - Inspection', 'GEN-INS', 3, 20, 'General cargo and vessel inspection'),
('Emergency Services', 'EMG-SRV', 4, 15, 'Emergency port services for all vessel types');

-- Insert Default Service Counters (Port Service Points by Vessel Type)
INSERT INTO service_counters (counter_number, counter_name, is_active, current_status) VALUES
('CGO-W1', 'Cargo Ship - Window 1', 1, 'available'),
('CGO-W2', 'Cargo Ship - Window 2', 1, 'available'),
('CGO-W3', 'Cargo Ship - Window 3', 1, 'available'),
('ROR-W1', 'RORO - Window 1', 1, 'available'),
('ROR-W2', 'RORO - Window 2', 1, 'available'),
('ROR-G1', 'RORO - Gate 1', 1, 'available'),
('BOT-W1', 'Boat Services - Window 1', 1, 'available'),
('BOT-W2', 'Boat Services - Window 2', 1, 'available'),
('INS-1', 'Inspection Bay 1', 1, 'available'),
('INS-2', 'Inspection Bay 2', 1, 'available');

-- Map all services to all counters (flexible setup)
INSERT INTO counter_services (counter_id, service_category_id)
SELECT c.id, s.id FROM service_counters c CROSS JOIN service_categories s;

-- Insert Default System Settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('business_hours_start', '08:00', 'Business opening time'),
('business_hours_end', '17:00', 'Business closing time'),
('max_tokens_per_day', '500', 'Maximum tokens that can be issued per day'),
('sms_enabled', '0', 'Enable SMS notifications (0=disabled, 1=enabled)'),
('sms_api_key', '', 'SMS provider API key'),
('email_enabled', '0', 'Enable email notifications'),
('auto_call_interval', '300', 'Auto-call next token after X seconds'),
('display_refresh_interval', '3', 'Display board refresh interval in seconds'),
('show_estimated_time', '1', 'Show estimated wait time to customers'),
('recall_timeout_minutes', '5', 'Minutes before a called token is auto-marked no-show if not recalled'),
('priority_lanes_enabled', '1', 'Enable priority lane (senior/PWD/pregnant/emergency) — 1=enabled'),
('prebooking_enabled', '1', 'Allow customers to make advance reservations — 1=enabled'),
('prebooking_advance_days', '7', 'How many days in advance customers can pre-book');

-- Insert Default Admin User (username: admin, password: admin123)
INSERT INTO admin_users (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$10$Du44RCoV1DLMsDCfU3LQDuR1DogUlH/8h8SFmqE/EElRB52U88Esi', 'System Administrator', 'admin@queueSystem.local', 'super_admin');

-- Create Views for Easy Querying

-- Active Queue View
CREATE OR REPLACE VIEW active_queue AS
SELECT 
    t.id,
    t.token_number,
    t.customer_name,
    t.customer_mobile,
    t.priority_type,
    t.status,
    t.queue_position,
    t.estimated_wait_time,
    t.issued_at,
    t.called_at,
    t.serving_at,
    t.counter_id,
    sc.name as service_category,
    sc.code as service_code,
    sc.priority_level,
    c.counter_number,
    c.counter_name,
    TIMESTAMPDIFF(MINUTE, t.issued_at, NOW()) as actual_waiting_minutes
FROM tokens t
INNER JOIN service_categories sc ON t.service_category_id = sc.id
LEFT JOIN service_counters c ON t.counter_id = c.id
WHERE t.status IN ('waiting', 'called', 'serving')
ORDER BY 
    CASE t.priority_type
        WHEN 'emergency' THEN 1
        WHEN 'urgent'    THEN 2
        WHEN 'senior'    THEN 3
        WHEN 'pwd'       THEN 3
        WHEN 'pregnant'  THEN 3
        WHEN 'hazmat'    THEN 4
        WHEN 'perishable' THEN 5
        WHEN 'express'   THEN 6
        ELSE 7
    END,
    sc.priority_level DESC,
    t.issued_at ASC;

-- Counter Status View
CREATE OR REPLACE VIEW counter_status_view AS
SELECT 
    c.id,
    c.counter_number,
    c.counter_name,
    c.current_status,
    c.staff_name,
    t.token_number as current_token,
    t.customer_name as current_customer,
    sc.name as current_service,
    COALESCE(t.serving_at, t.called_at) as serving_at,
    TIMESTAMPDIFF(MINUTE, COALESCE(t.serving_at, t.called_at), NOW()) as service_duration_minutes
FROM service_counters c
LEFT JOIN tokens t ON c.id = t.counter_id AND t.status IN ('called', 'serving')
LEFT JOIN service_categories sc ON t.service_category_id = sc.id
WHERE c.is_active = 1;

-- Daily Statistics View
CREATE OR REPLACE VIEW daily_statistics AS
SELECT 
    DATE(issued_at) as date,
    COUNT(*) as total_tokens,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tokens,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_tokens,
    COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_tokens,
    AVG(CASE WHEN actual_wait_time IS NOT NULL THEN actual_wait_time END) as avg_wait_time,
    AVG(CASE WHEN service_duration IS NOT NULL THEN service_duration END) as avg_service_time,
    MAX(actual_wait_time) as max_wait_time
FROM tokens
GROUP BY DATE(issued_at)
ORDER BY date DESC;

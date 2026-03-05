-- Enhanced Schedule System with Standard Schedules and Exceptions
-- Run this to upgrade the schedule system

USE queue_system;

-- Standard Schedules Table (Recurring schedules by day of week)
CREATE TABLE IF NOT EXISTS standard_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT,
    schedule_name VARCHAR(100) NOT NULL,
    trip_number_prefix VARCHAR(20),
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_time TIME NOT NULL COMMENT 'Daily departure time',
    arrival_time TIME COMMENT 'Estimated arrival time',
    -- Days of week (1 = active, 0 = inactive)
    monday TINYINT(1) DEFAULT 0,
    tuesday TINYINT(1) DEFAULT 0,
    wednesday TINYINT(1) DEFAULT 0,
    thursday TINYINT(1) DEFAULT 0,
    friday TINYINT(1) DEFAULT 0,
    saturday TINYINT(1) DEFAULT 0,
    sunday TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    effective_from DATE NOT NULL COMMENT 'Schedule starts from this date',
    effective_until DATE COMMENT 'Schedule ends on this date (NULL = no end)',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    INDEX idx_active (is_active),
    INDEX idx_vessel (vessel_id),
    INDEX idx_effective_dates (effective_from, effective_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Schedule Exceptions Table (One-time overrides or special schedules)
CREATE TABLE IF NOT EXISTS schedule_exceptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    standard_schedule_id INT NULL COMMENT 'NULL if its a standalone exception',
    vessel_id INT,
    exception_type ENUM('cancellation', 'time_change', 'special_trip', 'delay') DEFAULT 'special_trip',
    exception_date DATE NOT NULL,
    trip_number VARCHAR(50),
    origin VARCHAR(100),
    destination VARCHAR(100),
    departure_time TIME,
    arrival_time TIME,
    status ENUM('scheduled', 'boarding', 'departed', 'arrived', 'cancelled', 'delayed') DEFAULT 'scheduled',
    reason TEXT COMMENT 'Reason for exception',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (standard_schedule_id) REFERENCES standard_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    INDEX idx_date (exception_date),
    INDEX idx_type (exception_type),
    INDEX idx_vessel (vessel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add schedule reference to existing vessel_schedules if needed
ALTER TABLE vessel_schedules 
ADD COLUMN IF NOT EXISTS standard_schedule_id INT NULL COMMENT 'Reference to standard schedule if generated from one',
ADD COLUMN IF NOT EXISTS is_exception TINYINT(1) DEFAULT 0 COMMENT 'Is this an exception schedule',
ADD FOREIGN KEY IF NOT EXISTS (standard_schedule_id) REFERENCES standard_schedules(id) ON DELETE SET NULL;

-- Sample Standard Schedule Data (using vessel_id 1 which exists)
INSERT INTO standard_schedules 
(vessel_id, schedule_name, trip_number_prefix, origin, destination, departure_time, arrival_time, monday, tuesday, wednesday, thursday, friday, saturday, sunday, is_active, effective_from) 
VALUES
(1, 'Manila-Cebu Daily Route', 'MNL-CEB', 'Port of Manila', 'Port of Cebu', '08:00:00', '20:00:00', 1, 1, 1, 1, 1, 1, 1, 1, '2026-01-01'),
(1, 'Manila-Davao Weekday Express', 'MNL-DVO', 'Port of Manila', 'Port of Davao', '06:00:00', '22:00:00', 1, 1, 1, 1, 1, 0, 0, 1, '2026-01-01');


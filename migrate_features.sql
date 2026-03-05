-- ============================================================
-- Alabat Port QMS - Feature Migration
-- ============================================================

-- 1. standard_schedules: trip status, delay reason, capacity
ALTER TABLE standard_schedules
    ADD COLUMN trip_status ENUM('on_time','delayed','cancelled','boarding','departed') NOT NULL DEFAULT 'on_time',
    ADD COLUMN delay_reason VARCHAR(255) NULL,
    ADD COLUMN capacity_per_trip INT NULL DEFAULT NULL;

-- 2. tokens: booking type, schedule link, fare paid, passenger count
ALTER TABLE tokens
    ADD COLUMN booking_type ENUM('walkin','prebooked') NOT NULL DEFAULT 'walkin',
    ADD COLUMN schedule_id INT NULL,
    ADD COLUMN fare_paid DECIMAL(10,2) NULL DEFAULT NULL,
    ADD COLUMN passenger_count INT NOT NULL DEFAULT 1;

-- 3. Fix priority_type enum to include passenger types
ALTER TABLE tokens
    MODIFY COLUMN priority_type ENUM('regular','senior','pwd','pregnant','student','emergency') DEFAULT 'regular';

-- 4. Announcements table
CREATE TABLE IF NOT EXISTS announcements (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    title       VARCHAR(150) NOT NULL,
    body        TEXT NOT NULL,
    type        ENUM('info','warning','danger','success') NOT NULL DEFAULT 'info',
    show_customer TINYINT(1) NOT NULL DEFAULT 1,
    show_display  TINYINT(1) NOT NULL DEFAULT 1,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    starts_at   DATETIME NULL,
    ends_at     DATETIME NULL,
    created_by  VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


USE queue_system;

-- Vessels Table
CREATE TABLE IF NOT EXISTS vessels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('cargo', 'roro', 'boat', 'other') DEFAULT 'other',
    registration_number VARCHAR(50),
    owner_name VARCHAR(100),
    contact_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vessel Schedules Table
CREATE TABLE IF NOT EXISTS vessel_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT,
    trip_number VARCHAR(50),
    origin VARCHAR(100),
    destination VARCHAR(100),
    departure_time DATETIME,
    arrival_time DATETIME,
    status ENUM('scheduled', 'boarding', 'departed', 'arrived', 'cancelled', 'delayed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update tokens to link to schedule (optional, but good for linking token to trip)
-- Using a stored procedure to safely add column if not exists (or just try ADD COLUMN and ignore error if exists)
SET @dbname = DATABASE();
SET @tablename = "tokens";
SET @columnname = "schedule_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE tokens ADD COLUMN schedule_id INT NULL; ALTER TABLE tokens ADD CONSTRAINT fk_tokens_schedule FOREIGN KEY (schedule_id) REFERENCES vessel_schedules(id) ON DELETE SET NULL;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

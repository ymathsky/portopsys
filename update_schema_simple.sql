
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

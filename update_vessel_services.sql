-- Create vessel_services table to link vessels with service categories
CREATE TABLE IF NOT EXISTS vessel_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NOT NULL,
    service_category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (service_category_id) REFERENCES service_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vessel_service (vessel_id, service_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

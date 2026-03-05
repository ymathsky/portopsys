-- ============================================================
-- Alabat Port Queuing Management System
-- Service & Counter Setup Migration
-- Alabat, Quezon, Philippines
-- ============================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing counter-service mappings, services, and counters
DELETE FROM counter_services;
DELETE FROM service_categories;
DELETE FROM service_counters;

-- Reset auto increment
ALTER TABLE service_categories AUTO_INCREMENT = 1;
ALTER TABLE service_counters AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- ALABAT PORT - Essential Services
-- ============================================================
INSERT INTO service_categories (name, code, priority_level, avg_service_time, description) VALUES
('Emergency Services',        'EMG-SRV', 5, 5,  'Emergency and urgent port assistance'),
('Passenger Ticketing',       'PSG-TKT', 4, 8,  'Ticket purchase, reservation, and validation'),
('Passenger Boarding',        'PSG-BOD', 3, 10, 'Passenger check-in and vessel boarding'),
('RoRo / Vehicle Processing', 'ROR-VHC', 3, 15, 'Roll-on/Roll-off vehicle loading and unloading'),
('Cargo / Freight',           'CGO-FRT', 2, 20, 'Cargo and freight handling, documentation');

-- ============================================================
-- ALABAT PORT - Service Counters
-- ============================================================
INSERT INTO service_counters (counter_number, counter_name, is_active, current_status) VALUES
('TKT-1', 'Ticketing - Window 1',  1, 'available'),
('TKT-2', 'Ticketing - Window 2',  1, 'available'),
('PSG-1', 'Passenger - Window 1',  1, 'available'),
('PSG-2', 'Passenger - Window 2',  1, 'available'),
('ROR-1', 'RoRo / Vehicle Gate',   1, 'available'),
('CGO-1', 'Cargo - Window 1',      1, 'available'),
('EMG-1', 'Emergency Counter',     1, 'available');

-- Map all services to all counters (flexible)
INSERT INTO counter_services (counter_id, service_category_id)
SELECT c.id, s.id FROM service_counters c CROSS JOIN service_categories s;

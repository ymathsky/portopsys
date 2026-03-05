-- ============================================================
--  PASSENGER MANIFEST DEMO SEED DATA
--  Run this in phpMyAdmin against your portopsys database.
--  Safe to run multiple times (uses INSERT IGNORE).
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+08:00';

-- ── 1. SAMPLE VESSELS ────────────────────────────────────────
INSERT IGNORE INTO vessels (id, name, type, max_capacity, registration_number, owner_name, contact_number) VALUES
(10, 'MV Mabuhay Star',    'ferry',     120, 'REG-2024-001', 'Alabat Lines Corp.',  '0917-111-2233'),
(11, 'MV Quezon Express',  'roro',      200, 'REG-2024-002', 'QC Shipping Inc.',    '0918-444-5566'),
(12, 'MB Isla Bonita',     'passenger',  60, 'REG-2024-003', 'Isla Bonita Ferry',   '0919-777-8899');

-- ── 2. STANDARD SCHEDULES (active all 7 days) ────────────────
INSERT IGNORE INTO standard_schedules
    (id, vessel_id, schedule_name, trip_number_prefix,
     origin, destination, departure_time, arrival_time,
     fare, capacity_per_trip,
     monday, tuesday, wednesday, thursday, friday, saturday, sunday,
     effective_from, effective_until, notes, trip_status)
VALUES
(10, 10, 'Alabat – Perez Morning Trip',   'ABT-AM',
     'Alabat Port', 'Perez, Quezon', '07:00:00', '08:30:00',
     120.00, 120,  1,1,1,1,1,1,1, '2024-01-01', NULL, 'Morning ferry service', 'on_time'),

(11, 10, 'Alabat – Perez Afternoon Trip', 'ABT-PM',
     'Alabat Port', 'Perez, Quezon', '13:00:00', '14:30:00',
     120.00, 120,  1,1,1,1,1,1,1, '2024-01-01', NULL, 'Afternoon ferry service', 'on_time'),

(12, 11, 'Quezon RORO Morning',           'QZN-AM',
     'Lucena Port', 'Alabat Island', '06:30:00', '08:00:00',
     250.00, 200,  1,1,1,1,1,1,1, '2024-01-01', NULL, 'RORO vehicle + passenger', 'on_time'),

(13, 12, 'Isla Bonita Express',           'IBX-01',
     'Alabat Port', 'Real, Quezon',  '09:00:00', '10:00:00',
     85.00,  60,   1,1,1,1,1,1,1, '2024-01-01', NULL, 'Express passenger boat', 'departed');

-- ── 3. PASSENGER TOKENS ──────────────────────────────────────
--   Using service_category_id = 8 (Boat - Passenger Services)
--   Counter  id = 7 (BOT-W1)
--   Statuses: completed, waiting, called, serving, cancelled, no_show
-- ─────────────────────────────────────────────────────────────

-- Helper: all issued_at timestamps use CURDATE() so
-- they appear under "today" in the manifest.

INSERT IGNORE INTO tokens
 (token_number, service_category_id, customer_name, customer_mobile, customer_email,
  priority_type, status, queue_position, counter_id,
  issued_at, called_at, serving_at, completed_at,
  estimated_wait_time, actual_wait_time, service_duration,
  booking_type, vessel_id, schedule_id, passenger_count, fare_paid, notes)
VALUES

-- ── Trip ABT-AM (schedule_id=10, vessel_id=10) ─────────────

('ABT-AM-001', 8, 'Juan Dela Cruz',       '09171234567', 'juan@email.com',
 'regular',   'completed', 1, 7,
 CONCAT(CURDATE(),' 06:10:00'), CONCAT(CURDATE(),' 06:45:00'), CONCAT(CURDATE(),' 06:46:00'), CONCAT(CURDATE(),' 06:55:00'),
 30, 35, 9,  'walkin',   10, 10, 2, 240.00, NULL),

('ABT-AM-002', 8, 'Maria Santos',         '09189876543', 'maria@email.com',
 'senior',    'completed', 2, 7,
 CONCAT(CURDATE(),' 06:15:00'), CONCAT(CURDATE(),' 06:46:00'), CONCAT(CURDATE(),' 06:47:00'), CONCAT(CURDATE(),' 06:58:00'),
 28, 31, 11, 'walkin',   10, 10, 1, 120.00, 'Senior citizen discount applied'),

('ABT-AM-003', 8, 'Pedro Reyes',          '09201112233', NULL,
 'regular',   'completed', 3, 7,
 CONCAT(CURDATE(),' 06:20:00'), CONCAT(CURDATE(),' 06:47:00'), CONCAT(CURDATE(),' 06:48:00'), CONCAT(CURDATE(),' 07:00:00'),
 25, 27, 12, 'prebooked',10, 10, 3, 360.00, NULL),

('ABT-AM-004', 8, 'Ana Gonzales',         '09221234567', 'ana@gmail.com',
 'pwd',       'completed', 4, 7,
 CONCAT(CURDATE(),' 06:25:00'), CONCAT(CURDATE(),' 06:48:00'), CONCAT(CURDATE(),' 06:49:00'), CONCAT(CURDATE(),' 07:02:00'),
 22, 23, 13, 'walkin',   10, 10, 1, 120.00, 'PWD — wheelchair assisted'),

('ABT-AM-005', 8, 'Roberto Lim',          '09331231234', NULL,
 'regular',   'completed', 5, 7,
 CONCAT(CURDATE(),' 06:28:00'), CONCAT(CURDATE(),' 06:50:00'), CONCAT(CURDATE(),' 06:51:00'), CONCAT(CURDATE(),' 07:05:00'),
 20, 22, 14, 'walkin',   10, 10, 2, 240.00, NULL),

('ABT-AM-006', 8, 'Cynthia Flores',       '09451112222', NULL,
 'pregnant',  'completed', 6, 7,
 CONCAT(CURDATE(),' 06:30:00'), CONCAT(CURDATE(),' 06:51:00'), CONCAT(CURDATE(),' 06:52:00'), CONCAT(CURDATE(),' 07:06:00'),
 18, 21, 14, 'walkin',   10, 10, 1, 120.00, 'Pregnant passenger — priority boarding'),

('ABT-AM-007', 8, 'Danilo Cruz',           NULL,           NULL,
 'regular',   'completed', 7, 7,
 CONCAT(CURDATE(),' 06:35:00'), CONCAT(CURDATE(),' 06:52:00'), CONCAT(CURDATE(),' 06:53:00'), CONCAT(CURDATE(),' 07:10:00'),
 15, 18, 17, 'walkin',   10, 10, 4, 480.00, NULL),

('ABT-AM-008', 8, 'Elisa Bautista',       '09561231234', NULL,
 'student',   'completed', 8, 7,
 CONCAT(CURDATE(),' 06:38:00'), CONCAT(CURDATE(),' 06:53:00'), CONCAT(CURDATE(),' 06:54:00'), CONCAT(CURDATE(),' 07:12:00'),
 13, 15, 18, 'prebooked',10, 10, 1, 120.00, 'Student ID verified'),

('ABT-AM-009', 8, 'Fernando Aquino',      '09671112233', NULL,
 'regular',   'no_show',   9, 7,
 CONCAT(CURDATE(),' 06:40:00'), CONCAT(CURDATE(),' 06:55:00'), NULL, NULL,
 10, NULL, NULL, 'walkin',  10, 10, 2, 240.00, 'Did not appear after 2 calls'),

('ABT-AM-010', 8, 'Gloria Mendoza',       '09781234567', 'gloria@email.com',
 'senior',    'completed', 10, 7,
 CONCAT(CURDATE(),' 06:42:00'), CONCAT(CURDATE(),' 06:56:00'), CONCAT(CURDATE(),' 06:57:00'), CONCAT(CURDATE(),' 07:15:00'),
 8,  13, 18, 'walkin',   10, 10, 1, 120.00, NULL),

('ABT-AM-011', 8, 'Hernando Villanueva',  '09891231234', NULL,
 'regular',   'cancelled', 11, NULL,
 CONCAT(CURDATE(),' 06:45:00'), NULL, NULL, NULL,
 5,  NULL, NULL, 'walkin',  10, 10, 2, 0.00,  'Passenger cancelled — missed boat'),

('ABT-AM-012', 8, 'Imelda Torres',        '09901112222', NULL,
 'regular',   'completed', 12, 7,
 CONCAT(CURDATE(),' 06:48:00'), CONCAT(CURDATE(),' 06:58:00'), CONCAT(CURDATE(),' 06:59:00'), CONCAT(CURDATE(),' 07:18:00'),
 5,  10, 19, 'prebooked',10, 10, 3, 360.00, NULL),

-- ── Trip ABT-PM (schedule_id=11, vessel_id=10) ─────────────

('ABT-PM-001', 8, 'Jose Ramos',           '09121234567', NULL,
 'regular',   'completed', 1, 7,
 CONCAT(CURDATE(),' 12:10:00'), CONCAT(CURDATE(),' 12:45:00'), CONCAT(CURDATE(),' 12:46:00'), CONCAT(CURDATE(),' 12:58:00'),
 30, 35, 12, 'walkin',   10, 11, 2, 240.00, NULL),

('ABT-PM-002', 8, 'Katrina Dela Rosa',    '09231112233', 'kat@email.com',
 'student',   'completed', 2, 7,
 CONCAT(CURDATE(),' 12:15:00'), CONCAT(CURDATE(),' 12:46:00'), CONCAT(CURDATE(),' 12:47:00'), CONCAT(CURDATE(),' 13:00:00'),
 28, 30, 13, 'prebooked',10, 11, 1, 120.00, 'University student'),

('ABT-PM-003', 8, 'Lorenzo Pascual',      '09341231234', NULL,
 'regular',   'completed', 3, 7,
 CONCAT(CURDATE(),' 12:20:00'), CONCAT(CURDATE(),' 12:47:00'), CONCAT(CURDATE(),' 12:48:00'), CONCAT(CURDATE(),' 13:02:00'),
 25, 28, 14, 'walkin',   10, 11, 1, 120.00, NULL),

('ABT-PM-004', 8, 'Mila Ocampo',          '09451234567', 'mila@email.com',
 'senior',    'completed', 4, 7,
 CONCAT(CURDATE(),' 12:25:00'), CONCAT(CURDATE(),' 12:48:00'), CONCAT(CURDATE(),' 12:49:00'), CONCAT(CURDATE(),' 13:05:00'),
 22, 23, 16, 'walkin',   10, 11, 1, 120.00, 'Senior citizen, 60+ years'),

('ABT-PM-005', 8, 'Nestor Garcia',        '09561112222', NULL,
 'regular',   'waiting',   5, NULL,
 CONCAT(CURDATE(),' 12:30:00'), NULL, NULL, NULL,
 20, NULL, NULL, 'walkin',  10, 11, 3, 360.00, NULL),

('ABT-PM-006', 8, 'Olivia Fernandez',     '09671234567', NULL,
 'pwd',       'waiting',   6, NULL,
 CONCAT(CURDATE(),' 12:32:00'), NULL, NULL, NULL,
 18, NULL, NULL, 'walkin',  10, 11, 1, 120.00, 'Mobility impaired'),

('ABT-PM-007', 8, 'Pablo Castillo',       '09781112233', 'pablo@email.com',
 'regular',   'called',    7, 7,
 CONCAT(CURDATE(),' 12:35:00'), CONCAT(CURDATE(),' 12:55:00'), NULL, NULL,
 15, NULL, NULL, 'prebooked',10, 11, 2, 240.00, NULL),

('ABT-PM-008', 8, 'Queenie Alvarez',      '09891231234', NULL,
 'regular',   'serving',   8, 7,
 CONCAT(CURDATE(),' 12:38:00'), CONCAT(CURDATE(),' 12:50:00'), CONCAT(CURDATE(),' 12:51:00'), NULL,
 12, 12, NULL,'walkin',   10, 11, 4, 480.00, NULL),

-- ── Trip QZN-AM RORO (schedule_id=12, vessel_id=11) ────────

('QZN-AM-001', 4, 'Romeo dela Peña',      '09121112222', NULL,
 'regular',   'completed', 1, 4,
 CONCAT(CURDATE(),' 05:30:00'), CONCAT(CURDATE(),' 06:00:00'), CONCAT(CURDATE(),' 06:01:00'), CONCAT(CURDATE(),' 06:20:00'),
 40, 30, 19, 'walkin',   11, 12, 1, 250.00, '1 motorcycle'),

('QZN-AM-002', 4, 'Susan Navarro',        '09231234567', NULL,
 'regular',   'completed', 2, 4,
 CONCAT(CURDATE(),' 05:35:00'), CONCAT(CURDATE(),' 06:01:00'), CONCAT(CURDATE(),' 06:02:00'), CONCAT(CURDATE(),' 06:22:00'),
 38, 26, 20, 'prebooked',11, 12, 5, 1250.00, 'Family of 5 + 1 SUV'),

('QZN-AM-003', 4, 'Teodoro Magtoto',      '09341112233', NULL,
 'regular',   'completed', 3, 4,
 CONCAT(CURDATE(),' 05:40:00'), CONCAT(CURDATE(),' 06:02:00'), CONCAT(CURDATE(),' 06:03:00'), CONCAT(CURDATE(),' 06:25:00'),
 35, 23, 22, 'walkin',   11, 12, 2, 500.00, NULL),

('QZN-AM-004', 4, 'Ursulina Espiritu',    '09451234567', NULL,
 'senior',    'completed', 4, 4,
 CONCAT(CURDATE(),' 05:45:00'), CONCAT(CURDATE(),' 06:03:00'), CONCAT(CURDATE(),' 06:04:00'), CONCAT(CURDATE(),' 06:28:00'),
 30, 18, 24, 'walkin',   11, 12, 1, 250.00, NULL),

('QZN-AM-005', 4, 'Vicente Salazar',      '09561231234', NULL,
 'regular',   'completed', 5, 4,
 CONCAT(CURDATE(),' 05:48:00'), CONCAT(CURDATE(),' 06:04:00'), CONCAT(CURDATE(),' 06:05:00'), CONCAT(CURDATE(),' 06:30:00'),
 28, 17, 25, 'prebooked',11, 12, 3, 750.00, '1 tricycle'),

('QZN-AM-006', 4, 'Wilma Batungbakal',    '09671112222', NULL,
 'regular',   'cancelled', 6, NULL,
 CONCAT(CURDATE(),' 05:50:00'), NULL, NULL, NULL,
 25, NULL, NULL, 'walkin',  11, 12, 2, 0.00,  'Cancelled — vehicle breakdown'),

-- ── Trip IBX-01 Isla Bonita (schedule_id=13, vessel_id=12) ─

('IBX-001', 8, 'Xavier Reyes',          '09121231234', NULL,
 'regular',   'completed', 1, 7,
 CONCAT(CURDATE(),' 08:20:00'), CONCAT(CURDATE(),' 08:50:00'), CONCAT(CURDATE(),' 08:51:00'), CONCAT(CURDATE(),' 09:05:00'),
 30, 30, 14, 'walkin',   12, 13, 2, 170.00, NULL),

('IBX-002', 8, 'Yolanda Medina',        '09231112233', 'yolanda@email.com',
 'student',   'completed', 2, 7,
 CONCAT(CURDATE(),' 08:25:00'), CONCAT(CURDATE(),' 08:51:00'), CONCAT(CURDATE(),' 08:52:00'), CONCAT(CURDATE(),' 09:07:00'),
 28, 26, 15, 'prebooked',12, 13, 1, 85.00,  'Field trip — Quezon National University'),

('IBX-003', 8, 'Zenaida Castro',        '09341234567', NULL,
 'regular',   'completed', 3, 7,
 CONCAT(CURDATE(),' 08:28:00'), CONCAT(CURDATE(),' 08:52:00'), CONCAT(CURDATE(),' 08:53:00'), CONCAT(CURDATE(),' 09:10:00'),
 25, 22, 17, 'walkin',   12, 13, 1, 85.00,  NULL),

('IBX-004', 8, 'Alfredo Santos',        '09451112222', NULL,
 'senior',    'completed', 4, 7,
 CONCAT(CURDATE(),' 08:30:00'), CONCAT(CURDATE(),' 08:53:00'), CONCAT(CURDATE(),' 08:54:00'), CONCAT(CURDATE(),' 09:12:00'),
 22, 20, 18, 'walkin',   12, 13, 1, 85.00,  'Senior citizen'),

('IBX-005', 8, 'Babylyn Soriano',       '09561234567', NULL,
 'regular',   'completed', 5, 7,
 CONCAT(CURDATE(),' 08:32:00'), CONCAT(CURDATE(),' 08:54:00'), CONCAT(CURDATE(),' 08:55:00'), CONCAT(CURDATE(),' 09:15:00'),
 20, 18, 20, 'walkin',   12, 13, 3, 255.00, NULL),

('IBX-006', 8, 'Carlos Ignacio',        NULL,           NULL,
 'regular',   'no_show',   6, 7,
 CONCAT(CURDATE(),' 08:35:00'), CONCAT(CURDATE(),' 08:55:00'), NULL, NULL,
 18, NULL, NULL, 'prebooked',12, 13, 2, 170.00, 'Pre-booked, did not show'),

('IBX-007', 8, 'Delia Padilla',         '09781112233', NULL,
 'pregnant',  'completed', 7, 7,
 CONCAT(CURDATE(),' 08:38:00'), CONCAT(CURDATE(),' 08:56:00'), CONCAT(CURDATE(),' 08:57:00'), CONCAT(CURDATE(),' 09:18:00'),
 15, 20, 21, 'walkin',   12, 13, 1, 85.00,  'Priority boarding — 7 months pregnant');

-- ── Summary ──────────────────────────────────────────────────
-- After import, visit:
--   /admin/manifest.php
-- Select today's date to see all 4 trips with full passenger lists.
-- ─────────────────────────────────────────────────────────────
SELECT 'Seed complete!' AS status,
       (SELECT COUNT(*) FROM tokens  WHERE token_number LIKE 'ABT-%' OR token_number LIKE 'QZN-%' OR token_number LIKE 'IBX-%') AS tokens_inserted,
       (SELECT COUNT(*) FROM vessels WHERE id IN (10,11,12))   AS vessels_inserted,
       (SELECT COUNT(*) FROM standard_schedules WHERE id IN (10,11,12,13)) AS schedules_inserted;

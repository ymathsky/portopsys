-- Add maximum capacity column to vessels table
ALTER TABLE vessels ADD COLUMN max_capacity INT DEFAULT 0 AFTER type;

-- Update existing vessels with default capacity
UPDATE vessels SET max_capacity = 100 WHERE type = 'boat';
UPDATE vessels SET max_capacity = 200 WHERE type = 'roro';
UPDATE vessels SET max_capacity = 150 WHERE type = 'cargo';
UPDATE vessels SET max_capacity = 50 WHERE type = 'other';

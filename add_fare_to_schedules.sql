-- Add fare/ticket price to standard schedules table
USE queue_system;

ALTER TABLE standard_schedules 
ADD COLUMN fare DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Ticket price/fare for this route' AFTER arrival_time;

ALTER TABLE schedule_exceptions 
ADD COLUMN fare DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Ticket price/fare for this route' AFTER arrival_time;

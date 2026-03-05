-- Migration: Add 'boarding' to standard_schedules.trip_status ENUM
-- Run this ONCE on your hosting database.
-- Without this, setting a trip to "Boarding" silently fails (not in the original ENUM).

ALTER TABLE standard_schedules
    MODIFY COLUMN trip_status
        ENUM('on_time','boarding','delayed','cancelled','departed','arrived')
        NOT NULL DEFAULT 'on_time';

-- Done. The ALTER TABLE above already ran successfully.

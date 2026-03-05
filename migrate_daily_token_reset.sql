-- Migration: Enable daily token number reset
-- Run this ONCE on the live database.
-- -----------------------------------------------------------------------

-- 1. Drop the old unique constraint on token_number
ALTER TABLE tokens DROP INDEX token_number;

-- 2. Add a regular (non-unique) index for query performance
ALTER TABLE tokens ADD INDEX idx_token_number (token_number);

-- 3. Create token_resets log table (EOD reset inserts a row here;
--    generateTokenNumber counts only tokens issued after the last row)
CREATE TABLE IF NOT EXISTS token_resets (
    id       INT PRIMARY KEY AUTO_INCREMENT,
    reset_at DATETIME NOT NULL,
    INDEX idx_reset_at (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Insert a seed row so existing tokens (before this migration) are
--    treated as pre-reset and the NEXT token issued starts at 0001.
INSERT INTO token_resets (reset_at) VALUES (NOW());

-- Done!

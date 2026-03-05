-- Migration: Enable daily token number reset
-- Run this ONCE on the live database to allow token numbers (e.g. RORO-PSG-0001)
-- to reset to 0001 at the start of each new day.
--
-- Root cause: The UNIQUE constraint on token_number prevented the same number
-- from being reused on a new day (e.g. RORO-PSG-0001 on March 4 blocked
-- RORO-PSG-0001 on March 5). The sequence counter already resets per-day
-- in code (DATE(issued_at) = CURDATE()), but the DB constraint stopped it.
--
-- After this migration:
--   • Each new calendar day starts fresh at 0001 per service code
--   • End-of-Day Reset + next calendar day both produce 0001
--   • Uniqueness within a day is still guaranteed via GET_LOCK in PHP
-- -----------------------------------------------------------------------

-- 1. Drop the old unique constraint on token_number
--    (MySQL named it 'token_number' by default when defined inline)
ALTER TABLE tokens DROP INDEX token_number;

-- 2. Add a regular (non-unique) index for query performance
ALTER TABLE tokens ADD INDEX idx_token_number (token_number);

-- Done! No data changes needed; existing rows are unaffected.
-- SELECT 'Migration complete: token_number is no longer globally unique.' AS result;

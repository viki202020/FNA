-- =====================================================================
-- Re-opening a closed financial year (administrator only)
-- =====================================================================
-- Every year except the company's current year is closed: read only,
-- hidden from the year boxes by default. This table records the rare
-- cases where an administrator deliberately re-opens one, and why.
-- While a year is re-opened it accepts changes and shows a red banner.
-- Closing it again = fill in closed_again_at.
-- AFTER ANY CHANGE to a re-opened year, check the next year's opening
-- balances (the opening-balance script / Close Book).
-- =====================================================================

-- STEP 1: create the log table (run once)
CREATE TABLE IF NOT EXISTS financial_year_reopen (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  merchant_id     INT          NOT NULL,
  financial_year  VARCHAR(20)  NOT NULL,
  reason          VARCHAR(500) NOT NULL,
  reopened_by     VARCHAR(100) NOT NULL,
  reopened_at     DATETIME     NOT NULL,
  closed_again_at DATETIME     NULL,
  KEY idx_year (merchant_id, financial_year)
);

-- STEP 2: re-open a year (edit the three values, then run)
-- INSERT INTO financial_year_reopen (merchant_id, financial_year, reason, reopened_by, reopened_at)
-- VALUES (1, '2022-2023', 'Why this year must be re-opened', 'Your name', NOW());

-- STEP 3: close it again when the work is finished
-- UPDATE financial_year_reopen SET closed_again_at = NOW()
-- WHERE merchant_id = 1 AND financial_year = '2022-2023' AND closed_again_at IS NULL;

-- Who has re-opened what, and when (read-only)
-- SELECT * FROM financial_year_reopen ORDER BY id;

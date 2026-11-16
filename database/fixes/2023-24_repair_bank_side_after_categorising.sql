-- =====================================================================
-- Repair the bank-side entries of FY 2023-2024 after categorising lines
-- =====================================================================
-- WHY the Trial Balance was out: when a line is categorised with
--   * "Assign Manually (BULK)", "Apply Rule" or "Apply Rule (BULK)" the
--     app saved the account but did NOT create the matching entry on the
--     bank account;
--   * "Assign Manually" (single line) the app created that entry but left
--     its financial year empty, so reports could not see it.
-- This script fixes both, for AU SFB-5398 (bank/head id 24), FY 2023-2024.
-- It is safe to run more than once.
--
-- HOW TO USE (HeidiSQL, database fna_claude selected, after a backup):
--   STEP 1  preview (read-only)
--   STEP 2  run A and B (B ends with COMMIT)
--   STEP 3  check: missing_bank_side must be 0, and the Trial Balance
--           for 2023-2024 must balance.
-- =====================================================================


-- ---------------------------- STEP 1: preview (read-only) ------------
SELECT
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0 AND status = 1
     AND bank_id = 24 AND head_id <> '24' AND voucher_no IS NULL)            AS categorised_lines,
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_rows,
  (SELECT COUNT(*) FROM statement WHERE fyear IS NULL AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_without_year;


-- ---------------------------- STEP 2 ---------------------------------
-- A: bank-side entries that have no financial year
UPDATE statement
SET fyear = '2023-2024'
WHERE fyear IS NULL AND opening = 0 AND head_id = '24' AND bank_id = 24;

-- B: add the missing bank-side entry for each categorised line
--    (counts identical lines separately, so two equal lines on one day
--     each get their own entry)
INSERT INTO statement
  (voucher_no, debit, credit, remark, head_id, st_date, bank_id, fyear,
   opening, status, merchant_id, created_at, created_by)
SELECT NULL, o.credit, o.debit, o.remark, '24', o.st_date, 24, o.fyear,
       0, 1, o.merchant_id, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), o.created_by
FROM (
  SELECT s.*, ROW_NUMBER() OVER (PARTITION BY s.st_date, s.remark, s.debit, s.credit ORDER BY s.id) AS rn
  FROM statement s
  WHERE s.fyear = '2023-2024' AND s.opening = 0 AND s.status = 1
    AND s.bank_id = 24 AND s.head_id <> '24' AND s.voucher_no IS NULL
) o
WHERE (SELECT COUNT(*) FROM statement x
       WHERE x.head_id = '24' AND x.bank_id = 24 AND x.opening = 0 AND x.fyear = '2023-2024'
         AND x.st_date = o.st_date AND x.remark = o.remark
         AND x.debit = o.credit AND x.credit = o.debit) < o.rn
ORDER BY o.id;

COMMIT;


-- ---------------------------- STEP 3: check (read-only) --------------
-- Expect: bank_side_rows = categorised_lines, missing_bank_side = 0
SELECT
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0 AND status = 1
     AND bank_id = 24 AND head_id <> '24' AND voucher_no IS NULL)            AS categorised_lines,
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_rows,
  (SELECT COUNT(*) FROM statement WHERE fyear IS NULL AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_without_year;

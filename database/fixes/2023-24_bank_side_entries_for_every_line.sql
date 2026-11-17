-- =====================================================================
-- Bank-side entry for EVERY bank line of FY 2023-2024 (AU SFB-5398)
-- =====================================================================
-- Every line of a bank statement must have its own entry on the bank
-- account, exactly as the bank shows it (money in = debit on the bank,
-- money out = credit). Categorising a line later only chooses the
-- account for the other, reversed entry.
--
-- This script adds the bank entry for every 2023-24 line that lacks one,
-- whether the line is categorised or not, and gives a financial year to
-- bank entries that have none. It is safe to run more than once.
--
-- AFTER IT: the AU bank balance equals the bank statement at all times
-- (2,287.96 on 28-03-2024). The Trial Balance TOTAL is out only by the
-- lines that are not categorised yet.
--
-- HOW TO USE (HeidiSQL, database fna_claude selected, after a backup):
--   STEP 1  preview (read-only)
--   STEP 2  run A and B (B ends with COMMIT)
--   STEP 3  check: bank_side_rows must equal bank_lines, missing = 0
-- =====================================================================


-- ---------------------------- STEP 1: preview (read-only) ------------
SELECT
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0 AND bank_id = 24
     AND (head_id IS NULL OR head_id <> '24') AND (voucher_no IS NULL OR voucher_no LIKE 'SBV-%'))        AS bank_lines,
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_rows,
  (SELECT COUNT(*) FROM statement WHERE fyear IS NULL AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_without_year;


-- ---------------------------- STEP 2 ---------------------------------
-- A: bank-side entries that have no financial year
UPDATE statement
SET fyear = '2023-2024'
WHERE fyear IS NULL AND opening = 0 AND head_id = '24' AND bank_id = 24;

-- B: add the missing bank entry for each line (equal lines on one day are
--    counted separately, so each gets its own entry)
INSERT INTO statement
  (voucher_no, debit, credit, remark, head_id, st_date, bank_id, fyear,
   opening, status, merchant_id, created_at, created_by)
SELECT NULL, o.credit, o.debit, o.remark, '24', o.st_date, 24, o.fyear,
       0, 1, o.merchant_id, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), o.created_by
FROM (
  SELECT s.*, ROW_NUMBER() OVER (PARTITION BY s.st_date, s.remark, s.debit, s.credit ORDER BY s.id) AS rn
  FROM statement s
  WHERE s.fyear = '2023-2024' AND s.opening = 0 AND s.bank_id = 24
    AND (s.head_id IS NULL OR s.head_id <> '24') AND (s.voucher_no IS NULL OR s.voucher_no LIKE 'SBV-%')
) o
WHERE (SELECT COUNT(*) FROM statement x
       WHERE x.head_id = '24' AND x.bank_id = 24 AND x.opening = 0 AND x.fyear = '2023-2024'
         AND x.st_date = o.st_date AND x.remark = o.remark
         AND x.debit = o.credit AND x.credit = o.debit) < o.rn
ORDER BY o.id;

COMMIT;


-- ---------------------------- STEP 3: check (read-only) --------------
-- Expect: bank_side_rows = bank_lines (100), bank_side_without_year = 0
SELECT
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0 AND bank_id = 24
     AND (head_id IS NULL OR head_id <> '24') AND (voucher_no IS NULL OR voucher_no LIKE 'SBV-%'))        AS bank_lines,
  (SELECT COUNT(*) FROM statement WHERE fyear = '2023-2024' AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_rows,
  (SELECT COUNT(*) FROM statement WHERE fyear IS NULL AND opening = 0
     AND head_id = '24' AND bank_id = 24)                                    AS bank_side_without_year;

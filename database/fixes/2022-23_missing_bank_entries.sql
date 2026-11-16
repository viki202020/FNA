-- =====================================================================
-- Missing bank-side entries for AU SFB-5398 (head id 24), FY 2022-2023
-- =====================================================================
-- WHY: 140 bank-statement lines were posted to income/expense/other
--      accounts, but the matching entry on the bank account itself was
--      never created, so the bank balance never moved and the Trial
--      Balance / Balance Sheet do not balance.
--
-- CHECKED against the real AU SFB-5398 statement: all 140 lines match
--      the 140 rows in the books by date and amount. The net movement is
--      -1,725,575.42 and the closing balance on 17-03-2023 is 22,832.96.
--
-- HOW TO USE (in HeidiSQL, with database fna_claude selected):
--   0. FIRST export a backup of the whole database.
--   1. Run STEP 1 only (select it and press F9). It changes nothing.
--      Expect: rows_missing = 140, total_debit = 6627865.50,
--              total_credit = 4902290.08
--   2. Run STEP 2 (inserts 140 rows). It does nothing if the bank
--      entries already exist, so running it twice is safe.
--   3. Run STEP 3 and compare with the expected values.
--
-- The new rows copy date, remark and amounts from the existing rows and
-- swap debit and credit, exactly as the app's own import does.
-- =====================================================================


-- ---------------------------- STEP 1: preview (read-only) ------------
SELECT COUNT(*) AS rows_missing,
       SUM(CAST(NULLIF(NULLIF(debit,'-'),'') AS DECIMAL(15,2)))  AS total_debit,
       SUM(CAST(NULLIF(NULLIF(credit,'-'),'') AS DECIMAL(15,2))) AS total_credit
FROM statement
WHERE voucher_no IS NULL AND opening = 0 AND status = 1
  AND fyear = '2022-2023' AND bank_id IS NULL;


-- ---------------------------- STEP 2: add the bank-side rows ---------
INSERT INTO statement
  (voucher_no, debit, credit, remark, head_id, st_date, bank_id, fyear,
   opening, status, merchant_id, created_at, created_by)
SELECT NULL, s.credit, s.debit, s.remark, '24', s.st_date, 24, s.fyear,
       0, 1, s.merchant_id, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), s.created_by
FROM statement s
WHERE s.voucher_no IS NULL AND s.opening = 0 AND s.status = 1
  AND s.fyear = '2022-2023' AND s.bank_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM statement x
                  WHERE x.head_id = '24' AND x.bank_id = 24
                    AND x.opening = 0 AND x.fyear = '2022-2023');


-- ---------------------------- STEP 3: check the result (read-only) ---
-- Expect: rows_added = 140, bank_debit = 4902290.08, bank_credit = 6627865.50
SELECT COUNT(*) AS rows_added,
       SUM(CAST(NULLIF(NULLIF(debit,'-'),'') AS DECIMAL(15,2)))  AS bank_debit,
       SUM(CAST(NULLIF(NULLIF(credit,'-'),'') AS DECIMAL(15,2))) AS bank_credit
FROM statement
WHERE head_id = '24' AND bank_id = 24 AND opening = 0 AND fyear = '2022-2023';

-- Expect: closing = 22832.96  (opening 1748408.38 + bank_debit - bank_credit)
SELECT 1748408.38
       + IFNULL(SUM(CAST(NULLIF(NULLIF(debit,'-'),'')  AS DECIMAL(15,2))),0)
       - IFNULL(SUM(CAST(NULLIF(NULLIF(credit,'-'),'') AS DECIMAL(15,2))),0) AS closing_balance
FROM statement
WHERE head_id = '24' AND opening = 0 AND fyear = '2022-2023';

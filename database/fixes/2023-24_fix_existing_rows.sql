-- =====================================================================
-- Fix the 54 bank lines for AU SFB-5398 that are in the database with NO
-- financial year (April - November 2023)
-- =====================================================================
-- They are invisible to every year report because fyear is empty.
--   31 rows  not yet categorised   (status 0)
--   21 rows  categorised, but with no bank-side entry (status 1, head set)
--    2 rows  bank-side entries that have no account set (status 1, head empty;
--            the two "AMB NON MAINTENANCE CHARGE" lines of 9 Jun and 8 Aug 2023)
--
-- HOW TO USE (HeidiSQL, database fna_claude selected, after a backup):
--   STEP 1  preview.  Expect: 31 / 21 / 2.
--   STEP 2  run A, B, C and D.  Each is safe to run twice.
--   STEP 3  check.
-- =====================================================================


-- ---------------------------- STEP 1: preview (read-only) ------------
SELECT CASE WHEN status = 0 THEN 'A: not categorised'
            WHEN head_id IS NULL OR head_id = '' THEN 'C: bank-side, no account'
            ELSE 'B: categorised' END AS kind,
       COUNT(*) AS n
FROM statement WHERE fyear IS NULL GROUP BY 1 ORDER BY 1;


-- ---------------------------- STEP 2 ---------------------------------
-- A + B: give the 52 original lines their year and bank
UPDATE statement
SET fyear = '2023-2024', bank_id = 24
WHERE fyear IS NULL AND (status = 0 OR (status = 1 AND head_id IS NOT NULL AND head_id <> ''));

-- C: the 2 bank-side entries with no account -> the AU bank account
UPDATE statement
SET head_id = '24', bank_id = 24, fyear = '2023-2024'
WHERE fyear IS NULL AND status = 1 AND (head_id IS NULL OR head_id = '');

-- D: add the missing bank-side entry for each categorised line
--    (lines that already have one, e.g. the two above, are skipped)
INSERT INTO statement
  (voucher_no, debit, credit, remark, head_id, st_date, bank_id, fyear,
   opening, status, merchant_id, created_at, created_by)
SELECT NULL, s.credit, s.debit, s.remark, '24', s.st_date, 24, '2023-2024',
       0, 1, s.merchant_id, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), s.created_by
FROM statement s
WHERE s.fyear = '2023-2024' AND s.opening = 0 AND s.status = 1
  AND s.bank_id = 24 AND s.head_id <> '24' AND s.voucher_no IS NULL
  AND NOT EXISTS (SELECT 1 FROM statement x
                  WHERE x.head_id = '24' AND x.bank_id = 24 AND x.fyear = '2023-2024'
                    AND x.opening = 0 AND x.st_date = s.st_date AND x.remark = s.remark
                    AND x.debit = s.credit AND x.credit = s.debit)
ORDER BY s.id;

COMMIT;


-- ---------------------------- STEP 3: check (read-only) --------------
-- Expect: 31 not categorised, 21 categorised (+19 new bank-side + 2 fixed = 21 on AU)
SELECT 'rows with no year left' AS what, COUNT(*) AS n FROM statement WHERE fyear IS NULL
UNION ALL
SELECT 'not categorised, 2023-24', COUNT(*) FROM statement WHERE fyear = '2023-2024' AND status = 0
UNION ALL
SELECT 'categorised lines, 2023-24', COUNT(*) FROM statement
  WHERE fyear = '2023-2024' AND status = 1 AND opening = 0 AND bank_id = 24 AND head_id <> '24'
UNION ALL
SELECT 'bank-side entries on AU, 2023-24', COUNT(*) FROM statement
  WHERE fyear = '2023-2024' AND opening = 0 AND head_id = '24' AND bank_id = 24;

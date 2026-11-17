-- =====================================================================
-- Voucher 26 (1 April 2022): record the 1,000 difference from the audited
-- 2021-22 balance sheet
-- =====================================================================
-- Voucher 26 has debits of 12,000 and credits of 13,000. The 1,000 comes
-- from the 2021-22 audited opening figures, which the auditors had out by
-- 1,000. This adds a fourth line, a DEBIT of 1,000 to Retained Earnings
-- (a prior-period adjustment), so the voucher balances and its reason is
-- written down. Nothing else changes. Safe to run twice.
--
-- HOW TO USE (HeidiSQL, database fna_claude selected, after a backup):
--   STEP 1  preview: shows voucher 26 and the difference (1000.00)
--   STEP 2  run the INSERT (it ends with COMMIT)
--   STEP 3  check: difference must now be 0.00
-- =====================================================================


-- ---------------------------- STEP 1: preview (read-only) ------------
SELECT s.id, s.st_date, h.name AS account, s.debit, s.credit, LEFT(s.remark, 60) AS remark
FROM statement s LEFT JOIN head h ON h.id = s.head_id
WHERE s.voucher_no = '26' ORDER BY s.id;

SELECT ROUND(SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2)))
           - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))), 2) AS difference
FROM statement WHERE voucher_no = '26';


-- ---------------------------- STEP 2: add the line -------------------
INSERT INTO statement
  (voucher_no, debit, credit, remark, head_id, st_date, bank_id, fyear,
   opening, status, merchant_id, created_at, created_by)
SELECT '26', '1000', '',
       'Difference in 2021-22 audited opening balances (auditors) - prior period adjustment',
       CAST(h.id AS CHAR), '2022-04-01', NULL, '2022-2023',
       0, 1, 1, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), 1
FROM head h
WHERE h.name = 'Retained Earnings' AND h.status = 1
  AND NOT EXISTS (SELECT 1 FROM statement x
                  WHERE x.voucher_no = '26' AND x.head_id = CAST(h.id AS CHAR)
                    AND x.opening = 0 AND x.fyear = '2022-2023');

COMMIT;


-- ---------------------------- STEP 3: check (read-only) --------------
-- Expect: difference = 0.00
SELECT ROUND(SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2)))
           - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))), 2) AS difference
FROM statement WHERE voucher_no = '26';

-- =====================================================================
-- Opening balances for FY 2023-2024 (carried forward from 2022-2023)
-- =====================================================================
-- Same rules as the app's Close Book button (as corrected):
--   * every Balance Sheet account starts 2023-24 with its 2022-23 closing
--     balance (opening row + all entries, debit minus credit);
--   * Profit & Loss accounts start again from zero;
--   * the 2022-23 profit/loss is added to Retained Earnings.
--
-- RUN THIS ONLY AFTER:
--   1. the 2022-23 bank repair (2022-23_missing_bank_entries.sql), and
--   2. voucher 26 has been corrected (its two sides differ by 1,000.00).
--
-- HOW TO USE (HeidiSQL, database fna_claude selected, after a backup):
--   STEP 1  run the preview. Check "difference" = 0.00 (a non-zero value
--           means 2022-23 still does not balance; do NOT go on).
--   STEP 2  run the INSERT (it does nothing if 2023-24 openings exist).
--   STEP 3  run the check.
-- =====================================================================


-- ---------------------------- STEP 1: preview (read-only) ------------
SELECT h.id AS head_id, h.name,
       ROUND(IFNULL(x.dr,0) - IFNULL(x.cr,0)
             + IF(h.name = 'Retained Earnings', IFNULL(pl.net,0), 0), 2) AS opening_dr_minus_cr
FROM head h
JOIN account_type a        ON a.id = h.account_type_id
JOIN account_type_parent p ON p.id = a.parent_id AND p.financial_statement_id = 1
LEFT JOIN (SELECT head_id,
                  SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2))) AS dr,
                  SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))) AS cr
           FROM statement
           WHERE fyear = '2022-2023' AND status = 1 AND merchant_id = 1
           GROUP BY head_id) x ON x.head_id = h.id
CROSS JOIN (SELECT IFNULL(SUM(IFNULL(y.dr,0) - IFNULL(y.cr,0)),0) AS net
            FROM head h2
            JOIN account_type a2        ON a2.id = h2.account_type_id
            JOIN account_type_parent p2 ON p2.id = a2.parent_id AND p2.financial_statement_id = 2
            LEFT JOIN (SELECT head_id,
                              SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2))) AS dr,
                              SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))) AS cr
                       FROM statement
                       WHERE fyear = '2022-2023' AND status = 1 AND merchant_id = 1
                       GROUP BY head_id) y ON y.head_id = h2.id
            WHERE h2.status = 1 AND (h2.merchant = 1 OR h2.merchant IS NULL)) pl
WHERE h.status = 1 AND (h.merchant = 1 OR h.merchant IS NULL)
HAVING ABS(opening_dr_minus_cr) >= 0.005
ORDER BY h.id;

-- difference between total debit and total credit of ALL 2022-23 entries
-- (must be 0.00 once voucher 26 is corrected)
SELECT ROUND(SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2)))
           - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))), 2) AS difference
FROM statement
WHERE fyear = '2022-2023' AND status = 1 AND merchant_id = 1;


-- ---------------------------- STEP 2: create the opening rows --------
INSERT INTO statement
  (voucher_no, debit, credit, remark, head_id, st_date, bank_id, fyear,
   opening, status, merchant_id, created_at, created_by)
SELECT NULL,
       IF(t.bal > 0, TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(t.bal AS CHAR))), ''),
       IF(t.bal < 0, TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(-t.bal AS CHAR))), ''),
       'Opening Balance', t.head_id, '2023-04-01', NULL, '2023-2024',
       1, 1, 1, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), 1
FROM (
  SELECT h.id AS head_id,
         ROUND(IFNULL(x.dr,0) - IFNULL(x.cr,0)
               + IF(h.name = 'Retained Earnings', IFNULL(pl.net,0), 0), 2) AS bal
  FROM head h
  JOIN account_type a        ON a.id = h.account_type_id
  JOIN account_type_parent p ON p.id = a.parent_id AND p.financial_statement_id = 1
  LEFT JOIN (SELECT head_id,
                    SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2))) AS dr,
                    SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))) AS cr
             FROM statement
             WHERE fyear = '2022-2023' AND status = 1 AND merchant_id = 1
             GROUP BY head_id) x ON x.head_id = h.id
  CROSS JOIN (SELECT IFNULL(SUM(IFNULL(y.dr,0) - IFNULL(y.cr,0)),0) AS net
              FROM head h2
              JOIN account_type a2        ON a2.id = h2.account_type_id
              JOIN account_type_parent p2 ON p2.id = a2.parent_id AND p2.financial_statement_id = 2
              LEFT JOIN (SELECT head_id,
                                SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'')  AS DECIMAL(15,2))) AS dr,
                                SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))) AS cr
                         FROM statement
                         WHERE fyear = '2022-2023' AND status = 1 AND merchant_id = 1
                         GROUP BY head_id) y ON y.head_id = h2.id
              WHERE h2.status = 1 AND (h2.merchant = 1 OR h2.merchant IS NULL)) pl
  WHERE h.status = 1 AND (h.merchant = 1 OR h.merchant IS NULL)
  HAVING ABS(bal) >= 0.005
) t
WHERE NOT EXISTS (SELECT 1 FROM statement z WHERE z.fyear = '2023-2024' AND z.opening = 1)
ORDER BY t.head_id;

COMMIT;


-- ---------------------------- STEP 3: check (read-only) --------------
-- Expect: total_debit = total_credit once voucher 26 is corrected.
SELECT COUNT(*) AS opening_rows,
       SUM(CAST(NULLIF(NULLIF(debit,'-'),'')  AS DECIMAL(15,2))) AS total_debit,
       SUM(CAST(NULLIF(NULLIF(credit,'-'),'') AS DECIMAL(15,2))) AS total_credit
FROM statement WHERE fyear = '2023-2024' AND opening = 1;

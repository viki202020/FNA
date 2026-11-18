-- Lost phone AND lost recovery codes? An administrator can reset one user's authenticator.
-- The user then signs in with their password and sets up a new authenticator.
-- Nothing else about the user changes.
--
-- STEP 1: check who you are about to reset (change the email).
SELECT id, name, email, totp_enabled FROM merchant WHERE email = 'user@example.com';

-- STEP 2: reset (run only when STEP 1 shows exactly the right person).
UPDATE merchant
SET totp_enabled = 0, totp_secret = NULL, totp_last_step = 0, recovery_codes = NULL
WHERE email = 'user@example.com';
COMMIT;

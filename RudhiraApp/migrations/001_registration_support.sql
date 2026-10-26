-- =====================================================================
-- Migration 001: support self-registration, roles and login throttling
-- For databases created from the ORIGINAL database.sql that already hold data.
-- It only ADDS columns/tables. No rows are deleted and no table is dropped.
--
-- !! Run this ONCE. Running it twice fails with "Duplicate column name" (harmless).
-- !! Fresh installs using the new database.sql do NOT need this file.
-- BACK UP FIRST: phpMyAdmin -> rudhira_db -> Export -> Quick -> SQL -> Go.
-- =====================================================================
USE rudhira_db;

ALTER TABLE volunteers
    ADD COLUMN email  VARCHAR(100) NULL AFTER name,
    ADD COLUMN role   ENUM('volunteer','admin') NOT NULL DEFAULT 'volunteer' AFTER department,
    ADD COLUMN status ENUM('Active','Disabled') NOT NULL DEFAULT 'Active' AFTER role,
    ADD UNIQUE KEY uq_volunteers_email (email);

-- The oldest existing account becomes the administrator (needed for CSV import).
-- Change later with: UPDATE volunteers SET role='admin' WHERE username='someone';
UPDATE volunteers SET role = 'admin' ORDER BY id ASC LIMIT 1;

-- Records failed logins / registration attempts for rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_type  ENUM('login','register') NOT NULL,
    username_key  VARCHAR(50) NOT NULL DEFAULT '',
    ip_address    VARCHAR(45) NOT NULL,
    attempted_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempts_ip   (attempt_type, ip_address, attempted_at),
    INDEX idx_attempts_user (attempt_type, username_key, ip_address, attempted_at)
) ENGINE=InnoDB;

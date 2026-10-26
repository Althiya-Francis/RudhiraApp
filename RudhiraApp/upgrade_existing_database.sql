-- =====================================================================
-- upgrade_existing_database.sql
-- ONLY for people who already imported the OLD RudhiraApp database.sql.
-- Run it ONCE in phpMyAdmin (database rudhira_db -> SQL tab), then import database.sql.
-- It deletes nothing. It only makes 'students' able to store the PDF data:
--   * adds the new columns  place  and  registered_at
--   * allows NULL in contact_number, year and department (the PDF has no year,
--     and one donor has no valid phone number)
-- If phpMyAdmin says "Duplicate column name 'place'" you already ran it - that is fine.
-- =====================================================================
USE rudhira_db;

ALTER TABLE students
    MODIFY contact_number VARCHAR(15) NULL,
    MODIFY year           VARCHAR(10) NULL,
    MODIFY department     VARCHAR(20) NULL,
    ADD COLUMN place         VARCHAR(50) NULL AFTER contact_number,
    ADD COLUMN registered_at DATETIME    NULL AFTER donor_status;

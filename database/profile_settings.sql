-- HomeBid buyer and seller profile settings
-- Run this once in phpMyAdmin after selecting house_and_lot_db.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS last_name VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS phone_number VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS mailing_address VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS auction_notifications TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS marketing_notifications TINYINT(1) NOT NULL DEFAULT 0;

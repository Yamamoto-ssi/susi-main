-- HomeBid supplemental schema requirements
-- Import database/house_and_lot_db.sql first, then run this file in phpMyAdmin.
-- Uses MariaDB's IF NOT EXISTS syntax so it can be safely rerun on XAMPP/MariaDB.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS last_name VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS phone_number VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS mailing_address VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS auction_notifications TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS marketing_notifications TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS seller_applications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,
    id_type VARCHAR(100) NOT NULL,
    id_number VARCHAR(100) NOT NULL,
    id_front_path VARCHAR(255) NOT NULL,
    selfie_with_id_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected','needs_revision') NOT NULL DEFAULT 'pending',
    reviewer_id INT(11) DEFAULT NULL,
    reviewer_note TEXT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_seller_applications_user (user_id),
    KEY idx_seller_applications_status (status),
    KEY idx_seller_applications_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE seller_applications
    ADD COLUMN IF NOT EXISTS user_id INT(11) NOT NULL,
    ADD COLUMN IF NOT EXISTS full_name VARCHAR(160) NOT NULL,
    ADD COLUMN IF NOT EXISTS phone_number VARCHAR(30) NOT NULL,
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS id_type VARCHAR(100) NOT NULL,
    ADD COLUMN IF NOT EXISTS id_number VARCHAR(100) NOT NULL,
    ADD COLUMN IF NOT EXISTS id_front_path VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS selfie_with_id_path VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected','needs_revision') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS reviewer_id INT(11) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reviewer_note TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reviewed_at DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS password_change_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    new_password_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_change_token (token_hash),
    KEY idx_password_change_user (user_id),
    KEY idx_password_change_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE password_change_requests
    ADD COLUMN IF NOT EXISTS user_id INT(11) NOT NULL,
    ADD COLUMN IF NOT EXISTS token_hash CHAR(64) NOT NULL,
    ADD COLUMN IF NOT EXISTS new_password_hash VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS expires_at DATETIME NOT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

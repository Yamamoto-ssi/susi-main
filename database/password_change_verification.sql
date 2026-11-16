-- HomeBid: email verification for password changes
-- Run this once in phpMyAdmin after selecting house_and_lot_db.
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
    KEY idx_password_change_expiry (expires_at),
    CONSTRAINT fk_password_change_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

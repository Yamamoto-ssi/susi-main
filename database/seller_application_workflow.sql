-- HomeBid seller application workflow
-- Run in phpMyAdmin after selecting house_and_lot_db.
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
    KEY idx_seller_app_user (user_id),
    KEY idx_seller_app_status (status),
    CONSTRAINT fk_seller_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_seller_app_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
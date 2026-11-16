-- Separate review status for auction schedules.
ALTER TABLE auctions
    ADD COLUMN review_status ENUM('pending','admin_approval','needs_revision','approved') NOT NULL DEFAULT 'approved' AFTER status,
    ADD COLUMN review_feedback TEXT DEFAULT NULL AFTER review_status,
    ADD COLUMN reviewed_by INT(11) DEFAULT NULL AFTER review_feedback,
    ADD COLUMN reviewed_at DATETIME DEFAULT NULL AFTER reviewed_by,
    ADD KEY idx_auctions_review_status (review_status),
    ADD CONSTRAINT fk_auctions_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

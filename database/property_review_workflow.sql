-- HomeBid property review workflow
ALTER TABLE properties
    ADD COLUMN review_status ENUM('pending','admin_approval','needs_revision','approved') NOT NULL DEFAULT 'pending' AFTER is_approved,
    ADD COLUMN review_feedback TEXT DEFAULT NULL AFTER review_status,
    ADD COLUMN reviewed_by INT(11) DEFAULT NULL AFTER review_feedback,
    ADD COLUMN reviewed_at DATETIME DEFAULT NULL AFTER reviewed_by,
    ADD COLUMN submission_type ENUM('listing','auction') NOT NULL DEFAULT 'listing' AFTER reviewed_at,
    ADD KEY idx_properties_review_status (review_status),
    ADD CONSTRAINT fk_properties_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

UPDATE properties
SET review_status = CASE WHEN is_approved=1 THEN 'approved' ELSE 'pending' END;


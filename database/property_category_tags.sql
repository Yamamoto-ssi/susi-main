-- HomeBid: add property category and searchable tags.
-- Run this migration once after selecting house_and_lot_db.

ALTER TABLE properties
    ADD COLUMN category VARCHAR(80) DEFAULT NULL AFTER description;

CREATE TABLE IF NOT EXISTS property_tags (
    id INT(11) NOT NULL AUTO_INCREMENT,
    property_id INT(11) NOT NULL,
    tag_name VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_property_tag (property_id, tag_name),
    KEY idx_property_tags_name (tag_name),
    CONSTRAINT fk_property_tags_property
        FOREIGN KEY (property_id) REFERENCES properties(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

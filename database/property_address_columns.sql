-- HomeBid: add structured Philippine address fields to properties.
-- Existing location values are preserved for compatibility with current pages.
ALTER TABLE properties
    ADD COLUMN region VARCHAR(100) DEFAULT NULL AFTER location,
    ADD COLUMN province VARCHAR(150) DEFAULT NULL AFTER region,
    ADD COLUMN city VARCHAR(150) DEFAULT NULL AFTER province,
    ADD COLUMN barangay VARCHAR(150) DEFAULT NULL AFTER city,
    ADD COLUMN lot_number VARCHAR(100) DEFAULT NULL AFTER barangay;

-- HomeBid bid notification trigger fix
-- Run once in phpMyAdmin with house_and_lot_db selected.
-- The previous trigger attempted to UPDATE bids from an AFTER INSERT trigger
-- on bids, which MariaDB rejects (error 1442).
-- Outbid statuses are now updated by property-view.php before inserting a new bid.

DROP TRIGGER IF EXISTS homebid_after_bid_insert;

DELIMITER $$

CREATE TRIGGER homebid_after_bid_insert
AFTER INSERT ON bids
FOR EACH ROW
BEGIN
    -- Notify buyers whose earlier bids are lower than the newly placed bid.
    INSERT INTO notifications (user_id, title, message, related_entity, related_id)
    SELECT DISTINCT b.user_id,
           'You have been outbid',
           CONCAT('A higher bid of ₱', FORMAT(NEW.bid_amount, 2), ' was placed on ', p.title, '. Your bid is no longer the highest.'),
           'property',
           NEW.property_id
    FROM bids b
    INNER JOIN properties p ON p.id = NEW.property_id
    WHERE b.property_id = NEW.property_id
      AND b.user_id <> NEW.user_id
      AND b.bid_amount < NEW.bid_amount
      AND b.status IN ('Pending', 'Accepted');

    -- Notify the property owner about the new bid.
    INSERT INTO notifications (user_id, title, message, related_entity, related_id)
    SELECT p.user_id,
           'New bid on your property',
           CONCAT('A buyer placed a bid of ₱', FORMAT(NEW.bid_amount, 2), ' on ', p.title, '.'),
           'property',
           NEW.property_id
    FROM properties p
    WHERE p.id = NEW.property_id
      AND p.user_id <> NEW.user_id;
END$$

DELIMITER ;

-- Confirm the corrected trigger is installed.
SHOW TRIGGERS LIKE 'bids';

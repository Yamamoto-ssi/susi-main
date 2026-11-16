-- HomeBid bid and purchase notifications
-- Run this once in phpMyAdmin after selecting house_and_lot_db.

DROP TRIGGER IF EXISTS homebid_after_bid_insert;
DROP TRIGGER IF EXISTS homebid_after_payment_paid;

DELIMITER $$

CREATE TRIGGER homebid_after_bid_insert
AFTER INSERT ON bids
FOR EACH ROW
BEGIN
    -- Alert buyers whose earlier bids are now lower than the newly placed bid.
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

    -- Mark those earlier bids as outbid.
    UPDATE bids
    SET status = 'Outbid'
    WHERE property_id = NEW.property_id
      AND user_id <> NEW.user_id
      AND bid_amount < NEW.bid_amount
      AND status IN ('Pending', 'Accepted');

    -- Alert the property owner about every newly recorded bid.
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

CREATE TRIGGER homebid_after_payment_paid
AFTER UPDATE ON checkout_payments
FOR EACH ROW
BEGIN
    -- A purchase notification is sent only when payment first becomes paid.
    IF NEW.status = 'paid' AND OLD.status <> 'paid' THEN
        INSERT INTO notifications (user_id, title, message, related_entity, related_id)
        SELECT p.user_id,
               'Property purchased',
               CONCAT('A buyer completed payment for ', p.title, '.'),
               'property',
               NEW.property_id
        FROM properties p
        WHERE p.id = NEW.property_id
          AND p.user_id <> NEW.user_id;
    END IF;
END$$

DELIMITER ;

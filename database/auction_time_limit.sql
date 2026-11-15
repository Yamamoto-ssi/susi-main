-- HomeBid: auction time limits and automatic result recording
-- Database: house_and_lot_db
-- Run this file once in phpMyAdmin > SQL while house_and_lot_db is selected.
--
-- Existing HomeBid columns used:
-- auctions: id, property_id, start_time, end_time, status, starting_bid, current_bid
-- bids: id, property_id, user_id, bid_amount, created_at
--
-- The event scheduler runs every minute. Server-side bid validation must ALSO
-- reject bids when NOW() >= auctions.end_time; a browser countdown is not security.

CREATE TABLE IF NOT EXISTS auction_results (
    id INT NOT NULL AUTO_INCREMENT,
    auction_id INT NOT NULL,
    winner_user_id INT NULL,
    winning_bid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    result_status VARCHAR(30) NOT NULL DEFAULT 'no_bids',
    ended_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auction_results_auction (auction_id),
    KEY idx_auction_results_winner (winner_user_id),
    KEY idx_auction_results_status (result_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep upcoming/active statuses synchronized with the configured schedule.
-- Only scheduled auctions are changed; cancelled/completed records are untouched.
DROP EVENT IF EXISTS homebid_update_auction_statuses;

CREATE EVENT homebid_update_auction_statuses
ON SCHEDULE EVERY 1 MINUTE
ON COMPLETION PRESERVE
ENABLE
DO
BEGIN
    UPDATE auctions
       SET status = CASE
           WHEN NOW() < start_time THEN 'upcoming'
           WHEN NOW() >= start_time AND NOW() < end_time THEN 'active'
           ELSE 'ended'
       END
     WHERE status IN ('upcoming', 'active')
       AND start_time IS NOT NULL
       AND end_time IS NOT NULL;

    -- Save the highest valid bid once an auction has ended.
    -- Ties are resolved in favor of the earliest submitted bid.
    INSERT IGNORE INTO auction_results
        (auction_id, winner_user_id, winning_bid, result_status, ended_at)
    SELECT
        a.id,
        (
            SELECT b.user_id
              FROM bids b
             WHERE b.property_id = a.property_id
             ORDER BY b.bid_amount DESC, b.created_at ASC, b.id ASC
             LIMIT 1
        ),
        COALESCE(
            (
                SELECT b.bid_amount
                  FROM bids b
                 WHERE b.property_id = a.property_id
                 ORDER BY b.bid_amount DESC, b.created_at ASC, b.id ASC
                 LIMIT 1
            ),
            0.00
        ),
        CASE
            WHEN EXISTS (SELECT 1 FROM bids b WHERE b.property_id = a.property_id)
            THEN 'provisional_winner'
            ELSE 'no_bids'
        END,
        NOW()
      FROM auctions a
     WHERE a.status = 'ended'
       AND a.end_time IS NOT NULL
       AND a.end_time <= NOW();
END;

-- Check whether the MySQL event scheduler is enabled:
-- SHOW VARIABLES LIKE 'event_scheduler';
--
-- If the result is OFF and your MySQL account permits it, enable it with:
-- SET GLOBAL event_scheduler = ON;
--
-- On XAMPP, if SET GLOBAL is unavailable, enable event_scheduler in the
-- MySQL server configuration (my.ini) and restart MySQL.
--
-- Verify installation:
-- SHOW EVENTS FROM house_and_lot_db;
-- SELECT * FROM auction_results ORDER BY ended_at DESC;

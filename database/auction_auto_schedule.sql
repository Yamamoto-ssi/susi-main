-- HomeBid automatic auction scheduling
-- Run this file once in phpMyAdmin with the HomeBid database selected.
-- The MySQL/MariaDB account must have EVENT privilege and permission to enable
-- the global event scheduler.

SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS homebid_sync_auction_status
ON SCHEDULE EVERY 1 MINUTE
ON COMPLETION PRESERVE
ENABLE
DO
BEGIN
    -- Automatically open approved auctions when their start time arrives.
    UPDATE auctions
       SET status = 'active'
     WHERE status = 'upcoming'
       AND review_status = 'approved'
       AND start_time <= NOW()
       AND end_time > NOW();

    -- Automatically close auctions when their deadline has passed.
    UPDATE auctions
       SET status = 'ended'
     WHERE status IN ('upcoming', 'active')
       AND review_status = 'approved'
       AND end_time <= NOW();
END;

-- Verify that the scheduler is enabled and the event exists.
SHOW VARIABLES LIKE 'event_scheduler';
SHOW EVENTS LIKE 'homebid_sync_auction_status';

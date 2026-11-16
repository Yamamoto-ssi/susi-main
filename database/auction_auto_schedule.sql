-- HomeBid automatic auction scheduling
-- Run this file once in phpMyAdmin with the HomeBid database selected.
-- Your MySQL/MariaDB account must have EVENT privilege and permission to
-- enable the global event scheduler.

SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS homebid_sync_auction_status
ON SCHEDULE EVERY 1 MINUTE
ON COMPLETION PRESERVE
ENABLE
DO
    UPDATE auctions
       SET status = CASE
           WHEN end_time <= NOW() THEN 'ended'
           WHEN start_time <= NOW() THEN 'active'
           ELSE status
       END
     WHERE review_status = 'approved'
       AND status IN ('upcoming', 'active')
       AND (start_time <= NOW() OR end_time <= NOW());

-- Verify that the scheduler is enabled and the event exists.
SHOW VARIABLES LIKE 'event_scheduler';
SHOW EVENTS LIKE 'homebid_sync_auction_status';

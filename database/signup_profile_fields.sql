-- HomeBid signup profile fields migration
-- Run once in phpMyAdmin with house_and_lot_db selected.
ALTER TABLE users
  ADD COLUMN first_name varchar(80) DEFAULT NULL AFTER username,
  ADD COLUMN last_name varchar(80) DEFAULT NULL AFTER first_name,
  ADD COLUMN phone_number varchar(20) DEFAULT NULL AFTER last_name;

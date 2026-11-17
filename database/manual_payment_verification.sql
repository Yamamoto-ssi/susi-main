-- HomeBid manual payment verification migration
-- Run once in phpMyAdmin with house_and_lot_db selected.
ALTER TABLE checkout_payments
  ADD COLUMN payment_method varchar(40) DEFAULT NULL AFTER status,
  ADD COLUMN proof_path varchar(255) DEFAULT NULL AFTER payment_method,
  ADD COLUMN reviewed_by int(11) DEFAULT NULL AFTER proof_path,
  ADD COLUMN reviewed_at datetime DEFAULT NULL AFTER reviewed_by,
  ADD COLUMN review_note text DEFAULT NULL AFTER reviewed_at;

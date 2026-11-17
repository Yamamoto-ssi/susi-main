-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2026 at 01:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `house_and_lot_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `record_id`, `ip_address`, `created_at`) VALUES
(1, 13, 'Changed property featured status', 'property', 2, '::1', '2026-11-15 05:11:35'),
(2, 13, 'Changed property featured status', 'property', 2, '::1', '2026-11-15 05:11:37'),
(3, 13, 'Updated property status to Available', 'property', 2, '::1', '2026-11-15 05:56:22'),
(4, 13, 'Changed property featured status', 'property', 2, '::1', '2026-11-15 05:59:03'),
(5, 13, 'Updated property status to Available', 'property', 2, '::1', '2026-11-15 05:59:05'),
(6, 13, 'Changed property featured status', 'property', 3, '::1', '2026-11-15 05:59:15'),
(7, 13, 'Changed property featured status', 'property', 4, '::1', '2026-11-15 05:59:16'),
(8, 13, 'Changed property featured status', 'property', 5, '::1', '2026-11-15 05:59:17'),
(9, 13, 'Changed property featured status', 'property', 6, '::1', '2026-11-15 05:59:19'),
(10, 13, 'Changed property featured status', 'property', 1, '::1', '2026-11-15 05:59:21'),
(11, 13, 'Updated property status to Available', 'property', 2, '::1', '2026-11-15 05:59:38'),
(12, 13, 'Updated property status to Available', 'property', 3, '::1', '2026-11-15 05:59:39'),
(13, 13, 'Updated property status to Available', 'property', 4, '::1', '2026-11-15 05:59:40'),
(14, 13, 'Updated property status to Available', 'property', 5, '::1', '2026-11-15 05:59:42'),
(15, 13, 'Updated property status to Available', 'property', 6, '::1', '2026-11-15 05:59:44'),
(16, 13, 'Updated property status to Available', 'property', 1, '::1', '2026-11-15 05:59:45'),
(17, 13, 'Changed property featured status', 'property', 2, '::1', '2026-11-15 06:13:07'),
(18, 13, 'Changed property featured status', 'property', 3, '::1', '2026-11-15 06:13:08'),
(19, 13, 'Changed property featured status', 'property', 4, '::1', '2026-11-15 06:13:08'),
(20, 13, 'Changed property featured status', 'property', 5, '::1', '2026-11-15 06:13:09'),
(21, 13, 'Changed property featured status', 'property', 6, '::1', '2026-11-15 06:13:10'),
(22, 13, 'Changed property featured status', 'property', 1, '::1', '2026-11-15 06:13:11'),
(23, 14, 'Changed auction status to active', 'auction', 3, '::1', '2026-11-16 07:44:57'),
(24, 14, 'Updated auction status to ended', 'auction', 2, '::1', '2026-11-16 07:46:20'),
(25, 14, 'Updated auction status to ended', 'auction', 2, '::1', '2026-11-16 07:47:02'),
(26, 16, 'Submitted seller application', 'seller_application', 1, '::1', '2026-11-16 18:26:40'),
(27, 13, 'Seller application approved', 'seller_application', 1, '::1', '2026-11-16 18:48:22'),
(28, 16, 'Logged out', 'user', 16, '::1', '2026-11-16 22:55:38'),
(29, 16, 'Logged in', 'user', 16, '::1', '2026-11-16 22:55:57'),
(30, 16, 'Logged out', 'user', 16, '::1', '2026-11-16 23:09:51'),
(31, 13, 'Logged in', 'user', 13, '::1', '2026-11-16 23:11:05'),
(32, 13, 'Placed a bid of ₱5,000,000.00 on Premium House and Lot Auction', 'bid', 0, '::1', '2026-11-16 23:17:08'),
(33, 13, 'Logged out', 'user', 13, '::1', '2026-11-16 23:26:16'),
(34, 16, 'Logged in', 'user', 16, '::1', '2026-11-16 23:26:32'),
(35, 16, 'Logged out', 'user', 16, '::1', '2026-11-16 23:27:27'),
(36, 14, 'Logged in', 'user', 14, '::1', '2026-11-16 23:27:39'),
(37, 14, 'Logged out', 'user', 14, '::1', '2026-11-16 23:58:28'),
(38, 16, 'Logged in', 'user', 16, '::1', '2026-11-16 23:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `auctions`
--

CREATE TABLE `auctions` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `starting_bid` decimal(12,2) NOT NULL,
  `current_bid` decimal(12,2) NOT NULL,
  `highest_bidder_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('upcoming','active','ended','cancelled') DEFAULT 'upcoming',
  `review_status` enum('pending','admin_approval','needs_revision','approved') NOT NULL DEFAULT 'approved',
  `review_feedback` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auctions`
--

INSERT INTO `auctions` (`id`, `property_id`, `starting_bid`, `current_bid`, `highest_bidder_id`, `start_time`, `end_time`, `status`, `review_status`, `review_feedback`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(5, 11, 4500000.00, 5000000.00, 13, '2026-11-17 00:00:00', '2026-11-24 23:59:59', 'active', 'approved', NULL, NULL, NULL, '2026-11-16 19:07:46'),
(6, 12, 3800000.00, 3800000.00, NULL, '2026-11-17 00:00:00', '2026-11-25 23:59:59', 'active', 'approved', NULL, NULL, NULL, '2026-11-16 19:07:46');

-- --------------------------------------------------------

--
-- Table structure for table `auction_results`
--

CREATE TABLE `auction_results` (
  `id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `winner_user_id` int(11) DEFAULT NULL,
  `winning_bid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `result_status` varchar(30) NOT NULL DEFAULT 'no_bids',
  `ended_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bid_amount` decimal(12,2) NOT NULL,
  `status` enum('Pending','Accepted','Rejected','Outbid') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bids`
--

INSERT INTO `bids` (`id`, `property_id`, `user_id`, `bid_amount`, `status`, `created_at`) VALUES
(3, 11, 13, 5000000.00, 'Pending', '2026-11-16 23:17:08');

--
-- Triggers `bids`
--
DELIMITER $$
CREATE TRIGGER `homebid_after_bid_insert` AFTER INSERT ON `bids` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkout_payments`
--

CREATE TABLE `checkout_payments` (
  `id` int(11) NOT NULL,
  `payment_type` enum('reservation','auction') NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `auction_id` int(11) DEFAULT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `invoice_id` varchar(100) DEFAULT NULL,
  `invoice_url` varchar(500) DEFAULT NULL,
  `status` enum('creating','pending','paid','expired','failed') NOT NULL DEFAULT 'creating',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkout_payments`
--

INSERT INTO `checkout_payments` (`id`, `payment_type`, `reservation_id`, `auction_id`, `property_id`, `user_id`, `amount`, `external_id`, `invoice_id`, `invoice_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'auction', NULL, 5, 12, 16, 2500000.00, 'HB-AUC-2026-0001', 'INV-SAMPLE-AUC-001', NULL, 'paid', '2026-11-16 23:22:12', '2026-11-16 23:22:12'),
(2, 'reservation', 8, NULL, 15, 16, 5000.00, 'HB-RES-2026-0001', 'INV-SAMPLE-RES-001', NULL, 'paid', '2026-11-16 23:22:12', '2026-11-16 23:22:12');

--
-- Triggers `checkout_payments`
--
DELIMITER $$
CREATE TRIGGER `homebid_after_payment_paid` AFTER UPDATE ON `checkout_payments` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','responded','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_entity` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `related_entity`, `related_id`, `created_at`) VALUES
(1, 16, 'Seller application Approved', 'Your seller application was approved. Sign in again to access Seller features.', 0, 'seller_application', 1, '2026-11-16 18:48:22'),
(2, 16, 'New bid on your property', 'A buyer placed a bid of ₱5,000,000.00 on Premium House and Lot Auction.', 0, 'property', 11, '2026-11-16 23:17:08');

-- --------------------------------------------------------

--
-- Table structure for table `password_change_requests`
--

CREATE TABLE `password_change_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `new_password_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_change_requests`
--

INSERT INTO `password_change_requests` (`id`, `user_id`, `token_hash`, `new_password_hash`, `expires_at`, `created_at`) VALUES
(2, 16, 'e3a1cfdfb8e980f14d8fc3b9148d6d3c97a6e500af7c03563e2600bad732cc31', '$2y$10$yqPI6HYAUKO0M0RUE5Hje.3kPOgwKVFbckqjhDmmpQuXO78Y0H8e.', '2026-11-17 03:45:04', '2026-11-16 19:30:04');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(4, 'brixsedillo@gmail.com', '82003cc2691e04fb16dbe9e55d0641c09135fca1a857fdc499ed5b1afaf0cce2', '2026-11-10 10:44:51', '2026-11-10 08:44:51'),
(5, 'sbrixtorres@gmail.com', 'c1c0c15c9003d0d2d2de97dc4e026ec0d6171b65bf970a810165cb0af02a42e5', '2026-11-10 10:46:01', '2026-11-10 08:46:01');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(150) DEFAULT NULL,
  `city` varchar(150) DEFAULT NULL,
  `barangay` varchar(150) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `lot_area` decimal(10,2) NOT NULL,
  `floor_area` decimal(10,2) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `status` enum('Available','Reserved','Sold','Auction') NOT NULL DEFAULT 'Available',
  `image_path` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `review_status` enum('pending','admin_approval','needs_revision','approved') NOT NULL DEFAULT 'pending',
  `review_feedback` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submission_type` enum('listing','auction') NOT NULL DEFAULT 'listing',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `user_id`, `title`, `description`, `category`, `location`, `region`, `province`, `city`, `barangay`, `lot_number`, `lot_area`, `floor_area`, `price`, `status`, `image_path`, `is_featured`, `created_at`, `is_approved`, `review_status`, `review_feedback`, `reviewed_by`, `reviewed_at`, `submission_type`, `latitude`, `longitude`) VALUES
(9, 16, 'Modern Family House in Angeles City', 'A modern family home with spacious bedrooms, a functional kitchen, private parking, and convenient access to schools and commercial establishments.', 'House and Lot', 'Pulungbulu, Angeles City, Pampanga', 'Region III (Central Luzon)', 'Pampanga', 'Angeles City', 'Pulungbulu', 'Block 8 Lot 12', 120.00, 95.00, 3850000.00, 'Available', 'assets/image/hero-house.jpg', 0, '2026-11-16 19:07:46', 1, 'approved', NULL, NULL, NULL, 'listing', NULL, NULL),
(10, 16, 'Affordable Townhouse in San Fernando', 'A comfortable townhouse suitable for a growing family, featuring a practical layout and convenient access to public transportation and nearby establishments.', 'House and Lot', 'San Fernando City, Pampanga', 'Region III (Central Luzon)', 'Pampanga', 'San Fernando', 'Dolores', 'Block 3 Lot 5', 90.00, 75.00, 2850000.00, 'Available', 'assets/image/property-alabang.jpg', 0, '2026-11-16 19:07:46', 1, 'approved', NULL, NULL, NULL, 'listing', NULL, NULL),
(11, 16, 'Premium House and Lot Auction', 'A spacious residential property offered through auction, featuring a modern design and a peaceful neighborhood.', 'House and Lot', 'Clark Freeport Zone, Pampanga', 'Region III (Central Luzon)', 'Pampanga', 'Angeles City', 'Clark', 'Block 5 Lot 10', 180.00, 140.00, 4500000.00, 'Auction', 'assets/image/why-house.jpg', 0, '2026-11-16 19:07:46', 1, 'approved', NULL, NULL, NULL, 'auction', NULL, NULL),
(12, 16, 'Contemporary Family Home Auction', 'A contemporary family home with generous living spaces, modern finishes, and convenient access to major roads.', 'House and Lot', 'Mabalacat City, Pampanga', 'Region III (Central Luzon)', 'Pampanga', 'Mabalacat City', 'Dau', 'Block 2 Lot 8', 150.00, 115.00, 3800000.00, 'Auction', 'assets/image/property-nuvali.jpg', 0, '2026-11-16 19:07:46', 1, 'approved', NULL, NULL, NULL, 'auction', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_tags`
--

CREATE TABLE `property_tags` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tag_name` varchar(40) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reservation_fee` decimal(10,2) NOT NULL,
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_applications`
--

CREATE TABLE `seller_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(160) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `address` varchar(255) NOT NULL,
  `id_type` varchar(100) NOT NULL,
  `id_number` varchar(100) NOT NULL,
  `id_front_path` varchar(255) NOT NULL,
  `selfie_with_id_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','needs_revision') NOT NULL DEFAULT 'pending',
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_note` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`id`, `user_id`, `full_name`, `phone_number`, `address`, `id_type`, `id_number`, `id_front_path`, `selfie_with_id_path`, `status`, `reviewer_id`, `reviewer_note`, `reviewed_at`, `created_at`) VALUES
(1, 16, 'John Lorence Inong', '09486318794', '82, 8th st Lakandula', 'Philippine National ID', '1231231231231233', 'uploads/private/seller-verification/0fc5c33149f88b62646137fc3b7c59acb879.png', 'uploads/private/seller-verification/2521105779763fbe9d2690abd2d3ea4ebdf1.png', 'approved', 13, NULL, '2026-11-17 02:48:22', '2026-11-16 18:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrator','Staff','Buyer','Client') NOT NULL DEFAULT 'Buyer',
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `mailing_address` varchar(255) DEFAULT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `auction_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `marketing_notifications` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `google_id`, `password`, `role`, `verification_token`, `is_verified`, `created_at`, `first_name`, `last_name`, `phone_number`, `mailing_address`, `email_notifications`, `auction_notifications`, `marketing_notifications`) VALUES
(1, 'admin', 'admin@houseandlot.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, 1, '2026-10-28 16:12:53', NULL, NULL, NULL, NULL, 1, 1, 0),
(2, 'angelotorres', 'sbrixtorres@gmail.com', '117620565271595565048', '$2y$10$8F23Ert8nry1.BXgvYv7pugpU23KZw/HMKvCYGb.gf9fxQ/RajZve', 'Buyer', NULL, 1, '2026-11-01 19:00:42', NULL, NULL, NULL, NULL, 1, 1, 0),
(3, 'admin1', 'jasdfljksafdjl@gmail.com', NULL, 'admin123', 'Administrator', NULL, 0, '2026-11-02 14:50:42', NULL, NULL, NULL, NULL, 1, 1, 0),
(4, 'asdf', 'asdf', NULL, 'asdf', 'Administrator', NULL, 0, '2026-11-02 15:07:28', NULL, NULL, NULL, NULL, 1, 1, 0),
(5, 'shootermen99', 'brix@gmail.com', NULL, '$2y$10$8aNQJBVmDwbk3PnCjBXx0OuGW4AU76MqHOG6YnZ.D.4KNhD03C1Lu', 'Administrator', NULL, 0, '2026-11-02 15:09:24', NULL, NULL, NULL, NULL, 1, 1, 0),
(6, 'nreco', 'asdfadsf@gmail.com', NULL, '$2y$10$2OkkKhk/MO1W9us1f.pugeMTCb.EfB2IazpRvz5MVlmnftSn5Dnnu', 'Administrator', NULL, 0, '2026-11-02 16:55:43', NULL, NULL, NULL, NULL, 1, 1, 0),
(7, 'qwe', 'qwe@gmail.com', NULL, 'qwe', 'Buyer', NULL, 0, '2026-11-02 17:21:57', NULL, NULL, NULL, NULL, 1, 1, 0),
(9, 'brix sedillo', 'brixsedillo@gmail.com', '118305699041903171057', '$2y$10$s0Epu705gyrBJXm4.ZDXR.aZYbQX5YRyDjFhMAOWjMRXE6kFYewiW', 'Buyer', NULL, 1, '2026-11-03 21:45:13', 'Brix Angelo', 'Sedillo', '09486318794', '82, 8th st Lakandula', 1, 1, 0),
(13, 'brixbuyer', 'brixbuyer@gmail.com', NULL, '$2y$10$N2nBVgDfTyB2RpFi5rKP3.BcZQazrVQLW3X/6uZRrP9epo3KfcxB6', 'Buyer', NULL, 1, '2026-11-03 22:43:47', NULL, NULL, NULL, NULL, 1, 1, 0),
(14, 'brixadmin', 'brixadmin@gmail.com', NULL, '$2y$10$gLyJYcX4UftxmosjdqJW3OnOWMrgQejD25huzSOk0VwOSyS60hnqi', 'Administrator', '', 1, '2026-11-03 22:43:47', NULL, NULL, NULL, NULL, 1, 1, 0),
(15, 'Brixangelo Sedillo', 'sbrixangelo@gmail.com', '105516443382730612843', '', 'Buyer', NULL, 1, '2026-11-08 20:28:40', NULL, NULL, NULL, NULL, 1, 1, 0),
(16, 'janjan', 'lorenxz09@gmail.com', '103742249210865975607', '$2y$10$Iu6s7rcZp6HQjMt7aG5KXeXzUKF5FH/OsPDykKfj2zxZniSQ.Hb3O', 'Client', NULL, 1, '2026-11-16 11:11:43', 'Brix Angelo', 'Sedillo', '', '82, 8th st Lakandula', 1, 1, 0),
(17, 'jan', 'jan@gmail.com', NULL, '$2y$10$sK6eR8Qwf.i5izv24d2kVuLjXHYv1CQPeJgHW/lT5itDdcqXNSIhG', 'Buyer', NULL, 1, '2026-11-16 21:58:51', NULL, NULL, NULL, NULL, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `viewing_bookings`
--

CREATE TABLE `viewing_bookings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `auctions`
--
ALTER TABLE `auctions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `highest_bidder_id` (`highest_bidder_id`),
  ADD KEY `idx_auctions_review_status` (`review_status`),
  ADD KEY `fk_auctions_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `auction_results`
--
ALTER TABLE `auction_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_auction_results_auction` (`auction_id`),
  ADD KEY `idx_auction_results_winner` (`winner_user_id`),
  ADD KEY `idx_auction_results_status` (`result_status`);

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `checkout_payments`
--
ALTER TABLE `checkout_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `external_id` (`external_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `auction_id` (`auction_id`),
  ADD KEY `user_status` (`user_id`,`status`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_change_requests`
--
ALTER TABLE `password_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_password_change_token` (`token_hash`),
  ADD KEY `idx_password_change_user` (`user_id`),
  ADD KEY `idx_password_change_expiry` (`expires_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_properties_review_status` (`review_status`),
  ADD KEY `fk_properties_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_property_images_property` (`property_id`);

--
-- Indexes for table `property_tags`
--
ALTER TABLE `property_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_property_tag` (`property_id`,`tag_name`),
  ADD KEY `idx_property_tags_name` (`tag_name`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_app_user` (`user_id`),
  ADD KEY `idx_seller_app_status` (`status`),
  ADD KEY `fk_seller_app_reviewer` (`reviewer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `viewing_bookings`
--
ALTER TABLE `viewing_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `auctions`
--
ALTER TABLE `auctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `auction_results`
--
ALTER TABLE `auction_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `checkout_payments`
--
ALTER TABLE `checkout_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_change_requests`
--
ALTER TABLE `password_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `property_tags`
--
ALTER TABLE `property_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `viewing_bookings`
--
ALTER TABLE `viewing_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `auctions`
--
ALTER TABLE `auctions`
  ADD CONSTRAINT `auctions_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auctions_ibfk_2` FOREIGN KEY (`highest_bidder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_auctions_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `bids_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bids_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_change_requests`
--
ALTER TABLE `password_change_requests`
  ADD CONSTRAINT `fk_password_change_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `fk_property_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_tags`
--
ALTER TABLE `property_tags`
  ADD CONSTRAINT `fk_property_tags_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD CONSTRAINT `fk_seller_app_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `viewing_bookings`
--
ALTER TABLE `viewing_bookings`
  ADD CONSTRAINT `viewing_bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `viewing_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

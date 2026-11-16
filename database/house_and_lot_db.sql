-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2026 at 02:52 AM
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
(22, 13, 'Changed property featured status', 'property', 1, '::1', '2026-11-15 06:13:11');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auctions`
--

INSERT INTO `auctions` (`id`, `property_id`, `starting_bid`, `current_bid`, `highest_bidder_id`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(2, 2, 4000000.00, 111111111.00, 6, '2026-11-03 01:35:44', '2026-11-05 01:35:44', 'active', '2026-11-02 17:35:44'),
(3, 3, 1800000.00, 1800000.00, NULL, '2026-11-06 01:35:44', '2026-11-13 01:35:44', 'upcoming', '2026-11-02 17:35:44'),
(4, 1, 3000000.00, 3500000.00, NULL, '2026-10-27 01:35:44', '2026-11-02 01:35:44', 'ended', '2026-11-02 17:35:44');

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

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `property_id`, `user_id`, `visit_date`, `visit_time`, `status`, `created_at`) VALUES
(1, 5, 6, '2026-11-04', '15:11:00', 'pending', '2026-11-02 19:11:11');

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
  `location` varchar(255) NOT NULL,
  `lot_area` decimal(10,2) NOT NULL,
  `floor_area` decimal(10,2) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `status` enum('Available','Reserved','Sold','Auction') NOT NULL DEFAULT 'Available',
  `image_path` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `user_id`, `title`, `description`, `location`, `lot_area`, `floor_area`, `price`, `status`, `image_path`, `is_featured`, `created_at`, `is_approved`, `latitude`, `longitude`) VALUES
(1, 13, 'Modern House and Lot', 'Test description for checkout', 'Manila, Philippines', 120.00, 85.00, 2500000.00, 'Available', 'assets/image/hero-house.jpg', 1, '2026-10-31 07:41:52', 1, NULL, NULL),
(2, 13, 'Modern House and Lot', 'Test description for checkout', 'Manila, Philippines', 120.00, 85.00, 2500000.00, 'Available', 'assets/image/property-alabang.jpg', 1, '2026-11-02 14:06:50', 1, NULL, NULL),
(3, 13, 'Minimalist Townhouse', 'Spacious townhouse with modern finishes, open-concept kitchen, and secure garage.', 'Angeles City, Pampanga', 100.00, 90.00, 3200000.00, 'Available', 'assets/image/property-nuvali.jpg', 1, '2026-11-02 14:06:50', 1, NULL, NULL),
(4, 13, 'Luxury Subdivision Estate', 'High-end luxury property featuring a wide second-floor balcony, landscaped garden, and master suite.', 'Clark Freeport Zone', 200.00, 150.00, 7500000.00, 'Available', 'assets/image/why-house.jpg', 1, '2026-11-02 14:06:50', 1, NULL, NULL),
(5, 13, 'Affordable Family Duplex', 'Cozy and budget-friendly duplex designed for growing families close to schools and commercial centers.', 'San Fernando, Pampanga', 80.00, 65.00, 2100000.00, 'Available', 'assets/image/property-cebu.jpg', 1, '2026-11-02 14:06:50', 1, NULL, NULL),
(6, 13, 'Contemporary Single-Detached', 'Stunning single-detached house with high ceilings, large windows, and a two-car carport.', 'Tarlac City', 150.00, 110.00, 4800000.00, 'Available', 'assets/image/property-cebu.jpg', 1, '2026-11-02 14:06:50', 1, NULL, NULL);

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

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `property_id`, `user_id`, `reservation_fee`, `proof_of_payment`, `status`, `created_at`) VALUES
(2, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:42:04'),
(3, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:42:18'),
(4, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:42:20'),
(5, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:42:22'),
(6, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:43:14'),
(7, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:45:57'),
(8, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:47:42'),
(9, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:51:43'),
(10, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 07:54:14'),
(11, 1, 1, 5000.00, NULL, 'Pending', '2026-10-31 10:22:55');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `google_id`, `password`, `role`, `verification_token`, `is_verified`, `created_at`) VALUES
(1, 'admin', 'admin@houseandlot.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, 1, '2026-10-28 16:12:53'),
(2, 'angelotorres', 'sbrixtorres@gmail.com', '117620565271595565048', '$2y$10$8F23Ert8nry1.BXgvYv7pugpU23KZw/HMKvCYGb.gf9fxQ/RajZve', 'Buyer', NULL, 1, '2026-11-01 19:00:42'),
(3, 'admin1', 'jasdfljksafdjl@gmail.com', NULL, 'admin123', 'Administrator', NULL, 0, '2026-11-02 14:50:42'),
(4, 'asdf', 'asdf', NULL, 'asdf', 'Administrator', NULL, 0, '2026-11-02 15:07:28'),
(5, 'shootermen99', 'brix@gmail.com', NULL, '$2y$10$8aNQJBVmDwbk3PnCjBXx0OuGW4AU76MqHOG6YnZ.D.4KNhD03C1Lu', 'Administrator', NULL, 0, '2026-11-02 15:09:24'),
(6, 'nreco', 'asdfadsf@gmail.com', NULL, '$2y$10$2OkkKhk/MO1W9us1f.pugeMTCb.EfB2IazpRvz5MVlmnftSn5Dnnu', 'Administrator', NULL, 0, '2026-11-02 16:55:43'),
(7, 'qwe', 'qwe@gmail.com', NULL, 'qwe', 'Buyer', NULL, 0, '2026-11-02 17:21:57'),
(9, 'brix sedillo', 'brixsedillo@gmail.com', '118305699041903171057', '$2y$10$cSvFlcA69Puej37BeITDUelPEnwEsfLbbTHh0dJ7gPRcFodAjRmdO', 'Buyer', NULL, 1, '2026-11-03 21:45:13'),
(13, 'brixbuyer', 'brixbuyer@gmail.com', NULL, '$2y$10$N2nBVgDfTyB2RpFi5rKP3.BcZQazrVQLW3X/6uZRrP9epo3KfcxB6', 'Buyer', NULL, 1, '2026-11-03 22:43:47'),
(14, 'brixadmin', 'brixadmin@gmail.com', NULL, '$2y$10$gLyJYcX4UftxmosjdqJW3OnOWMrgQejD25huzSOk0VwOSyS60hnqi', 'Administrator', '', 1, '2026-11-03 22:43:47'),
(15, 'Brixangelo Sedillo', 'sbrixangelo@gmail.com', '105516443382730612843', '', 'Buyer', NULL, 1, '2026-11-08 20:28:40');

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
  ADD KEY `highest_bidder_id` (`highest_bidder_id`);

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
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `auctions`
--
ALTER TABLE `auctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `auction_results`
--
ALTER TABLE `auction_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `checkout_payments`
--
ALTER TABLE `checkout_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  ADD CONSTRAINT `auctions_ibfk_2` FOREIGN KEY (`highest_bidder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- test update
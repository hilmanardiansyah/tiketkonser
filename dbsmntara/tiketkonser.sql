-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 26, 2026 at 05:21 PM
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
-- Database: `tiketkonser`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `venue` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `created_by`, `title`, `description`, `venue`, `city`, `event_date`, `start_time`, `end_time`, `poster_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Konser UAS', 'Event contoh untuk UAS', 'GOR', 'Bandung', '2026-02-01', '19:00:00', '22:00:00', NULL, 'ACTIVE', '2026-01-20 11:25:25', '2026-01-20 12:39:03'),
(2, 1, 'Taylor swift', 'awndkawdnkawdadw', 'ADWMALD', 'Jakarta', '2026-01-27', '06:35:00', '18:35:00', '', 'ACTIVE', '2026-01-20 14:35:52', '2026-01-20 14:35:52');

-- --------------------------------------------------------

--
-- Table structure for table `event_sessions`
--

CREATE TABLE `event_sessions` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_code` varchar(60) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_code`, `order_date`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES
(2, 1, 'ORD-20260120065631-d82588', '2026-01-20 12:56:31', 'PAID', 300000.00, '2026-01-20 12:56:31', '2026-01-20 12:58:36'),
(3, 2, 'FES-11FE4B', '2026-01-26 22:59:14', 'PAID', 150000.00, '2026-01-26 22:59:14', '2026-01-26 22:59:14'),
(4, 2, 'FES-BACC2A', '2026-01-26 23:00:06', 'PAID', 150000.00, '2026-01-26 23:00:06', '2026-01-26 23:00:06'),
(5, 2, 'FES-401C3F', '2026-01-26 23:01:44', 'PAID', 150000.00, '2026-01-26 23:01:44', '2026-01-26 23:01:44'),
(6, 2, 'FES-6484FC', '2026-01-26 23:11:43', 'PAID', 150000.00, '2026-01-26 23:11:43', '2026-01-26 23:11:43'),
(7, 2, 'FES-065292', '2026-01-26 23:12:11', 'PAID', 150000.00, '2026-01-26 23:12:11', '2026-01-26 23:12:11'),
(8, 2, 'FES-643F67', '2026-01-26 23:13:35', 'PAID', 150000.00, '2026-01-26 23:13:35', '2026-01-26 23:13:35');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `ticket_type_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `ticket_type_id`, `qty`, `unit_price`, `subtotal`) VALUES
(1, 2, 1, 2, 150000.00, 300000.00),
(2, 3, 1, 1, 150000.00, 150000.00),
(3, 4, 1, 1, 150000.00, 150000.00),
(4, 5, 1, 1, 150000.00, 150000.00),
(5, 6, 1, 1, 150000.00, 150000.00),
(6, 7, 1, 1, 150000.00, 150000.00),
(7, 8, 1, 1, 150000.00, 150000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `method` varchar(30) NOT NULL DEFAULT 'transfer',
  `payment_ref` varchar(80) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'PAID',
  `paid_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `method`, `payment_ref`, `amount`, `status`, `paid_at`) VALUES
(1, 2, 'transfer', 'PAY-20260120065836-51d4c9', 300000.00, 'PAID', '2026-01-20 12:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `ticket_code` varchar(80) NOT NULL,
  `qr_payload` text DEFAULT NULL,
  `attendee_name` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'ACTIVE',
  `checked_in_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `order_item_id`, `ticket_code`, `qr_payload`, `attendee_name`, `status`, `checked_in_at`) VALUES
(1, 1, 'TIX-20260120065836-172fc1d7', '{\"ticket_code\":\"TIX-20260120065836-172fc1d7\",\"order_code\":\"ORD-20260120065631-d82588\"}', NULL, 'ACTIVE', NULL),
(2, 1, 'TIX-20260120065836-8beac531', '{\"ticket_code\":\"TIX-20260120065836-8beac531\",\"order_code\":\"ORD-20260120065631-d82588\"}', NULL, 'ACTIVE', NULL),
(3, 2, 'FES-11FE4B-001', '{\"order_code\":\"FES-11FE4B\",\"ticket_code\":\"FES-11FE4B-001\",\"event_id\":1,\"ticket_type\":\"Regular\"}', 'User 1', 'UNUSED', NULL),
(4, 3, 'FES-BACC2A-001', '{\"order_code\":\"FES-BACC2A\",\"ticket_code\":\"FES-BACC2A-001\",\"event_id\":1,\"ticket_type\":\"Regular\"}', 'User 1', 'UNUSED', NULL),
(5, 4, 'FES-401C3F-001', '{\"order_code\":\"FES-401C3F\",\"ticket_code\":\"FES-401C3F-001\",\"event_id\":1,\"ticket_type\":\"Regular\"}', 'User 1', 'UNUSED', NULL),
(6, 5, 'FES-6484FC-001', '{\"order_code\":\"FES-6484FC\",\"ticket_code\":\"FES-6484FC-001\",\"event_id\":1,\"ticket_type\":\"Regular\"}', 'User 1', 'UNUSED', NULL),
(7, 6, 'FES-065292-001', '{\"order_code\":\"FES-065292\",\"ticket_code\":\"FES-065292-001\",\"event_id\":1,\"ticket_type\":\"Regular\"}', 'User 1', 'UNUSED', NULL),
(8, 7, 'FES-643F67-001', '{\"order_code\":\"FES-643F67\",\"ticket_code\":\"FES-643F67-001\",\"event_id\":1,\"ticket_type\":\"Regular\"}', 'User 1', 'UNUSED', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_types`
--

CREATE TABLE `ticket_types` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quota` int(11) NOT NULL DEFAULT 0,
  `sold` int(11) NOT NULL DEFAULT 0,
  `sales_start` datetime DEFAULT NULL,
  `sales_end` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_types`
--

INSERT INTO `ticket_types` (`id`, `event_id`, `name`, `price`, `quota`, `sold`, `sales_start`, `sales_end`, `created_at`, `updated_at`) VALUES
(1, 1, 'Regular', 150000.00, 50, 8, '2026-01-20 11:25:26', '2026-01-27 11:25:26', '2026-01-20 11:25:26', '2026-01-26 23:13:35'),
(2, 1, 'VIP', 300000.00, 20, 0, '2026-01-20 11:25:26', '2026-01-27 11:25:26', '2026-01-20 11:25:26', '2026-01-20 11:25:26'),
(3, 1, 'akdnkawdna', 1200000.00, 20, 0, NULL, NULL, '2026-01-20 14:36:16', '2026-01-20 14:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'USER',
  `api_token` varchar(128) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `role`, `api_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin EO', 'admin@test.com', '$2y$10$ki8dZv00OgGAMbxJRDOLB.UdDCG3pHC9KKFNFmGDkgp8R4P5kDYW2', '08123456789', 'ADMIN', '781ecbcdf6ca7594c74e6fc7f15bdb05c7dee8fdfc5bfce6b41dcbac7d6a1820', '2026-01-20 11:25:25', '2026-01-20 12:34:03'),
(2, 'User 1', 'user@test.com', '$2y$10$ki8dZv00OgGAMbxJRDOLB.UdDCG3pHC9KKFNFmGDkgp8R4P5kDYW2', '08999999999', 'USER', NULL, '2026-01-20 11:25:25', '2026-01-20 11:26:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_created_by` (`created_by`);

--
-- Indexes for table `event_sessions`
--
ALTER TABLE `event_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_sessions_event_id` (`event_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_orders_order_code` (`order_code`),
  ADD KEY `idx_orders_user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_ticket_type_id` (`ticket_type_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payments_payment_ref` (`payment_ref`),
  ADD KEY `idx_payments_order_id` (`order_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tickets_ticket_code` (`ticket_code`),
  ADD KEY `idx_tickets_order_item_id` (`order_item_id`);

--
-- Indexes for table `ticket_types`
--
ALTER TABLE `ticket_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_types_event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_api_token` (`api_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_sessions`
--
ALTER TABLE `event_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ticket_types`
--
ALTER TABLE `ticket_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `event_sessions`
--
ALTER TABLE `event_sessions`
  ADD CONSTRAINT `fk_event_sessions_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_ticket_type` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket_types`
--
ALTER TABLE `ticket_types`
  ADD CONSTRAINT `fk_ticket_types_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 05:49 PM
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
-- Database: `watan_freelance`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` varchar(20) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `freelancer_id` int(11) DEFAULT NULL,
  `service_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `total_booking_hours` int(11) NOT NULL,
  `calculated_total_price` decimal(10,2) NOT NULL,
  `work_mode` enum('online','offline') NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `booking_status` enum('Job In Review','Job Rejected','Job In Progress','Job Pending Verification','Job Completed','Job Cancelled') DEFAULT 'Job In Review',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt` varchar(255) DEFAULT NULL,
  `deliverables` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `client_id`, `freelancer_id`, `service_title`, `description`, `deadline`, `total_booking_hours`, `calculated_total_price`, `work_mode`, `location`, `booking_status`, `created_at`, `receipt`, `deliverables`) VALUES
('BK20251208163025328', 2, 3, 'Booking for Ashikin', 'A school project again', '2025-12-09', 3, 60.00, 'online', NULL, 'Job In Progress', '2025-12-08 15:30:49', NULL, NULL),
('BK20251208163205450', 2, 1, 'Booking for Badriatul Shafiyah', 'A data entry project', '2025-12-20', 5, 100.00, 'online', NULL, 'Job Rejected', '2025-12-08 15:33:28', NULL, NULL),
('BK20251208173815830', 2, 1, 'Booking for Badriatul Shafiyah', 'A new project', '2025-12-12', 5, 100.00, 'offline', NULL, 'Job In Review', '2025-12-08 16:38:26', NULL, NULL),
('BK20251208174636143', 2, 3, 'Booking for Ashikin', 'A new project', '2025-12-26', 5, 100.00, 'offline', NULL, 'Job In Review', '2025-12-08 16:46:53', '1765212422_BACKDROP PENUTUP BARU-08.png', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender` varchar(255) NOT NULL,
  `receiver` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender`, `receiver`, `message`, `created_at`) VALUES
(49, 'shikin', 'Badriatulshf', 'hi !', '2025-12-07 14:55:08'),
(50, 'badriatulshf', 'Shikin', 'hi ', '2025-12-07 14:55:15'),
(51, 'badriatulshf', 'Shikin', 'Apa khabar', '2025-12-07 14:55:25'),
(52, 'shikin', 'Badriatulshf', 'Khabar Baik', '2025-12-07 14:55:30'),
(53, 'dania123', 'badriatulshf', 'Hi', '2025-12-08 05:50:38'),
(54, 'badriatulshf', 'dania123', 'Hello !', '2025-12-08 05:50:45'),
(55, 'dania123', 'badriatulshf', 'Nama saya Dania', '2025-12-08 05:50:53'),
(56, 'dania123', 'badriatulshf', 'Hello', '2025-12-08 05:51:34'),
(57, 'badriatulshf', 'dania123', 'Testing', '2025-12-08 05:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`user_id`, `name`, `email`, `bio`, `address`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 'Badriatul Shafiyah', 'badriatulshf@gmail.com', 'swsw', 'Universiti Kebangsaan Malaysia Jalan Bangi', 'client_6936dbbdcd7684.06267253.png', '2025-12-08 13:11:08', '2025-12-08 14:07:57'),
(2, 'Dania Amirah', 'dania@gmail.com', 'A learner', 'Universiti Kebangsaan Malaysia Jalan Bangi', 'client_6936de822d3b05.20318084.png', '2025-12-08 14:19:46', '2025-12-08 14:19:46');

-- --------------------------------------------------------

--
-- Table structure for table `freelancers`
--

CREATE TABLE `freelancers` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `skills` varchar(255) DEFAULT NULL,
  `availability` enum('Full-time','Part-time','Occasional') DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `qr_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `freelancers`
--

INSERT INTO `freelancers` (`user_id`, `name`, `email`, `bio`, `skills`, `availability`, `profile_picture`, `created_at`, `updated_at`, `qr_code`) VALUES
(1, 'Badriatul Shafiyah', 'badriatulshf@gmail.com', 'A student with a great personality with huge attraction to teach', 'Data Entry, Photography', 'Full-time', 'pfp_69366365460349.31550452.png', '2025-09-25 06:07:29', '2025-12-08 05:34:29', 'qr_6912b73f04af91.51957462.jpg'),
(3, 'Ashikin', 'shikin@gmail.com', 'A pro', 'Data Entry, Photography', 'Full-time', 'pfp_69367134bd9205.84503202.png', '2025-12-08 06:33:24', '2025-12-08 06:33:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `phone` varchar(12) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','admin','freelancer') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `password`, `role`, `created_at`, `fullname`, `email`) VALUES
(1, 'badriatulshf', '01151392092', '$2y$10$GiafktseGz2AeIspf1kIluzzz9qtYWzMaFBBCJti4bJxqkreicGQS', 'freelancer', '2025-12-08 03:49:48', 'Badriatul Shafiyah', 'badriatulshf@gmail.com'),
(2, 'dania123', '012-6497886', '$2y$10$NQTzk.pHWjJsnAIrEAx1U.EB3ja/9ju10pz/7vAiKud9v1vkIzfNu', 'client', '2025-12-08 05:42:51', 'Dania Amirah', 'dania@gmail.com'),
(3, 'shikin123', '011982363092', '$2y$10$okShIL6qyjFQnVd4vXMTtOsdHwVV0ZvCAb5Ls0B8uHllVNjc4mV8m', 'freelancer', '2025-12-08 05:56:25', 'Ashikin', 'shikin@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_client` (`client_id`),
  ADD KEY `fk_freelancer` (`freelancer_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD KEY `fk_clients_user` (`user_id`);

--
-- Indexes for table `freelancers`
--
ALTER TABLE `freelancers`
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancers`
--
ALTER TABLE `freelancers`
  ADD CONSTRAINT `fk_freelancer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 12:07 AM
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
-- Database: `smart_library`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 5, 'return_book', 'Returned book: The Hobbit (Record ID: 1)', '2025-12-15 03:21:57'),
(2, 5, 'return_book', 'Returned book: The Hobbit (Record ID: 2)', '2025-12-15 03:23:05'),
(3, 5, 'return_book', 'Returned book: The Great Gatsby (Record ID: 4)', '2025-12-15 03:45:02'),
(4, 6, 'return_book', 'Returned book: 1984 (Record ID: 5)', '2025-12-15 04:22:59'),
(5, 6, 'return_book', 'Returned book: 1984 (Record ID: 6)', '2025-12-15 04:23:01'),
(6, 6, 'return_book', 'Returned book: 1984 (Record ID: 7)', '2025-12-15 04:23:03'),
(7, 9, 'return_book', 'Returned book: The Great Gatsby (Record ID: 8)', '2025-12-15 08:26:23'),
(8, 9, 'return_book', 'Returned book: 1984 (Record ID: 9)', '2025-12-15 08:26:30'),
(9, 9, 'return_book', 'Returned book: To Kill a Mockingbird (Record ID: 10)', '2025-12-15 08:26:35'),
(10, 5, 'return_book', 'Returned book: 1984 (Record ID: 3)', '2025-12-15 21:38:58'),
(11, 6, 'return_book', 'Returned book: Pride and Prejudice (Record ID: 12)', '2025-12-15 21:40:21');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(100) NOT NULL,
  `isbn` varchar(20) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `copies_available` int(11) DEFAULT 1,
  `total_copies` int(11) DEFAULT 1,
  `publication_year` int(11) DEFAULT NULL,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `publisher` varchar(255) DEFAULT NULL,
  `published_year` int(11) DEFAULT NULL,
  `edition` varchar(50) DEFAULT NULL,
  `total_pages` int(11) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `call_number` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT 'default-book.jpg',
  `status` enum('active','deleted') DEFAULT 'active',
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `category`, `copies_available`, `total_copies`, `publication_year`, `added_date`, `publisher`, `published_year`, `edition`, `total_pages`, `language`, `location`, `call_number`, `description`, `cover_image`, `status`, `deleted_at`, `updated_at`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'Fiction', 5, 5, 1925, '2025-12-14 19:44:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 21:55:34'),
(2, 'To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'Fiction', 3, 3, 1960, '2025-12-14 19:44:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 22:06:04'),
(3, '1984', 'George Orwell', '9780451524935', 'Science Fiction', 4, 4, 1949, '2025-12-14 19:44:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 21:55:34'),
(4, 'Pride and Prejudice', 'Jane Austen', '9781503290563', 'Romance', 1, 2, 1813, '2025-12-14 19:44:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 21:55:34'),
(5, 'The Catcher in the Rye', 'J.D. Salinger', '9780316769488', 'Fiction', 6, 6, 1951, '2025-12-14 19:44:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 21:55:34'),
(6, 'The Hobbit', 'J.R.R. Tolkien', '9780547928227', 'Fantasy', 4, 4, 1937, '2025-12-14 19:44:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 21:55:34'),
(7, 'Clean Code', 'Robert C. Martin', '9780132350884', 'Programming', 0, 0, 2008, '2025-12-15 16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default-book.jpg', 'active', NULL, '2025-12-15 21:55:34');

-- --------------------------------------------------------

--
-- Table structure for table `borrowing_records`
--

CREATE TABLE `borrowing_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` date NOT NULL,
  `return_date` timestamp NULL DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `fine_applied` tinyint(1) DEFAULT 0,
  `fine_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowing_records`
--

INSERT INTO `borrowing_records` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `fine_applied`, `fine_amount`) VALUES
(1, 5, 6, '2025-12-15 02:32:35', '2025-12-28', '2025-12-15 03:21:57', 'returned', 0, 0.00),
(2, 5, 6, '2025-12-15 03:22:10', '2025-12-28', '2025-12-15 03:23:05', 'returned', 0, 0.00),
(3, 5, 3, '2025-12-15 03:23:26', '2025-12-28', '2025-12-15 21:38:58', 'returned', 0, 0.00),
(4, 5, 1, '2025-12-15 03:44:55', '2025-12-28', '2025-12-15 03:45:02', 'returned', 0, 0.00),
(5, 6, 3, '2025-12-15 04:21:17', '2026-01-13', '2025-12-15 04:22:59', 'returned', 0, 0.00),
(6, 6, 3, '2025-12-15 04:22:47', '2026-01-13', '2025-12-15 04:23:01', 'returned', 0, 0.00),
(7, 6, 3, '2025-12-15 04:22:50', '2026-01-13', '2025-12-15 04:23:03', 'returned', 0, 0.00),
(8, 9, 1, '2025-12-15 08:25:01', '2025-12-29', '2025-12-15 08:26:23', 'returned', 0, 0.00),
(9, 9, 3, '2025-12-15 08:25:24', '2025-12-29', '2025-12-15 08:26:30', 'returned', 0, 0.00),
(10, 9, 2, '2025-12-15 08:25:29', '2025-12-29', '2025-12-15 08:26:35', 'returned', 0, 0.00),
(11, 5, 4, '2025-12-15 21:38:53', '2025-12-29', NULL, 'borrowed', 0, 0.00),
(12, 6, 4, '2025-12-15 21:40:12', '2026-01-14', '2025-12-15 21:40:21', 'returned', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `course_book_requests`
--

CREATE TABLE `course_book_requests` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','purchased') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_materials`
--

INSERT INTO `course_materials` (`id`, `teacher_id`, `book_id`, `course_name`, `semester`, `request_date`, `status`) VALUES
(1, 6, 3, 'dfgdgdfg', 'dgdfgdfgf', '2025-12-15 12:37:30', 'pending'),
(2, 6, 7, 'Programming', '2nd sem', '2025-12-16 05:40:50', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `borrowing_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `paid` tinyint(1) DEFAULT 0,
  `payment_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reservation_date` datetime DEFAULT current_timestamp(),
  `expiry_date` date NOT NULL,
  `status` enum('active','cancelled','fulfilled','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `book_id`, `reservation_date`, `expiry_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 7, '2025-12-16 05:08:24', '2025-12-22', 'cancelled', '2025-12-15 21:08:24', '2025-12-15 21:08:41'),
(2, 5, 7, '2025-12-16 05:32:05', '2025-12-22', 'cancelled', '2025-12-15 21:32:05', '2025-12-15 21:38:05'),
(3, 5, 7, '2025-12-16 05:38:13', '2025-12-22', 'active', '2025-12-15 21:38:13', '2025-12-15 21:38:13'),
(4, 6, 7, '2025-12-16 05:39:40', '2025-12-22', 'cancelled', '2025-12-15 21:39:40', '2025-12-15 21:40:29'),
(5, 6, 7, '2025-12-16 07:04:00', '2025-12-22', 'cancelled', '2025-12-15 23:04:00', '2025-12-15 23:04:07');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('student','teacher','librarian','staff') NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `registration_date`, `approved`, `approved_by`, `approval_date`) VALUES
(4, 'jophet2344', 'jophetsanchez2344@gmail.com', '$2y$10$n.itRKU/K4MOhtU53Bu/CerMeT8N308MTC58LuyIKWmXUMk946JPy', 'Administrator', 'staff', '2025-12-14 19:57:16', 1, NULL, NULL),
(5, 'nicaangelasamson123', 'nicaangelasamson@gmail.com', '$2y$10$wtu/GglaXohBhLTb3bu8OepaaZEe61wXxIxf1SuoXRXyqqzp1G8iG', 'Nica Angela Samson', 'student', '2025-12-14 23:55:01', 1, 4, '2025-12-15 00:41:38'),
(6, 'riosagaad123', 'Rio@gmail.com', '$2y$10$zjjM3hQOlM2I0fvCACEFAOQ2kYUxsXCTd7b0SYZ2oG9Z0Rkj47zQy', 'Rio Jen J. Sagaad', 'teacher', '2025-12-15 00:46:28', 1, 4, '2025-12-15 00:46:57'),
(7, 'Russelbenedict123', 'russelbenedict@gmail.com', '$2y$10$iOc07LcHLMOMlMGtoWq7m.9qdG9EbAYhsEuPP0RcRkxjShw1/mDVS', 'Russel Benedict Alforque', 'librarian', '2025-12-15 00:50:53', 1, 4, '2025-12-15 00:51:23'),
(9, 'aerialabella143', 'aerialabella143@gmail.com', '$2y$10$TnjnVlZ2Df8gvVB83RSXd.kh/YlMcUKERJ.fneS70MF5Ol4iKWuKC', 'Aerial Abella', 'student', '2025-12-15 08:22:57', 1, 4, '2025-12-15 08:23:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `borrowing_records`
--
ALTER TABLE `borrowing_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `course_book_requests`
--
ALTER TABLE `course_book_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `borrowing_id` (`borrowing_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_book_status` (`book_id`,`status`),
  ADD KEY `idx_expiry_date` (`expiry_date`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_book` (`user_id`,`book_id`),
  ADD KEY `idx_book_rating` (`book_id`,`rating`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `borrowing_records`
--
ALTER TABLE `borrowing_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `course_book_requests`
--
ALTER TABLE `course_book_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrowing_records`
--
ALTER TABLE `borrowing_records`
  ADD CONSTRAINT `borrowing_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrowing_records_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `course_book_requests`
--
ALTER TABLE `course_book_requests`
  ADD CONSTRAINT `course_book_requests_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowing_records` (`id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

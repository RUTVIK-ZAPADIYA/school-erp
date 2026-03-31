-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 31, 2026 at 04:29 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_erp`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_products_delete` (IN `p_id` INT)   BEGIN
    DELETE FROM products WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_products_edit` (IN `p_id` INT)   BEGIN
    SELECT
        id, name, category_id, brand, price, discount, final_price,
        stock, description, long_description, image, gallery_images, status
    FROM products
    WHERE id = p_id
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_products_insert` (IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_price` DECIMAL(10,2), IN `p_discount` INT, IN `p_stock` INT, IN `p_description` TEXT, IN `p_long_description` LONGTEXT, IN `p_image` VARCHAR(255), IN `p_gallery_images` JSON, IN `p_status` VARCHAR(10))   BEGIN
    INSERT INTO products (
        name, category_id, brand, price, discount, stock,
        description, long_description, image, gallery_images, status
    ) VALUES (
        p_name, p_category_id, p_brand, p_price,
        CASE
            WHEN p_discount IS NULL OR p_discount < 0 THEN 0
            WHEN p_discount > 30 THEN 30
            ELSE p_discount
        END,
        p_stock,
        p_description, p_long_description, p_image, p_gallery_images,
        CASE WHEN p_status IN ('Active', 'Inactive') THEN p_status ELSE 'Active' END
    );

    SELECT LAST_INSERT_ID() AS inserted_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_products_read` ()   BEGIN
    SELECT
        id, name, category_id, brand, price, discount, final_price,
        stock, description, long_description, image, gallery_images, status
    FROM products
    ORDER BY id DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_products_update` (IN `p_id` INT, IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_price` DECIMAL(10,2), IN `p_discount` INT, IN `p_stock` INT, IN `p_description` TEXT, IN `p_long_description` LONGTEXT, IN `p_image` VARCHAR(255), IN `p_gallery_images` JSON, IN `p_status` VARCHAR(10))   BEGIN
    UPDATE products
    SET
        name = p_name,
        category_id = p_category_id,
        brand = p_brand,
        price = p_price,
        discount = CASE
            WHEN p_discount IS NULL OR p_discount < 0 THEN 0
            WHEN p_discount > 30 THEN 30
            ELSE p_discount
        END,
        stock = p_stock,
        description = p_description,    
        long_description = p_long_description,
        image = p_image,
        gallery_images = p_gallery_images,
        status = CASE WHEN p_status IN ('Active', 'Inactive') THEN p_status ELSE 'Active' END
    WHERE id = p_id;

    SELECT ROW_COUNT() AS affected_rows;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `teacher_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `total_marks` int DEFAULT '100',
  `total_points` int DEFAULT '100',
  `allow_late_submissions` tinyint(1) DEFAULT '0',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int NOT NULL,
  `assignment_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(255) DEFAULT NULL,
  `marks_obtained` decimal(10,2) DEFAULT NULL,
  `grade` decimal(10,2) DEFAULT NULL,
  `remarks` text,
  `status` enum('pending','submitted','graded','late') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date` date DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `class_id`, `attendance_date`, `status`, `created_at`, `date`, `subject_id`, `teacher_id`) VALUES
(1, 12, 346, NULL, 'present', '2026-03-31 15:15:05', '2026-03-31', 3, 1271),
(2, 13, 346, NULL, 'present', '2026-03-31 15:15:05', '2026-03-31', 3, 1271),
(3, 12, 346, NULL, 'present', '2026-03-31 15:15:17', '2026-03-25', 3, 1271),
(4, 13, 346, NULL, 'absent', '2026-03-31 15:15:17', '2026-03-25', 3, 1271);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) DEFAULT NULL,
  `room_number` varchar(30) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `academic_year` varchar(30) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `section`, `teacher_id`, `status`, `created_at`, `name`, `room_number`, `capacity`, `academic_year`, `description`) VALUES
(346, '10', 'a', 1271, 'Active', '2026-03-31 14:27:38', '10', '101', 20, '2024-25', 'fb gbh ergth t');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_date` date DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `exam_type` varchar(60) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `total_marks` int DEFAULT NULL,
  `room_number` varchar(30) DEFAULT NULL,
  `invigilator` int DEFAULT NULL,
  `instructions` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_name`, `exam_date`, `class_id`, `subject_id`, `status`, `created_at`, `exam_type`, `start_time`, `duration`, `total_marks`, `room_number`, `invigilator`, `instructions`) VALUES
(1, 's strfhrt', '2026-04-01', NULL, NULL, 'Scheduled', '2026-03-31 11:43:19', 'Unit Test', '20:13:00', 120, 100, '1234', NULL, 'sd gtdrfgh sdg');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `fee_type` varchar(50) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_method` varchar(40) DEFAULT NULL,
  `remarks` text,
  `paid_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `amount`, `fee_type`, `due_date`, `status`, `created_at`, `payment_method`, `remarks`, `paid_date`) VALUES
(1, NULL, 43534.00, 'Tuition Fee', '2026-03-31', 'Paid', '2026-03-31 11:42:19', 'Cash', 'df gtredfg', '2026-03-31'),
(2, NULL, 10000.00, 'Exam Fee', '2026-03-31', 'Paid', '2026-03-31 11:46:00', 'Online', 'srdg sfg', '2026-03-31');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `exam_type` varchar(60) DEFAULT NULL,
  `total_marks` int DEFAULT '100',
  `obtained_marks` int DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `remarks` text,
  `teacher_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `student_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `marks` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `exam_type` varchar(60) DEFAULT NULL,
  `remarks` text,
  `student_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `roll_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `roll_no`, `name`, `class`, `email`, `phone`, `status`, `created_at`, `updated_at`, `class_id`, `user_id`, `username`) VALUES
(12, 'STU001', 'soham', '10', 'soham@gmail.com', '1234567890', 'Active', '2026-03-31 14:28:26', '2026-03-31 16:02:15', 346, NULL, 'STU001'),
(13, 'stu02', 'shira', '10', 'shira@gmail.com', '+1 234 567 8900', 'Active', '2026-03-31 14:29:04', '2026-03-31 16:12:59', 346, 1274, 'shira');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(150) DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `credits` int DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `code`, `status`, `created_at`, `name`, `class_id`, `teacher_id`, `credits`, `type`, `description`) VALUES
(2, 'Physics', 'PHY', 'Active', '2026-03-29 11:39:26', 'Physics', NULL, NULL, NULL, NULL, NULL),
(3, 'Chemistry', 'CHEM', 'Active', '2026-03-29 11:39:26', 'Chemistry', 346, 1271, 4, 'Core', '');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `category` varchar(50) DEFAULT 'Technical Issue',
  `status` varchar(20) DEFAULT 'Open',
  `admin_reply` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `student_id`, `title`, `message`, `category`, `status`, `admin_reply`, `created_at`, `updated_at`, `replied_at`) VALUES
(1, 1274, 'fa sdfad f', 'd dafasd dsf adsf', 'Data Error', 'Open', NULL, '2026-03-31 16:25:24', '2026-03-31 16:25:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `experience` int DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `address` text,
  `salary` decimal(10,2) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `email`, `phone`, `subject`, `status`, `created_at`, `updated_at`, `experience`, `qualification`, `first_name`, `last_name`, `joining_date`, `address`, `salary`, `user_id`, `username`) VALUES
(1271, 'RUTVIK ZAPADIYA', 'rutvikzapadiya111@gmail.com', '1234567890', 'Chemistry', 'Active', '2026-03-31 14:27:01', '2026-03-31 14:27:01', 12, 'msc', 'RUTVIK', 'ZAPADIYA', '2026-03-31', 'fdge etgr e', 1000.00, 1273, 'rutvik');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$I7SJP6I89U2LQ7O/eQvQ0.oV6fyV2osBOUGwvF1nIbqTB4VT1mD5G', 'admin', 'Admin User', 'admin@school.com', '+1000000001', 'Active', '2026-03-29 11:39:26', '2026-03-29 11:39:26'),
(3, 'student1', '$2y$10$trcpl/VPFEX0y1Q.YH2VkeEnFrm33W.YUvZmg0QGdtMrbbVz3DPzG', 'student', 'Rahul Sharma', 'student1@school.com', '+1000000003', 'Active', '2026-03-29 11:39:26', '2026-03-29 11:39:26'),
(1273, 'rutvik', '$2y$10$7o6doclnJl4NtdilzJm9b.e9OwYuxN6xpMumNVzv2fNEnuiNf0wJa', 'teacher', 'RUTVIK ZAPADIYA', 'rutvikzapadiya111@gmail.com', '1234567890', 'Active', '2026-03-31 14:27:01', '2026-03-31 14:27:01'),
(1274, 'shira', '$2y$10$cCk6RXT4qgK5E8WYAySa5.uETvllbA2E2us.Ie5D9DxvE0C4FN2ky', 'student', 'shira', 'shira@gmail.com', '+1 234 567 8900', 'Active', '2026-03-31 16:12:59', '2026-03-31 16:12:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_name` (`class_name`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_users_username` (`username`),
  ADD UNIQUE KEY `uniq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=347;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1272;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1275;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 02, 2026 at 05:23 AM
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

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `title`, `description`, `teacher_id`, `subject_id`, `class_id`, `due_date`, `total_marks`, `total_points`, `allow_late_submissions`, `file_path`, `created_at`) VALUES
(1, 'assign-1', 'f sh', 1271, 2, 346, '2026-04-11', 100, 100, 0, NULL, '2026-03-31 17:03:48');

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
(4, 13, 346, NULL, 'absent', '2026-03-31 15:15:17', '2026-03-25', 3, 1271),
(5, 12, 346, NULL, 'present', '2026-04-01 16:39:48', '2026-04-01', 3, 1271),
(6, 13, 346, NULL, 'late', '2026-04-01 16:39:48', '2026-04-01', 3, 1271),
(7, 12, 346, NULL, 'absent', '2026-04-02 04:24:46', '2026-04-02', 3, 1271),
(8, 13, 346, NULL, 'absent', '2026-04-02 04:24:46', '2026-04-02', 3, 1271),
(9, 14, 346, NULL, 'absent', '2026-04-02 04:24:46', '2026-04-02', 3, 1271);

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
(3, 13, 10000.00, 'Tuition Fee', '2026-04-11', 'Pending', '2026-04-01 17:21:04', 'Cash', 'f gsfdg f', NULL),
(4, 15, 10000.00, 'Tuition Fee', '2026-04-11', 'Pending', '2026-04-02 04:36:07', 'Cash', '', NULL),
(5, 14, 3000.00, 'Exam Fee', '2026-04-22', 'Pending', '2026-04-02 05:19:18', 'Cash', 'Please pay your pending fees before due date.', NULL);

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

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `exam_type`, `total_marks`, `obtained_marks`, `grade`, `remarks`, `teacher_id`, `created_at`, `student_user_id`) VALUES
(1, 12, 3, 'Mid-term', 100, 0, 'F', '', 1271, '2026-04-01 16:39:29', 12),
(2, 13, 3, 'Mid-term', 100, 0, 'F', '', 1271, '2026-04-01 16:39:29', 1274),
(3, 14, 3, 'Mid-term', 100, 76, 'B+', 'need attention and be regular', 1271, '2026-04-02 04:28:40', 1275);

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int NOT NULL,
  `application_id` varchar(50) DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `student_user_id` int DEFAULT NULL,
  `leave_type` varchar(40) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` int NOT NULL,
  `reason` text,
  `status` varchar(20) DEFAULT 'pending',
  `admin_remark` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `teacher_remark` text,
  `reviewed_by_teacher_id` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `application_id`, `student_id`, `student_user_id`, `leave_type`, `from_date`, `to_date`, `days`, `reason`, `status`, `admin_remark`, `created_at`, `updated_at`, `teacher_remark`, `reviewed_by_teacher_id`, `reviewed_at`) VALUES
(1, 'LA20260331171453017', 13, 1274, 'sick', '2026-03-31', '2026-04-05', 6, 'd sdfgsfdsdfg', 'approved', NULL, '2026-03-31 17:14:53', '2026-04-02 05:03:40', '', 1273, '2026-04-02 10:33:40'),
(2, 'LA20260331171546285', 13, 1274, 'sick', '2026-03-31', '2026-04-05', 6, 'd sdfgsfdsdfg', 'rejected', NULL, '2026-03-31 17:15:46', '2026-04-02 05:12:39', 'please connect me to your parents', 1273, '2026-04-02 10:42:39'),
(3, 'LA20260401163646936', 13, 1274, 'sick', '2026-04-01', '2026-04-07', 7, 'DSFG SDFGSDFGS DF FD GSDFG SD', 'approved', NULL, '2026-04-01 16:36:46', '2026-04-02 05:03:38', '', 1273, '2026-04-02 10:33:38'),
(4, 'LA20260402024349327', 14, 1275, 'sick', '2026-04-02', '2026-04-13', 12, 'ds fasd fadsf asd fd', 'rejected', NULL, '2026-04-02 02:43:49', '2026-04-02 05:03:36', '', 1273, '2026-04-02 10:33:36'),
(5, 'LA20260402043012847', 14, 1275, 'sick', '2026-04-02', '2026-04-02', 1, 'qvery ill due to fever and cold', 'approved', NULL, '2026-04-02 04:30:12', '2026-04-02 05:03:33', '', 1273, '2026-04-02 10:33:33'),
(6, 'LA20260402050418581', 14, 1275, 'sick', '2026-04-03', '2026-04-15', 13, 'dfghjk;lkjh', 'pending', NULL, '2026-04-02 05:04:18', '2026-04-02 05:04:18', NULL, NULL, NULL);

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

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `teacher_id`, `class_id`, `subject_id`, `day_of_week`, `start_time`, `end_time`, `room`) VALUES
(1, 1271, 346, 3, 'Monday', '08:00:00', '10:00:00', '101');

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
(13, 'stu02', 'shira', '10', 'shira@gmail.com', '+1 234 567 8900', 'Active', '2026-03-31 14:29:04', '2026-03-31 16:12:59', 346, 1274, 'shira'),
(14, 'STU1275', 'ammar bharmal', '10', 'hardipzapadiya5931@gmail.com', '1234567890', 'Active', '2026-04-01 17:12:30', '2026-04-02 02:43:02', 346, 1275, 'ammar'),
(15, 'STU1276', 'Meet Desai', '10', 'zerodayalliance@gmail.com', '1234567890', 'Active', '2026-04-02 04:34:27', '2026-04-02 04:34:42', 346, 1276, 'Meet1');

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
(1, 1274, 'fa sdfad f', 'd dafasd dsf adsf', 'Data Error', 'Resolved', 'your issue has been resolved', '2026-03-31 16:25:24', '2026-03-31 17:01:27', '2026-03-31 17:01:27'),
(2, 1275, 'login error', 'can not login in website properly', 'Database Problem', 'Resolved', 'issue resolved', '2026-04-02 04:30:51', '2026-04-02 04:40:19', '2026-04-02 04:40:19'),
(3, 1275, 'still same problem', 'cannot login still', 'Login Issue', 'Open', NULL, '2026-04-02 04:31:16', '2026-04-02 04:31:16', NULL);

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

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'school_name', 'Neo School Of Science', '2026-04-02 05:00:12'),
(2, 'school_email', 'info@schoolneo.com', '2026-04-02 05:00:13'),
(3, 'school_phone', '+1 234 567 8900', '2026-03-31 17:19:21'),
(4, 'school_address', '123 School St', '2026-03-31 17:19:21');

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
(1271, 'dwij malaviya', 'rutvikzapadiya111@gmail.com', '1234567890', 'Chemistry', 'Active', '2026-03-31 14:27:01', '2026-04-02 02:42:26', 12, 'msc', 'dwij', 'malaviya', '2026-03-31', 'fdge etgr e', 1000.00, 1273, 'dwij'),
(1272, 'testing', 'hardipzapadiya5931@gmail.com', '1234567890', 'General', 'Active', '2026-04-01 17:12:21', '2026-04-01 17:12:21', NULL, NULL, 'testing', NULL, NULL, NULL, NULL, 1275, 'deep');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verification_expiry` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `phone`, `status`, `created_at`, `updated_at`, `reset_token`, `reset_token_expiry`, `email_verification_token`, `email_verification_expiry`, `email_verified`) VALUES
(1, 'rutvik', '$2y$10$0zZr7FO1pzJkdq6gZa2Sn.vXHaycbHX0kbtX./oBGKyUHfwWww.FC', 'admin', 'rutvik zapadiya', 'admin@school.com', '+1000000001', 'Active', '2026-03-29 11:39:26', '2026-04-02 04:57:45', NULL, NULL, NULL, NULL, 0),
(1273, 'dwij', '$2y$10$/UUhH48BgBMNtvgvd4MYTeLWK3CFA1OtSp4Iv7HEcb1iCZ6vyACfq', 'teacher', 'dwij malaviya', 'rutvikzapadiya111@gmail.com', '1234567890', 'Active', '2026-03-31 14:27:01', '2026-04-02 04:27:11', '20ae257bc0813d7f0fecaeead21793d018dbb1872c296c3ae1c32ef2a4509236', '2026-04-02 10:27:11', NULL, NULL, 0),
(1274, 'shira', '$2y$10$cCk6RXT4qgK5E8WYAySa5.uETvllbA2E2us.Ie5D9DxvE0C4FN2ky', 'student', 'shira', 'shira@gmail.com', '+1 234 567 8900', 'Active', '2026-03-31 16:12:59', '2026-04-02 04:58:11', NULL, NULL, NULL, NULL, 0),
(1275, 'ammar', '$2y$10$ahTVSvWZp46i9It5O/wi0eLCUIg7MQuOZYEH3pHtfrSkPgr9NUyXu', 'student', 'ammar bharmal', 'hardipzapadiya5931@gmail.com', '1234567890', 'Active', '2026-04-01 16:41:23', '2026-04-02 02:43:02', NULL, NULL, NULL, NULL, 0),
(1276, 'Meet1', '$2y$10$z/YDf6cG1yOWkRUwN2vpROQn3NsZN2xAIGr9E1FpZIAdoCrD3hqGe', 'student', 'Meet Desai', 'zerodayalliance@gmail.com', '1234567890', 'Active', '2026-04-02 04:33:51', '2026-04-02 05:21:13', NULL, NULL, NULL, NULL, 0);

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
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_leave_application_id` (`application_id`),
  ADD KEY `idx_leave_student_id` (`student_id`),
  ADD KEY `idx_leave_student_user_id` (`student_user_id`),
  ADD KEY `idx_leave_status` (`status`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1273;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1277;

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

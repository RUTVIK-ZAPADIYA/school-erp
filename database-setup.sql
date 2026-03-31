CREATE DATABASE IF NOT EXISTS school_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_erp;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_users_username (username),
    UNIQUE KEY uniq_users_email (email)
);

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(100) NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_teachers_email (email)
);

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NULL,
    class_name VARCHAR(100) NULL,
    section VARCHAR(20) NULL,
    teacher_id INT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    class VARCHAR(100) NULL,
    class_id INT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Male','Female','Other') NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_students_roll (roll_no)
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NULL,
    subject_name VARCHAR(150) NULL,
    code VARCHAR(30) NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subjects_code (code)
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    teacher_id INT NULL,
    subject_id INT NULL,
    class_id INT NULL,
    due_date DATE NULL,
    total_marks INT DEFAULT 100,
    total_points INT DEFAULT 100,
    allow_late_submissions BOOLEAN DEFAULT FALSE,
    file_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NULL,
    student_id INT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(255) NULL,
    marks_obtained DECIMAL(10,2) NULL,
    grade DECIMAL(10,2) NULL,
    remarks TEXT NULL,
    status ENUM('pending','submitted','graded','late') DEFAULT 'submitted'
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NULL,
    class_id INT NULL,
    date DATE NULL,
    attendance_date DATE NULL,
    status VARCHAR(20) NULL,
    subject_id INT NULL,
    teacher_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NULL,
    student_user_id INT NULL,
    subject_id INT NULL,
    exam_type VARCHAR(60) NULL,
    total_marks INT DEFAULT 100,
    obtained_marks INT NULL,
    grade VARCHAR(10) NULL,
    remarks TEXT NULL,
    teacher_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NULL,
    student_user_id INT NULL,
    subject_id INT NULL,
    teacher_id INT NULL,
    marks INT NULL,
    date DATE NULL,
    exam_type VARCHAR(60) NULL,
    remarks TEXT NULL
);

CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    fee_type VARCHAR(80) NULL,
    due_date DATE NULL,
    status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS leave_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NULL,
    student_id INT NULL,
    student_user_id INT NULL,
    leave_type VARCHAR(40) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    days INT NOT NULL,
    reason TEXT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    admin_remark TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_leave_application_id (application_id),
    KEY idx_leave_student_id (student_id),
    KEY idx_leave_student_user_id (student_user_id),
    KEY idx_leave_status (status)
);

CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NULL,
    class_id INT NULL,
    subject_id INT NULL,
    day_of_week VARCHAR(20) NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    room VARCHAR(60) NULL
);

CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(120) NOT NULL,
    exam_date DATE NULL,
    class_id INT NULL,
    subject_id INT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO users (username, password, role, name, email, phone) VALUES
('admin', 'admin123', 'admin', 'Admin User', 'admin@school.com', '+1000000001'),
('teacher1', 'teacher123', 'teacher', 'Prof. Priya Patel', 'teacher1@school.com', '+1000000002'),
('student1', 'student123', 'student', 'Rahul Sharma', 'student1@school.com', '+1000000003');

INSERT IGNORE INTO teachers (id, user_id, name, email, phone, subject, status) VALUES
(2, 2, 'Prof. Priya Patel', 'teacher1@school.com', '+1000000002', 'Mathematics', 'Active');

INSERT IGNORE INTO subjects (name, subject_name, code, status) VALUES
('Mathematics', 'Mathematics', 'MATH', 'Active'),
('Physics', 'Physics', 'PHY', 'Active'),
('Chemistry', 'Chemistry', 'CHEM', 'Active');

INSERT IGNORE INTO classes (id, name, class_name, section, teacher_id, status) VALUES
(1, 'Grade 10A', 'Grade 10A', 'A', 2, 'Active'),
(2, 'Grade 10B', 'Grade 10B', 'B', 2, 'Active'),
(3, 'Grade 12', 'Grade 12', 'A', 2, 'Active');

INSERT IGNORE INTO students (roll_no, name, user_id, username, class, class_id, email, phone, status) VALUES
('STU001', 'Rahul Sharma', 3, 'student1', 'Grade 10A', 1, 'rahul@school.com', '+1 234 567 8900', 'Active'),
('STU002', 'Priya Verma', NULL, 'stu002', 'Grade 10B', 2, 'priya@school.com', '+1 234 567 8901', 'Active'),
('STU003', 'Amit Kumar', NULL, 'stu003', 'Grade 12', 3, 'amit@school.com', '+1 234 567 8902', 'Active');

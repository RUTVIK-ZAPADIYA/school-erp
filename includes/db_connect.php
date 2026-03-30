<?php
mysqli_report(MYSQLI_REPORT_OFF);

// Database connection configuration
$servername = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'school_erp';

// Create connection without specifying database first.
$conn = mysqli_connect($servername, $username, $password);

if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Ensure application database exists.
$createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!mysqli_query($conn, $createDbSql)) {
    die('Error creating database: ' . mysqli_error($conn));
}

if (!mysqli_select_db($conn, $dbname)) {
    die('Error selecting database: ' . mysqli_error($conn));
}

mysqli_set_charset($conn, 'utf8mb4');

if (!function_exists('ensure_school_erp_column')) {
    function ensure_school_erp_column($conn, $tableName, $columnName, $definition)
    {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $definition");
        }
    }
}

if (!function_exists('ensure_school_erp_schema')) {
    function ensure_school_erp_schema($conn)
    {
        mysqli_query($conn, "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NULL,
                class_name VARCHAR(100) NULL,
                section VARCHAR(20) NULL,
                teacher_id INT NULL,
                status VARCHAR(20) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                roll_no VARCHAR(50) NOT NULL,
                name VARCHAR(150) NOT NULL,
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NULL,
                subject_name VARCHAR(150) NULL,
                code VARCHAR(30) NULL,
                status VARCHAR(20) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_subjects_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS grades (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NULL,
                subject_id INT NULL,
                exam_type VARCHAR(60) NULL,
                total_marks INT DEFAULT 100,
                obtained_marks INT NULL,
                grade VARCHAR(10) NULL,
                remarks TEXT NULL,
                teacher_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS marks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NULL,
                subject_id INT NULL,
                teacher_id INT NULL,
                marks INT NULL,
                date DATE NULL,
                exam_type VARCHAR(60) NULL,
                remarks TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS fees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NULL,
                amount DECIMAL(10,2) DEFAULT 0,
                fee_type VARCHAR(80) NULL,
                due_date DATE NULL,
                status VARCHAR(30) DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS schedule (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NULL,
                class_id INT NULL,
                subject_id INT NULL,
                day_of_week VARCHAR(20) NULL,
                start_time TIME NULL,
                end_time TIME NULL,
                room VARCHAR(60) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS exams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                exam_name VARCHAR(120) NOT NULL,
                exam_date DATE NULL,
                class_id INT NULL,
                subject_id INT NULL,
                status VARCHAR(20) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS support_tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                category VARCHAR(50) DEFAULT 'Technical Issue',
                status VARCHAR(20) DEFAULT 'Open',
                admin_reply TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                replied_at TIMESTAMP NULL,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        ensure_school_erp_column($conn, 'classes', 'name', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'classes', 'class_name', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'students', 'class', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'students', 'class_id', 'INT NULL');
        ensure_school_erp_column($conn, 'subjects', 'name', "VARCHAR(150) NULL");
        ensure_school_erp_column($conn, 'subjects', 'subject_name', "VARCHAR(150) NULL");
        ensure_school_erp_column($conn, 'assignments', 'total_points', 'INT DEFAULT 100');
        ensure_school_erp_column($conn, 'assignments', 'total_marks', 'INT DEFAULT 100');
        ensure_school_erp_column($conn, 'attendance', 'date', 'DATE NULL');
        ensure_school_erp_column($conn, 'attendance', 'attendance_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'assignment_submissions', 'grade', 'DECIMAL(10,2) NULL');

        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $teacherPass = password_hash('teacher123', PASSWORD_DEFAULT);
        $studentPass = password_hash('student123', PASSWORD_DEFAULT);

        mysqli_query($conn, "INSERT IGNORE INTO users (username, password, role, name, email, phone) VALUES
            ('admin', '$adminPass', 'admin', 'Admin User', 'admin@school.com', '+1000000001'),
            ('teacher1', '$teacherPass', 'teacher', 'Prof. Priya Patel', 'teacher1@school.com', '+1000000002'),
            ('student1', '$studentPass', 'student', 'Rahul Sharma', 'student1@school.com', '+1000000003')
        ");

        $teacherId = 2;
        $teacherRes = mysqli_query($conn, "SELECT id FROM users WHERE username = 'teacher1' LIMIT 1");
        if ($teacherRes) {
            $teacherRow = mysqli_fetch_assoc($teacherRes);
            if ($teacherRow && isset($teacherRow['id'])) {
                $teacherId = (int)$teacherRow['id'];
            }
        }

        mysqli_query($conn, "INSERT IGNORE INTO teachers (id, user_id, name, email, phone, subject, status) VALUES
            ($teacherId, $teacherId, 'Prof. Priya Patel', 'teacher1@school.com', '+1000000002', 'Mathematics', 'Active')
        ");

        $subjectsCountRes = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM subjects');
        $subjectsCount = 0;
        if ($subjectsCountRes) {
            $subjectsCountRow = mysqli_fetch_assoc($subjectsCountRes);
            $subjectsCount = (int)($subjectsCountRow['cnt'] ?? 0);
        }

        if ($subjectsCount === 0) {
            mysqli_query($conn, "INSERT INTO subjects (name, subject_name, code, status) VALUES
                ('Mathematics', 'Mathematics', 'MATH', 'Active'),
                ('Physics', 'Physics', 'PHY', 'Active'),
                ('Chemistry', 'Chemistry', 'CHEM', 'Active')
            ");
        }

        $classesCountRes = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM classes');
        $classesCount = 0;
        if ($classesCountRes) {
            $classesCountRow = mysqli_fetch_assoc($classesCountRes);
            $classesCount = (int)($classesCountRow['cnt'] ?? 0);
        }

        if ($classesCount === 0) {
            mysqli_query($conn, "INSERT INTO classes (name, class_name, section, teacher_id, status) VALUES
                ('Grade 10A', 'Grade 10A', 'A', $teacherId, 'Active'),
                ('Grade 10B', 'Grade 10B', 'B', $teacherId, 'Active'),
                ('Grade 12', 'Grade 12', 'A', $teacherId, 'Active')
            ");
        }
    }
}

if (!defined('SCHOOL_ERP_SCHEMA_READY')) {
    define('SCHOOL_ERP_SCHEMA_READY', true);
    ensure_school_erp_schema($conn);
}
?>
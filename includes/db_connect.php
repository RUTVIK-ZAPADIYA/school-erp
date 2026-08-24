<?php
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/env_loader.php';
school_erp_load_env(__DIR__ . '/../.env');

// Database connection configuration
$isRender = strtolower((string) getenv('RENDER')) === 'true';
$servername = getenv('DB_HOST') ?: ($isRender ? '' : '127.0.0.1');
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'school_erp';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);

if ($servername === '') {
    error_log('Database connection failed: DB_HOST is not configured.');
    die('System error: database host is not configured. Set DB_HOST, DB_USER, DB_PASS, DB_NAME, and DB_PORT in Render.');
}

// Create connection without specifying database first.
$conn = new mysqli($servername, $username, $password, '', $dbPort);

if ($conn->connect_errno) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('System error: unable to connect to database. Please contact administrator.');
}

// Local MySQL can create the database; managed databases provision it beforehand.
if (!$isRender) {
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query( $createDbSql)) {
        error_log('Database creation failed: ' . $conn->error);
        die('System error: unable to initialize database. Please contact administrator.');
    }
}

if (!$conn->select_db( $dbname)) {
    error_log('Database selection failed: ' . $conn->error);
    die('System error: unable to access database. Please contact administrator.');
}

$conn->set_charset( 'utf8mb4');

if (!function_exists('ensure_school_erp_column')) {
    function ensure_school_erp_column($conn, $tableName, $columnName, $definition)
    {
        $result = $conn->query( "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        if (!$result) {
            error_log("Schema check failed for {$tableName}.{$columnName}: " . $conn->error);
            return;
        }

        if ($result->num_rows === 0) {
            if (!$conn->query( "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $definition")) {
                error_log("Schema migration failed for {$tableName}.{$columnName}: " . $conn->error);
            }
        }
    }
}

if (!function_exists('school_erp_table_exists')) {
    function school_erp_table_exists($conn, $tableName)
    {
        $safeTable = $conn->real_escape_string($tableName);
        $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('school_erp_column_exists')) {
    function school_erp_column_exists($conn, $tableName, $columnName)
    {
        if (!school_erp_table_exists($conn, $tableName)) {
            return false;
        }

        $safeTable = $conn->real_escape_string($tableName);
        $safeColumn = $conn->real_escape_string($columnName);
        $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('ensure_school_erp_schema')) {
    function ensure_school_erp_schema($conn)
    {
        $conn->query( "
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

        $conn->query( "
            CREATE TABLE IF NOT EXISTS teachers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                name VARCHAR(150) NOT NULL,
                username VARCHAR(100) NULL,
                email VARCHAR(150) NULL,
                phone VARCHAR(30) NULL,
                subject VARCHAR(100) NULL,
                status VARCHAR(20) DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_teachers_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query( "
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

        $conn->query( "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query( "
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

        $conn->query( "
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

        $conn->query( "
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

        $conn->query( "
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

        $conn->query( "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query( "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query( "
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

        $conn->query( "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query( "
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

        $conn->query( "
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

        $conn->query( "
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

        $conn->query( "
            CREATE TABLE IF NOT EXISTS notices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                target_audience VARCHAR(30) DEFAULT 'all',
                class_id INT NULL,
                publish_date DATE NULL,
                expiry_date DATE NULL,
                status VARCHAR(20) DEFAULT 'Active',
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        ensure_school_erp_column($conn, 'classes', 'name', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'classes', 'class_name', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'users', 'reset_token', 'VARCHAR(255) NULL');
        ensure_school_erp_column($conn, 'users', 'reset_token_expiry', 'DATETIME NULL');
        ensure_school_erp_column($conn, 'users', 'email_verification_token', 'VARCHAR(255) NULL');
        ensure_school_erp_column($conn, 'users', 'email_verification_expiry', 'DATETIME NULL');
        ensure_school_erp_column($conn, 'users', 'email_verified', 'TINYINT(1) DEFAULT 0');
        ensure_school_erp_column($conn, 'students', 'user_id', 'INT NULL');
        ensure_school_erp_column($conn, 'students', 'username', 'VARCHAR(100) NULL');
        ensure_school_erp_column($conn, 'students', 'class', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'students', 'class_id', 'INT NULL');
        ensure_school_erp_column($conn, 'students', 'email', "VARCHAR(150) NULL");
        ensure_school_erp_column($conn, 'students', 'phone', "VARCHAR(30) NULL");
        ensure_school_erp_column($conn, 'students', 'status', "VARCHAR(20) DEFAULT 'Active'");
        ensure_school_erp_column($conn, 'teachers', 'username', "VARCHAR(100) NULL");
        ensure_school_erp_column($conn, 'subjects', 'name', "VARCHAR(150) NULL");
        ensure_school_erp_column($conn, 'subjects', 'subject_name', "VARCHAR(150) NULL");
        ensure_school_erp_column($conn, 'assignments', 'total_points', 'INT DEFAULT 100');
        ensure_school_erp_column($conn, 'assignments', 'total_marks', 'INT DEFAULT 100');
        ensure_school_erp_column($conn, 'attendance', 'date', 'DATE NULL');
        ensure_school_erp_column($conn, 'attendance', 'attendance_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'attendance', 'class_id', 'INT NULL');
        ensure_school_erp_column($conn, 'attendance', 'subject_id', 'INT NULL');
        ensure_school_erp_column($conn, 'attendance', 'teacher_id', 'INT NULL');
        ensure_school_erp_column($conn, 'attendance', 'status', "VARCHAR(20) NULL");
        ensure_school_erp_column($conn, 'assignment_submissions', 'grade', 'DECIMAL(10,2) NULL');
        ensure_school_erp_column($conn, 'grades', 'student_user_id', 'INT NULL');
        ensure_school_erp_column($conn, 'marks', 'student_user_id', 'INT NULL');
        ensure_school_erp_column($conn, 'fees', 'payment_method', 'VARCHAR(40) NULL');
        ensure_school_erp_column($conn, 'fees', 'remarks', 'TEXT NULL');
        ensure_school_erp_column($conn, 'fees', 'paid_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'fees', 'razorpay_order_id', 'VARCHAR(80) NULL');
        ensure_school_erp_column($conn, 'fees', 'razorpay_payment_id', 'VARCHAR(80) NULL');
        ensure_school_erp_column($conn, 'fees', 'razorpay_signature', 'VARCHAR(255) NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'application_id', 'VARCHAR(50) NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'student_user_id', 'INT NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'leave_type', 'VARCHAR(40) NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'from_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'to_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'days', 'INT NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'reason', 'TEXT NULL');
        ensure_school_erp_column($conn, 'leave_applications', 'status', "VARCHAR(20) DEFAULT 'pending'");
        ensure_school_erp_column($conn, 'leave_applications', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        ensure_school_erp_column($conn, 'notices', 'title', 'VARCHAR(255) NOT NULL');
        ensure_school_erp_column($conn, 'notices', 'message', 'TEXT NOT NULL');
        ensure_school_erp_column($conn, 'notices', 'target_audience', "VARCHAR(30) DEFAULT 'all'");
        ensure_school_erp_column($conn, 'notices', 'class_id', 'INT NULL');
        ensure_school_erp_column($conn, 'notices', 'publish_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'notices', 'expiry_date', 'DATE NULL');
        ensure_school_erp_column($conn, 'notices', 'status', "VARCHAR(20) DEFAULT 'Active'");
        ensure_school_erp_column($conn, 'notices', 'created_by', 'INT NULL');
        ensure_school_erp_column($conn, 'notices', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $teacherPass = password_hash('teacher123', PASSWORD_DEFAULT);
        $studentPass = password_hash('student123', PASSWORD_DEFAULT);

        $usersCountRes = $conn->query( 'SELECT COUNT(*) AS cnt FROM users');
        $usersCount = 0;
        if ($usersCountRes) {
            $usersCountRow = $usersCountRes->fetch_assoc();
            $usersCount = (int) ($usersCountRow['cnt'] ?? 0);
        }

        $seededDefaultUsers = false;
        if ($usersCount === 0) {
            $conn->query( "INSERT INTO users (username, password, role, name, email, phone) VALUES
                ('admin', '$adminPass', 'admin', 'Admin User', 'admin@school.com', '+1000000001'),
                ('teacher1', '$teacherPass', 'teacher', 'Prof. Priya Patel', 'teacher1@school.com', '+1000000002'),
                ('student1', '$studentPass', 'student', 'Rahul Sharma', 'student1@school.com', '+1000000003')
            ");
            $seededDefaultUsers = true;
        }

        $teacherId = 0;
        $teacherRes = $conn->query( "SELECT id FROM users WHERE username = 'teacher1' LIMIT 1");
        if ($teacherRes) {
            $teacherRow = $teacherRes->fetch_assoc();
            if ($teacherRow && isset($teacherRow['id'])) {
                $teacherId = (int)$teacherRow['id'];
            }
        }

        $teachersCountRes = $conn->query( 'SELECT COUNT(*) AS cnt FROM teachers');
        $teachersCount = 0;
        if ($teachersCountRes) {
            $teachersCountRow = $teachersCountRes->fetch_assoc();
            $teachersCount = (int) ($teachersCountRow['cnt'] ?? 0);
        }

        if ($teachersCount === 0 && $seededDefaultUsers && $teacherId > 0) {
            $conn->query( "INSERT INTO teachers (id, user_id, name, email, phone, subject, status) VALUES
                ($teacherId, $teacherId, 'Prof. Priya Patel', 'teacher1@school.com', '+1000000002', 'Mathematics', 'Active')
            ");
        }

        $subjectsCountRes = $conn->query( 'SELECT COUNT(*) AS cnt FROM subjects');
        $subjectsCount = 0;
        if ($subjectsCountRes) {
            $subjectsCountRow = $subjectsCountRes->fetch_assoc();
            $subjectsCount = (int)($subjectsCountRow['cnt'] ?? 0);
        }

        if ($subjectsCount === 0) {
            $conn->query( "INSERT INTO subjects (name, subject_name, code, status) VALUES
                ('Mathematics', 'Mathematics', 'MATH', 'Active'),
                ('Physics', 'Physics', 'PHY', 'Active'),
                ('Chemistry', 'Chemistry', 'CHEM', 'Active')
            ");
        }

        $classesCountRes = $conn->query( 'SELECT COUNT(*) AS cnt FROM classes');
        $classesCount = 0;
        if ($classesCountRes) {
            $classesCountRow = $classesCountRes->fetch_assoc();
            $classesCount = (int)($classesCountRow['cnt'] ?? 0);
        }

        if ($classesCount === 0) {
            $conn->query( "INSERT INTO classes (name, class_name, section, teacher_id, status) VALUES
                ('Grade 10A', 'Grade 10A', 'A', $teacherId, 'Active'),
                ('Grade 10B', 'Grade 10B', 'B', $teacherId, 'Active'),
                ('Grade 12', 'Grade 12', 'A', $teacherId, 'Active')
            ");
        }

        if (
            school_erp_table_exists($conn, 'students')
            && school_erp_table_exists($conn, 'classes')
            && school_erp_column_exists($conn, 'students', 'class_id')
            && school_erp_column_exists($conn, 'students', 'class')
            && school_erp_column_exists($conn, 'classes', 'name')
            && school_erp_column_exists($conn, 'classes', 'class_name')
        ) {
            $studentClassNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(s.`class`, '')), ' ', ''), '-', ''))";
            $classNameNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(c.name, '')), ' ', ''), '-', ''))";
            $classNameAltNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(c.class_name, '')), ' ', ''), '-', ''))";

            $conn->query(
                "UPDATE students s
                 LEFT JOIN classes c ON s.class_id = c.id
                 SET s.class_id = NULL
                 WHERE s.class_id IS NOT NULL AND s.class_id <> 0 AND c.id IS NULL"
            );

            $conn->query(
                "UPDATE students s
                 INNER JOIN classes c
                    ON {$studentClassNorm} <> ''
                   AND ({$studentClassNorm} = {$classNameNorm} OR {$studentClassNorm} = {$classNameAltNorm})
                 SET s.class_id = c.id
                 WHERE (s.class_id IS NULL OR s.class_id = 0)"
            );

            $conn->query(
                "UPDATE students s
                 INNER JOIN classes c ON s.class_id = c.id
                 SET s.`class` = COALESCE(NULLIF(c.name, ''), c.class_name, s.`class`)
                 WHERE s.`class` IS NULL OR TRIM(COALESCE(s.`class`, '')) = ''"
            );
        }

        if (
            school_erp_table_exists($conn, 'students')
            && school_erp_table_exists($conn, 'users')
            && school_erp_column_exists($conn, 'students', 'user_id')
            && school_erp_column_exists($conn, 'users', 'role')
            && school_erp_column_exists($conn, 'users', 'username')
        ) {
            if (school_erp_column_exists($conn, 'students', 'username')) {
                $conn->query(
                    "UPDATE students s
                     SET s.username = s.roll_no
                     WHERE (s.username IS NULL OR TRIM(COALESCE(s.username, '')) = '')
                       AND TRIM(COALESCE(s.roll_no, '')) <> ''"
                );

                $conn->query(
                    "UPDATE students s
                     INNER JOIN users u ON s.user_id = u.id
                     SET s.username = COALESCE(NULLIF(s.username, ''), u.username)
                     WHERE u.role = 'student'"
                );

                $conn->query(
                    "UPDATE students s
                     INNER JOIN users u
                        ON u.role = 'student'
                       AND s.user_id IS NULL
                       AND LOWER(TRIM(COALESCE(s.username, ''))) = LOWER(TRIM(COALESCE(u.username, '')))
                     SET s.user_id = u.id"
                );
            }

            if (school_erp_column_exists($conn, 'students', 'email') && school_erp_column_exists($conn, 'users', 'email')) {
                $conn->query(
                    "UPDATE students s
                     INNER JOIN users u
                        ON u.role = 'student'
                       AND s.user_id IS NULL
                       AND LOWER(TRIM(COALESCE(s.email, ''))) <> ''
                       AND LOWER(TRIM(COALESCE(s.email, ''))) = LOWER(TRIM(COALESCE(u.email, '')))
                     SET s.user_id = u.id"
                );
            }

            if (school_erp_column_exists($conn, 'users', 'name')) {
                $conn->query(
                    "UPDATE students s
                     INNER JOIN users u
                        ON u.role = 'student'
                       AND s.user_id IS NULL
                       AND LOWER(TRIM(COALESCE(s.name, ''))) <> ''
                       AND LOWER(TRIM(COALESCE(s.name, ''))) = LOWER(TRIM(COALESCE(u.name, '')))
                     SET s.user_id = u.id"
                );
            }

            if (school_erp_column_exists($conn, 'students', 'username')) {
                $conn->query(
                    "UPDATE students s
                     INNER JOIN users u ON s.user_id = u.id
                     SET s.username = u.username
                     WHERE u.role = 'student'"
                );
            }
        }

        if (
            school_erp_table_exists($conn, 'students')
            && school_erp_column_exists($conn, 'students', 'id')
            && school_erp_column_exists($conn, 'students', 'user_id')
        ) {
            if (
                school_erp_table_exists($conn, 'grades')
                && school_erp_column_exists($conn, 'grades', 'student_id')
                && school_erp_column_exists($conn, 'grades', 'student_user_id')
            ) {
                $conn->query(
                    "UPDATE grades g
                     INNER JOIN students s ON g.student_id = s.id
                     SET g.student_user_id = COALESCE(NULLIF(g.student_user_id, 0), s.user_id)
                     WHERE s.user_id IS NOT NULL AND s.user_id <> 0"
                );

                if (school_erp_table_exists($conn, 'users') && school_erp_column_exists($conn, 'users', 'role')) {
                    $conn->query(
                        "UPDATE grades g
                         LEFT JOIN students s ON g.student_id = s.id
                         INNER JOIN users u ON g.student_id = u.id AND u.role = 'student'
                         SET g.student_user_id = g.student_id
                         WHERE s.id IS NULL AND (g.student_user_id IS NULL OR g.student_user_id = 0)"
                    );
                }

                $conn->query(
                    "UPDATE grades g
                     INNER JOIN students s ON g.student_user_id = s.user_id
                                         SET g.student_id = s.id
                                         WHERE s.user_id IS NOT NULL
                                             AND s.user_id <> 0
                                             AND (g.student_id IS NULL OR g.student_id = 0 OR g.student_id <> s.id)"
                );
            }

            if (
                school_erp_table_exists($conn, 'marks')
                && school_erp_column_exists($conn, 'marks', 'student_id')
                && school_erp_column_exists($conn, 'marks', 'student_user_id')
            ) {
                $conn->query(
                    "UPDATE marks m
                     INNER JOIN students s ON m.student_id = s.id
                     SET m.student_user_id = COALESCE(NULLIF(m.student_user_id, 0), s.user_id)
                     WHERE s.user_id IS NOT NULL AND s.user_id <> 0"
                );

                if (school_erp_table_exists($conn, 'users') && school_erp_column_exists($conn, 'users', 'role')) {
                    $conn->query(
                        "UPDATE marks m
                         LEFT JOIN students s ON m.student_id = s.id
                         INNER JOIN users u ON m.student_id = u.id AND u.role = 'student'
                         SET m.student_user_id = m.student_id
                         WHERE s.id IS NULL AND (m.student_user_id IS NULL OR m.student_user_id = 0)"
                    );
                }

                $conn->query(
                    "UPDATE marks m
                     INNER JOIN students s ON m.student_user_id = s.user_id
                     SET m.student_id = s.id
                     WHERE s.user_id IS NOT NULL
                       AND s.user_id <> 0
                       AND (m.student_id IS NULL OR m.student_id = 0 OR m.student_id <> s.id)"
                );
            }

            if (
                school_erp_table_exists($conn, 'leave_applications')
                && school_erp_column_exists($conn, 'leave_applications', 'student_id')
                && school_erp_column_exists($conn, 'leave_applications', 'student_user_id')
            ) {
                $conn->query(
                    "UPDATE leave_applications l
                     INNER JOIN students s ON l.student_id = s.id
                     SET l.student_user_id = COALESCE(NULLIF(l.student_user_id, 0), s.user_id)
                     WHERE s.user_id IS NOT NULL AND s.user_id <> 0"
                );

                $conn->query(
                    "UPDATE leave_applications l
                     LEFT JOIN students s_profile ON l.student_id = s_profile.id
                     INNER JOIN students s_user ON l.student_id = s_user.user_id
                     SET l.student_user_id = l.student_id,
                         l.student_id = s_user.id
                     WHERE s_profile.id IS NULL
                       AND s_user.user_id IS NOT NULL
                       AND s_user.user_id <> 0
                       AND (l.student_user_id IS NULL OR l.student_user_id = 0)"
                );

                $conn->query(
                    "UPDATE leave_applications l
                     INNER JOIN students s ON l.student_user_id = s.user_id
                     SET l.student_id = s.id
                     WHERE s.user_id IS NOT NULL
                       AND s.user_id <> 0
                       AND (l.student_id IS NULL OR l.student_id = 0 OR l.student_id <> s.id)"
                );
            }
        }

        if (
            school_erp_table_exists($conn, 'leave_applications')
            && school_erp_column_exists($conn, 'leave_applications', 'application_id')
            && school_erp_column_exists($conn, 'leave_applications', 'id')
        ) {
            $conn->query(
                "UPDATE leave_applications
                 SET application_id = CONCAT('LA', LPAD(id, 6, '0'))
                 WHERE application_id IS NULL OR TRIM(application_id) = ''"
            );
        }

        $studentsCountRes = $conn->query('SELECT COUNT(*) AS cnt FROM students');
        $studentsCount = 0;
        if ($studentsCountRes) {
            $studentsCountRow = $studentsCountRes->fetch_assoc();
            $studentsCount = (int) ($studentsCountRow['cnt'] ?? 0);
        }

        if ($studentsCount === 0) {
            $classRows = [];
            $classSeedResult = $conn->query(
                "SELECT id, COALESCE(NULLIF(name, ''), class_name, CONCAT('Class ', id)) AS class_label
                 FROM classes
                 ORDER BY id ASC
                 LIMIT 3"
            );
            if ($classSeedResult) {
                while ($classSeedRow = $classSeedResult->fetch_assoc()) {
                    $classRows[] = [
                        'id' => (int) ($classSeedRow['id'] ?? 0),
                        'label' => (string) ($classSeedRow['class_label'] ?? ''),
                    ];
                }
            }

            if (empty($classRows)) {
                $classRows = [
                    ['id' => 0, 'label' => 'Grade 10A'],
                    ['id' => 0, 'label' => 'Grade 10B'],
                    ['id' => 0, 'label' => 'Grade 12'],
                ];
            }

            $seedStudents = [
                ['roll_no' => 'STU001', 'name' => 'Rahul Sharma', 'email' => 'rahul@school.com', 'phone' => '+1000000101'],
                ['roll_no' => 'STU002', 'name' => 'Priya Verma', 'email' => 'priya@school.com', 'phone' => '+1000000102'],
                ['roll_no' => 'STU003', 'name' => 'Amit Kumar', 'email' => 'amit@school.com', 'phone' => '+1000000103'],
            ];

            foreach ($seedStudents as $index => $studentSeed) {
                $classSeed = $classRows[$index % count($classRows)];
                $safeRoll = $conn->real_escape_string((string) $studentSeed['roll_no']);
                $safeName = $conn->real_escape_string((string) $studentSeed['name']);
                $safeEmail = $conn->real_escape_string((string) $studentSeed['email']);
                $safePhone = $conn->real_escape_string((string) $studentSeed['phone']);
                $safeClass = $conn->real_escape_string((string) $classSeed['label']);
                $classIdSql = ((int) ($classSeed['id'] ?? 0) > 0) ? (string) (int) $classSeed['id'] : 'NULL';

                $safeUsername = $conn->real_escape_string(strtolower((string) $studentSeed['roll_no']));

                if (school_erp_column_exists($conn, 'students', 'username')) {
                    $conn->query(
                        "INSERT INTO students (roll_no, name, username, class, class_id, email, phone, status)
                         VALUES ('{$safeRoll}', '{$safeName}', '{$safeUsername}', '{$safeClass}', {$classIdSql}, '{$safeEmail}', '{$safePhone}', 'Active')"
                    );
                } else {
                    $conn->query(
                        "INSERT INTO students (roll_no, name, class, class_id, email, phone, status)
                         VALUES ('{$safeRoll}', '{$safeName}', '{$safeClass}', {$classIdSql}, '{$safeEmail}', '{$safePhone}', 'Active')"
                    );
                }
            }
        }
    }
}

if (!defined('SCHOOL_ERP_SCHEMA_READY')) {
    define('SCHOOL_ERP_SCHEMA_READY', true);
    ensure_school_erp_schema($conn);
}
?>
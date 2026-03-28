<?php
// Database connection configuration (without database name initially)
$servername = "localhost";
$username = "root";
$password = "";

// Create connection without database
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS teacher";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select the database
mysqli_select_db($conn, "teacher");

// Set charset to utf8 for proper encoding
mysqli_set_charset($conn, "utf8");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . mysqli_error($conn) . "<br>";
}

// Create classes table
$sql = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    teacher_id INT,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Classes table created successfully<br>";
} else {
    echo "Error creating classes table: " . mysqli_error($conn) . "<br>";
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    class_id INT,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    FOREIGN KEY (class_id) REFERENCES classes(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Students table created successfully<br>";
} else {
    echo "Error creating students table: " . mysqli_error($conn) . "<br>";
}

// Create subjects table
$sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE
)";

if (mysqli_query($conn, $sql)) {
    echo "Subjects table created successfully<br>";
} else {
    echo "Error creating subjects table: " . mysqli_error($conn) . "<br>";
}

// Create attendance table
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    subject_id INT,
    teacher_id INT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Attendance table created successfully<br>";
} else {
    echo "Error creating attendance table: " . mysqli_error($conn) . "<br>";
}

// Create grades table
$sql = "CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject_id INT,
    exam_type VARCHAR(50),
    total_marks INT,
    obtained_marks INT,
    grade VARCHAR(5),
    remarks TEXT,
    teacher_id INT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Grades table created successfully<br>";
} else {
    echo "Error creating grades table: " . mysqli_error($conn) . "<br>";
}

// Create schedule table
$sql = "CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    class_id INT,
    subject_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME,
    end_time TIME,
    room VARCHAR(50),
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Schedule table created successfully<br>";
} else {
    echo "Error creating schedule table: " . mysqli_error($conn) . "<br>";
}

// Insert sample schedule data
$sql = "INSERT IGNORE INTO schedule (teacher_id, class_id, subject_id, day_of_week, start_time, end_time, room) VALUES 
(2, 1, 1, 'Monday', '09:00:00', '10:00:00', 'Room 101'),
(2, 2, 1, 'Tuesday', '09:00:00', '10:00:00', 'Room 101'),
(2, 1, 1, 'Wednesday', '09:00:00', '10:00:00', 'Room 101'),
(2, 2, 1, 'Thursday', '09:00:00', '10:00:00', 'Room 101'),
(2, 3, 2, 'Friday', '09:00:00', '10:00:00', 'Room 205'),
(2, 3, 2, 'Monday', '11:00:00', '12:00:00', 'Room 205'),
(2, 3, 2, 'Wednesday', '11:00:00', '12:00:00', 'Room 205'),
(2, 1, 1, 'Tuesday', '14:00:00', '15:00:00', 'Room 101'),
(2, 1, 1, 'Thursday', '14:00:00', '15:00:00', 'Room 101')";

if (mysqli_query($conn, $sql)) {
    echo "Schedule data inserted<br>";
}

// Insert teacher
$sql = "INSERT IGNORE INTO users (username, password, role, name, email) VALUES 
('teacher1', '" . password_hash('teacher123', PASSWORD_DEFAULT) . "', 'teacher', 'Prof. Priya Patel', 'priya.patel@school.com')";

if (mysqli_query($conn, $sql)) {
    echo "Teacher inserted<br>";
}

// Insert subjects
$sql = "INSERT IGNORE INTO subjects (name, code) VALUES 
('Mathematics', 'MATH'),
('Physics', 'PHY'),
('Chemistry', 'CHEM')";

if (mysqli_query($conn, $sql)) {
    echo "Subjects inserted<br>";
}

// Insert classes
$sql = "INSERT IGNORE INTO classes (name, teacher_id) VALUES 
('Grade 10A', 2),
('Grade 10B', 2),
('Grade 12', 2)";

if (mysqli_query($conn, $sql)) {
    echo "Classes inserted<br>";
}

// Create assignments table
$sql = "CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    teacher_id INT,
    subject_id INT,
    class_id INT,
    due_date DATE,
    total_points INT DEFAULT 100,
    allow_late_submissions BOOLEAN DEFAULT FALSE,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Assignments table created successfully<br>";
} else {
    echo "Error creating assignments table: " . mysqli_error($conn) . "<br>";
}

// Ensure assignments table has the correct columns (for existing databases)
$result = mysqli_query($conn, "SHOW COLUMNS FROM assignments LIKE 'total_points'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE assignments ADD COLUMN total_points INT DEFAULT 100";
    if (mysqli_query($conn, $sql)) {
        echo "Added total_points column to assignments table<br>";
    } else {
        echo "Error adding total_points column: " . mysqli_error($conn) . "<br>";
    }
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM assignments LIKE 'allow_late_submissions'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE assignments ADD COLUMN allow_late_submissions BOOLEAN DEFAULT FALSE";
    if (mysqli_query($conn, $sql)) {
        echo "Added allow_late_submissions column to assignments table<br>";
    } else {
        echo "Error adding allow_late_submissions column: " . mysqli_error($conn) . "<br>";
    }
}

// Create assignment_submissions table
$sql = "CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    student_id INT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(255),
    marks_obtained INT,
    remarks TEXT,
    status ENUM('submitted', 'graded', 'late') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Assignment submissions table created successfully<br>";
} else {
    echo "Error creating assignment submissions table: " . mysqli_error($conn) . "<br>";
}

// Ensure assignment_submissions table has the correct columns
$result = mysqli_query($conn, "SHOW COLUMNS FROM assignment_submissions LIKE 'marks_obtained'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE assignment_submissions ADD COLUMN marks_obtained INT";
    if (mysqli_query($conn, $sql)) {
        echo "Added marks_obtained column to assignment_submissions table<br>";
    } else {
        echo "Error adding marks_obtained column: " . mysqli_error($conn) . "<br>";
    }
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM assignment_submissions LIKE 'remarks'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE assignment_submissions ADD COLUMN remarks TEXT";
    if (mysqli_query($conn, $sql)) {
        echo "Added remarks column to assignment_submissions table<br>";
    } else {
        echo "Error adding remarks column: " . mysqli_error($conn) . "<br>";
    }
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM assignment_submissions LIKE 'status'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE assignment_submissions ADD COLUMN status ENUM('submitted', 'graded', 'late') DEFAULT 'submitted'";
    if (mysqli_query($conn, $sql)) {
        echo "Added status column to assignment_submissions table<br>";
    } else {
        echo "Error adding status column: " . mysqli_error($conn) . "<br>";
    }
}

// Create marks table (for storing marks/grades with dates)
$sql = "CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject_id INT,
    teacher_id INT,
    marks INT,
    date DATE,
    exam_type VARCHAR(50),
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Marks table created successfully<br>";
} else {
    echo "Error creating marks table: " . mysqli_error($conn) . "<br>";
}

// Insert sample assignments
$sql = "INSERT IGNORE INTO assignments (title, description, teacher_id, subject_id, class_id, due_date, total_points) VALUES 
('Mathematics Assignment 1', 'Solve problems 1-10 from chapter 5', 2, 1, 1, '2024-12-15', 50),
('Physics Lab Report', 'Complete the pendulum experiment report', 2, 2, 1, '2024-12-20', 30),
('Chemistry Quiz', 'Chapter 3 quiz on chemical reactions', 2, 3, 2, '2024-12-18', 25)";

if (mysqli_query($conn, $sql)) {
    echo "Sample assignments inserted<br>";
}

// Insert sample marks data for the last 10 weeks
$sql = "INSERT IGNORE INTO marks (student_id, subject_id, teacher_id, marks, date, exam_type) VALUES 
(1, 1, 2, 85, DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 'Weekly Test'),
(2, 1, 2, 78, DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 'Weekly Test'),
(3, 1, 2, 92, DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 'Weekly Test'),
(1, 1, 2, 88, DATE_SUB(CURDATE(), INTERVAL 2 WEEK), 'Weekly Test'),
(2, 1, 2, 76, DATE_SUB(CURDATE(), INTERVAL 2 WEEK), 'Weekly Test'),
(3, 1, 2, 89, DATE_SUB(CURDATE(), INTERVAL 2 WEEK), 'Weekly Test'),
(1, 1, 2, 82, DATE_SUB(CURDATE(), INTERVAL 3 WEEK), 'Weekly Test'),
(2, 1, 2, 79, DATE_SUB(CURDATE(), INTERVAL 3 WEEK), 'Weekly Test'),
(3, 1, 2, 91, DATE_SUB(CURDATE(), INTERVAL 3 WEEK), 'Weekly Test'),
(1, 1, 2, 87, DATE_SUB(CURDATE(), INTERVAL 4 WEEK), 'Weekly Test'),
(2, 1, 2, 81, DATE_SUB(CURDATE(), INTERVAL 4 WEEK), 'Weekly Test'),
(3, 1, 2, 94, DATE_SUB(CURDATE(), INTERVAL 4 WEEK), 'Weekly Test'),
(1, 1, 2, 84, DATE_SUB(CURDATE(), INTERVAL 5 WEEK), 'Weekly Test'),
(2, 1, 2, 77, DATE_SUB(CURDATE(), INTERVAL 5 WEEK), 'Weekly Test'),
(3, 1, 2, 88, DATE_SUB(CURDATE(), INTERVAL 5 WEEK), 'Weekly Test'),
(1, 1, 2, 89, DATE_SUB(CURDATE(), INTERVAL 6 WEEK), 'Weekly Test'),
(2, 1, 2, 83, DATE_SUB(CURDATE(), INTERVAL 6 WEEK), 'Weekly Test'),
(3, 1, 2, 96, DATE_SUB(CURDATE(), INTERVAL 6 WEEK), 'Weekly Test'),
(1, 1, 2, 86, DATE_SUB(CURDATE(), INTERVAL 7 WEEK), 'Weekly Test'),
(2, 1, 2, 80, DATE_SUB(CURDATE(), INTERVAL 7 WEEK), 'Weekly Test'),
(3, 1, 2, 93, DATE_SUB(CURDATE(), INTERVAL 7 WEEK), 'Weekly Test'),
(1, 1, 2, 91, DATE_SUB(CURDATE(), INTERVAL 8 WEEK), 'Weekly Test'),
(2, 1, 2, 85, DATE_SUB(CURDATE(), INTERVAL 8 WEEK), 'Weekly Test'),
(3, 1, 2, 97, DATE_SUB(CURDATE(), INTERVAL 8 WEEK), 'Weekly Test'),
(1, 1, 2, 88, DATE_SUB(CURDATE(), INTERVAL 9 WEEK), 'Weekly Test'),
(2, 1, 2, 82, DATE_SUB(CURDATE(), INTERVAL 9 WEEK), 'Weekly Test'),
(3, 1, 2, 95, DATE_SUB(CURDATE(), INTERVAL 9 WEEK), 'Weekly Test'),
(1, 1, 2, 90, DATE_SUB(CURDATE(), INTERVAL 10 WEEK), 'Weekly Test'),
(2, 1, 2, 87, DATE_SUB(CURDATE(), INTERVAL 10 WEEK), 'Weekly Test'),
(3, 1, 2, 98, DATE_SUB(CURDATE(), INTERVAL 10 WEEK), 'Weekly Test')";

if (mysqli_query($conn, $sql)) {
    echo "Sample marks data inserted<br>";
}

// Insert sample assignment submissions
$sql = "INSERT IGNORE INTO assignment_submissions (assignment_id, student_id, marks_obtained, status) VALUES 
(1, 1, 42, 'graded'),
(1, 2, 38, 'graded'),
(2, 1, 28, 'graded'),
(2, 2, 25, 'graded')";

if (mysqli_query($conn, $sql)) {
    echo "Sample assignment submissions inserted<br>";
}

// Insert students
$sql = "INSERT IGNORE INTO students (roll_no, name, class_id, email) VALUES 
('001', 'Rahul Sharma', 1, 'rahul@school.com'),
('002', 'Priya Verma', 1, 'priya@school.com'),
('003', 'Amit Kumar', 2, 'amit@school.com'),
('004', 'Sneha Reddy', 2, 'sneha@school.com'),
('005', 'Arjun Singh', 3, 'arjun@school.com'),
('006', 'satyam', 1, 'satyam@school.com'),
('007', 'Rutvik Shira', 1, 'rutvik@school.com'),
('008', 'Hardip Zapadiya', 1, 'hardip@school.com'),
('009', 'Deep Ramani', 1, 'deep@school.com'),
('010', 'Pranshu jr.', 1, 'pranshu@school.com')";

if (mysqli_query($conn, $sql)) {
    echo "Students inserted<br>";
}

echo "Database setup completed!";
?>
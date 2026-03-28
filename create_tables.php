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
$sql = "CREATE DATABASE IF NOT EXISTS school_erp";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select the database
mysqli_select_db($conn, "school_erp");

// Set charset to utf8 for proper encoding
mysqli_set_charset($conn, "utf8");

// Create assignments table
$sql = "CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    teacher_id INT,
    subject_id INT,
    class_id INT,
    due_date DATE,
    total_marks INT,
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
$sql = "INSERT IGNORE INTO assignments (title, description, teacher_id, subject_id, class_id, due_date, total_marks) VALUES
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

echo "Database setup completed!";
?>
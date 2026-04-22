<?php
require_once __DIR__ . '/auth.php';

include '../includes/db_connect.php';

// Resolve teacher identity across users.id and teachers.id.
$teacherContext = teacher_auth_resolve_context($conn);
$teacher_id = (int) ($teacherContext['user_id'] ?? 0);
$teacher_owner_ids = (array) ($teacherContext['teacher_ids'] ?? [$teacher_id]);
$teacher_ids_sql = (string) ($teacherContext['teacher_ids_sql'] ?? '0');
$teacher_name = (string) ($teacherContext['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher');

function teacher_table_exists($conn, $tableName)
{
    $safeTable = $conn->real_escape_string( $tableName);
    $result = $conn->query( "SHOW TABLES LIKE '{$safeTable}'");

    return $result && $result->num_rows > 0;
}

function teacher_column_exists($conn, $tableName, $columnName)
{
    if (!teacher_table_exists($conn, $tableName)) {
        return false;
    }

    $safeTable = $conn->real_escape_string( $tableName);
    $safeColumn = $conn->real_escape_string( $columnName);
    $result = $conn->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

    return $result && $result->num_rows > 0;
}

function teacher_first_existing_column($conn, $tableName, array $candidates)
{
    foreach ($candidates as $candidate) {
        if (teacher_column_exists($conn, $tableName, $candidate)) {
            return $candidate;
        }
    }

    return null;
}

$classNameColumn = teacher_first_existing_column($conn, 'classes', ['name', 'class_name']);
$subjectNameColumn = teacher_first_existing_column($conn, 'subjects', ['name', 'subject_name']);
$assignmentPointsColumn = teacher_first_existing_column($conn, 'assignments', ['total_points', 'total_marks']);
$assignmentPointsExpr = $assignmentPointsColumn !== null ? "a.`{$assignmentPointsColumn}`" : '0';
$studentsHasUserId = teacher_column_exists($conn, 'students', 'user_id');
$gradesHasStudentUserId = teacher_column_exists($conn, 'grades', 'student_user_id');

// Get student details
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($student_id == 0) {
    header("Location: students.php");
    exit();
}

$classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
$studentClassJoinParts = [];
$hasStudentClassId = teacher_column_exists($conn, 'students', 'class_id');
if ($hasStudentClassId) {
    $studentClassJoinParts[] = 's.class_id = c.id';
}
if (teacher_column_exists($conn, 'students', 'class')) {
    $studentClassNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(s.`class`, '')), ' ', ''), '-', ''))";
    $classNameNorm = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', ''))";
    $fallbackCondition = "({$studentClassNorm} <> '' AND {$studentClassNorm} = {$classNameNorm})";
    if ($hasStudentClassId) {
        $fallbackCondition = "(COALESCE(s.class_id, 0) = 0 AND {$fallbackCondition})";
    }
    $studentClassJoinParts[] = $fallbackCondition;
}

$student = null;
if (!empty($studentClassJoinParts)) {
    $studentClassJoinSql = implode(' OR ', $studentClassJoinParts);
    $query = "SELECT DISTINCT s.*, {$classNameExpr} AS class_name
              FROM students s
              JOIN classes c ON ({$studentClassJoinSql})
              WHERE s.id = ? AND c.teacher_id IN ({$teacher_ids_sql})
              LIMIT 1";
    $stmt = $conn->prepare( $query);
    if ($stmt) {
        $stmt->bind_param( "i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

if (!$student) {
    header("Location: students.php");
    exit();
}

$student_user_id = $studentsHasUserId ? (int) ($student['user_id'] ?? 0) : 0;
if ($student_user_id <= 0) {
    $student_user_id = $student_id;
}

// Get student's grades
$subjectNameExpr = $subjectNameColumn !== null ? "sub.`{$subjectNameColumn}`" : "CONCAT('Subject ', sub.id)";
$query = "SELECT g.exam_type, g.total_marks, g.obtained_marks, g.grade, g.remarks, {$subjectNameExpr} AS subject_name
          FROM grades g
          JOIN subjects sub ON g.subject_id = sub.id
          WHERE " . ($gradesHasStudentUserId ? '(g.student_id = ? OR g.student_user_id = ?)' : 'g.student_id = ?') . "
          ORDER BY g.exam_type, subject_name";
$stmt = $conn->prepare( $query);
if ($gradesHasStudentUserId) {
    $stmt->bind_param( "ii", $student_id, $student_user_id);
} else {
    $stmt->bind_param( "i", $student_id);
}
$stmt->execute();
$result = $stmt->get_result();
$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}
$stmt->close();

// Get student's attendance
$query = "SELECT COUNT(*) as total_days,
          SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
          FROM attendance
          WHERE student_id IN (?, ?)";
$stmt = $conn->prepare( $query);
$stmt->bind_param( "ii", $student_id, $student_user_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();
$attendance_percentage = $attendance['total_days'] > 0 ?
    round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) : 0;

// Calculate average grade and performance metrics
$avg_grade = 0;
$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
$total_subjects = count($grades);

if ($total_subjects > 0) {
    $total_marks = 0;
    foreach ($grades as $grade) {
        $total_marks += $grade['obtained_marks'];

        $percentage = ($grade['obtained_marks'] / $grade['total_marks']) * 100;
        if ($percentage >= 90) $grade_distribution['A']++;
        elseif ($percentage >= 80) $grade_distribution['B']++;
        elseif ($percentage >= 70) $grade_distribution['C']++;
        elseif ($percentage >= 60) $grade_distribution['D']++;
        else $grade_distribution['F']++;
    }
    $avg_grade = round($total_marks / $total_subjects, 1);
}

// Get recent assignments for this student
$query = "SELECT a.title, asub.marks_obtained, asub.status, {$assignmentPointsExpr} AS max_marks, a.due_date
          FROM assignment_submissions asub
          JOIN assignments a ON asub.assignment_id = a.id
          WHERE asub.student_id IN (?, ?) AND a.teacher_id IN ({$teacher_ids_sql})
          ORDER BY a.due_date DESC LIMIT 5";
$stmt = $conn->prepare( $query);
$stmt->bind_param( "ii", $student_id, $student_user_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_assignments = [];
while ($row = $result->fetch_assoc()) {
    $recent_assignments[] = $row;
}
$stmt->close();

// Calculate assignment statistics
$assignment_stats = ['completed' => 0, 'pending' => 0, 'graded' => 0];
foreach ($recent_assignments as $assignment) {
    if ($assignment['status'] == 'submitted') $assignment_stats['completed']++;
    elseif ($assignment['marks_obtained'] !== null) $assignment_stats['graded']++;
    else $assignment_stats['pending']++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - Pro Edition</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container-low": "#f6f3f2",
                        "error-container": "#ffdad6",
                        "on-surface": "#1b1c1c",
                        "secondary-fixed": "#e4e2e1",
                        "surface-container-high": "#eae8e7",
                        "on-tertiary-fixed-variant": "#920600",
                        "surface-variant": "#e4e2e1",
                        "on-error-container": "#93000a",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-highest": "#e4e2e1",
                        "on-primary": "#ffffff",
                        "tertiary-fixed": "#ffdad4",
                        "surface-bright": "#fbf9f8",
                        "on-secondary-container": "#656464",
                        "secondary": "#5f5e5e",
                        "on-primary-fixed": "#001947",
                        "on-background": "#1b1c1c",
                        "surface-tint": "#1357c9",
                        "on-primary-container": "#beceff",
                        "primary-container": "#0051c3",
                        "on-tertiary-fixed": "#400100",
                        "tertiary": "#870500",
                        "on-tertiary-container": "#ffc0b6",
                        "error": "#ba1a1a",
                        "outline-variant": "#c3c6d6",
                        "outline": "#737785",
                        "on-tertiary": "#ffffff",
                        "on-secondary-fixed-variant": "#474747",
                        "surface-dim": "#dcd9d9",
                        "secondary-fixed-dim": "#c8c6c6",
                        "on-secondary-fixed": "#1b1c1c",
                        "secondary-container": "#e4e2e1",
                        "background": "#fbf9f8",
                        "primary-fixed-dim": "#b1c5ff",
                        "on-secondary": "#ffffff",
                        "surface": "#fbf9f8",
                        "on-surface-variant": "#434653",
                        "on-primary-fixed-variant": "#00419f",
                        "surface-container": "#f0eded",
                        "on-error": "#ffffff",
                        "inverse-surface": "#303030",
                        "inverse-primary": "#b1c5ff",
                        "tertiary-fixed-dim": "#ffb4a7",
                        "primary": "#003b93",
                        "tertiary-container": "#b20f03",
                        "primary-fixed": "#dae2ff",
                        "inverse-on-surface": "#f3f0f0"
                    },
                    fontFamily: {
                        "headline": ["Inter", "sans-serif"],
                        "body": ["Inter", "sans-serif"],
                        "label": ["Inter", "sans-serif"],
                        "mono": ["JetBrains Mono", "monospace"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .glass-panel {
            background: rgba(251, 249, 248, 0.7);
            backdrop-filter: blur(12px);
        }
        .brand-gradient {
            background: linear-gradient(135deg, #003b93 0%, #0051c3 100%);
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #eae8e7; border-radius: 10px; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface">
    <?php include 'sidebar.php'; ?>

    <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-10 lg:pt-10 space-y-10">
        <!-- Header Section -->
        <section class="space-y-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-on-surface"><?php echo htmlspecialchars($student['name']); ?></h1>
                    <p class="text-on-surface-variant font-medium">Student Details • <?php echo htmlspecialchars($student['class_name']); ?></p>
                    <div class="flex items-center gap-4 mt-2">
                        <span class="text-sm text-on-surface-variant">Roll No: <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></span>
                        <span class="text-sm text-on-surface-variant">•</span>
                        <span class="text-sm text-on-surface-variant">Grade: <?php
                            if ($avg_grade >= 90) echo 'A';
                            elseif ($avg_grade >= 80) echo 'B';
                            elseif ($avg_grade >= 70) echo 'C';
                            elseif ($avg_grade >= 60) echo 'D';
                            else echo 'F';
                        ?></span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button onclick="exportReport()" class="bg-surface-container-lowest border border-outline text-on-surface px-4 py-2 rounded-md font-medium hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-sm mr-2">download</span>
                        Export Report
                    </button>
                    <a href="students.php" class="bg-surface-container-lowest border border-outline text-on-surface px-4 py-2 rounded-md font-medium hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-sm mr-2">arrow_back</span>
                        Back to Students
                    </a>
                </div>
            </div>
        </section>

        <!-- Statistics Cards -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="glass-panel p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-on-surface-variant text-sm font-medium">Attendance</p>
                        <p class="text-2xl font-bold text-on-surface"><?php echo $attendance_percentage; ?>%</p>
                    </div>
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">calendar_today</span>
                    </div>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-on-surface-variant text-sm font-medium">Average Score</p>
                        <p class="text-2xl font-bold text-on-surface"><?php echo $avg_grade; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-secondary">grade</span>
                    </div>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-on-surface-variant text-sm font-medium">Total Exams</p>
                        <p class="text-2xl font-bold text-on-surface"><?php echo $total_subjects; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-tertiary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-tertiary">assessment</span>
                    </div>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-on-surface-variant text-sm font-medium">Assignments</p>
                        <p class="text-2xl font-bold text-on-surface"><?php echo count($recent_assignments); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">assignment</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Personal Information -->
        <section class="glass-panel p-6 rounded-xl">
            <h3 class="text-xl font-semibold text-on-surface mb-6">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Full Name</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['name']); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Roll Number</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Class</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['class_name']); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Email</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Phone</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Date of Birth</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['date_of_birth'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Gender</span>
                        <span class="text-on-surface"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-outline-variant">
                        <span class="font-medium text-on-surface">Address</span>
                        <span class="text-on-surface max-w-xs truncate"><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Grade Distribution Summary -->
        <?php if ($total_subjects > 0): ?>
        <section class="glass-panel p-6 rounded-xl">
            <h3 class="text-xl font-semibold text-on-surface mb-6">Grade Distribution</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <?php foreach ($grade_distribution as $gradeLabel => $gradeCount): ?>
                    <?php
                        $gradePercent = $total_subjects > 0 ? round(($gradeCount / $total_subjects) * 100, 1) : 0;
                    ?>
                    <div class="bg-white border border-outline-variant rounded-lg p-3 text-center">
                        <p class="text-sm text-on-surface-variant">Grade <?php echo htmlspecialchars((string) $gradeLabel); ?></p>
                        <p class="text-xl font-bold text-on-surface"><?php echo (int) $gradeCount; ?></p>
                        <p class="text-xs text-on-surface-variant"><?php echo $gradePercent; ?>%</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Academic Performance -->
        <section class="glass-panel p-6 rounded-xl">
            <h3 class="text-xl font-semibold text-on-surface mb-6">Academic Performance</h3>
            <div class="overflow-x-auto">
                <?php if (count($grades) > 0): ?>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="text-left py-3 px-4 text-on-surface font-semibold">Subject</th>
                            <th class="text-left py-3 px-4 text-on-surface font-semibold">Exam Type</th>
                            <th class="text-left py-3 px-4 text-on-surface font-semibold">Score</th>
                            <th class="text-left py-3 px-4 text-on-surface font-semibold">Grade</th>
                            <th class="text-left py-3 px-4 text-on-surface font-semibold">Percentage</th>
                            <th class="text-left py-3 px-4 text-on-surface font-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                        <span class="material-symbols-outlined text-primary text-sm">subject</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-on-surface"><?php echo htmlspecialchars($grade['subject_name']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-on-surface"><?php echo htmlspecialchars($grade['exam_type']); ?></td>
                            <td class="py-4 px-4 font-semibold text-on-surface">
                                <?php echo htmlspecialchars($grade['obtained_marks']); ?>/<?php echo htmlspecialchars($grade['total_marks']); ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php
                                $percentage = ($grade['obtained_marks'] / $grade['total_marks']) * 100;
                                $grade_letter = '';
                                if ($percentage >= 90) $grade_letter = 'A';
                                elseif ($percentage >= 80) $grade_letter = 'B';
                                elseif ($percentage >= 70) $grade_letter = 'C';
                                elseif ($percentage >= 60) $grade_letter = 'D';
                                else $grade_letter = 'F';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    <?php
                                    if ($percentage >= 90) echo 'bg-tertiary/10 text-tertiary';
                                    elseif ($percentage >= 80) echo 'bg-secondary/10 text-secondary';
                                    elseif ($percentage >= 70) echo 'bg-primary/10 text-primary';
                                    elseif ($percentage >= 60) echo 'bg-error-container text-on-error-container';
                                    else echo 'bg-error-container text-on-error-container';
                                    ?>">
                                    <?php echo $grade_letter; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 font-semibold text-on-surface">
                                <?php echo round($percentage, 1); ?>%
                            </td>
                            <td class="py-4 px-4 text-on-surface max-w-xs truncate">
                                <?php echo htmlspecialchars($grade['remarks'] ?? 'No remarks'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-on-surface-variant text-2xl">assessment</span>
                    </div>
                    <h3 class="text-lg font-semibold text-on-surface mb-2">No grades available</h3>
                    <p class="text-on-surface-variant">This student hasn't taken any exams yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        function exportReport() {
            alert('Student report export feature coming soon in Pro Edition!');
        }
    </script>
    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>
</body>
</html>
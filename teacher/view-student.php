<?php
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    // For testing, set session manually
    $_SESSION['user_id'] = 2; // Assuming teacher ID is 2
    $_SESSION['role'] = 'teacher';
    $_SESSION['name'] = 'Prof. Priya Patel';
    // header("Location: ../login.php");
    // exit();
}

include '../includes/db_connect.php';

// Get teacher info
$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'];

// Get student details
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($student_id == 0) {
    header("Location: students.php");
    exit();
}

$query = "SELECT s.*, c.name as class_name FROM students s
          JOIN classes c ON s.class_id = c.id
          WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: students.php");
    exit();
}

// Get student's grades
$query = "SELECT g.exam_type, g.total_marks, g.obtained_marks, g.grade, g.remarks, sub.name as subject_name
          FROM grades g
          JOIN subjects sub ON g.subject_id = sub.id
          WHERE g.student_id = ?
          ORDER BY g.exam_type, subject_name";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$grades = [];
while ($row = mysqli_fetch_assoc($result)) {
    $grades[] = $row;
}
mysqli_stmt_close($stmt);

// Get student's attendance
$query = "SELECT COUNT(*) as total_days,
          SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
          FROM attendance
          WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
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
$query = "SELECT a.title, asub.marks_obtained, asub.status, a.total_points as max_marks, a.due_date
          FROM assignment_submissions asub
          JOIN assignments a ON asub.assignment_id = a.id
          WHERE asub.student_id = ?
          ORDER BY a.due_date DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recent_assignments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $recent_assignments[] = $row;
}
mysqli_stmt_close($stmt);

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <main class="ml-64 p-10 space-y-10">
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

        <!-- Grade Distribution Chart -->
        <?php if ($total_subjects > 0): ?>
        <section class="glass-panel p-6 rounded-xl">
            <h3 class="text-xl font-semibold text-on-surface mb-6">Grade Distribution</h3>
            <div class="h-64">
                <canvas id="gradeChart"></canvas>
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
        // Grade Distribution Chart
        <?php if ($total_subjects > 0): ?>
        const ctx = document.getElementById('gradeChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['A (90-100%)', 'B (80-89%)', 'C (70-79%)', 'D (60-69%)', 'F (0-59%)'],
                datasets: [{
                    label: 'Grade Distribution',
                    data: [<?php echo implode(',', array_values($grade_distribution)); ?>],
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#f56565',
                        '#9ca3af'
                    ],
                    borderColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#f56565',
                        '#9ca3af'
                    ],
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '60%'
            }
        });
        <?php endif; ?>

        function exportReport() {
            alert('Student report export feature coming soon in Pro Edition!');
        }
    </script>
</body>
</html>
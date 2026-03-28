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

// Include database connection
include '../includes/db_connect.php';

// Get teacher info
$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'];

// Get dashboard statistics with error handling
$stats = [
    'total_students' => 0,
    'total_assignments' => 0,
    'graded_submissions' => 0,
    'today_attendance' => 0
];

try {
    // Get total students
    $sql_students = "SELECT COUNT(*) as count FROM students WHERE class_id IN (SELECT id FROM classes WHERE teacher_id = ?)";
    $stmt = mysqli_prepare($conn, $sql_students);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['total_students'] = $row['count'] ?? 0;
    mysqli_stmt_close($stmt);

    // Get total assignments
    $sql_assignments = "SELECT COUNT(*) as count FROM assignments WHERE teacher_id = ?";
    $stmt = mysqli_prepare($conn, $sql_assignments);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['total_assignments'] = $row['count'] ?? 0;
    mysqli_stmt_close($stmt);

    // Get graded submissions
    $sql_graded = "SELECT COUNT(*) as count FROM assignment_submissions WHERE assignment_id IN (SELECT id FROM assignments WHERE teacher_id = ?) AND status = 'graded'";
    $stmt = mysqli_prepare($conn, $sql_graded);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['graded_submissions'] = $row['count'] ?? 0;
    mysqli_stmt_close($stmt);

    // Get today's attendance
    $sql_attendance = "SELECT COUNT(*) as count FROM attendance WHERE teacher_id = ? AND DATE(date) = CURDATE()";
    $stmt = mysqli_prepare($conn, $sql_attendance);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['today_attendance'] = $row['count'] ?? 0;
    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    // If queries fail, use default values
    error_log("Dashboard query error: " . $e->getMessage());
}

// Get recent assignments with error handling
$recent_assignments = [];
try {
    $sql_recent = "SELECT a.title, a.due_date, COUNT(sub.id) as submissions
                   FROM assignments a
                   LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id
                   WHERE a.teacher_id = ?
                   GROUP BY a.id
                   ORDER BY a.created_at DESC LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql_recent);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_assignments[] = $row;
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    // If query fails, use empty array
    error_log("Recent assignments query error: " . $e->getMessage());
}

// Get attendance trend (last 7 days) with error handling
$attendance_trend = [];
try {
    $sql_trend = "SELECT DATE(date) as attendance_date, COUNT(*) as count
                  FROM attendance
                  WHERE teacher_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  GROUP BY DATE(date)
                  ORDER BY date";
    $stmt = mysqli_prepare($conn, $sql_trend);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $attendance_trend[] = $row;
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    // If query fails, use empty array
    error_log("Attendance trend query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - School ERP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
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
    }
    .glass-panel {
      background: rgba(251, 249, 248, 0.7);
      backdrop-filter: blur(12px);
    }
    .pro-shadow {
      box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
    }
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-10 space-y-10">
    <!-- Header Section -->
    <section class="space-y-6">
      <div class="flex justify-between items-end">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Teacher Dashboard</h1>
          <p class="text-on-surface-variant font-medium">Welcome back, <?php echo htmlspecialchars($teacher_name); ?>! Here's your academic overview.</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="glass-panel rounded-xl p-4 pro-shadow">
            <div class="text-sm text-on-surface-variant">Last Updated</div>
            <div class="text-lg font-semibold text-on-surface"><?php echo date('M j, Y H:i'); ?></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Cards -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">people</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['total_students']; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Total Students</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-primary h-2 rounded-full" style="width: 100%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">assignment</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['total_assignments']; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Assignments</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $stats['total_assignments'] > 0 ? min(100, ($stats['total_assignments'] / 10) * 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">grade</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['graded_submissions']; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Graded</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $stats['total_assignments'] > 0 ? min(100, ($stats['graded_submissions'] / ($stats['total_assignments'] * $stats['total_students'])) * 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">check_circle</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['today_attendance']; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Today's Attendance</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $stats['total_students'] > 0 ? min(100, ($stats['today_attendance'] / $stats['total_students']) * 100) : 0; ?>%"></div>
        </div>
      </div>
    </section>

    <!-- Quick Actions -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <a href="assignments.php" class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">assignment_add</span>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-on-surface">Create Assignment</h3>
            <p class="text-on-surface-variant">Add new assignments for your classes</p>
          </div>
        </div>
      </a>

      <a href="attendance.php" class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600">fact_check</span>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-on-surface">Take Attendance</h3>
            <p class="text-on-surface-variant">Mark student attendance</p>
          </div>
        </div>
      </a>

      <a href="students.php" class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600">school</span>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-on-surface">View Students</h3>
            <p class="text-on-surface-variant">Manage student information</p>
          </div>
        </div>
      </a>
    </section>

    <!-- Recent Assignments Table -->
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-5 border-b border-outline-variant">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">assignment</span>
          </div>
          <div>
            <h3 class="text-xl font-bold text-on-surface">Recent Assignments</h3>
            <p class="text-on-surface-variant">Your latest assignment activities</p>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-surface-variant/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-on-surface-variant uppercase tracking-widest">Assignment</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-on-surface-variant uppercase tracking-widest">Due Date</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-on-surface-variant uppercase tracking-widest">Submissions</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant">
            <?php if (count($recent_assignments) > 0): ?>
              <?php foreach ($recent_assignments as $assignment): ?>
              <tr class="hover:bg-surface-variant/30">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                      <span class="material-symbols-outlined text-primary text-sm">assignment</span>
                    </div>
                    <div>
                      <div class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($assignment['title']); ?></div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-on-surface">
                  <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                </td>
                <td class="px-6 py-4 text-sm text-on-surface">
                  <?php echo $assignment['submissions']; ?> submissions
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    Active
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
            <tr>
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="w-16 h-16 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
                  <span class="material-symbols-outlined text-on-surface-variant text-2xl">assignment</span>
                </div>
                <h3 class="text-lg font-semibold text-on-surface mb-2">No assignments yet</h3>
                <p class="text-on-surface-variant">Create your first assignment to get started.</p>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>


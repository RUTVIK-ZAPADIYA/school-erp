<?php
require_once __DIR__ . '/auth.php';

// Include database connection
include '../includes/db_connect.php';

// Resolve teacher identity across users.id and teachers.id.
$teacherContext = teacher_auth_resolve_context($conn);
$user_id = (int) ($teacherContext['user_id'] ?? 0);
$teacher_profile_id = (int) ($teacherContext['teacher_profile_id'] ?? 0);
$teacher_ids = teacher_auth_sanitize_ids((array) ($teacherContext['teacher_ids'] ?? []));
if (empty($teacher_ids)) {
  $teacher_ids = [0];
}
$teacher_ids_sql = implode(',', $teacher_ids);
$teacher_id_placeholders = implode(',', array_fill(0, count($teacher_ids), '?'));
$teacher_id_types = str_repeat('i', count($teacher_ids));

$account_table = null;
$account_id = 0;
$user = null;

$users_table_check = $conn->query( "SHOW TABLES LIKE 'users'");
if ($users_table_check && $users_table_check->num_rows > 0 && $user_id > 0) {
  $userStmt = $conn->prepare( 'SELECT * FROM users WHERE id = ? LIMIT 1');
  if ($userStmt) {
    $userStmt->bind_param( 'i', $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult ? $userResult->fetch_assoc() : null;
    $userStmt->close();
    if ($user) {
      $account_table = 'users';
      $account_id = $user_id;
    }
  }
}

$teachers_table_check = $conn->query( "SHOW TABLES LIKE 'teachers'");
if (!$user && $teachers_table_check && $teachers_table_check->num_rows > 0) {
  if ($teacher_profile_id > 0) {
    $teacherStmt = $conn->prepare( 'SELECT * FROM teachers WHERE id = ? LIMIT 1');
    if ($teacherStmt) {
      $teacherStmt->bind_param( 'i', $teacher_profile_id);
      $teacherStmt->execute();
      $teacherResult = $teacherStmt->get_result();
      $user = $teacherResult ? $teacherResult->fetch_assoc() : null;
      $teacherStmt->close();
    }
  }

  if (!$user && $user_id > 0) {
    $teacherUserColumnCheck = $conn->query( "SHOW COLUMNS FROM `teachers` LIKE 'user_id'");
    if ($teacherUserColumnCheck && $teacherUserColumnCheck->num_rows > 0) {
      $teacherByUserStmt = $conn->prepare( 'SELECT * FROM teachers WHERE user_id = ? LIMIT 1');
      if ($teacherByUserStmt) {
        $teacherByUserStmt->bind_param( 'i', $user_id);
        $teacherByUserStmt->execute();
        $teacherByUserResult = $teacherByUserStmt->get_result();
        $user = $teacherByUserResult ? $teacherByUserResult->fetch_assoc() : null;
        $teacherByUserStmt->close();
      }
    }
  }

  if ($user) {
    $account_table = 'teachers';
    $account_id = (int) ($user['id'] ?? 0);
  }
}

if (!$user) {
  $error = $account_table === null
    ? "Account table not found. Run setup/create script to create users or teachers table."
    : "Teacher profile not found for the current account.";
  $user = [
    'name' => ($_SESSION['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher'),
    'email' => '',
    'phone' => ''
  ];
}

function table_exists($conn, $table_name) {
  $table_name = $conn->real_escape_string( $table_name);
  $result = $conn->query( "SHOW TABLES LIKE '$table_name'");
  return $result && $result->num_rows > 0;
}

function column_exists($conn, $table_name, $column_name) {
  if (!table_exists($conn, $table_name)) {
    return false;
  }

  $table_name = $conn->real_escape_string( $table_name);
  $column_name = $conn->real_escape_string( $column_name);
  $result = $conn->query( "SHOW COLUMNS FROM `$table_name` LIKE '$column_name'");
  return $result && $result->num_rows > 0;
}

function profile_bind_dynamic_params($stmt, $types, array &$params) {
  if ($types === '') {
    return true;
  }

  $bindArgs = [$types];
  foreach ($params as $index => &$value) {
    $bindArgs[] = &$value;
  }

  return call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));

  if ($account_table === null || $account_id <= 0) {
    $error = "Cannot update profile because account table is missing.";
  } elseif (!in_array($account_table, ['users', 'teachers'], true)) {
    $error = "Cannot update profile because account table is invalid.";
  } else {
    $updateStmt = $conn->prepare("UPDATE `{$account_table}` SET name = ?, email = ?, phone = ? WHERE id = ?");
    if (!$updateStmt) {
      $error = "Error preparing profile update: " . $conn->error;
    } else {
      $updateStmt->bind_param('sssi', $name, $email, $phone, $account_id);

      if ($updateStmt->execute()) {
        $success = "Profile updated successfully!";
        // Update session
        $_SESSION['name'] = $name;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
      } else {
        $error = "Error updating profile: " . $updateStmt->error;
      }

      $updateStmt->close();
    }
  }
}

// Get teacher statistics with table-safe queries to avoid runtime errors.
$stats = [
  'total_classes' => 0,
  'total_students' => 0,
  'total_assignments' => 0,
  'avg_class_performance' => null,
  'total_present_days' => 0,
  'total_absent_days' => 0,
];

if (table_exists($conn, 'classes') && column_exists($conn, 'classes', 'teacher_id')) {
  $classesCountSql = "SELECT COUNT(*) AS total_classes FROM classes WHERE teacher_id IN ({$teacher_id_placeholders})";
  $classesCountStmt = $conn->prepare($classesCountSql);
  if ($classesCountStmt) {
    $classesCountParams = $teacher_ids;
    if (profile_bind_dynamic_params($classesCountStmt, $teacher_id_types, $classesCountParams) && $classesCountStmt->execute()) {
      $classes_count_result = $classesCountStmt->get_result();
      if ($classes_count_result) {
        $row = $classes_count_result->fetch_assoc();
        $stats['total_classes'] = (int)($row['total_classes'] ?? 0);
      }
    }
    $classesCountStmt->close();
  }
}

if (
  table_exists($conn, 'students')
  && table_exists($conn, 'classes')
  && column_exists($conn, 'classes', 'teacher_id')
  && (column_exists($conn, 'students', 'class_id') || column_exists($conn, 'students', 'class'))
) {
  $studentJoinParts = [];
  if (column_exists($conn, 'students', 'class_id')) {
    $studentJoinParts[] = 's.class_id = c.id';
  }
  if (column_exists($conn, 'students', 'class')) {
    $studentClassNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(s.`class`, '')), ' ', ''), '-', ''))";
    $classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
    $classNameNorm = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', ''))";
    $studentJoinParts[] = "({$studentClassNorm} <> '' AND {$studentClassNorm} = {$classNameNorm})";
  }

  $studentJoinSql = empty($studentJoinParts) ? '1 = 0' : implode(' OR ', $studentJoinParts);
  $studentsCountSql = "SELECT COUNT(DISTINCT s.id) AS total_students FROM students s INNER JOIN classes c ON ({$studentJoinSql}) WHERE c.teacher_id IN ({$teacher_id_placeholders})";
  $studentsCountStmt = $conn->prepare($studentsCountSql);
  if ($studentsCountStmt) {
    $studentsCountParams = $teacher_ids;
    if (profile_bind_dynamic_params($studentsCountStmt, $teacher_id_types, $studentsCountParams) && $studentsCountStmt->execute()) {
      $students_count_result = $studentsCountStmt->get_result();
      if ($students_count_result) {
        $row = $students_count_result->fetch_assoc();
        $stats['total_students'] = (int)($row['total_students'] ?? 0);
      }
    }
    $studentsCountStmt->close();
  }
}

if (table_exists($conn, 'assignments') && column_exists($conn, 'assignments', 'teacher_id')) {
  $assignmentsCountSql = "SELECT COUNT(*) AS total_assignments FROM assignments WHERE teacher_id IN ({$teacher_id_placeholders})";
  $assignmentsCountStmt = $conn->prepare($assignmentsCountSql);
  if ($assignmentsCountStmt) {
    $assignmentsCountParams = $teacher_ids;
    if (profile_bind_dynamic_params($assignmentsCountStmt, $teacher_id_types, $assignmentsCountParams) && $assignmentsCountStmt->execute()) {
      $assignments_count_result = $assignmentsCountStmt->get_result();
      if ($assignments_count_result) {
        $row = $assignments_count_result->fetch_assoc();
        $stats['total_assignments'] = (int)($row['total_assignments'] ?? 0);
      }
    }
    $assignmentsCountStmt->close();
  }
}

if (table_exists($conn, 'marks') && column_exists($conn, 'marks', 'teacher_id') && column_exists($conn, 'marks', 'marks')) {
  $avgMarksSql = "SELECT AVG(marks) AS avg_class_performance FROM marks WHERE teacher_id IN ({$teacher_id_placeholders})";
  $avgMarksStmt = $conn->prepare($avgMarksSql);
  if ($avgMarksStmt) {
    $avgMarksParams = $teacher_ids;
    if (profile_bind_dynamic_params($avgMarksStmt, $teacher_id_types, $avgMarksParams) && $avgMarksStmt->execute()) {
      $avg_marks_result = $avgMarksStmt->get_result();
      if ($avg_marks_result) {
        $row = $avg_marks_result->fetch_assoc();
        $stats['avg_class_performance'] = $row['avg_class_performance'];
      }
    }
    $avgMarksStmt->close();
  }
}

if (table_exists($conn, 'attendance') && column_exists($conn, 'attendance', 'teacher_id') && column_exists($conn, 'attendance', 'status')) {
  $attendanceSql = "SELECT
      SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS total_present_days,
      SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS total_absent_days
      FROM attendance
      WHERE teacher_id IN ({$teacher_id_placeholders})";
  $attendanceStmt = $conn->prepare($attendanceSql);
  if ($attendanceStmt) {
    $attendanceParams = $teacher_ids;
    if (profile_bind_dynamic_params($attendanceStmt, $teacher_id_types, $attendanceParams) && $attendanceStmt->execute()) {
      $attendance_result = $attendanceStmt->get_result();
      if ($attendance_result) {
        $row = $attendance_result->fetch_assoc();
        $stats['total_present_days'] = (int)($row['total_present_days'] ?? 0);
        $stats['total_absent_days'] = (int)($row['total_absent_days'] ?? 0);
      }
    }
    $attendanceStmt->close();
  }
}

// Calculate additional metrics
$attendance_rate = 0;
if (($stats['total_present_days'] + $stats['total_absent_days']) > 0) {
    $attendance_rate = round(($stats['total_present_days'] / ($stats['total_present_days'] + $stats['total_absent_days'])) * 100, 1);
}

// Get recent activities
$result_recent = false;
$recent_query_parts = [];
$recent_types = '';
$recent_params = [];

if (table_exists($conn, 'assignments') && column_exists($conn, 'assignments', 'teacher_id') && column_exists($conn, 'assignments', 'title') && column_exists($conn, 'assignments', 'created_at')) {
  $recent_query_parts[] = "SELECT 'assignment' as type, title as description, created_at as date FROM assignments WHERE teacher_id IN ({$teacher_id_placeholders})";
  $recent_types .= $teacher_id_types;
  $recent_params = array_merge($recent_params, $teacher_ids);
}

if (table_exists($conn, 'marks') && column_exists($conn, 'marks', 'teacher_id') && column_exists($conn, 'marks', 'date') && column_exists($conn, 'marks', 'student_id') && table_exists($conn, 'students')) {
  $student_name_expr = column_exists($conn, 'students', 'name')
    ? "(SELECT name FROM students WHERE id = marks.student_id)"
    : "marks.student_id";
  $recent_query_parts[] = "SELECT 'grade' as type, CONCAT('Graded ', $student_name_expr) as description, date as date FROM marks WHERE teacher_id IN ({$teacher_id_placeholders})";
  $recent_types .= $teacher_id_types;
  $recent_params = array_merge($recent_params, $teacher_ids);
}

if (!empty($recent_query_parts)) {
  $sql_recent = implode(" UNION ALL ", $recent_query_parts) . " ORDER BY date DESC LIMIT 5";
  $recentStmt = $conn->prepare($sql_recent);
  if ($recentStmt) {
    if (profile_bind_dynamic_params($recentStmt, $recent_types, $recent_params) && $recentStmt->execute()) {
      $result_recent = $recentStmt->get_result();
    }
    $recentStmt->close();
  }
}

// Get monthly performance trend (last 6 months)
$result_trend = false;
if (table_exists($conn, 'marks') && column_exists($conn, 'marks', 'teacher_id') && column_exists($conn, 'marks', 'date') && column_exists($conn, 'marks', 'marks')) {
  $sql_trend = "SELECT
      DATE_FORMAT(date, '%Y-%m') as month,
      AVG(marks) as avg_performance,
      COUNT(*) as total_grades
      FROM marks
      WHERE teacher_id IN ({$teacher_id_placeholders}) AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
      GROUP BY DATE_FORMAT(date, '%Y-%m')
      ORDER BY month";
  $trendStmt = $conn->prepare($sql_trend);
  if ($trendStmt) {
    $trendParams = $teacher_ids;
    if (profile_bind_dynamic_params($trendStmt, $teacher_id_types, $trendParams) && $trendStmt->execute()) {
      $result_trend = $trendStmt->get_result();
    }
    $trendStmt->close();
  }
}

$performance_trend = [];
if ($result_trend) {
  while ($row = $result_trend->fetch_assoc()) {
    $performance_trend[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - The Academic Editorial</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
  </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-10 space-y-10">
    <!-- Header Section -->
    <section class="space-y-6">
      <div class="flex justify-between items-end">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">My Profile</h1>
          <p class="text-on-surface-variant font-medium">Manage your personal information and view your teaching statistics</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="glass-panel rounded-xl p-4 pro-shadow">
            <div class="text-sm text-on-surface-variant">Last Updated</div>
            <div class="text-lg font-semibold text-on-surface"><?php echo date('M j, Y'); ?></div>
          </div>
          <button onclick="openEditModal()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">edit</span>
            Edit Profile
          </button>
        </div>
      </div>
    </section>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
    <div class="glass-panel p-4 rounded-xl pro-shadow border-l-4 border-green-500">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-green-600">check_circle</span>
        <p class="text-sm font-semibold text-green-800"><?php echo $success; ?></p>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="glass-panel p-4 rounded-xl pro-shadow border-l-4 border-red-500">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-red-600">error</span>
        <p class="text-sm font-semibold text-red-800"><?php echo $error; ?></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <section class="glass-panel rounded-xl p-8 pro-shadow">
      <div class="flex items-center gap-8">
        <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center text-white text-3xl font-bold">
          <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-3xl font-bold text-on-surface"><?php echo htmlspecialchars($user['name']); ?></h1>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              <span class="material-symbols-outlined text-xs">verified</span>
              Active Teacher
            </span>
          </div>
          <p class="text-on-surface-variant text-lg mb-3">Teacher ID: TCH<?php echo str_pad($user_id, 4, '0', STR_PAD_LEFT); ?></p>
          <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">school</span>
              <span class="text-sm text-on-surface-variant">Mathematics Department</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">calendar_today</span>
              <span class="text-sm text-on-surface-variant">Joined August 2012</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">grade</span>
              <span class="text-sm text-on-surface-variant">12 Years Experience</span>
            </div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-2xl font-bold text-primary mb-1"><?php echo $stats['avg_class_performance'] ? round($stats['avg_class_performance'], 1) . '%' : 'N/A'; ?></div>
          <div class="text-sm text-on-surface-variant">Avg Class Performance</div>
        </div>
      </div>
    </section>

    <!-- Analytics Cards -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">school</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['total_classes'] ?? 0; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Total Classes</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-primary h-2 rounded-full" style="width: 100%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600">people</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['total_students'] ?? 0; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Total Students</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $stats['total_students'] > 0 ? min(($stats['total_students'] / 200) * 100, 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600">assignment</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $stats['total_assignments'] ?? 0; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Assignments</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $stats['total_assignments'] > 0 ? min(($stats['total_assignments'] / 50) * 100, 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-purple-600">trending_up</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $attendance_rate; ?>%</p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Attendance Rate</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $attendance_rate; ?>%"></div>
        </div>
      </div>
    </section>

    <!-- Performance Summary and Recent Activity -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Performance Trend Summary -->
      <div class="glass-panel rounded-xl pro-shadow">
        <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center gap-3 bg-stone-50/30">
          <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">analytics</span>
          </div>
          <div>
            <h2 class="text-xl font-bold text-on-surface">Performance Trend</h2>
            <p class="text-on-surface-variant">Last 6 months class performance</p>
          </div>
        </div>
        <div class="p-6">
          <?php if (!empty($performance_trend)): ?>
            <div class="space-y-3">
              <?php foreach ($performance_trend as $trend): ?>
                <?php
                  $monthLabel = isset($trend['month']) ? date('M Y', strtotime((string) $trend['month'] . '-01')) : 'N/A';
                  $avgPerformance = isset($trend['avg_performance']) ? round((float) $trend['avg_performance'], 1) : 0;
                  $totalGrades = isset($trend['total_grades']) ? (int) $trend['total_grades'] : 0;
                ?>
                <div class="flex items-center justify-between py-2 border-b border-outline-variant/10">
                  <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($monthLabel); ?></span>
                  <span class="text-sm text-on-surface-variant"><?php echo $avgPerformance; ?>% across <?php echo $totalGrades; ?> grades</span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-sm text-on-surface-variant">No performance records are available for the last 6 months.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="glass-panel rounded-xl pro-shadow">
        <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center gap-3 bg-stone-50/30">
          <div class="w-10 h-10 bg-tertiary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-tertiary">history</span>
          </div>
          <div>
            <h2 class="text-xl font-bold text-on-surface">Recent Activity</h2>
            <p class="text-on-surface-variant">Your latest teaching activities</p>
          </div>
        </div>
        <div class="divide-y divide-outline-variant/10">
          <?php if ($result_recent && $result_recent->num_rows > 0): ?>
            <?php while ($activity = $result_recent->fetch_assoc()): ?>
            <div class="px-6 py-4">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-<?php echo $activity['type'] === 'assignment' ? 'blue' : 'green'; ?>-100 rounded-xl flex items-center justify-center">
                  <span class="material-symbols-outlined text-<?php echo $activity['type'] === 'assignment' ? 'blue' : 'green'; ?>-600 text-sm">
                    <?php echo $activity['type'] === 'assignment' ? 'assignment' : 'grade'; ?>
                  </span>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($activity['description']); ?></p>
                  <p class="text-xs text-on-surface-variant"><?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?></p>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="px-6 py-8 text-center">
              <div class="w-12 h-12 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-on-surface-variant">history</span>
              </div>
              <p class="text-sm text-on-surface-variant">No recent activities</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Profile Information -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Personal Information -->
      <div class="glass-panel rounded-xl pro-shadow">
        <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center gap-3 bg-stone-50/30">
          <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">person</span>
          </div>
          <h2 class="text-xl font-bold text-on-surface">Personal Information</h2>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Full Name</span>
            <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($user['name']); ?></span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Email</span>
            <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($user['email']); ?></span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Phone Number</span>
            <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Role</span>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
              <span class="material-symbols-outlined text-xs">school</span>
              Teacher
            </span>
          </div>
          <div class="flex justify-between items-center py-3">
            <span class="text-sm font-medium text-on-surface-variant">Status</span>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              <span class="material-symbols-outlined text-xs">check_circle</span>
              Active
            </span>
          </div>
        </div>
      </div>

      <!-- Professional Information -->
      <div class="glass-panel rounded-xl pro-shadow">
        <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center gap-3 bg-stone-50/30">
          <div class="w-10 h-10 bg-tertiary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-tertiary">work</span>
          </div>
          <h2 class="text-xl font-bold text-on-surface">Professional Information</h2>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Department</span>
            <span class="text-sm font-semibold text-on-surface">Mathematics</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Qualification</span>
            <span class="text-sm font-semibold text-on-surface">M.Sc. Mathematics, B.Ed.</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Experience</span>
            <span class="text-sm font-semibold text-on-surface">12 Years</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-outline-variant/10">
            <span class="text-sm font-medium text-on-surface-variant">Joining Date</span>
            <span class="text-sm font-semibold text-on-surface">August 15, 2012</span>
          </div>
          <div class="flex justify-between items-center py-3">
            <span class="text-sm font-medium text-on-surface-variant">Subjects</span>
            <span class="text-sm font-semibold text-on-surface">Mathematics, Advanced Mathematics</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Account Settings -->
    <section class="glass-panel rounded-xl pro-shadow">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center gap-3 bg-stone-50/30">
        <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-primary">settings</span>
        </div>
        <h2 class="text-xl font-bold text-on-surface">Account Settings</h2>
      </div>
      <div class="p-6">
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600">notifications</span>
              </div>
              <div>
                <h3 class="text-sm font-semibold text-on-surface">Email Notifications</h3>
                <p class="text-sm text-on-surface-variant">Receive notifications about assignments and grades</p>
              </div>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="email_notifications" class="h-4 w-4 text-primary focus:ring-primary border-outline-variant rounded">
              <label for="email_notifications" class="ml-2 text-sm text-on-surface">Enabled</label>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600">security</span>
              </div>
              <div>
                <h3 class="text-sm font-semibold text-on-surface">Two-Factor Authentication</h3>
                <p class="text-sm text-on-surface-variant">Add an extra layer of security to your account</p>
              </div>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="two_factor" class="h-4 w-4 text-primary focus:ring-primary border-outline-variant rounded">
              <label for="two_factor" class="ml-2 text-sm text-on-surface">Disabled</label>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Edit Profile Modal -->
  <div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 pointer-events-none">
    <div class="glass-panel rounded-2xl p-8 w-full max-w-md mx-4 pro-shadow">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-on-surface">Edit Profile</h3>
        <button onclick="closeEditModal()" class="w-8 h-8 bg-surface-variant rounded-full flex items-center justify-center text-on-surface-variant hover:bg-outline-variant">
          <span class="material-symbols-outlined text-sm">close</span>
        </button>
      </div>

      <form method="POST" class="space-y-6" novalidate>
        <div>
          <label class="block text-sm font-semibold text-on-surface mb-2">Full Name</label>
          <input type="text" name="name" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Enter your full name" data-validation="required,alphabetic,min,max" data-min="3" data-max="80">
          <p id="name_error" class="text-sm text-red-600 hidden"></p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-on-surface mb-2">Email Address</label>
          <input type="email" name="email" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus-border-primary" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Enter your email" data-validation="required,email">
          <p id="email_error" class="text-sm text-red-600 hidden"></p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-on-surface mb-2">Phone Number</label>
          <input type="text" name="phone" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus-border-primary" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number" data-validation="required,number,min,max" data-min="7" data-max="15">
          <p id="phone_error" class="text-sm text-red-600 hidden"></p>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 border border-outline-variant rounded-xl text-sm font-medium text-on-surface hover:bg-surface-variant">
            Cancel
          </button>
          <button type="submit" name="update_profile" class="px-6 py-2.5 bg-primary rounded-xl text-sm font-medium text-white pro-shadow">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
  <script>
    function openEditModal() {
      const modal = document.getElementById('editModal');
      modal.classList.remove('opacity-0', 'pointer-events-none');
      modal.classList.add('opacity-100', 'pointer-events-auto');
    }

    function closeEditModal() {
      const modal = document.getElementById('editModal');
      modal.classList.add('opacity-0', 'pointer-events-none');
      modal.classList.remove('opacity-100', 'pointer-events-auto');
    }

    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(event) {
      if (event.target === this) {
        closeEditModal();
      }
    });
  </script>
</body>
</html>
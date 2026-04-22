<?php
require_once __DIR__ . '/auth.php';

include '../includes/db_connect.php';

$teacherContext = teacher_auth_resolve_context($conn);
$teacherUserId = (int) ($teacherContext['user_id'] ?? 0);
$teacherIds = teacher_auth_sanitize_ids((array) ($teacherContext['teacher_ids'] ?? [$teacherUserId]));
if (empty($teacherIds)) {
  $teacherIds = [0];
}
$teacherIdSql = implode(',', $teacherIds);
$teacherIdPlaceholders = implode(',', array_fill(0, count($teacherIds), '?'));
$teacherIdTypes = str_repeat('i', count($teacherIds));
$teacherName = (string) ($teacherContext['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher');

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

function teacher_bind_dynamic_params($stmt, $types, array &$params)
{
  if ($types === '') {
    return true;
  }

  $bindArgs = [$types];
  foreach ($params as $index => &$value) {
    $bindArgs[] = &$value;
  }

  return call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

function teacher_scalar_value($conn, $sql, $types = '', array $params = [], $defaultValue = 0)
{
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return $defaultValue;
  }

  if ($types !== '') {
    $bindParams = $params;
    if (!teacher_bind_dynamic_params($stmt, $types, $bindParams)) {
      $stmt->close();
      return $defaultValue;
    }
  }

  if (!$stmt->execute()) {
    $stmt->close();
    return $defaultValue;
  }

  $result = $stmt->get_result();
  if (!$result) {
    $stmt->close();
    return $defaultValue;
  }

  $row = $result->fetch_row();
  $stmt->close();
  if (!$row || !isset($row[0])) {
    return $defaultValue;
  }

  return $row[0];
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

$attendanceDateColumn = teacher_first_existing_column($conn, 'attendance', ['date', 'attendance_date']);

$stats = [
  'total_students' => 0,
  'total_assignments' => 0,
  'graded_submissions' => 0,
  'today_attendance' => 0,
];

if (
  teacher_table_exists($conn, 'students')
  && teacher_table_exists($conn, 'classes')
  && (teacher_column_exists($conn, 'students', 'class_id') || teacher_column_exists($conn, 'students', 'class'))
  && teacher_column_exists($conn, 'classes', 'teacher_id')
) {
  $classJoinParts = [];
  if (teacher_column_exists($conn, 'students', 'class_id')) {
    $classJoinParts[] = 's.class_id = c.id';
  }
  if (teacher_column_exists($conn, 'students', 'class')) {
    $studentClassNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(s.`class`, '')), ' ', ''), '-', ''))";
    $classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
    $classNameNorm = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', ''))";
    $classJoinParts[] = "({$studentClassNorm} <> '' AND {$studentClassNorm} = {$classNameNorm})";
  }

  $classJoinSql = empty($classJoinParts) ? '1 = 0' : implode(' OR ', $classJoinParts);
  $stats['total_students'] = (int) teacher_scalar_value(
    $conn,
    "SELECT COUNT(DISTINCT s.id)
     FROM students s
     INNER JOIN classes c ON ({$classJoinSql})
     WHERE c.teacher_id IN ({$teacherIdPlaceholders})",
    $teacherIdTypes,
    $teacherIds,
    0
  );
}

if (teacher_table_exists($conn, 'assignments') && teacher_column_exists($conn, 'assignments', 'teacher_id')) {
  $stats['total_assignments'] = (int) teacher_scalar_value(
    $conn,
    "SELECT COUNT(*) FROM assignments WHERE teacher_id IN ({$teacherIdPlaceholders})",
    $teacherIdTypes,
    $teacherIds,
    0
  );
}

if (
  teacher_table_exists($conn, 'assignment_submissions')
  && teacher_table_exists($conn, 'assignments')
  && teacher_column_exists($conn, 'assignment_submissions', 'assignment_id')
  && teacher_column_exists($conn, 'assignment_submissions', 'status')
  && teacher_column_exists($conn, 'assignments', 'teacher_id')
) {
  $stats['graded_submissions'] = (int) teacher_scalar_value(
    $conn,
    "SELECT COUNT(*)
     FROM assignment_submissions sub
     INNER JOIN assignments a ON sub.assignment_id = a.id
     WHERE a.teacher_id IN ({$teacherIdPlaceholders})
       AND LOWER(COALESCE(sub.status, '')) = 'graded'",
    $teacherIdTypes,
    $teacherIds,
    0
  );
}

if (
  teacher_table_exists($conn, 'attendance')
  && teacher_column_exists($conn, 'attendance', 'teacher_id')
  && $attendanceDateColumn !== null
) {
  $stats['today_attendance'] = (int) teacher_scalar_value(
    $conn,
    "SELECT COUNT(*)
     FROM attendance
     WHERE teacher_id IN ({$teacherIdPlaceholders})
       AND DATE({$attendanceDateColumn}) = CURDATE()",
    $teacherIdTypes,
    $teacherIds,
    0
  );
}

$recentAssignments = [];
if (
  teacher_table_exists($conn, 'assignments')
  && teacher_column_exists($conn, 'assignments', 'teacher_id')
  && teacher_column_exists($conn, 'assignments', 'title')
  && teacher_column_exists($conn, 'assignments', 'due_date')
) {
  $hasSubmissionJoin = teacher_table_exists($conn, 'assignment_submissions')
    && teacher_column_exists($conn, 'assignment_submissions', 'assignment_id');
  $submissionExpr = $hasSubmissionJoin ? 'COUNT(sub.id)' : '0';
  $joinClause = $hasSubmissionJoin ? 'LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id' : '';
  $orderColumn = teacher_column_exists($conn, 'assignments', 'created_at') ? 'a.created_at' : 'a.id';

  $recentSql = "SELECT a.title, a.due_date, {$submissionExpr} AS submissions
                FROM assignments a
                {$joinClause}
                WHERE a.teacher_id IN ({$teacherIdPlaceholders})
                GROUP BY a.id
                ORDER BY {$orderColumn} DESC
                LIMIT 5";
  $recentStmt = $conn->prepare($recentSql);
  if ($recentStmt) {
    $recentParams = $teacherIds;
    if (teacher_bind_dynamic_params($recentStmt, $teacherIdTypes, $recentParams) && $recentStmt->execute()) {
      $recentResult = $recentStmt->get_result();
      if ($recentResult) {
        while ($recentRow = $recentResult->fetch_assoc()) {
          $recentAssignments[] = $recentRow;
        }
      }
    }
    $recentStmt->close();
  }
}

$trendMap = [];
if (
  teacher_table_exists($conn, 'attendance')
  && teacher_column_exists($conn, 'attendance', 'teacher_id')
  && $attendanceDateColumn !== null
) {
  $trendSql = "SELECT DATE({$attendanceDateColumn}) AS attendance_day, COUNT(*) AS total
               FROM attendance
               WHERE teacher_id IN ({$teacherIdPlaceholders})
                 AND {$attendanceDateColumn} >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
               GROUP BY DATE({$attendanceDateColumn})
               ORDER BY attendance_day ASC";
  $trendStmt = $conn->prepare($trendSql);
  if ($trendStmt) {
    $trendParams = $teacherIds;
    if (teacher_bind_dynamic_params($trendStmt, $teacherIdTypes, $trendParams) && $trendStmt->execute()) {
      $trendResult = $trendStmt->get_result();
      if ($trendResult) {
        while ($trendRow = $trendResult->fetch_assoc()) {
          $trendMap[(string) ($trendRow['attendance_day'] ?? '')] = (int) ($trendRow['total'] ?? 0);
        }
      }
    }
    $trendStmt->close();
  }
}

$trendLabels = [];
$trendValues = [];
for ($i = 6; $i >= 0; $i--) {
  $dateKey = date('Y-m-d', strtotime('-' . $i . ' day'));
  $trendLabels[] = date('M j', strtotime($dateKey));
  $trendValues[] = (int) ($trendMap[$dateKey] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
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
      box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased">
  <?php include 'sidebar.php'; ?>

  <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-10 lg:pt-10 space-y-8">
    <section class="flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-on-surface">Teacher Dashboard</h1>
        <p class="text-on-surface-variant font-medium">Welcome back, <?php echo htmlspecialchars($teacherName); ?>.</p>
      </div>
      <div class="glass-panel rounded-xl px-4 py-3 pro-shadow text-sm text-on-surface-variant">
        Updated: <?php echo date('M j, Y H:i'); ?>
      </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <p class="text-sm text-on-surface-variant">Total Students</p>
        <p class="text-3xl font-bold text-on-surface mt-2"><?php echo (int) $stats['total_students']; ?></p>
      </div>
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <p class="text-sm text-on-surface-variant">Assignments</p>
        <p class="text-3xl font-bold text-on-surface mt-2"><?php echo (int) $stats['total_assignments']; ?></p>
      </div>
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <p class="text-sm text-on-surface-variant">Graded Submissions</p>
        <p class="text-3xl font-bold text-on-surface mt-2"><?php echo (int) $stats['graded_submissions']; ?></p>
      </div>
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <p class="text-sm text-on-surface-variant">Today's Attendance</p>
        <p class="text-3xl font-bold text-on-surface mt-2"><?php echo (int) $stats['today_attendance']; ?></p>
      </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="glass-panel rounded-xl pro-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/20">
          <h2 class="text-xl font-semibold text-on-surface">Recent Assignments</h2>
        </div>
        <div class="p-4">
          <?php if (!empty($recentAssignments)): ?>
            <div class="space-y-3">
              <?php foreach ($recentAssignments as $assignment): ?>
                <div class="bg-white border border-outline-variant/30 rounded-lg px-4 py-3">
                  <p class="font-semibold text-on-surface"><?php echo htmlspecialchars((string) ($assignment['title'] ?? 'Untitled')); ?></p>
                  <p class="text-sm text-on-surface-variant">
                    Due: <?php echo !empty($assignment['due_date']) ? date('M j, Y', strtotime((string) $assignment['due_date'])) : '-'; ?>
                    | Submissions: <?php echo (int) ($assignment['submissions'] ?? 0); ?>
                  </p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-sm text-on-surface-variant">No assignments found.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="glass-panel rounded-xl pro-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/20">
          <h2 class="text-xl font-semibold text-on-surface">Attendance Trend (7 Days)</h2>
        </div>
        <div class="p-4">
          <?php $trendTotal = array_sum($trendValues); ?>
          <?php if ($trendTotal > 0): ?>
            <div class="space-y-2">
              <?php foreach ($trendLabels as $index => $trendLabel): ?>
                <?php
                  $entryCount = (int) ($trendValues[$index] ?? 0);
                  $entryPct = $trendTotal > 0 ? (int) round(($entryCount / $trendTotal) * 100) : 0;
                ?>
                <div class="flex items-center justify-between bg-white border border-outline-variant/30 rounded-lg px-3 py-2">
                  <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars((string) $trendLabel); ?></span>
                  <span class="text-sm text-on-surface-variant"><?php echo $entryCount; ?> entries (<?php echo $entryPct; ?>%)</span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-sm text-on-surface-variant">No attendance entries found in the last 7 days.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <a href="assignments.php" class="glass-panel rounded-xl p-6 pro-shadow hover:bg-white/80">
        <p class="font-semibold text-on-surface">Create Assignment</p>
        <p class="text-sm text-on-surface-variant mt-1">Add work for your classes.</p>
      </a>
      <a href="attendance.php" class="glass-panel rounded-xl p-6 pro-shadow hover:bg-white/80">
        <p class="font-semibold text-on-surface">Take Attendance</p>
        <p class="text-sm text-on-surface-variant mt-1">Mark present and absent students.</p>
      </a>
      <a href="students.php" class="glass-panel rounded-xl p-6 pro-shadow hover:bg-white/80">
        <p class="font-semibold text-on-surface">View Students</p>
        <p class="text-sm text-on-surface-variant mt-1">Monitor academic progress.</p>
      </a>
      <a href="notices.php" class="glass-panel rounded-xl p-6 pro-shadow hover:bg-white/80">
        <p class="font-semibold text-on-surface">Notice Board</p>
        <p class="text-sm text-on-surface-variant mt-1">Read official announcements.</p>
      </a>
    </section>
  </main>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

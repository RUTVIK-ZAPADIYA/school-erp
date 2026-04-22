<?php
require_once __DIR__ . '/auth.php';

// Include database connection
include '../includes/db_connect.php';

// Resolve teacher identity across users.id and teachers.id.
$teacherContext = teacher_auth_resolve_context($conn);
$teacher_id = (int) ($teacherContext['user_id'] ?? 0);
$teacher_owner_ids = teacher_auth_sanitize_ids((array) ($teacherContext['teacher_ids'] ?? [$teacher_id]));
if (empty($teacher_owner_ids)) {
  $teacher_owner_ids = [0];
}
$teacher_ids_sql = implode(',', $teacher_owner_ids);
$teacher_id_placeholders = implode(',', array_fill(0, count($teacher_owner_ids), '?'));
$teacher_id_types = str_repeat('i', count($teacher_owner_ids));
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

$classNameColumn = teacher_first_existing_column($conn, 'classes', ['name', 'class_name']);
$studentRollColumn = teacher_first_existing_column($conn, 'students', ['roll_no', 'roll_number']);
$studentsHasUserId = teacher_column_exists($conn, 'students', 'user_id');
$gradesHasStudentUserId = teacher_column_exists($conn, 'grades', 'student_user_id');

// Get students in teacher's classes with enhanced statistics
$students = [];
$total_students = 0;
$excellent_students = 0;
$good_students = 0;
$needs_attention = 0;

if (
  teacher_table_exists($conn, 'students')
  && teacher_table_exists($conn, 'classes')
  && teacher_column_exists($conn, 'students', 'name')
  && teacher_column_exists($conn, 'classes', 'teacher_id')
  && (teacher_column_exists($conn, 'students', 'class_id') || teacher_column_exists($conn, 'students', 'class'))
) {
  $classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
  $rollSelect = $studentRollColumn !== null ? "s.`{$studentRollColumn}`" : "''";
  $studentUserSelect = $studentsHasUserId ? ', COALESCE(NULLIF(s.user_id, 0), s.id) AS linked_user_id' : ', s.id AS linked_user_id';
  $orderBy = $studentRollColumn !== null ? "s.`{$studentRollColumn}`" : 's.id';

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

  $studentClassJoinSql = empty($studentClassJoinParts) ? '1 = 0' : implode(' OR ', $studentClassJoinParts);

  $sql = "SELECT DISTINCT s.id, {$rollSelect} AS roll_no, s.name, s.email, {$classNameExpr} AS class_name, c.id AS class_id{$studentUserSelect}
      FROM students s
      INNER JOIN classes c ON ({$studentClassJoinSql})
      WHERE c.teacher_id IN ({$teacher_id_placeholders})
      ORDER BY {$orderBy} ASC";
  $studentsQueryStmt = $conn->prepare($sql);
  if ($studentsQueryStmt) {
    $studentParams = $teacher_owner_ids;
    $result = false;
    if (teacher_bind_dynamic_params($studentsQueryStmt, $teacher_id_types, $studentParams) && $studentsQueryStmt->execute()) {
      $result = $studentsQueryStmt->get_result();
    }

    if ($result) {
    $seenStudentIds = [];

    while ($result && ($row = $result->fetch_assoc())) {
      $student_id = (int) ($row['id'] ?? 0);
      if ($student_id <= 0 || isset($seenStudentIds[$student_id])) {
        continue;
      }

      $seenStudentIds[$student_id] = true;
      $total_students++;
      $linked_user_id = (int) ($row['linked_user_id'] ?? $student_id);
      if ($linked_user_id <= 0) {
        $linked_user_id = $student_id;
      }

      $row['avg_grade'] = 0;
      if (teacher_table_exists($conn, 'grades') && teacher_column_exists($conn, 'grades', 'student_id')) {
        if ($gradesHasStudentUserId) {
          $gradeStmt = $conn->prepare( 'SELECT COALESCE(AVG(obtained_marks), 0) AS avg_grade FROM grades WHERE student_id = ? OR student_user_id = ?');
        } else {
          $gradeStmt = $conn->prepare( 'SELECT COALESCE(AVG(obtained_marks), 0) AS avg_grade FROM grades WHERE student_id = ?');
        }

        if ($gradeStmt) {
          if ($gradesHasStudentUserId) {
            $gradeStmt->bind_param( 'ii', $student_id, $linked_user_id);
          } else {
            $gradeStmt->bind_param( 'i', $student_id);
          }

          $gradeStmt->execute();
          $gradeResult = $gradeStmt->get_result();
          $gradeData = $gradeResult ? $gradeResult->fetch_assoc() : null;
          $row['avg_grade'] = round((float) ($gradeData['avg_grade'] ?? 0), 1);
          $gradeStmt->close();
        }
      }

      $row['attendance_percent'] = 0;
      if (teacher_table_exists($conn, 'attendance') && teacher_column_exists($conn, 'attendance', 'student_id')) {
        $attendanceStmt = $conn->prepare(
          "SELECT COUNT(*) AS total_days,
              SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'present' THEN 1 ELSE 0 END) AS present_days
           FROM attendance
           WHERE student_id = ?"
        );
        if ($attendanceStmt) {
          $attendanceStmt->bind_param( 'i', $student_id);
          $attendanceStmt->execute();
          $attendanceResult = $attendanceStmt->get_result();
          $attendanceData = $attendanceResult ? $attendanceResult->fetch_assoc() : null;
          $totalDays = (int) ($attendanceData['total_days'] ?? 0);
          $presentDays = (int) ($attendanceData['present_days'] ?? 0);
          if ($totalDays > 0) {
            $row['attendance_percent'] = round(($presentDays / $totalDays) * 100, 1);
          }
          $attendanceStmt->close();
        }
      }

      $avg_performance = ($row['avg_grade'] + $row['attendance_percent']) / 2;
      if ($avg_performance >= 85) {
        $row['performance_category'] = 'excellent';
        $excellent_students++;
      } elseif ($avg_performance >= 70) {
        $row['performance_category'] = 'good';
        $good_students++;
      } else {
        $row['performance_category'] = 'needs_attention';
        $needs_attention++;
      }

      $students[] = $row;
    }
    }

    $studentsQueryStmt->close();
  }
}

// Get class distribution
$class_distribution = [];
if (teacher_table_exists($conn, 'classes') && teacher_column_exists($conn, 'classes', 'teacher_id')) {
  $classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
  $distributionJoinParts = [];
  $hasStudentClassId = teacher_column_exists($conn, 'students', 'class_id');
  if ($hasStudentClassId) {
    $distributionJoinParts[] = 's.class_id = c.id';
  }
  if (teacher_column_exists($conn, 'students', 'class')) {
    $studentClassNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(s.`class`, '')), ' ', ''), '-', ''))";
    $classNameNorm = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', ''))";
    $fallbackCondition = "({$studentClassNorm} <> '' AND {$studentClassNorm} = {$classNameNorm})";
    if ($hasStudentClassId) {
      $fallbackCondition = "(COALESCE(s.class_id, 0) = 0 AND {$fallbackCondition})";
    }
    $distributionJoinParts[] = $fallbackCondition;
  }

  $distributionJoinSql = empty($distributionJoinParts) ? '1 = 0' : implode(' OR ', $distributionJoinParts);
  $sql_classes = "SELECT {$classNameExpr} AS name, COUNT(DISTINCT s.id) AS student_count
          FROM classes c
          LEFT JOIN students s ON ({$distributionJoinSql})
          WHERE c.teacher_id IN ({$teacher_id_placeholders})
          GROUP BY c.id, {$classNameExpr}
          ORDER BY {$classNameExpr} ASC";
  $distributionStmt = $conn->prepare($sql_classes);
  if ($distributionStmt) {
    $distributionParams = $teacher_owner_ids;
    if (teacher_bind_dynamic_params($distributionStmt, $teacher_id_types, $distributionParams) && $distributionStmt->execute()) {
      $result_classes = $distributionStmt->get_result();
      if ($result_classes) {
        while ($row = $result_classes->fetch_assoc()) {
          $class_distribution[] = $row;
        }
      }
    }
    $distributionStmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Management - The Academic Editorial</title>
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

  <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-10 lg:pt-10 space-y-10">
    <!-- Header Section -->
    <section class="space-y-6">
      <div class="flex justify-between items-end">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Student Management</h1>
          <p class="text-on-surface-variant font-medium">Monitor student performance, attendance, and academic progress</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
            </span>
            <input type="text" id="searchInput" placeholder="Search students..." class="pl-10 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-sm w-64 focus:ring-2 focus:ring-primary/20">
          </div>
          <button onclick="document.getElementById('studentsTable').scrollIntoView()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">group</span>
            View Students
          </button>
        </div>
      </div>
    </section>

    <!-- Analytics Cards -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">group</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $total_students; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Total Students</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-primary h-2 rounded-full" style="width: 100%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600">stars</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $excellent_students; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Excellent</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $total_students > 0 ? (($excellent_students / $total_students) * 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600">thumb_up</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $good_students; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Good</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $total_students > 0 ? (($good_students / $total_students) * 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-yellow-600">warning</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $needs_attention; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Needs Attention</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-yellow-500 h-2 rounded-full" style="width: <?php echo $total_students > 0 ? (($needs_attention / $total_students) * 100) : 0; ?>%"></div>
        </div>
      </div>
    </section>

    <!-- Students Management Table -->
    <section id="studentsTable" class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <div>
          <h2 class="text-xl font-bold text-on-surface">Student Directory</h2>
          <p class="text-on-surface-variant">Comprehensive view of all students under your supervision</p>
        </div>
        <div class="flex items-center gap-3">
          <select id="classFilter" class="px-4 py-2 bg-white border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            <option value="">All Classes</option>
            <?php foreach ($class_distribution as $class): ?>
            <option value="<?php echo htmlspecialchars($class['name']); ?>"><?php echo htmlspecialchars($class['name']); ?> (<?php echo $class['student_count']; ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-stone-50/50">
            <tr>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Student</th>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Class</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-on-surface-variant uppercase tracking-widest">Attendance</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-on-surface-variant uppercase tracking-widest">Grade</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-on-surface-variant uppercase tracking-widest">Performance</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-on-surface-variant uppercase tracking-widest">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            <?php foreach ($students as $student): ?>
            <tr>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600 text-sm">person</span>
                  </div>
                  <div>
                    <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($student['name']); ?></span>
                    <p class="text-xs text-on-surface-variant">Roll: <?php echo htmlspecialchars($student['roll_no']); ?></p>
                    <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($student['email']); ?></p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                  <?php echo htmlspecialchars($student['class_name']); ?>
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex flex-col items-center gap-1">
                  <span class="text-sm font-semibold text-on-surface"><?php echo $student['attendance_percent']; ?>%</span>
                  <div class="w-16 bg-surface-variant rounded-full h-1.5">
                    <div class="bg-primary h-1.5 rounded-full" style="width: <?php echo $student['attendance_percent']; ?>%"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex flex-col items-center gap-1">
                  <span class="text-sm font-semibold text-on-surface"><?php echo $student['avg_grade']; ?>%</span>
                  <div class="w-16 bg-surface-variant rounded-full h-1.5">
                    <div class="bg-primary h-1.5 rounded-full" style="width: <?php echo $student['avg_grade']; ?>%"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <?php
                $category = $student['performance_category'];
                $categoryClass = '';
                $categoryIcon = '';
                $categoryText = '';

                switch ($category) {
                  case 'excellent':
                    $categoryClass = 'bg-green-100 text-green-800';
                    $categoryIcon = 'stars';
                    $categoryText = 'Excellent';
                    break;
                  case 'good':
                    $categoryClass = 'bg-blue-100 text-blue-800';
                    $categoryIcon = 'thumb_up';
                    $categoryText = 'Good';
                    break;
                  case 'needs_attention':
                    $categoryClass = 'bg-yellow-100 text-yellow-800';
                    $categoryIcon = 'warning';
                    $categoryText = 'Needs Attention';
                    break;
                }
                ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium <?php echo $categoryClass; ?>">
                  <span class="material-symbols-outlined text-xs"><?php echo $categoryIcon; ?></span>
                  <?php echo $categoryText; ?>
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-2">
                  <a href="view-student.php?id=<?php echo $student['id']; ?>" class="text-primary hover:text-primary/80 p-1 rounded-lg hover:bg-primary/10">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                  </a>
                  <button onclick="sendMessage(<?php echo $student['id']; ?>)" class="text-tertiary hover:text-tertiary/80 p-1 rounded-lg hover:bg-tertiary/10">
                    <span class="material-symbols-outlined text-sm">mail</span>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (empty($students)): ?>
      <div class="px-6 py-12 text-center">
        <div class="w-12 h-12 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="material-symbols-outlined text-on-surface-variant text-xl">group</span>
        </div>
        <h3 class="text-sm font-semibold text-on-surface mb-2">No students found</h3>
        <p class="text-on-surface-variant">There are no students assigned to your classes yet.</p>
      </div>
      <?php endif; ?>
    </section>
  </main>

  <script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('tbody tr');

    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();

      tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
      });
    });

    // Class filter functionality
    const classFilter = document.getElementById('classFilter');

    classFilter.addEventListener('change', function() {
      const selectedClass = this.value.toLowerCase();

      tableRows.forEach(row => {
        if (selectedClass === '' || selectedClass === 'all classes') {
          row.style.display = '';
        } else {
          const classCell = row.querySelector('td:nth-child(2)');
          const classText = classCell ? classCell.textContent.toLowerCase() : '';
          const isVisible = classText.includes(selectedClass);
          row.style.display = isVisible ? '' : 'none';
        }
      });
    });

    function sendMessage(studentId) {
      alert('Direct messaging feature coming soon in Pro Edition!');
    }
  </script>
  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

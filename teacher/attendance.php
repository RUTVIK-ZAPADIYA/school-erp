<?php
require_once __DIR__ . '/auth.php';

// Include database connection
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

function teacher_class_owner_id($conn, $classId, array $teacherIds)
{
  if ($classId <= 0 || empty($teacherIds) || !teacher_column_exists($conn, 'classes', 'teacher_id')) {
    return 0;
  }

  if (function_exists('teacher_auth_class_owner_id')) {
    return teacher_auth_class_owner_id($conn, $classId, $teacherIds);
  }

  $safeTeacherIds = array_values(array_unique(array_filter(array_map('intval', $teacherIds), function ($id) {
    return $id > 0;
  })));
  if (empty($safeTeacherIds)) {
    return 0;
  }
  $teacherIdSql = implode(',', $safeTeacherIds);

  $stmt = $conn->prepare( "SELECT teacher_id FROM classes WHERE id = ? AND teacher_id IN ({$teacherIdSql}) LIMIT 1");
  if (!$stmt) {
    return 0;
  }

  $stmt->bind_param( 'i', $classId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();

  return (int) ($row['teacher_id'] ?? 0);
}

$classNameColumn = teacher_first_existing_column($conn, 'classes', ['name', 'class_name']);
$subjectNameColumn = teacher_first_existing_column($conn, 'subjects', ['name', 'subject_name']);
$attendanceDateColumn = teacher_first_existing_column($conn, 'attendance', ['date', 'attendance_date']);
$attendanceHasSubject = teacher_column_exists($conn, 'attendance', 'subject_id');
$attendanceHasTeacher = teacher_column_exists($conn, 'attendance', 'teacher_id');
$attendanceHasClass = teacher_column_exists($conn, 'attendance', 'class_id');
$attendanceHasStatus = teacher_column_exists($conn, 'attendance', 'status');
$studentRollColumn = teacher_first_existing_column($conn, 'students', ['roll_no', 'roll_number']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
  $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
  $subject_id = isset($_POST['subject_id']) ? (int) $_POST['subject_id'] : 0;
  $dateInput = trim((string) ($_POST['date'] ?? date('Y-m-d')));
  $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput) ? $dateInput : date('Y-m-d');
  $class_owner_id = 0;

  if ($class_id <= 0) {
    $error = 'Please select a class.';
  } elseif (($class_owner_id = teacher_class_owner_id($conn, $class_id, $teacher_owner_ids)) <= 0) {
    $error = 'Selected class is not assigned to your account.';
  } elseif ($attendanceDateColumn === null) {
    $error = 'Attendance date column is not available in the database.';
  } elseif (!$attendanceHasStatus) {
    $error = 'Attendance status column is not available in the database.';
  } elseif ($attendanceHasSubject && $subject_id <= 0) {
    $error = 'Please select a subject.';
  } else {
    $selectedClassLabel = teacher_auth_class_label_by_id($conn, $class_id);
    $studentClassWhere = teacher_auth_student_class_where_sql($conn, 'students', $class_id, $selectedClassLabel);
    $sqlStudents = "SELECT id FROM students WHERE {$studentClassWhere}";
    $resultStudents = $conn->query( $sqlStudents);

    if ($resultStudents) {
      $safeDate = $conn->real_escape_string( $date);
      $savedCount = 0;
      $failedCount = 0;
      $lastSqlError = '';

      while ($student = $resultStudents->fetch_assoc()) {
        $student_id = (int) ($student['id'] ?? 0);
        if ($student_id <= 0) {
          continue;
        }

        $statusInput = strtolower(trim((string) ($_POST['attendance'][$student_id] ?? 'absent')));
        $status = in_array($statusInput, ['present', 'absent', 'late'], true) ? $statusInput : 'absent';

        $whereSql = "student_id = {$student_id} AND `{$attendanceDateColumn}` = '{$safeDate}'";
        if ($attendanceHasClass) {
          $whereSql .= " AND COALESCE(NULLIF(class_id, 0), {$class_id}) = {$class_id}";
        }
        if ($attendanceHasSubject) {
          $whereSql .= " AND subject_id = {$subject_id}";
        }

        $checkSql = "SELECT id FROM attendance WHERE {$whereSql} LIMIT 1";
        $checkResult = $conn->query( $checkSql);

        if ($checkResult && $checkResult->num_rows > 0) {
          $updateParts = ["status = '{$status}'"];
          if ($attendanceHasTeacher) {
            $updateParts[] = "teacher_id = {$class_owner_id}";
          }
          if ($attendanceHasClass) {
            $updateParts[] = "class_id = {$class_id}";
          }

          $sql = 'UPDATE attendance SET ' . implode(', ', $updateParts) . " WHERE {$whereSql}";
        } else {
          $insertColumns = ['student_id', "`{$attendanceDateColumn}`", 'status'];
          $insertValues = [$student_id, "'{$safeDate}'", "'{$status}'"];

          if ($attendanceHasTeacher) {
            $insertColumns[] = 'teacher_id';
            $insertValues[] = $class_owner_id;
          }

          if ($attendanceHasClass) {
            $insertColumns[] = 'class_id';
            $insertValues[] = $class_id;
          }

          if ($attendanceHasSubject) {
            $insertColumns[] = 'subject_id';
            $insertValues[] = $subject_id;
          }

          $sql = 'INSERT INTO attendance (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
        }

        if ($conn->query( $sql)) {
          $savedCount++;
        } else {
          $failedCount++;
          if ($lastSqlError === '') {
            $lastSqlError = (string) $conn->error;
          }
        }
      }

      if ($savedCount > 0 && $failedCount === 0) {
        $success = 'Attendance saved successfully!';
      } elseif ($savedCount > 0) {
        $error = 'Attendance saved partially. Some records could not be saved.';
        if ($lastSqlError !== '') {
          $error .= ' Details: ' . $lastSqlError;
        }
      } else {
        $error = 'Unable to save attendance right now.';
        if ($lastSqlError !== '') {
          $error .= ' Details: ' . $lastSqlError;
        }
      }
    } else {
      $error = 'Error retrieving students for the selected class.';
    }
  }
}

// Get classes for the teacher
$classes = [];
if ($classNameColumn !== null && teacher_column_exists($conn, 'classes', 'teacher_id')) {
  $sql_classes = "SELECT id, {$classNameColumn} AS name FROM classes WHERE teacher_id IN ({$teacher_ids_sql}) ORDER BY {$classNameColumn} ASC";
  $result_classes = $conn->query( $sql_classes);
  if ($result_classes) {
    while ($row = $result_classes->fetch_assoc()) {
      $classes[] = $row;
    }
  }
}

// Get subjects
$subjects = [];
if ($subjectNameColumn !== null) {
  $sql_subjects = "SELECT id, {$subjectNameColumn} AS name FROM subjects ORDER BY {$subjectNameColumn} ASC";
  $result_subjects = $conn->query( $sql_subjects);
  if ($result_subjects) {
    while ($row = $result_subjects->fetch_assoc()) {
      $subjects[] = $row;
    }
  }
}

// Default class and subject for display
$selected_class = isset($_POST['class_id']) ? (int) $_POST['class_id'] : (int) ($classes[0]['id'] ?? 0);
$selected_subject = isset($_POST['subject_id']) ? (int) $_POST['subject_id'] : (int) ($subjects[0]['id'] ?? 0);
$selectedDateInput = trim((string) ($_POST['date'] ?? date('Y-m-d')));
$selected_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateInput) ? $selectedDateInput : date('Y-m-d');
$selected_class_owner_id = teacher_class_owner_id($conn, $selected_class, $teacher_owner_ids);

if ($selected_class_owner_id <= 0) {
  $selected_class = (int) ($classes[0]['id'] ?? 0);
  $selected_class_owner_id = teacher_class_owner_id($conn, $selected_class, $teacher_owner_ids);
}

$students = [];
$stats = [
  'total_students' => 0,
  'present_count' => 0,
  'absent_count' => 0,
  'late_count' => 0,
  'unmarked_count' => 0,
];

// Get students for selected class with attendance data
if ($selected_class > 0 && $selected_class_owner_id > 0 && teacher_column_exists($conn, 'students', 'name')) {
  $safeDate = $conn->real_escape_string( $selected_date);
  $selectedClassLabel = teacher_auth_class_label_by_id($conn, $selected_class);
  $studentClassWhere = teacher_auth_student_class_where_sql($conn, 's', $selected_class, $selectedClassLabel);
  $rollSelectSql = $studentRollColumn !== null ? "s.`{$studentRollColumn}` AS roll_no" : "'' AS roll_no";
  $orderBySql = $studentRollColumn !== null ? "s.`{$studentRollColumn}`" : 's.id';

  $attendanceStatusSql = "'unmarked' AS attendance_status";
  $attendanceJoinSql = '';

  if (teacher_table_exists($conn, 'attendance') && $attendanceDateColumn !== null) {
    $joinParts = [
      's.id = a.student_id',
      "a.`{$attendanceDateColumn}` = '{$safeDate}'",
    ];

    if ($attendanceHasClass) {
      $joinParts[] = "COALESCE(NULLIF(a.class_id, 0), {$selected_class}) = {$selected_class}";
    }

    if ($attendanceHasSubject && $selected_subject > 0) {
      $joinParts[] = "a.subject_id = {$selected_subject}";
    }

    $attendanceJoinSql = 'LEFT JOIN attendance a ON ' . implode(' AND ', $joinParts);
    $attendanceStatusSql = "COALESCE(a.status, 'unmarked') AS attendance_status";
  }

  $sql_students = "SELECT s.id, {$rollSelectSql}, s.name, {$attendanceStatusSql}
           FROM students s
           {$attendanceJoinSql}
           WHERE {$studentClassWhere}
           ORDER BY {$orderBySql}";
  $result_students = $conn->query( $sql_students);

  if ($result_students) {
    while ($row = $result_students->fetch_assoc()) {
      $students[] = $row;
    }
  } else {
    $error = 'Unable to load students for attendance.';
  }
}

// Get attendance statistics from loaded rows (prevents SQL subquery failures)
$stats['total_students'] = count($students);
foreach ($students as $studentRow) {
  $status = strtolower((string) ($studentRow['attendance_status'] ?? 'unmarked'));
  if ($status === 'present') {
    $stats['present_count']++;
  } elseif ($status === 'absent') {
    $stats['absent_count']++;
  } elseif ($status === 'late') {
    $stats['late_count']++;
  } else {
    $stats['unmarked_count']++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Management - The Academic Editorial</title>
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
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Attendance Management</h1>
          <p class="text-on-surface-variant font-medium">Track and manage student attendance</p>
        </div>
        <div class="flex items-center gap-4">
          <button onclick="document.getElementById('attendanceForm').scrollIntoView()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">edit</span>
            Mark Attendance
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

    <!-- Attendance Management Form -->
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <h3 class="text-sm font-bold text-on-surface">Mark Attendance</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
            </span>
            <input type="text" id="searchInput" placeholder="Search students..." class="pl-10 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-sm w-64 focus:ring-2 focus:ring-primary/20">
          </div>
        </div>
      </div>

      <form method="POST" class="p-6 space-y-6" id="attendanceForm" novalidate>
        <!-- Form Controls -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Select Class</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="class_id" data-validation="required,select" onchange="this.form.submit()">
              <?php foreach ($classes as $class): ?>
              <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <p id="class_id_error" class="text-sm text-red-600 hidden"></p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Date</label>
            <input type="date" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="date" value="<?php echo $selected_date; ?>" data-validation="required" onchange="this.form.submit()">
            <p id="date_error" class="text-sm text-red-600 hidden"></p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="subject_id" data-validation="required,select" onchange="this.form.submit()">
              <?php foreach ($subjects as $subject): ?>
              <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject == $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <p id="subject_id_error" class="text-sm text-red-600 hidden"></p>
          </div>
        </div>

        <!-- Students Attendance Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-stone-50/50">
              <tr>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Student</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Present</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Absent</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Late</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10">
              <?php foreach ($students as $student): ?>
              <tr>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded flex items-center justify-center">
                      <span class="material-symbols-outlined text-blue-600 text-sm">person</span>
                    </div>
                    <div>
                      <span class="text-xs font-bold text-on-surface"><?php echo htmlspecialchars($student['name']); ?></span>
                      <p class="text-xs text-on-surface-variant">Roll: <?php echo htmlspecialchars($student['roll_no']); ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="present"
                         <?php echo $student['attendance_status'] == 'present' ? 'checked' : ''; ?>
                         class="w-4 h-4 text-green-600 bg-white border-outline-variant focus:ring-green-500 focus:ring-2">
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="absent"
                         <?php echo $student['attendance_status'] == 'absent' ? 'checked' : ''; ?>
                         class="w-4 h-4 text-red-600 bg-white border-outline-variant focus:ring-red-500 focus:ring-2">
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="late"
                         <?php echo $student['attendance_status'] == 'late' ? 'checked' : ''; ?>
                         class="w-4 h-4 text-yellow-600 bg-white border-outline-variant focus:ring-yellow-500 focus:ring-2">
                </td>
                <td class="px-6 py-4">
                  <?php
                  $status = $student['attendance_status'];
                  $statusClass = '';
                  $statusIcon = '';
                  $statusText = ucfirst($status);

                  switch ($status) {
                    case 'present':
                      $statusClass = 'bg-green-100 text-green-800';
                      $statusIcon = 'check_circle';
                      break;
                    case 'absent':
                      $statusClass = 'bg-red-100 text-red-800';
                      $statusIcon = 'cancel';
                      break;
                    case 'late':
                      $statusClass = 'bg-yellow-100 text-yellow-800';
                      $statusIcon = 'schedule';
                      break;
                    default:
                      $statusClass = 'bg-gray-100 text-gray-800';
                      $statusIcon = 'help';
                      $statusText = 'Unmarked';
                      break;
                  }
                  ?>
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                    <span class="material-symbols-outlined text-xs"><?php echo $statusIcon; ?></span>
                    <?php echo $statusText; ?>
                  </span>
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
          <p class="text-on-surface-variant">No students are enrolled in the selected class.</p>
        </div>
        <?php endif; ?>

        <!-- Submit Button -->
        <?php if (!empty($students)): ?>
        <div class="pt-6 border-t border-outline-variant flex justify-between items-center">
          <div class="text-sm text-on-surface-variant">
            Changes will be saved for all students in the selected class, date, and subject.
          </div>
          <button type="submit" name="save_attendance" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold pro-shadow flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Save Attendance
          </button>
        </div>
        <?php endif; ?>
      </form>
    </section>
  </main>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
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

    // Update status badge when radio button changes
    document.addEventListener('change', function(e) {
      if (e.target.type === 'radio' && e.target.name.startsWith('attendance[')) {
        const row = e.target.closest('tr');
        const statusCell = row.querySelector('td:last-child span');
        const status = e.target.value;

        const statusClasses = {
          present: 'bg-green-100 text-green-800',
          absent: 'bg-red-100 text-red-800',
          late: 'bg-yellow-100 text-yellow-800',
          unmarked: 'bg-gray-100 text-gray-800'
        };

        const statusIcons = {
          present: 'check_circle',
          absent: 'cancel',
          late: 'schedule',
          unmarked: 'help'
        };

        const statusTexts = {
          present: 'Present',
          absent: 'Absent',
          late: 'Late',
          unmarked: 'Unmarked'
        };

        statusCell.className = `inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${statusClasses[status]}`;
        statusCell.innerHTML = `<span class="material-symbols-outlined text-xs">${statusIcons[status]}</span>${statusTexts[status]}`;
      }
    });
  </script>
</body>
</html>

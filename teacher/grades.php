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

function teacher_grade_from_marks($obtainedMarks, $totalMarks)
{
  if ($totalMarks <= 0) {
    return 'F';
  }

  $percentage = ($obtainedMarks / $totalMarks) * 100;
  if ($percentage >= 90) {
    return 'A+';
  }
  if ($percentage >= 80) {
    return 'A';
  }
  if ($percentage >= 70) {
    return 'B+';
  }
  if ($percentage >= 60) {
    return 'B';
  }
  if ($percentage >= 50) {
    return 'C+';
  }
  if ($percentage >= 40) {
    return 'C';
  }

  return 'F';
}

$classNameColumn = teacher_first_existing_column($conn, 'classes', ['name', 'class_name']);
$subjectNameColumn = teacher_first_existing_column($conn, 'subjects', ['name', 'subject_name']);
$studentRollColumn = teacher_first_existing_column($conn, 'students', ['roll_no', 'roll_number']);
$studentsHasUserId = teacher_column_exists($conn, 'students', 'user_id');
$gradesHasStudentUserId = teacher_column_exists($conn, 'grades', 'student_user_id');

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
  $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
  $exam_type = trim((string) ($_POST['exam_type'] ?? ''));
  $subject_id = isset($_POST['subject_id']) ? (int) $_POST['subject_id'] : 0;
  $class_owner_id = 0;

  if ($class_id <= 0 || $subject_id <= 0 || $exam_type === '') {
    $error = 'Please select class, exam type, and subject.';
  } elseif (($class_owner_id = teacher_class_owner_id($conn, $class_id, $teacher_owner_ids)) <= 0) {
    $error = 'Selected class is not assigned to your account.';
  } else {
    $selectedClassLabel = teacher_auth_class_label_by_id($conn, $class_id);
    $studentClassWhere = teacher_auth_student_class_where_sql($conn, 'students', $class_id, $selectedClassLabel);
    $studentSelectColumns = $studentsHasUserId
      ? 'id, COALESCE(NULLIF(user_id, 0), id) AS linked_user_id'
      : 'id, id AS linked_user_id';
    $studentStmt = $conn->prepare( "SELECT {$studentSelectColumns} FROM students WHERE {$studentClassWhere}");
    if (!$studentStmt) {
      $error = 'Error retrieving students.';
    } else {
      $studentStmt->execute();
      $result_students = $studentStmt->get_result();
      $savedRows = 0;

      while ($result_students && ($student = $result_students->fetch_assoc())) {
        $student_id = (int) ($student['id'] ?? 0);
        $linked_student_user_id = (int) ($student['linked_user_id'] ?? $student_id);
        if ($linked_student_user_id <= 0) {
          $linked_student_user_id = $student_id;
        }
        if ($student_id <= 0) {
          continue;
        }

        $obtained_marks = (int) ($_POST['marks_' . $student_id] ?? 0);
        $obtained_marks = max(0, min(100, $obtained_marks));
        $remarks = trim((string) ($_POST['remarks_' . $student_id] ?? ''));
        $total_marks = 100;
        $grade = teacher_grade_from_marks($obtained_marks, $total_marks);
        $existingGradeId = 0;

        if ($gradesHasStudentUserId) {
          $checkStmt = $conn->prepare(
            'SELECT id FROM grades WHERE (student_id = ? OR student_user_id = ?) AND subject_id = ? AND exam_type = ? ORDER BY id DESC LIMIT 1'
          );
        } else {
          $checkStmt = $conn->prepare( 'SELECT id FROM grades WHERE student_id = ? AND subject_id = ? AND exam_type = ? LIMIT 1');
        }

        if (!$checkStmt) {
          continue;
        }

        if ($gradesHasStudentUserId) {
          $checkStmt->bind_param( 'iiis', $student_id, $linked_student_user_id, $subject_id, $exam_type);
        } else {
          $checkStmt->bind_param( 'iis', $student_id, $subject_id, $exam_type);
        }

        $checkStmt->execute();
        $result_check = $checkStmt->get_result();
        $existingGradeRow = $result_check ? $result_check->fetch_assoc() : null;
        if ($existingGradeRow) {
          $existingGradeId = (int) ($existingGradeRow['id'] ?? 0);
        }
        $checkStmt->close();

        if ($existingGradeId > 0) {
          if ($gradesHasStudentUserId) {
            $updateStmt = $conn->prepare(
              'UPDATE grades SET student_id = ?, student_user_id = ?, obtained_marks = ?, grade = ?, remarks = ?, teacher_id = ? WHERE id = ?'
            );
            if (!$updateStmt) {
              continue;
            }

            $updateStmt->bind_param(
              'iiissii',
              $student_id,
              $linked_student_user_id,
              $obtained_marks,
              $grade,
              $remarks,
              $class_owner_id,
              $existingGradeId
            );
          } else {
            $updateStmt = $conn->prepare(
              'UPDATE grades SET student_id = ?, obtained_marks = ?, grade = ?, remarks = ?, teacher_id = ? WHERE id = ?'
            );
            if (!$updateStmt) {
              continue;
            }

            $updateStmt->bind_param(
              'iissii',
              $student_id,
              $obtained_marks,
              $grade,
              $remarks,
              $class_owner_id,
              $existingGradeId
            );
          }

          if ($updateStmt->execute()) {
            $savedRows++;
          }
          $updateStmt->close();
        } else {
          if ($gradesHasStudentUserId) {
            $insertStmt = $conn->prepare(
              'INSERT INTO grades (student_id, student_user_id, subject_id, exam_type, total_marks, obtained_marks, grade, remarks, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
          } else {
            $insertStmt = $conn->prepare(
              'INSERT INTO grades (student_id, subject_id, exam_type, total_marks, obtained_marks, grade, remarks, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
          }

          if (!$insertStmt) {
            continue;
          }

          if ($gradesHasStudentUserId) {
            $insertStmt->bind_param(
              'iiisiissi',
              $student_id,
              $linked_student_user_id,
              $subject_id,
              $exam_type,
              $total_marks,
              $obtained_marks,
              $grade,
              $remarks,
              $class_owner_id
            );
          } else {
            $insertStmt->bind_param( 'iisiissi', $student_id, $subject_id, $exam_type, $total_marks, $obtained_marks, $grade, $remarks, $class_owner_id);
          }

          if ($insertStmt->execute()) {
            $savedRows++;
          }
          $insertStmt->close();
        }
      }

      $studentStmt->close();

      if ($savedRows > 0) {
        $success = 'Grades saved successfully!';
      } else {
        $error = 'No grade rows were saved. Please verify input and try again.';
      }
    }
  }
}

// Get classes, subjects
$classes = [];
if ($classNameColumn !== null && teacher_table_exists($conn, 'classes') && teacher_column_exists($conn, 'classes', 'teacher_id')) {
  $sql_classes = "SELECT id, `{$classNameColumn}` AS name FROM classes WHERE teacher_id IN ({$teacher_ids_sql}) ORDER BY `{$classNameColumn}` ASC";
  $result_classes = $conn->query( $sql_classes);
  if ($result_classes) {
    while ($row = $result_classes->fetch_assoc()) {
      $classes[] = $row;
    }
  }
}

$subjects = [];
if ($subjectNameColumn !== null && teacher_table_exists($conn, 'subjects')) {
  $sql_subjects = "SELECT id, `{$subjectNameColumn}` AS name FROM subjects ORDER BY `{$subjectNameColumn}` ASC";
  $result_subjects = $conn->query( $sql_subjects);
  if ($result_subjects) {
    while ($row = $result_subjects->fetch_assoc()) {
      $subjects[] = $row;
    }
  }
}

// Default selections
$selected_class = isset($_POST['class_id']) ? (int) $_POST['class_id'] : (int) ($classes[0]['id'] ?? 0);
$selected_exam = $_POST['exam_type'] ?? 'Mid-term';
$selected_subject = isset($_POST['subject_id']) ? (int) $_POST['subject_id'] : (int) ($subjects[0]['id'] ?? 0);
$selected_class_owner_id = teacher_class_owner_id($conn, $selected_class, $teacher_owner_ids);

if ($selected_class_owner_id <= 0) {
  $selected_class = (int) ($classes[0]['id'] ?? 0);
  $selected_class_owner_id = teacher_class_owner_id($conn, $selected_class, $teacher_owner_ids);
}

// Get students and their grades
$students = [];
if ($selected_class > 0 && $selected_class_owner_id > 0 && teacher_column_exists($conn, 'students', 'name')) {
  $selectedClassLabel = teacher_auth_class_label_by_id($conn, $selected_class);
  $studentClassWhere = teacher_auth_student_class_where_sql($conn, 's', $selected_class, $selectedClassLabel);
  $rollSelect = $studentRollColumn !== null ? "s.`{$studentRollColumn}`" : "''";
  $orderBy = $studentRollColumn !== null ? "s.`{$studentRollColumn}`" : 's.id';
  $studentLinkedUserExpr = $studentsHasUserId ? 'COALESCE(NULLIF(s.user_id, 0), s.id)' : 's.id';
  $gradeJoinCondition = $gradesHasStudentUserId
    ? "(g.student_id = s.id OR g.student_user_id = {$studentLinkedUserExpr})"
    : 'g.student_id = s.id';

  $sql_students = "SELECT s.id, {$rollSelect} AS roll_no, s.name, g.obtained_marks, g.grade, g.remarks
           FROM students s
           LEFT JOIN grades g ON {$gradeJoinCondition} AND g.subject_id = ? AND g.exam_type = ?
           WHERE {$studentClassWhere}
           ORDER BY {$orderBy} ASC";
  $studentsStmt = $conn->prepare( $sql_students);
  if ($studentsStmt) {
    $studentsStmt->bind_param( 'is', $selected_subject, $selected_exam);
    $studentsStmt->execute();
    $result_students = $studentsStmt->get_result();
    if ($result_students) {
      while ($row = $result_students->fetch_assoc()) {
        $students[] = $row;
      }
    }
    $studentsStmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grade Management - The Academic Editorial</title>
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
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Grade Management</h1>
          <p class="text-on-surface-variant font-medium">Assign and track student performance</p>
        </div>
        <div class="flex items-center gap-4">
          <button onclick="document.getElementById('gradeForm').scrollIntoView()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">edit</span>
            Manage Grades
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

    <!-- Grades Management Form -->
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <h3 class="text-sm font-bold text-on-surface">Grade Entry & Management</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
            </span>
            <input type="text" id="searchInput" placeholder="Search students..." class="pl-10 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-sm w-64 focus:ring-2 focus:ring-primary/20">
          </div>
        </div>
      </div>

      <form method="POST" class="p-6 space-y-6" id="gradeForm" novalidate>
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
            <label class="block text-sm font-semibold text-on-surface mb-2">Exam Type</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="exam_type" data-validation="required,select" onchange="this.form.submit()">
              <option value="Mid-term" <?php echo ($selected_exam == 'Mid-term') ? 'selected' : ''; ?>>Mid-term Examination</option>
              <option value="Final" <?php echo ($selected_exam == 'Final') ? 'selected' : ''; ?>>Final Examination</option>
              <option value="Quiz" <?php echo ($selected_exam == 'Quiz') ? 'selected' : ''; ?>>Quiz Assessment</option>
            </select>
            <p id="exam_type_error" class="text-sm text-red-600 hidden"></p>
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

        <!-- Students Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-stone-50/50">
              <tr>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Roll No</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Student Name</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Marks</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Grade</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Remarks</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10">
              <?php foreach ($students as $student): ?>
              <tr>
                <td class="px-6 py-4 font-mono text-xs text-on-surface-variant"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                <td class="px-6 py-4">
                  <span class="text-xs font-bold text-on-surface"><?php echo htmlspecialchars($student['name']); ?></span>
                </td>
                <td class="px-6 py-4">
                  <input type="number" class="w-20 px-3 py-2 bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-center" name="marks_<?php echo $student['id']; ?>" value="<?php echo $student['obtained_marks'] ?? ''; ?>" max="100" min="0" placeholder="0" data-validation="required,number">
                  <p id="marks_<?php echo $student['id']; ?>_error" class="text-xs text-red-600 hidden mt-1"></p>
                </td>
                <td class="px-6 py-4">
                  <?php if (!empty($student['grade'])): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium
                      <?php
                      $grade = $student['grade'];
                      if (in_array($grade, ['A+', 'A'])) echo 'bg-green-100 text-green-800';
                      elseif (in_array($grade, ['B+', 'B'])) echo 'bg-blue-100 text-blue-800';
                      elseif (in_array($grade, ['C+', 'C'])) echo 'bg-yellow-100 text-yellow-800';
                      else echo 'bg-red-100 text-red-800';
                      ?>">
                      <?php echo htmlspecialchars($student['grade']); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-on-surface-variant text-xs">Not graded</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <input type="text" class="w-full px-3 py-2 bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="remarks_<?php echo $student['id']; ?>" value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>" placeholder="Add remarks..." data-validation="max" data-max="200">
                    <p id="remarks_<?php echo $student['id']; ?>_error" class="text-xs text-red-600 hidden mt-1"></p>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (empty($students)): ?>
        <div class="px-6 py-12 text-center">
          <div class="w-12 h-12 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-on-surface-variant text-xl">school</span>
          </div>
          <h3 class="text-sm font-semibold text-on-surface mb-2">No students found</h3>
          <p class="text-on-surface-variant">No students are enrolled in the selected class.</p>
        </div>
        <?php endif; ?>

        <!-- Submit Button -->
        <?php if (!empty($students)): ?>
        <div class="pt-6 border-t border-outline-variant flex justify-between items-center">
          <div class="text-sm text-on-surface-variant">
            Changes will be saved for all students in the selected class, exam, and subject.
          </div>
          <button type="submit" name="save_grades" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold pro-shadow flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Save All Grades
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

    // Auto-calculate grade based on marks
    document.querySelectorAll('input[type="number"]').forEach(input => {
      input.addEventListener('input', function() {
        const row = this.closest('tr');
        const gradeCell = row.querySelector('td:nth-child(4)');

        let grade = '';
        const marks = parseInt(this.value) || 0;
        const percentage = (marks / 100) * 100;
        if (percentage >= 90) grade = 'A+';
        else if (percentage >= 80) grade = 'A';
        else if (percentage >= 70) grade = 'B+';
        else if (percentage >= 60) grade = 'B';
        else if (percentage >= 50) grade = 'C+';
        else if (percentage >= 40) grade = 'C';
        else grade = 'F';

        if (grade) {
          const gradeClass = grade.includes('A') ? 'bg-green-100 text-green-800' :
                           grade.includes('B') ? 'bg-blue-100 text-blue-800' :
                           grade.includes('C') ? 'bg-yellow-100 text-yellow-800' :
                           'bg-red-100 text-red-800';

          gradeCell.innerHTML = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${gradeClass}">${grade}</span>`;
        }
      });
    });
  </script>
</body>
</html>

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
  $safeTable = $conn->real_escape_string($tableName);
  $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

  return $result && $result->num_rows > 0;
}

function teacher_column_exists($conn, $tableName, $columnName)
{
  if (!teacher_table_exists($conn, $tableName)) {
    return false;
  }

  $safeTable = $conn->real_escape_string($tableName);
  $safeColumn = $conn->real_escape_string($columnName);
  $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

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
$studentRollColumn = teacher_first_existing_column($conn, 'students', ['roll_no', 'roll_number']);
$assignmentPointsColumn = teacher_first_existing_column($conn, 'assignments', ['total_points', 'total_marks']);
$assignmentPointsExpr = $assignmentPointsColumn !== null ? "a.`{$assignmentPointsColumn}`" : '0';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: assignments.php");
  exit();
}

$assignment_id = (int)$_GET['id'];

$classNameExpr = $classNameColumn !== null ? "c.`{$classNameColumn}`" : "CONCAT('Class ', c.id)";
$subjectNameExpr = $subjectNameColumn !== null ? "s.`{$subjectNameColumn}`" : "CONCAT('Subject ', s.id)";
$sql_assignment = "SELECT a.*, {$assignmentPointsExpr} AS total_points,
           {$classNameExpr} AS class_name, {$subjectNameExpr} AS subject_name
           FROM assignments a
           JOIN classes c ON a.class_id = c.id
           JOIN subjects s ON a.subject_id = s.id
           WHERE a.id = ? AND a.teacher_id IN ({$teacher_ids_sql})";
$stmt_assignment = $conn->prepare( $sql_assignment);
$stmt_assignment->bind_param( "i", $assignment_id);
$stmt_assignment->execute();
$result_assignment = $stmt_assignment->get_result();
$assignment = $result_assignment->fetch_assoc();
$stmt_assignment->close();

if (!$assignment) {
  header("Location: assignments.php");
  exit();
}

$assignmentMaxPoints = (float) ($assignment['total_points'] ?? 0);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
  $submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
  $grade = isset($_POST['grade']) ? (float)$_POST['grade'] : -1;
  $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

  if ($submission_id > 0 && $grade >= 0 && $grade <= $assignmentMaxPoints) {
    $sql_update = "UPDATE assignment_submissions
             SET marks_obtained = ?, remarks = ?, status = 'graded'
             WHERE id = ? AND assignment_id = ?";
    $stmt_update = $conn->prepare( $sql_update);
    $stmt_update->bind_param( "dsii", $grade, $remarks, $submission_id, $assignment_id);

    if ($stmt_update->execute()) {
      $success = "Grade submitted successfully!";
    } else {
      $error = "Error submitting grade.";
    }

    $stmt_update->close();
  } else {
    $error = "Invalid grade or submission ID.";
  }
}

$rollSelectExpr = $studentRollColumn !== null ? "st.`{$studentRollColumn}` AS roll_no" : "'' AS roll_no";
$class_id = (int)($assignment['class_id'] ?? 0);
$assignmentClassLabel = teacher_auth_class_label_by_id($conn, $class_id);
$studentClassWhere = teacher_auth_student_class_where_sql($conn, 'st', $class_id, $assignmentClassLabel);

$sql_submissions = "SELECT
          st.id as student_id,
          st.name as student_name,
          {$rollSelectExpr},
          sub.id,
          sub.submission_date,
          CASE
            WHEN sub.id IS NULL THEN 'pending'
            WHEN sub.status IS NULL OR sub.status = '' THEN 'submitted'
            ELSE sub.status
          END as status,
          COALESCE(sub.marks_obtained, sub.grade) as grade,
          sub.remarks
          FROM students st
          LEFT JOIN assignment_submissions sub
            ON sub.student_id = st.id
            AND sub.assignment_id = ?
          WHERE {$studentClassWhere}
          ORDER BY (sub.submission_date IS NULL), sub.submission_date DESC, st.name ASC";
$stmt_submissions = $conn->prepare( $sql_submissions);
$stmt_submissions->bind_param( "i", $assignment_id);
$stmt_submissions->execute();
$result_submissions = $stmt_submissions->get_result();
$submissions = [];
while ($row = $result_submissions->fetch_assoc()) {
  $submissions[] = $row;
}
$stmt_submissions->close();

$sql_total_students = "SELECT COUNT(*) as total FROM students st WHERE {$studentClassWhere}";
$stmt_total = $conn->prepare( $sql_total_students);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_students = (int)($result_total->fetch_assoc()['total'] ?? 0);
$stmt_total->close();

$submitted_count = 0;
$pending_count = 0;
$graded_count = 0;
$total_grades = 0;
$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];

foreach ($submissions as $submission) {
  if (!empty($submission['id'])) {
    $submitted_count++;
  } else {
    $pending_count++;
  }

  if ($submission['status'] === 'graded' && $submission['grade'] !== null) {
    $graded_count++;
    $grade_value = (float)$submission['grade'];
    $total_grades += $grade_value;
    $percentage = $assignmentMaxPoints > 0 ? ($grade_value / $assignmentMaxPoints) * 100 : 0;

    if ($percentage >= 90) {
      $grade_distribution['A']++;
    } elseif ($percentage >= 80) {
      $grade_distribution['B']++;
    } elseif ($percentage >= 70) {
      $grade_distribution['C']++;
    } elseif ($percentage >= 60) {
      $grade_distribution['D']++;
    } else {
      $grade_distribution['F']++;
    }
  }
}

if ($total_students > 0 && ($submitted_count + $pending_count) !== $total_students) {
  $pending_count = max(0, $total_students - $submitted_count);
}

$submission_rate = $total_students > 0 ? round(($submitted_count / $total_students) * 100, 1) : 0;

$average_grade = $graded_count > 0 ? round($total_grades / $graded_count, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignment Details - The Academic Editorial</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #003b93;
      --primary-hover: #002a6e;
      --surface: #ffffff;
      --surface-variant: #f8fafc;
      --on-surface: #1e293b;
      --on-surface-variant: #64748b;
      --outline-variant: #e2e8f0;
      --tertiary: #7c3aed;
      --tertiary-hover: #6d28d9;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      min-height: 100vh;
    }

    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .glass-panel {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .pro-shadow {
      box-shadow: 0 8px 32px rgba(0, 59, 147, 0.1), 0 2px 8px rgba(0, 59, 147, 0.08);
    }

    .floating-action {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 1000;
    }
      to { opacity: 1; transform: translateY(0); }
    }

    .assignment-header {
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      border: 1px solid rgba(0, 59, 147, 0.1);
    }

    .grade-badge {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .pending-badge {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }

    .submitted-badge {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }
  </style>
</head>
<body class="text-on-surface">
  <?php include 'sidebar.php'; ?>

  <!-- Floating Quick Actions -->
  <div class="floating-action">
    <div class="relative">
      <button id="fab-main" class="w-14 h-14 bg-primary hover:bg-primary-hover rounded-full shadow-lg flex items-center justify-center text-white pro-shadow">
        <span class="material-symbols-outlined">assignment</span>
      </button>

      <div id="fab-menu" class="absolute bottom-16 right-0 space-y-3 opacity-0 scale-95 pointer-events-none">
        <button onclick="exportGrades()" class="w-12 h-12 bg-tertiary hover:bg-tertiary-hover rounded-full shadow-lg flex items-center justify-center text-white pro-shadow">
          <span class="material-symbols-outlined text-sm">download</span>
        </button>
        <button onclick="bulkGrade()" class="w-12 h-12 bg-primary hover:bg-primary-hover rounded-full shadow-lg flex items-center justify-center text-white pro-shadow">
          <span class="material-symbols-outlined text-sm">grade</span>
        </button>
        <button onclick="shareAssignment()" class="w-12 h-12 bg-surface hover:bg-surface-variant rounded-full shadow-lg flex items-center justify-center text-on-surface pro-shadow border border-outline-variant">
          <span class="material-symbols-outlined text-sm">share</span>
        </button>
      </div>
    </div>
  </div>

  <main class="ml-64 min-h-screen p-6">
    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex justify-between items-start mb-6">
        <div>
          <h1 class="text-3xl font-bold text-on-surface mb-2">Assignment Details</h1>
          <p class="text-on-surface-variant">Review submissions and manage grades for this assignment</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="glass-panel rounded-xl p-4 pro-shadow">
            <div class="text-sm text-on-surface-variant">Due Date</div>
            <div class="text-lg font-semibold text-on-surface"><?php echo date('M j, Y', strtotime($assignment['due_date'])); ?></div>
          </div>
          <a href="assignments.php" class="bg-surface hover:bg-surface-variant border border-outline-variant text-on-surface font-medium px-6 py-2.5 rounded-xl flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Back to Assignments
          </a>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
    <div class="mb-6 glass-panel rounded-xl p-4 pro-shadow border-l-4 border-green-500">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
          <span class="material-symbols-outlined text-green-600">check_circle</span>
        </div>
        <div>
          <p class="text-sm font-semibold text-green-800"><?php echo $success; ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="mb-6 glass-panel rounded-xl p-4 pro-shadow border-l-4 border-red-500">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
          <span class="material-symbols-outlined text-red-600">error</span>
        </div>
        <div>
          <p class="text-sm font-semibold text-red-800"><?php echo $error; ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Assignment Header -->
    <div class="assignment-header rounded-xl p-8 pro-shadow mb-8">
      <div class="flex items-start justify-between mb-6">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
              <span class="material-symbols-outlined text-primary">assignment</span>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-on-surface mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h1>
              <div class="flex items-center gap-6 text-sm text-on-surface-variant">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm">school</span>
                  <span><?php echo htmlspecialchars($assignment['class_name']); ?></span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm">subject</span>
                  <span><?php echo htmlspecialchars($assignment['subject_name']); ?></span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm">event</span>
                  <span><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                </div>
              </div>
            </div>
          </div>

          <?php if (!empty($assignment['description'])): ?>
          <div class="glass-panel rounded-xl p-6">
            <h3 class="text-lg font-semibold text-on-surface mb-3">Assignment Description</h3>
            <p class="text-on-surface-variant leading-relaxed"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
          </div>
          <?php endif; ?>
        </div>

        <div class="text-right">
          <div class="text-4xl font-bold text-primary mb-1"><?php echo $assignment['total_points']; ?></div>
          <div class="text-sm text-on-surface-variant">Total Points</div>
        </div>
      </div>
    </div>

    <!-- Enhanced Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">people</span>
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
          <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">check_circle</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $submitted_count; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Submitted</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $submission_rate; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">grade</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $graded_count; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Graded</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $submitted_count > 0 ? ($graded_count / $submitted_count) * 100 : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">analytics</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $average_grade; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Avg Grade</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $assignmentMaxPoints > 0 ? min(100, ($average_grade / $assignmentMaxPoints) * 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">schedule</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $pending_count; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Pending</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-orange-500 h-2 rounded-full" style="width: <?php echo $total_students > 0 ? ($pending_count / $total_students) * 100 : 0; ?>%"></div>
        </div>
      </div>
    </div>

    <!-- Grade Distribution Chart -->
    <?php if ($graded_count > 0): ?>
    <div class="glass-panel rounded-xl pro-shadow mb-8">
      <div class="px-6 py-5 border-b border-outline-variant">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-tertiary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-tertiary">bar_chart</span>
          </div>
          <div>
            <h2 class="text-xl font-bold text-on-surface">Grade Distribution</h2>
            <p class="text-on-surface-variant">Performance breakdown of graded submissions</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <canvas id="gradeChart" width="400" height="200"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <!-- Submissions Table -->
    <div class="glass-panel rounded-xl pro-shadow">
      <div class="px-6 py-5 border-b border-outline-variant">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
              <span class="material-symbols-outlined text-primary">assignment_turned_in</span>
            </div>
            <div>
              <h2 class="text-xl font-bold text-on-surface">Student Submissions</h2>
              <p class="text-on-surface-variant">Review and grade student submissions</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <select id="statusFilter" class="px-4 py-2 bg-surface border border-outline-variant rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
              <option value="all">All Submissions</option>
              <option value="graded">Graded</option>
              <option value="pending">Pending</option>
            </select>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-surface-variant/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Student</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Roll No</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Submission Date</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Grade</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Remarks</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant">
            <?php if (count($submissions) > 0): ?>
              <?php foreach ($submissions as $submission): ?>
              <tr class="hover:bg-surface-variant/30 submission-row" data-status="<?php echo $submission['status']; ?>">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                      <span class="material-symbols-outlined text-primary text-sm">person</span>
                    </div>
                    <div>
                      <div class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($submission['student_name']); ?></div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-on-surface"><?php echo htmlspecialchars($submission['roll_no']); ?></td>
                <td class="px-6 py-4 text-sm text-on-surface">
                  <?php echo !empty($submission['submission_date']) ? date('M d, Y H:i', strtotime($submission['submission_date'])) : '-'; ?>
                </td>
                <td class="px-6 py-4">
                  <?php
                  $status_class = '';
                  $status_text = '';
                  switch ($submission['status']) {
                    case 'submitted':
                      $status_class = 'submitted-badge';
                      $status_text = 'Submitted';
                      break;
                    case 'graded':
                      $status_class = 'grade-badge';
                      $status_text = 'Graded';
                      break;
                    default:
                      $status_class = 'pending-badge';
                      $status_text = 'Pending';
                      break;
                  }
                  ?>
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                    <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">
                      <?php echo $submission['status'] === 'graded' ? 'check_circle' : ($submission['status'] === 'submitted' ? 'assignment_turned_in' : 'schedule'); ?>
                    </span>
                    <?php echo $status_text; ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-on-surface">
                  <?php echo $submission['grade'] !== null ? $submission['grade'] . '/' . $assignment['total_points'] : 'Not graded'; ?>
                </td>
                <td class="px-6 py-4 text-sm text-on-surface max-w-xs truncate">
                  <?php echo !empty($submission['id']) ? htmlspecialchars($submission['remarks'] ?? 'No remarks') : 'No submission'; ?>
                </td>
                <td class="px-6 py-4">
                  <?php if (!empty($submission['id'])): ?>
                  <button onclick="gradeSubmission(<?php echo $submission['id']; ?>, '<?php echo addslashes($submission['student_name']); ?>', <?php echo $submission['grade'] ?? 0; ?>, '<?php echo addslashes($submission['remarks'] ?? ''); ?>')" class="bg-primary hover:bg-primary-hover text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">
                      <?php echo $submission['status'] == 'graded' ? 'edit' : 'grade'; ?>
                    </span>
                    <?php echo $submission['status'] == 'graded' ? 'Update' : 'Grade'; ?>
                  </button>
                  <?php else: ?>
                  <span class="text-xs text-on-surface-variant">Not submitted</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center">
                <div class="w-16 h-16 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
                  <span class="material-symbols-outlined text-on-surface-variant text-2xl">assignment</span>
                </div>
                <h3 class="text-lg font-semibold text-on-surface mb-2">No submissions yet</h3>
                <p class="text-on-surface-variant">Students haven't submitted this assignment yet.</p>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Grade Submission Modal -->
  <div id="gradeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 pointer-events-none">
    <div class="glass-panel rounded-2xl p-8 w-full max-w-md mx-4 pro-shadow">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-on-surface">Grade Submission</h3>
        <button onclick="closeGradeModal()" class="w-8 h-8 bg-surface-variant rounded-full flex items-center justify-center text-on-surface-variant hover:bg-outline-variant">
          <span class="material-symbols-outlined text-sm">close</span>
        </button>
      </div>

      <div class="mb-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">person</span>
          </div>
          <div>
            <p class="text-sm text-on-surface-variant">Student</p>
            <p class="text-lg font-semibold text-on-surface" id="gradeStudentName"></p>
          </div>
        </div>
      </div>

      <form method="POST" class="space-y-6" novalidate>
        <input type="hidden" name="submission_id" id="gradeSubmissionId">

        <div>
          <label class="block text-sm font-semibold text-on-surface mb-2">Grade (out of <?php echo $assignment['total_points']; ?>)</label>
          <input type="number" name="grade" id="gradeInput" step="0.5" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" data-validation="required,number">
          <p id="grade_error" class="text-sm text-red-600 hidden"></p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-on-surface mb-2">Remarks (Optional)</label>
          <textarea name="remarks" id="gradeRemarks" rows="4" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus-border-primary" placeholder="Add feedback or comments..." data-validation="max" data-max="300"></textarea>
          <p id="remarks_error" class="text-sm text-red-600 hidden"></p>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" onclick="closeGradeModal()" class="px-6 py-2.5 border border-outline-variant rounded-xl text-sm font-medium text-on-surface hover:bg-surface-variant">
            Cancel
          </button>
          <button type="submit" name="grade_submission" class="px-6 py-2.5 bg-primary hover:bg-primary-hover rounded-xl text-sm font-medium text-white pro-shadow">
            Submit Grade
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
  <script>
    // Grade Distribution Chart
    <?php if ($graded_count > 0): ?>
    const gradeCtx = document.getElementById('gradeChart').getContext('2d');
    new Chart(gradeCtx, {
      type: 'bar',
      data: {
        labels: ['A (90-100%)', 'B (80-89%)', 'C (70-79%)', 'D (60-69%)', 'F (0-59%)'],
        datasets: [{
          label: 'Number of Students',
          data: [<?php echo implode(',', array_values($grade_distribution)); ?>],
          backgroundColor: [
            'rgba(16, 185, 129, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(245, 101, 101, 0.8)',
            'rgba(156, 163, 175, 0.8)'
          ],
          borderColor: [
            '#10b981',
            '#3b82f6',
            '#f59e0b',
            '#f56565',
            '#9ca3af'
          ],
          borderWidth: 1,
          borderRadius: 8,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 59, 147, 0.1)'
            },
            ticks: {
              stepSize: 1
            }
          },
          x: {
            grid: {
              color: 'rgba(0, 59, 147, 0.1)'
            }
          }
        },
        elements: {
          bar: {
            borderRadius: 8
          }
        }
      }
    });
    <?php endif; ?>

    // Floating Action Button functionality
    const fabMain = document.getElementById('fab-main');
    const fabMenu = document.getElementById('fab-menu');
    let fabOpen = false;

    fabMain.addEventListener('click', () => {
      fabOpen = !fabOpen;
      if (fabOpen) {
        fabMenu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
        fabMenu.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
      } else {
        fabMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
      }
    });

    function gradeSubmission(submissionId, studentName, currentGrade, currentRemarks) {
      document.getElementById('gradeSubmissionId').value = submissionId;
      document.getElementById('gradeStudentName').textContent = studentName;
      document.getElementById('gradeInput').value = currentGrade;
      document.getElementById('gradeRemarks').value = currentRemarks;
      document.getElementById('gradeModal').classList.remove('opacity-0', 'pointer-events-none');
      document.getElementById('gradeModal').classList.add('opacity-100', 'pointer-events-auto');
    }

    function closeGradeModal() {
      document.getElementById('gradeModal').classList.add('opacity-0', 'pointer-events-none');
      document.getElementById('gradeModal').classList.remove('opacity-100', 'pointer-events-auto');
    }

    function exportGrades() {
      alert('Grade export feature coming soon in Pro Edition!');
    }

    function bulkGrade() {
      alert('Bulk grading feature coming soon in Pro Edition!');
    }

    function shareAssignment() {
      alert('Assignment sharing feature coming soon in Pro Edition!');
    }

    // Status filter functionality
    document.getElementById('statusFilter').addEventListener('change', function() {
      const filterValue = this.value;
      const rows = document.querySelectorAll('.submission-row');

      rows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (filterValue === 'all' || status === filterValue) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });

    // Close modal when clicking outside
    document.getElementById('gradeModal').addEventListener('click', function(event) {
      if (event.target === this) {
        closeGradeModal();
      }
    });

    // Close FAB menu when clicking outside
    document.addEventListener('click', function(event) {
      if (event.target === fabMenu) {
        fabMain.click();
      }
    });
  </script>
</body>
</html>


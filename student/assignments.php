<?php
// Include auth checks
require_once __DIR__ . '/auth.php';

// Resolve student context
$studentContext = student_auth_context();
$studentId = (int) ($studentContext['student_id'] ?? 0);
$studentUserId = (int) ($studentContext['user_id'] ?? 0);

// Check table existence
function student_page_table_exists($conn, $tableName)
{
    $safeTable = $conn->real_escape_string($tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

    return $result && $result->num_rows > 0;
}

  // Check column existence
function student_page_column_exists($conn, $tableName, $columnName)
{
    if (!student_page_table_exists($conn, $tableName)) {
        return false;
    }

    $safeTable = $conn->real_escape_string($tableName);
    $safeColumn = $conn->real_escape_string($columnName);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

    return $result && $result->num_rows > 0;
}

$studentClassId = 0;
$studentClassLabel = '';
// Build submission filter
$submissionFilter = student_auth_student_id_filter_sql('sub.student_id');

// Resolve student class
if (student_page_table_exists($conn, 'students') && $studentId > 0) {
    $studentStmt = $conn->prepare('SELECT class_id, class FROM students WHERE id = ? LIMIT 1');
    if ($studentStmt) {
        $studentStmt->bind_param('i', $studentId);
        $studentStmt->execute();
        $studentResult = $studentStmt->get_result();
        $studentRow = $studentResult ? $studentResult->fetch_assoc() : null;
        if ($studentRow) {
            $studentClassId = (int) ($studentRow['class_id'] ?? 0);
            $studentClassLabel = trim((string) ($studentRow['class'] ?? ''));
        }
        $studentStmt->close();
    }
}

  // Try user fallback
    if ($studentClassId <= 0 && $studentUserId > 0 && $studentUserId !== $studentId && student_page_table_exists($conn, 'students')) {
      if (student_page_column_exists($conn, 'students', 'user_id')) {
        $studentByUserStmt = $conn->prepare('SELECT class_id, class FROM students WHERE user_id = ? LIMIT 1');
      } else {
        $studentByUserStmt = $conn->prepare('SELECT class_id, class FROM students WHERE id = ? LIMIT 1');
      }

      if ($studentByUserStmt) {
        $studentByUserStmt->bind_param('i', $studentUserId);
        $studentByUserStmt->execute();
        $studentByUserResult = $studentByUserStmt->get_result();
        $studentByUserRow = $studentByUserResult ? $studentByUserResult->fetch_assoc() : null;
        if ($studentByUserRow) {
          $studentClassId = (int) ($studentByUserRow['class_id'] ?? 0);
          $studentClassLabel = trim((string) ($studentByUserRow['class'] ?? $studentClassLabel));
        }
        $studentByUserStmt->close();
      }
    }

  // Build subject expression
$subjectNameExpr = "''";
if (student_page_column_exists($conn, 'subjects', 'name') && student_page_column_exists($conn, 'subjects', 'subject_name')) {
    $subjectNameExpr = "COALESCE(NULLIF(s.name, ''), s.subject_name, CONCAT('Subject ', s.id))";
} elseif (student_page_column_exists($conn, 'subjects', 'name')) {
    $subjectNameExpr = 's.name';
} elseif (student_page_column_exists($conn, 'subjects', 'subject_name')) {
    $subjectNameExpr = 's.subject_name';
}

// Build class expression
$classNameExpr = "CONCAT('Class ', c.id)";
if (student_page_column_exists($conn, 'classes', 'name') && student_page_column_exists($conn, 'classes', 'class_name')) {
  $classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
} elseif (student_page_column_exists($conn, 'classes', 'name')) {
  $classNameExpr = 'c.name';
} elseif (student_page_column_exists($conn, 'classes', 'class_name')) {
  $classNameExpr = 'c.class_name';
}

// Build points expression
$pointsExpr = '0';
if (student_page_column_exists($conn, 'assignments', 'total_points')) {
  $pointsExpr = 'a.total_points';
} elseif (student_page_column_exists($conn, 'assignments', 'total_marks')) {
  $pointsExpr = 'a.total_marks';
}

$assignments = [];

// Load assignment rows
if (
    student_page_table_exists($conn, 'assignments')
    && student_page_table_exists($conn, 'assignment_submissions')
) {
    $whereClause = 'sub.student_id IS NOT NULL';
  $types = (string) ($submissionFilter['types'] ?? '');
  $params = (array) ($submissionFilter['params'] ?? []);

    if ($studentClassId > 0 && student_page_column_exists($conn, 'assignments', 'class_id')) {
        $whereClause = 'a.class_id = ?';
        $types .= 'i';
        $params[] = $studentClassId;
    } elseif ($studentClassLabel !== '') {
        $normalizedClass = strtolower(str_replace([' ', '-'], '', $studentClassLabel));
    $whereClause = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', '')) = ?";
        $types .= 's';
        $params[] = $normalizedClass;
    }

    $sql = "SELECT
                a.id,
                a.title,
                a.description,
                a.due_date,
        {$pointsExpr} AS total_points,
        {$classNameExpr} AS class_name,
                {$subjectNameExpr} AS subject_name,
                sub.id AS submission_id,
                sub.submission_date,
                sub.status AS submission_status,
                COALESCE(sub.marks_obtained, sub.grade) AS marks_obtained,
                sub.remarks
            FROM assignments a
            LEFT JOIN classes c ON a.class_id = c.id
            LEFT JOIN subjects s ON a.subject_id = s.id
            LEFT JOIN assignment_submissions sub
              ON sub.assignment_id = a.id
       AND {$submissionFilter['sql']}
            WHERE {$whereClause}
            ORDER BY a.due_date DESC, a.id DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
    // Bind dynamic params
    $queryParams = $params;
    if (!student_auth_bind_dynamic_params($stmt, $types, $queryParams)) {
      $stmt->close();
      $stmt = null;
    }
  }

  if ($stmt) {
      // Collect assignment data
        $stmt->execute();
        $result = $stmt->get_result();
        while ($result && ($row = $result->fetch_assoc())) {
            $assignments[] = $row;
        }
        $stmt->close();
    }
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'submitted' => 0,
    'graded' => 0,
];

// Compute dashboard stats
foreach ($assignments as $assignment) {
    $stats['total']++;

    if (empty($assignment['submission_id'])) {
        $stats['pending']++;
        continue;
    }

    $status = strtolower(trim((string) ($assignment['submission_status'] ?? 'submitted')));
    $hasMarks = $assignment['marks_obtained'] !== null && $assignment['marks_obtained'] !== '';

    if ($status === 'graded' || $hasMarks) {
        $stats['graded']++;
    } else {
        $stats['submitted']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignments - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-indigo-500" style="font-variation-settings: 'FILL' 1;">assignment</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Assignments</h1>
        <p class="text-sm text-stone-500">View all assignments for your class and submission status</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Total Assignments</p>
        <p class="text-3xl font-bold text-indigo-600"><?php echo (int) $stats['total']; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Pending</p>
        <p class="text-3xl font-bold text-amber-600"><?php echo (int) $stats['pending']; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Submitted</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo (int) $stats['submitted']; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Graded</p>
        <p class="text-3xl font-bold text-emerald-600"><?php echo (int) $stats['graded']; ?></p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">table_chart</span>
          Assignment List
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Title</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Subject</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Class</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Due Date</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Points</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Status</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Marks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (!empty($assignments)): ?>
              <?php foreach ($assignments as $assignment): ?>
                <?php
                  $isSubmitted = !empty($assignment['submission_id']);
                  $status = strtolower(trim((string) ($assignment['submission_status'] ?? 'pending')));
                  $hasMarks = $assignment['marks_obtained'] !== null && $assignment['marks_obtained'] !== '';

                  if (!$isSubmitted) {
                    $badgeClass = 'bg-amber-100 text-amber-700';
                    $label = 'Pending';
                  } elseif ($hasMarks || $status === 'graded') {
                    $badgeClass = 'bg-emerald-100 text-emerald-700';
                    $label = 'Graded';
                  } else {
                    $badgeClass = 'bg-blue-100 text-blue-700';
                    $label = 'Submitted';
                  }
                ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-stone-900"><?php echo htmlspecialchars((string) ($assignment['title'] ?? '-')); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars((string) ($assignment['subject_name'] ?? '-')); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars((string) ($assignment['class_name'] ?? '-')); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo !empty($assignment['due_date']) ? date('d M Y', strtotime((string) $assignment['due_date'])) : '-'; ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo (int) ($assignment['total_points'] ?? 0); ?></td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $badgeClass; ?>">
                      <?php echo $label; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-stone-700">
                    <?php echo $hasMarks ? htmlspecialchars((string) $assignment['marks_obtained']) : '-'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-8 text-center text-stone-500">No assignments found for your class</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>

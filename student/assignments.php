<?php
// Include auth checks
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/assignment_file_helper.php';

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
$successMessage = isset($_SESSION['assignment_success']) ? (string) $_SESSION['assignment_success'] : '';
$errorMessage = isset($_SESSION['assignment_error']) ? (string) $_SESSION['assignment_error'] : '';
unset($_SESSION['assignment_success'], $_SESSION['assignment_error']);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_submission'])) {
  $assignmentId = isset($_POST['assignment_id']) ? (int) $_POST['assignment_id'] : 0;
  $activeStudentId = $studentId > 0 ? $studentId : $studentUserId;
  $submissionUploadProvided = isset($_FILES['submission_file'])
    && (int) ($_FILES['submission_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

  if ($assignmentId <= 0) {
    $_SESSION['assignment_error'] = 'Invalid assignment selected.';
  } elseif ($activeStudentId <= 0) {
    $_SESSION['assignment_error'] = 'Student profile not found.';
  } elseif (!$submissionUploadProvided) {
    $_SESSION['assignment_error'] = 'Please choose a file to upload.';
  } else {
    $accessWhere = '';
    $accessTypes = 'i';
    $accessParams = [$assignmentId];

    if ($studentClassId > 0 && student_page_column_exists($conn, 'assignments', 'class_id')) {
      $accessWhere = 'a.class_id = ?';
      $accessTypes .= 'i';
      $accessParams[] = $studentClassId;
    } elseif ($studentClassLabel !== '') {
      $normalizedClass = strtolower(str_replace([' ', '-'], '', $studentClassLabel));
      $accessWhere = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', '')) = ?";
      $accessTypes .= 's';
      $accessParams[] = $normalizedClass;
    }

    if ($accessWhere === '') {
      $_SESSION['assignment_error'] = 'Your class information is missing. Please contact admin.';
    } else {
      $accessSql = "SELECT a.id, a.due_date
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.id = ?
          AND {$accessWhere}
        LIMIT 1";
      $accessStmt = $conn->prepare($accessSql);

      if (!$accessStmt || !student_auth_bind_dynamic_params($accessStmt, $accessTypes, $accessParams)) {
        if ($accessStmt) {
          $accessStmt->close();
        }
        $_SESSION['assignment_error'] = 'Unable to validate assignment access.';
      } else {
        $accessStmt->execute();
        $accessResult = $accessStmt->get_result();
        $assignmentRow = $accessResult ? $accessResult->fetch_assoc() : null;
        $accessStmt->close();

        if (!$assignmentRow) {
          $_SESSION['assignment_error'] = 'Assignment not found for your class.';
        } else {
          $uploadResult = assignment_file_save_upload((array) $_FILES['submission_file'], 'student_submission', $activeStudentId, $assignmentId);
          if (!($uploadResult['ok'] ?? false)) {
            $_SESSION['assignment_error'] = (string) ($uploadResult['error'] ?? 'Unable to upload submission file.');
          } else {
            $uploadedPath = (string) ($uploadResult['relative_path'] ?? '');
            $dueDate = (string) ($assignmentRow['due_date'] ?? '');
            $submissionStatus = 'submitted';
            if ($dueDate !== '' && strtotime($dueDate) < strtotime(date('Y-m-d'))) {
              $submissionStatus = 'late';
            }

            $existingSubmission = null;
            $existingFilter = student_auth_student_id_filter_sql('student_id');
            $existingSql = "SELECT id, file_path
              FROM assignment_submissions
              WHERE assignment_id = ?
                AND {$existingFilter['sql']}
              ORDER BY id DESC
              LIMIT 1";
            $existingStmt = $conn->prepare($existingSql);

            if ($existingStmt) {
              $existingTypes = 'i' . (string) $existingFilter['types'];
              $existingParams = array_merge([$assignmentId], (array) $existingFilter['params']);
              if (student_auth_bind_dynamic_params($existingStmt, $existingTypes, $existingParams)) {
                $existingStmt->execute();
                $existingResult = $existingStmt->get_result();
                $existingSubmission = $existingResult ? $existingResult->fetch_assoc() : null;
              }
              $existingStmt->close();
            }

            if ($existingSubmission) {
              $submissionId = (int) ($existingSubmission['id'] ?? 0);
              $oldPath = trim((string) ($existingSubmission['file_path'] ?? ''));

              $updateStmt = $conn->prepare('UPDATE assignment_submissions SET file_path = ?, submission_date = NOW(), status = ? WHERE id = ?');
              if ($updateStmt) {
                $updateStmt->bind_param('ssi', $uploadedPath, $submissionStatus, $submissionId);
                if ($updateStmt->execute()) {
                  if ($oldPath !== '' && $oldPath !== $uploadedPath) {
                    assignment_file_delete($oldPath);
                  }
                  $_SESSION['assignment_success'] = 'Submission updated successfully.';
                } else {
                  assignment_file_delete($uploadedPath);
                  $_SESSION['assignment_error'] = 'Unable to save submission.';
                }
                $updateStmt->close();
              } else {
                assignment_file_delete($uploadedPath);
                $_SESSION['assignment_error'] = 'Unable to update submission right now.';
              }
            } else {
              $insertStmt = $conn->prepare('INSERT INTO assignment_submissions (assignment_id, student_id, file_path, status, submission_date) VALUES (?, ?, ?, ?, NOW())');
              if ($insertStmt) {
                $insertStmt->bind_param('iiss', $assignmentId, $activeStudentId, $uploadedPath, $submissionStatus);
                if ($insertStmt->execute()) {
                  $_SESSION['assignment_success'] = 'Submission uploaded successfully.';
                } else {
                  assignment_file_delete($uploadedPath);
                  $_SESSION['assignment_error'] = 'Unable to save submission.';
                }
                $insertStmt->close();
              } else {
                assignment_file_delete($uploadedPath);
                $_SESSION['assignment_error'] = 'Unable to save submission right now.';
              }
            }
          }
        }
      }
    }
  }

  header('Location: ' . $_SERVER['PHP_SELF']);
  exit();
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
      a.file_path AS assignment_file_path,
        {$pointsExpr} AS total_points,
        {$classNameExpr} AS class_name,
                {$subjectNameExpr} AS subject_name,
                sub.id AS submission_id,
        sub.file_path AS submission_file_path,
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
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', sans-serif;
    }

    .assignment-file-input {
      width: 100%;
      font-size: 0.75rem;
      color: #57534e;
      border: 1px solid #d6d3d1;
      border-radius: 0.5rem;
      background: #ffffff;
      padding: 0.4rem 0.5rem;
    }

    .assignment-file-input::file-selector-button {
      border: 0;
      border-radius: 0.45rem;
      margin-right: 0.55rem;
      padding: 0.35rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 600;
      background: #e7e5e4;
      color: #44403c;
      cursor: pointer;
    }

    .assignment-file-input:hover::file-selector-button {
      background: #d6d3d1;
    }
  </style>
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-8 lg:pt-8">
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-blue-600" style="font-variation-settings: 'FILL' 1;">assignment</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Assignments</h1>
        <p class="text-sm text-stone-500">View all assignments for your class and submission status</p>
      </div>
    </div>

    <?php if ($successMessage !== ''): ?>
    <div class="mb-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
      <span class="material-symbols-outlined text-base">check_circle</span>
      <span><?php echo htmlspecialchars($successMessage); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
    <div class="mb-6 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
      <span class="material-symbols-outlined text-base">error</span>
      <span><?php echo htmlspecialchars($errorMessage); ?></span>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Total Assignments</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo (int) $stats['total']; ?></p>
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
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Assignment File</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Submission</th>
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
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-base text-blue-600" style="font-variation-settings: 'FILL' 1;">task</span>
                      <span class="text-sm font-medium text-stone-900"><?php echo htmlspecialchars((string) ($assignment['title'] ?? '-')); ?></span>
                    </div>
                  </td>
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
                  <td class="px-6 py-4 text-sm text-stone-700">
                    <?php if (!empty($assignment['assignment_file_path'])): ?>
                    <a href="../download-assignment-file.php?type=assignment&amp;id=<?php echo (int) $assignment['id']; ?>" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                      <span class="material-symbols-outlined text-sm">download</span>
                      Download
                    </a>
                    <?php else: ?>
                    <span class="text-xs text-stone-500">No file</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-stone-700">
                    <div class="flex flex-col gap-2">
                      <?php if (!empty($assignment['submission_file_path'])): ?>
                      <a href="../download-assignment-file.php?type=submission&amp;id=<?php echo (int) $assignment['submission_id']; ?>" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                        <span class="material-symbols-outlined text-sm">download</span>
                        My Submission
                      </a>
                      <?php endif; ?>

                      <form method="POST" enctype="multipart/form-data" class="rounded-lg border border-stone-200 bg-stone-50 p-2.5 space-y-2 min-w-[18rem]">
                        <input type="hidden" name="assignment_id" value="<?php echo (int) $assignment['id']; ?>">
                        <p class="text-[11px] font-medium text-stone-500"><?php echo !empty($assignment['submission_id']) ? 'Update your submission file' : 'Upload your submission file'; ?></p>
                        <input type="file" name="submission_file" required data-validation="required,fileType" data-filetype="pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,zip,rar" class="assignment-file-input" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.zip,.rar">
                        <button type="submit" name="upload_submission" class="inline-flex w-full items-center justify-center gap-1 rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                          <span class="material-symbols-outlined text-sm">upload</span>
                          <?php echo !empty($assignment['submission_id']) ? 'Re-upload' : 'Upload'; ?>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="px-6 py-8 text-center text-stone-500">No assignments found for your class</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

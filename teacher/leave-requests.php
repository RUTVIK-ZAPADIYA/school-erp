<?php
require_once __DIR__ . '/auth.php';
include '../includes/db_connect.php';

$teacherContext = teacher_auth_resolve_context($conn);
$teacherUserId = (int) ($teacherContext['user_id'] ?? 0);
$teacherIds = teacher_auth_sanitize_ids((array) ($teacherContext['teacher_ids'] ?? [$teacherUserId]));
if (empty($teacherIds)) {
  $teacherIds = [0];
}
$teacherIdPlaceholders = implode(',', array_fill(0, count($teacherIds), '?'));
$teacherIdTypes = str_repeat('i', count($teacherIds));

function teacher_leave_table_exists($conn, $tableName)
{
  $safeTable = $conn->real_escape_string($tableName);
  $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

  return $result && $result->num_rows > 0;
}

function teacher_leave_column_exists($conn, $tableName, $columnName)
{
  if (!teacher_leave_table_exists($conn, $tableName)) {
    return false;
  }

  $safeTable = $conn->real_escape_string($tableName);
  $safeColumn = $conn->real_escape_string($columnName);
  $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

  return $result && $result->num_rows > 0;
}

function teacher_leave_bind_dynamic_params($stmt, $types, array &$params)
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

function teacher_leave_first_existing_column($conn, $tableName, array $candidates)
{
  foreach ($candidates as $candidate) {
    if (teacher_leave_column_exists($conn, $tableName, $candidate)) {
      return $candidate;
    }
  }

  return null;
}

function teacher_leave_ensure_column($conn, $tableName, $columnName, $definition)
{
  if (function_exists('ensure_school_erp_column')) {
    ensure_school_erp_column($conn, $tableName, $columnName, $definition);
    return;
  }

  if (!teacher_leave_column_exists($conn, $tableName, $columnName)) {
    $conn->query("ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
  }
}

function teacher_leave_time_label($dateValue)
{
  $timestamp = strtotime((string) $dateValue);
  if ($timestamp === false) {
    return '-';
  }

  return date('d M Y', $timestamp);
}

$successMessage = '';
$errorMessage = '';

if (teacher_leave_table_exists($conn, 'leave_applications')) {
  teacher_leave_ensure_column($conn, 'leave_applications', 'status', "VARCHAR(20) DEFAULT 'pending'");
  teacher_leave_ensure_column($conn, 'leave_applications', 'teacher_remark', 'TEXT NULL');
  teacher_leave_ensure_column($conn, 'leave_applications', 'reviewed_by_teacher_id', 'INT NULL');
  teacher_leave_ensure_column($conn, 'leave_applications', 'reviewed_at', 'DATETIME NULL');
}

$ownedStudentsByProfileId = [];
$ownedStudentsByUserId = [];
$ownedCandidateIds = [];

if (
  teacher_leave_table_exists($conn, 'students')
  && teacher_leave_table_exists($conn, 'classes')
  && teacher_leave_column_exists($conn, 'classes', 'teacher_id')
  && teacher_leave_column_exists($conn, 'students', 'name')
  && (teacher_leave_column_exists($conn, 'students', 'class_id') || teacher_leave_column_exists($conn, 'students', 'class'))
) {
  $classNameExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
  $rollColumn = teacher_leave_first_existing_column($conn, 'students', ['roll_no', 'roll_number']);
  $rollExpr = $rollColumn !== null ? "COALESCE(s.`{$rollColumn}`, '')" : "''";

  $joinParts = [];
  $hasStudentClassId = teacher_leave_column_exists($conn, 'students', 'class_id');
  if ($hasStudentClassId) {
    $joinParts[] = 's.class_id = c.id';
  }

  if (teacher_leave_column_exists($conn, 'students', 'class')) {
    $studentClassNorm = "LOWER(REPLACE(REPLACE(TRIM(COALESCE(s.`class`, '')), ' ', ''), '-', ''))";
    $classNameNorm = "LOWER(REPLACE(REPLACE(TRIM({$classNameExpr}), ' ', ''), '-', ''))";
    $fallbackCondition = "({$studentClassNorm} <> '' AND {$studentClassNorm} = {$classNameNorm})";
    if ($hasStudentClassId) {
      $fallbackCondition = "(COALESCE(s.class_id, 0) = 0 AND {$fallbackCondition})";
    }
    $joinParts[] = $fallbackCondition;
  }

  $joinSql = empty($joinParts) ? '1 = 0' : implode(' OR ', $joinParts);

  $ownedStudentSql = "SELECT DISTINCT s.id, COALESCE(NULLIF(s.user_id, 0), 0) AS linked_user_id,
      COALESCE(NULLIF(s.name, ''), CONCAT('Student ', s.id)) AS student_name,
      {$rollExpr} AS roll_no,
      {$classNameExpr} AS class_name
    FROM students s
    INNER JOIN classes c ON ({$joinSql})
    WHERE c.teacher_id IN ({$teacherIdPlaceholders})
    ORDER BY student_name ASC";

  $ownedStmt = $conn->prepare($ownedStudentSql);
  if ($ownedStmt) {
    $ownedParams = $teacherIds;
    if (teacher_leave_bind_dynamic_params($ownedStmt, $teacherIdTypes, $ownedParams) && $ownedStmt->execute()) {
      $ownedResult = $ownedStmt->get_result();
      while ($ownedResult && ($ownedRow = $ownedResult->fetch_assoc())) {
        $profileId = (int) ($ownedRow['id'] ?? 0);
        $userId = (int) ($ownedRow['linked_user_id'] ?? 0);

        if ($profileId <= 0) {
          continue;
        }

        $studentInfo = [
          'student_name' => (string) ($ownedRow['student_name'] ?? 'Student'),
          'roll_no' => (string) ($ownedRow['roll_no'] ?? ''),
          'class_name' => (string) ($ownedRow['class_name'] ?? '-'),
        ];

        $ownedStudentsByProfileId[$profileId] = $studentInfo;
        $ownedCandidateIds[] = $profileId;

        if ($userId > 0) {
          $ownedStudentsByUserId[$userId] = $studentInfo;
          $ownedCandidateIds[] = $userId;
        }
      }
    }
    $ownedStmt->close();
  }
}

$uniqueOwnedIds = [];
foreach ($ownedCandidateIds as $candidateId) {
  $candidateId = (int) $candidateId;
  if ($candidateId <= 0 || in_array($candidateId, $uniqueOwnedIds, true)) {
    continue;
  }
  $uniqueOwnedIds[] = $candidateId;
}
$ownedCandidateIds = $uniqueOwnedIds;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = strtolower(trim((string) ($_POST['action'] ?? '')));
  $leaveId = (int) ($_POST['leave_id'] ?? 0);
  $teacherRemark = trim((string) ($_POST['teacher_remark'] ?? ''));

  $remarkLength = function_exists('mb_strlen') ? mb_strlen($teacherRemark) : strlen($teacherRemark);

  if (!in_array($action, ['approve', 'reject'], true)) {
    $errorMessage = 'Invalid leave action submitted.';
  } elseif ($leaveId <= 0) {
    $errorMessage = 'Invalid leave request selected.';
  } elseif ($remarkLength > 1000) {
    $errorMessage = 'Teacher remark must be at most 1000 characters.';
  } elseif (!teacher_leave_table_exists($conn, 'leave_applications')) {
    $errorMessage = 'Leave applications table is not available.';
  } elseif (empty($ownedCandidateIds)) {
    $errorMessage = 'You are not assigned to any students.';
  } else {
    $hasStudentIdColumn = teacher_leave_column_exists($conn, 'leave_applications', 'student_id');
    $hasStudentUserIdColumn = teacher_leave_column_exists($conn, 'leave_applications', 'student_user_id');

    $identityColumns = ['id'];
    if ($hasStudentIdColumn) {
      $identityColumns[] = 'student_id';
    }
    if ($hasStudentUserIdColumn) {
      $identityColumns[] = 'student_user_id';
    }

    if (!$hasStudentIdColumn && !$hasStudentUserIdColumn) {
      $errorMessage = 'Leave applications are missing student link columns.';
    } else {
      $identitySql = 'SELECT ' . implode(', ', $identityColumns) . ' FROM leave_applications WHERE id = ? LIMIT 1';
      $identityStmt = $conn->prepare($identitySql);

      if (!$identityStmt) {
        $errorMessage = 'Unable to load selected leave request.';
      } else {
        $identityStmt->bind_param('i', $leaveId);
        $identityStmt->execute();
        $identityResult = $identityStmt->get_result();
        $identityRow = $identityResult ? $identityResult->fetch_assoc() : null;
        $identityStmt->close();

        if (!$identityRow) {
          $errorMessage = 'Leave request not found.';
        } else {
          $leaveStudentId = (int) ($identityRow['student_id'] ?? 0);
          $leaveStudentUserId = (int) ($identityRow['student_user_id'] ?? 0);

          $isAuthorized = in_array($leaveStudentId, $ownedCandidateIds, true)
            || in_array($leaveStudentUserId, $ownedCandidateIds, true);

          if (!$isAuthorized) {
            $errorMessage = 'You are not allowed to review this leave request.';
          } else {
            if (!teacher_leave_column_exists($conn, 'leave_applications', 'status')) {
              $errorMessage = 'Leave status column is missing.';
            } else {
              $nextStatus = $action === 'approve' ? 'approved' : 'rejected';
              $setParts = ['status = ?'];
              $updateTypes = 's';
              $updateParams = [$nextStatus];

              if (teacher_leave_column_exists($conn, 'leave_applications', 'teacher_remark')) {
                $setParts[] = 'teacher_remark = ?';
                $updateTypes .= 's';
                $updateParams[] = $teacherRemark;
              } elseif (teacher_leave_column_exists($conn, 'leave_applications', 'admin_remark')) {
                $setParts[] = 'admin_remark = ?';
                $updateTypes .= 's';
                $updateParams[] = $teacherRemark;
              }

              if (teacher_leave_column_exists($conn, 'leave_applications', 'reviewed_by_teacher_id')) {
                $setParts[] = 'reviewed_by_teacher_id = ?';
                $updateTypes .= 'i';
                $updateParams[] = $teacherUserId;
              }

              if (teacher_leave_column_exists($conn, 'leave_applications', 'reviewed_at')) {
                $setParts[] = 'reviewed_at = NOW()';
              }

              $updateSql = 'UPDATE leave_applications SET ' . implode(', ', $setParts) . ' WHERE id = ?';
              $updateTypes .= 'i';
              $updateParams[] = $leaveId;

              $updateStmt = $conn->prepare($updateSql);
              if (!$updateStmt) {
                $errorMessage = 'Unable to update leave request right now.';
              } else {
                $bindParams = $updateParams;
                if (!teacher_leave_bind_dynamic_params($updateStmt, $updateTypes, $bindParams)) {
                  $errorMessage = 'Unable to bind leave update parameters.';
                } elseif ($updateStmt->execute()) {
                  $successMessage = $action === 'approve'
                    ? 'Leave request approved successfully.'
                    : 'Leave request rejected successfully.';
                } else {
                  $errorMessage = 'Unable to update leave request right now.';
                }
                $updateStmt->close();
              }
            }
          }
        }
      }
    }
  }
}

$leaveRecords = [];

if (teacher_leave_table_exists($conn, 'leave_applications') && !empty($ownedCandidateIds)) {
  $hasStudentIdColumn = teacher_leave_column_exists($conn, 'leave_applications', 'student_id');
  $hasStudentUserIdColumn = teacher_leave_column_exists($conn, 'leave_applications', 'student_user_id');

  $applicationIdSelect = teacher_leave_column_exists($conn, 'leave_applications', 'application_id')
    ? 'la.application_id'
    : (teacher_leave_column_exists($conn, 'leave_applications', 'id')
      ? "CONCAT('LA', LPAD(la.id, 6, '0')) AS application_id"
      : "'' AS application_id");

  $leaveTypeSelect = teacher_leave_column_exists($conn, 'leave_applications', 'leave_type') ? 'la.leave_type' : "'' AS leave_type";
  $fromDateSelect = teacher_leave_column_exists($conn, 'leave_applications', 'from_date') ? 'la.from_date' : 'NULL AS from_date';
  $toDateSelect = teacher_leave_column_exists($conn, 'leave_applications', 'to_date') ? 'la.to_date' : 'NULL AS to_date';
  $daysSelect = teacher_leave_column_exists($conn, 'leave_applications', 'days') ? 'la.days' : '0 AS days';
  $statusSelect = teacher_leave_column_exists($conn, 'leave_applications', 'status') ? 'la.status' : "'pending' AS status";
  $reasonSelect = teacher_leave_column_exists($conn, 'leave_applications', 'reason') ? 'la.reason' : "'' AS reason";

  if (teacher_leave_column_exists($conn, 'leave_applications', 'teacher_remark')) {
    $remarkSelect = 'la.teacher_remark';
  } elseif (teacher_leave_column_exists($conn, 'leave_applications', 'admin_remark')) {
    $remarkSelect = 'la.admin_remark AS teacher_remark';
  } else {
    $remarkSelect = "'' AS teacher_remark";
  }

  $createdAtSelect = teacher_leave_column_exists($conn, 'leave_applications', 'created_at') ? 'la.created_at' : 'NULL AS created_at';
  $studentIdSelect = $hasStudentIdColumn ? 'la.student_id' : '0 AS student_id';
  $studentUserIdSelect = $hasStudentUserIdColumn ? 'la.student_user_id' : '0 AS student_user_id';

  $orderByColumn = teacher_leave_column_exists($conn, 'leave_applications', 'created_at')
    ? 'la.created_at'
    : (teacher_leave_column_exists($conn, 'leave_applications', 'from_date') ? 'la.from_date' : 'la.id');

  $whereParts = [];
  $whereTypes = '';
  $whereParams = [];

  if ($hasStudentIdColumn) {
    $studentIdPlaceholders = implode(', ', array_fill(0, count($ownedCandidateIds), '?'));
    $whereParts[] = "la.student_id IN ({$studentIdPlaceholders})";
    $whereTypes .= str_repeat('i', count($ownedCandidateIds));
    $whereParams = array_merge($whereParams, $ownedCandidateIds);
  }

  if ($hasStudentUserIdColumn) {
    $studentUserIdPlaceholders = implode(', ', array_fill(0, count($ownedCandidateIds), '?'));
    $whereParts[] = "la.student_user_id IN ({$studentUserIdPlaceholders})";
    $whereTypes .= str_repeat('i', count($ownedCandidateIds));
    $whereParams = array_merge($whereParams, $ownedCandidateIds);
  }

  if (!empty($whereParts)) {
    $leaveSql = "SELECT la.id, {$applicationIdSelect}, {$leaveTypeSelect}, {$fromDateSelect}, {$toDateSelect}, {$daysSelect}, {$statusSelect}, {$reasonSelect}, {$remarkSelect}, {$createdAtSelect}, {$studentIdSelect}, {$studentUserIdSelect}
      FROM leave_applications la
      WHERE (" . implode(' OR ', $whereParts) . ")
      ORDER BY {$orderByColumn} DESC";

    $leaveStmt = $conn->prepare($leaveSql);
    if ($leaveStmt) {
      $bindParams = $whereParams;
      if (teacher_leave_bind_dynamic_params($leaveStmt, $whereTypes, $bindParams) && $leaveStmt->execute()) {
        $leaveResult = $leaveStmt->get_result();
        while ($leaveResult && ($leaveRow = $leaveResult->fetch_assoc())) {
          $profileId = (int) ($leaveRow['student_id'] ?? 0);
          $userId = (int) ($leaveRow['student_user_id'] ?? 0);

          $studentInfo = $ownedStudentsByProfileId[$profileId]
            ?? $ownedStudentsByUserId[$userId]
            ?? $ownedStudentsByUserId[$profileId]
            ?? $ownedStudentsByProfileId[$userId]
            ?? [
              'student_name' => 'Student #' . (string) ($profileId > 0 ? $profileId : $userId),
              'roll_no' => '',
              'class_name' => '-',
            ];

          $leaveRow['student_name'] = (string) ($studentInfo['student_name'] ?? 'Student');
          $leaveRow['roll_no'] = (string) ($studentInfo['roll_no'] ?? '');
          $leaveRow['class_name'] = (string) ($studentInfo['class_name'] ?? '-');
          $leaveRow['status'] = strtolower(trim((string) ($leaveRow['status'] ?? 'pending')));

          $leaveRecords[] = $leaveRow;
        }
      }
      $leaveStmt->close();
    }
  }
}

$stats = [
  'total' => count($leaveRecords),
  'pending' => 0,
  'approved' => 0,
  'rejected' => 0,
];

foreach ($leaveRecords as $leaveRecord) {
  $statusValue = (string) ($leaveRecord['status'] ?? 'pending');
  if ($statusValue === 'approved') {
    $stats['approved']++;
  } elseif ($statusValue === 'rejected') {
    $stats['rejected']++;
  } else {
    $stats['pending']++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Requests - Teacher Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <style>
    .text-primary { color: #003b93 !important; }
    .bg-primary { background-color: #003b93 !important; }
    .bg-primary\/5 { background-color: rgba(0, 59, 147, 0.06) !important; }
    .text-on-surface-variant { color: #434653 !important; }
    .border-outline-variant\/20 { border-color: rgba(195, 198, 214, 0.2) !important; }
  </style>
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-blue-600" style="font-variation-settings: 'FILL' 1;">approval_delegation</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Leave Requests</h1>
        <p class="text-sm text-stone-500">Review and approve or reject student leave applications.</p>
      </div>
    </div>

    <?php if ($successMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        <?php echo htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-stone-500">Total</p>
        <p class="mt-1 text-2xl font-bold text-stone-900"><?php echo (int) $stats['total']; ?></p>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-stone-500">Pending</p>
        <p class="mt-1 text-2xl font-bold text-amber-600"><?php echo (int) $stats['pending']; ?></p>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-stone-500">Approved</p>
        <p class="mt-1 text-2xl font-bold text-emerald-600"><?php echo (int) $stats['approved']; ?></p>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-stone-500">Rejected</p>
        <p class="mt-1 text-2xl font-bold text-red-600"><?php echo (int) $stats['rejected']; ?></p>
      </div>
    </div>

    <div class="rounded-lg border border-stone-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-stone-200 px-6 py-4">
        <h2 class="text-lg font-bold text-stone-900">Student Leave Applications</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Student</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Class</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Leave</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Dates</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Reason</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (!empty($leaveRecords)): ?>
              <?php foreach ($leaveRecords as $leaveRecord): ?>
                <?php
                  $statusValue = (string) ($leaveRecord['status'] ?? 'pending');
                  $isPending = $statusValue === 'pending';
                  $statusClasses = 'bg-amber-100 text-amber-700';
                  if ($statusValue === 'approved') {
                    $statusClasses = 'bg-emerald-100 text-emerald-700';
                  } elseif ($statusValue === 'rejected') {
                    $statusClasses = 'bg-red-100 text-red-700';
                  }
                  $remarkText = trim((string) ($leaveRecord['teacher_remark'] ?? ''));
                ?>
                <tr>
                  <td class="px-4 py-4 align-top">
                    <p class="text-sm font-semibold text-stone-900"><?php echo htmlspecialchars((string) ($leaveRecord['student_name'] ?? 'Student')); ?></p>
                    <p class="text-xs text-stone-500"><?php echo htmlspecialchars((string) (($leaveRecord['roll_no'] ?? '') !== '' ? $leaveRecord['roll_no'] : '-')); ?></p>
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-stone-700"><?php echo htmlspecialchars((string) ($leaveRecord['class_name'] ?? '-')); ?></td>
                  <td class="px-4 py-4 align-top">
                    <p class="text-sm font-medium text-stone-900"><?php echo htmlspecialchars(ucfirst((string) ($leaveRecord['leave_type'] ?? 'leave'))); ?></p>
                    <p class="text-xs text-stone-500"><?php echo (int) ($leaveRecord['days'] ?? 0); ?> day(s)</p>
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-stone-700">
                    <p><?php echo htmlspecialchars(teacher_leave_time_label($leaveRecord['from_date'] ?? '')); ?></p>
                    <p class="text-xs text-stone-500">to <?php echo htmlspecialchars(teacher_leave_time_label($leaveRecord['to_date'] ?? '')); ?></p>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <p class="text-sm text-stone-700 max-w-xs whitespace-pre-wrap"><?php echo htmlspecialchars((string) ($leaveRecord['reason'] ?? '')); ?></p>
                    <?php if ($remarkText !== ''): ?>
                      <p class="mt-2 text-xs text-blue-700">Teacher remark: <?php echo htmlspecialchars($remarkText); ?></p>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?php echo $statusClasses; ?>">
                      <?php echo htmlspecialchars(ucfirst($statusValue)); ?>
                    </span>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <?php if ($isPending): ?>
                      <form method="POST" class="space-y-2" novalidate>
                        <input type="hidden" name="leave_id" value="<?php echo (int) ($leaveRecord['id'] ?? 0); ?>">
                        <textarea name="teacher_remark" rows="2" maxlength="1000" class="w-full min-w-[220px] rounded-lg border border-stone-300 px-3 py-2 text-xs" placeholder="Optional remark..."></textarea>
                        <div class="flex gap-2">
                          <button type="submit" name="action" value="approve" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                          <button type="submit" name="action" value="reject" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Reject</button>
                        </div>
                      </form>
                    <?php else: ?>
                      <span class="text-xs text-stone-400">Reviewed</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-stone-500">No leave requests found for your students.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>

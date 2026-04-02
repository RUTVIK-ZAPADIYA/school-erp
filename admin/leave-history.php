<?php
// Admin read-only leave history page.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

function admin_leave_history_time_label($dateValue)
{
  $timestamp = strtotime((string) $dateValue);
  if ($timestamp === false) {
    return '-';
  }

  return date('d M Y', $timestamp);
}

function admin_leave_history_datetime_label($dateTimeValue)
{
  $timestamp = strtotime((string) $dateTimeValue);
  if ($timestamp === false) {
    return '-';
  }

  return date('d M Y H:i', $timestamp);
}

function admin_leave_history_class_name($connection, $classId, $fallbackClassName = '')
{
  $classId = (int) $classId;
  $fallbackClassName = trim((string) $fallbackClassName);

  static $cache = [];
  static $classNameColumn = null;
  static $classColumnResolved = false;

  $cacheKey = $classId . '|' . $fallbackClassName;
  if (isset($cache[$cacheKey])) {
    return $cache[$cacheKey];
  }

  $resolvedName = $fallbackClassName !== '' ? $fallbackClassName : '-';

  if (!$classColumnResolved) {
    $classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
    $classColumnResolved = true;
  }

  if ($classId > 0 && $classNameColumn !== null && admin_table_exists($connection, 'classes')) {
    $classStmt = $connection->prepare("SELECT {$classNameColumn} AS class_name, COALESCE(section, '') AS section FROM classes WHERE id = ? LIMIT 1");
    if ($classStmt) {
      $classStmt->bind_param('i', $classId);
      $classStmt->execute();
      $classResult = $classStmt->get_result();
      $classRow = $classResult ? $classResult->fetch_assoc() : null;
      $classStmt->close();

      if ($classRow) {
        $nameValue = trim((string) ($classRow['class_name'] ?? ''));
        $sectionValue = trim((string) ($classRow['section'] ?? ''));
        if ($nameValue !== '') {
          $resolvedName = $nameValue . ($sectionValue !== '' ? ' - ' . $sectionValue : '');
        }
      }
    }
  }

  $cache[$cacheKey] = $resolvedName;
  return $resolvedName;
}

function admin_leave_history_student_details($connection, $studentId, $studentUserId)
{
  $studentId = (int) $studentId;
  $studentUserId = (int) $studentUserId;

  static $cache = [];
  static $studentsHasUserId = null;
  static $studentsHasClassId = null;
  static $studentsHasClassName = null;
  static $rollColumn = null;

  $cacheKey = $studentId . '|' . $studentUserId;
  if (isset($cache[$cacheKey])) {
    return $cache[$cacheKey];
  }

  if ($studentsHasUserId === null) {
    $studentsHasUserId = admin_column_exists($connection, 'students', 'user_id');
    $studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
    $studentsHasClassName = admin_column_exists($connection, 'students', 'class');
    $rollColumn = admin_first_existing_column($connection, 'students', ['roll_no', 'roll_number']);
  }

  $details = [
    'student_name' => '-',
    'roll_no' => '-',
    'class_name' => '-',
  ];

  $studentRow = null;

  if (admin_table_exists($connection, 'students')) {
    if ($studentId > 0) {
      $byProfileStmt = $connection->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
      if ($byProfileStmt) {
        $byProfileStmt->bind_param('i', $studentId);
        $byProfileStmt->execute();
        $byProfileResult = $byProfileStmt->get_result();
        $studentRow = $byProfileResult ? $byProfileResult->fetch_assoc() : null;
        $byProfileStmt->close();
      }
    }

    if (!$studentRow && $studentsHasUserId && $studentUserId > 0) {
      $byUserStmt = $connection->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
      if ($byUserStmt) {
        $byUserStmt->bind_param('i', $studentUserId);
        $byUserStmt->execute();
        $byUserResult = $byUserStmt->get_result();
        $studentRow = $byUserResult ? $byUserResult->fetch_assoc() : null;
        $byUserStmt->close();
      }
    }

    if (!$studentRow && $studentUserId > 0) {
      $legacyByIdStmt = $connection->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
      if ($legacyByIdStmt) {
        $legacyByIdStmt->bind_param('i', $studentUserId);
        $legacyByIdStmt->execute();
        $legacyByIdResult = $legacyByIdStmt->get_result();
        $studentRow = $legacyByIdResult ? $legacyByIdResult->fetch_assoc() : null;
        $legacyByIdStmt->close();
      }
    }
  }

  if ($studentRow) {
    $nameValue = trim((string) ($studentRow['name'] ?? ''));
    if ($nameValue !== '') {
      $details['student_name'] = $nameValue;
    }

    if ($rollColumn !== null) {
      $rollValue = trim((string) ($studentRow[$rollColumn] ?? ''));
      if ($rollValue !== '') {
        $details['roll_no'] = $rollValue;
      }
    }

    $classId = $studentsHasClassId ? (int) ($studentRow['class_id'] ?? 0) : 0;
    $fallbackClassName = $studentsHasClassName ? trim((string) ($studentRow['class'] ?? '')) : '';
    $details['class_name'] = admin_leave_history_class_name($connection, $classId, $fallbackClassName);
  } elseif (admin_table_exists($connection, 'users') && admin_column_exists($connection, 'users', 'name')) {
    $userLookupId = $studentUserId > 0 ? $studentUserId : $studentId;
    if ($userLookupId > 0) {
      $userStmt = $connection->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
      if ($userStmt) {
        $userStmt->bind_param('i', $userLookupId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userRow = $userResult ? $userResult->fetch_assoc() : null;
        $userStmt->close();

        if ($userRow) {
          $userName = trim((string) ($userRow['name'] ?? ''));
          if ($userName !== '') {
            $details['student_name'] = $userName;
          }
        }
      }
    }
  }

  $cache[$cacheKey] = $details;
  return $details;
}

function admin_leave_history_teacher_name($connection, $reviewedByTeacherId)
{
  $reviewedByTeacherId = (int) $reviewedByTeacherId;
  if ($reviewedByTeacherId <= 0) {
    return '-';
  }

  static $cache = [];
  static $teachersHasUserId = null;

  if (isset($cache[$reviewedByTeacherId])) {
    return $cache[$reviewedByTeacherId];
  }

  if ($teachersHasUserId === null) {
    $teachersHasUserId = admin_column_exists($connection, 'teachers', 'user_id');
  }

  $resolvedName = '-';

  if (admin_table_exists($connection, 'teachers') && admin_column_exists($connection, 'teachers', 'name')) {
    if ($teachersHasUserId) {
      $byUserStmt = $connection->prepare('SELECT name FROM teachers WHERE user_id = ? LIMIT 1');
      if ($byUserStmt) {
        $byUserStmt->bind_param('i', $reviewedByTeacherId);
        $byUserStmt->execute();
        $byUserResult = $byUserStmt->get_result();
        $byUserRow = $byUserResult ? $byUserResult->fetch_assoc() : null;
        $byUserStmt->close();

        $candidate = trim((string) ($byUserRow['name'] ?? ''));
        if ($candidate !== '') {
          $resolvedName = $candidate;
        }
      }
    }

    if ($resolvedName === '-') {
      $byProfileStmt = $connection->prepare('SELECT name FROM teachers WHERE id = ? LIMIT 1');
      if ($byProfileStmt) {
        $byProfileStmt->bind_param('i', $reviewedByTeacherId);
        $byProfileStmt->execute();
        $byProfileResult = $byProfileStmt->get_result();
        $byProfileRow = $byProfileResult ? $byProfileResult->fetch_assoc() : null;
        $byProfileStmt->close();

        $candidate = trim((string) ($byProfileRow['name'] ?? ''));
        if ($candidate !== '') {
          $resolvedName = $candidate;
        }
      }
    }
  }

  if ($resolvedName === '-' && admin_table_exists($connection, 'users') && admin_column_exists($connection, 'users', 'name')) {
    $userStmt = $connection->prepare("SELECT name FROM users WHERE id = ? AND role = 'teacher' LIMIT 1");
    if ($userStmt) {
      $userStmt->bind_param('i', $reviewedByTeacherId);
      $userStmt->execute();
      $userResult = $userStmt->get_result();
      $userRow = $userResult ? $userResult->fetch_assoc() : null;
      $userStmt->close();

      $candidate = trim((string) ($userRow['name'] ?? ''));
      if ($candidate !== '') {
        $resolvedName = $candidate;
      }
    }
  }

  $cache[$reviewedByTeacherId] = $resolvedName;
  return $resolvedName;
}

$errorMessage = '';
$leaveHistory = [];
$stats = [
  'total' => 0,
  'pending' => 0,
  'approved' => 0,
  'rejected' => 0,
];

if (!admin_table_exists($connection, 'leave_applications')) {
  $errorMessage = 'Leave applications table is not available in the current schema.';
} else {
  admin_ensure_column($connection, 'leave_applications', 'status', "VARCHAR(20) DEFAULT 'pending'");
  admin_ensure_column($connection, 'leave_applications', 'teacher_remark', 'TEXT NULL');
  admin_ensure_column($connection, 'leave_applications', 'reviewed_by_teacher_id', 'INT NULL');
  admin_ensure_column($connection, 'leave_applications', 'reviewed_at', 'DATETIME NULL');

  $applicationIdSelect = admin_column_exists($connection, 'leave_applications', 'application_id')
    ? 'application_id'
    : "'' AS application_id";
  $studentIdSelect = admin_column_exists($connection, 'leave_applications', 'student_id') ? 'student_id' : '0 AS student_id';
  $studentUserIdSelect = admin_column_exists($connection, 'leave_applications', 'student_user_id') ? 'student_user_id' : '0 AS student_user_id';
  $leaveTypeSelect = admin_column_exists($connection, 'leave_applications', 'leave_type') ? 'leave_type' : "'' AS leave_type";
  $reasonSelect = admin_column_exists($connection, 'leave_applications', 'reason') ? 'reason' : "'' AS reason";
  $fromDateSelect = admin_column_exists($connection, 'leave_applications', 'from_date') ? 'from_date' : 'NULL AS from_date';
  $toDateSelect = admin_column_exists($connection, 'leave_applications', 'to_date') ? 'to_date' : 'NULL AS to_date';
  $daysSelect = admin_column_exists($connection, 'leave_applications', 'days') ? 'days' : '0 AS days';
  $statusSelect = admin_column_exists($connection, 'leave_applications', 'status') ? 'status' : "'pending' AS status";
  $reviewedBySelect = admin_column_exists($connection, 'leave_applications', 'reviewed_by_teacher_id')
    ? 'reviewed_by_teacher_id'
    : '0 AS reviewed_by_teacher_id';
  $reviewedAtSelect = admin_column_exists($connection, 'leave_applications', 'reviewed_at') ? 'reviewed_at' : 'NULL AS reviewed_at';
  $createdAtSelect = admin_column_exists($connection, 'leave_applications', 'created_at') ? 'created_at' : 'NULL AS created_at';

  if (admin_column_exists($connection, 'leave_applications', 'teacher_remark')) {
    $remarkSelect = 'teacher_remark';
  } elseif (admin_column_exists($connection, 'leave_applications', 'admin_remark')) {
    $remarkSelect = 'admin_remark AS teacher_remark';
  } else {
    $remarkSelect = "'' AS teacher_remark";
  }

  $orderByColumn = admin_column_exists($connection, 'leave_applications', 'created_at')
    ? 'created_at'
    : (admin_column_exists($connection, 'leave_applications', 'from_date') ? 'from_date' : 'id');

  $historySql = "SELECT id, {$applicationIdSelect}, {$studentIdSelect}, {$studentUserIdSelect}, {$leaveTypeSelect}, {$reasonSelect}, {$fromDateSelect}, {$toDateSelect}, {$daysSelect}, {$statusSelect}, {$remarkSelect}, {$reviewedBySelect}, {$reviewedAtSelect}, {$createdAtSelect}
    FROM leave_applications
    ORDER BY {$orderByColumn} DESC, id DESC";

  $historyStmt = $connection->prepare($historySql);
  if ($historyStmt) {
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();

    while ($historyResult && ($historyRow = $historyResult->fetch_assoc())) {
      $statusValue = strtolower(trim((string) ($historyRow['status'] ?? 'pending')));
      if (!in_array($statusValue, ['pending', 'approved', 'rejected'], true)) {
        $statusValue = 'pending';
      }

      $stats['total']++;
      if ($statusValue === 'approved') {
        $stats['approved']++;
      } elseif ($statusValue === 'rejected') {
        $stats['rejected']++;
      } else {
        $stats['pending']++;
      }

      $studentDetails = admin_leave_history_student_details(
        $connection,
        (int) ($historyRow['student_id'] ?? 0),
        (int) ($historyRow['student_user_id'] ?? 0)
      );

      $reviewedByTeacherId = (int) ($historyRow['reviewed_by_teacher_id'] ?? 0);
      $reviewedTeacherName = $statusValue === 'pending'
        ? '-'
        : admin_leave_history_teacher_name($connection, $reviewedByTeacherId);

      $applicationId = trim((string) ($historyRow['application_id'] ?? ''));
      if ($applicationId === '') {
        $applicationId = 'LA' . str_pad((string) ((int) ($historyRow['id'] ?? 0)), 6, '0', STR_PAD_LEFT);
      }

      $leaveHistory[] = [
        'id' => (int) ($historyRow['id'] ?? 0),
        'application_id' => $applicationId,
        'student_name' => (string) ($studentDetails['student_name'] ?? '-'),
        'roll_no' => (string) ($studentDetails['roll_no'] ?? '-'),
        'class_name' => (string) ($studentDetails['class_name'] ?? '-'),
        'leave_type' => (string) ($historyRow['leave_type'] ?? ''),
        'reason' => (string) ($historyRow['reason'] ?? ''),
        'from_date' => (string) ($historyRow['from_date'] ?? ''),
        'to_date' => (string) ($historyRow['to_date'] ?? ''),
        'days' => (int) ($historyRow['days'] ?? 0),
        'status' => $statusValue,
        'teacher_remark' => (string) ($historyRow['teacher_remark'] ?? ''),
        'reviewed_teacher' => $reviewedTeacherName,
        'reviewed_at' => (string) ($historyRow['reviewed_at'] ?? ''),
        'created_at' => (string) ($historyRow['created_at'] ?? ''),
      ];
    }

    $historyStmt->close();
  } else {
    $errorMessage = 'Unable to load leave history right now.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .summary-item { text-align: center; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .summary-value { font-size: 2rem; font-weight: 700; color: #3498db; }
    .summary-label { color: #7f8c8d; margin-top: 5px; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-file-alt"></i> Leave Application History</h2>
    </div>

    <?php if ($errorMessage !== ''): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="summary-item"><div class="summary-value"><?php echo (int) $stats['total']; ?></div><div class="summary-label">Total Requests</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $stats['pending']; ?></div><div class="summary-label">Pending</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $stats['approved']; ?></div><div class="summary-label">Approved</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $stats['rejected']; ?></div><div class="summary-label">Rejected</div></div>
    </div>

    <div class="content-card">
      <h5 class="mb-4">Read-only Leave Records</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Application ID</th>
              <th>Student</th>
              <th>Class</th>
              <th>Leave Description</th>
              <th>Leave Dates</th>
              <th>Status</th>
              <th>Teacher Review</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($leaveHistory)): ?>
              <?php foreach ($leaveHistory as $historyItem): ?>
                <?php
                  $statusValue = (string) ($historyItem['status'] ?? 'pending');
                  $statusLabel = ucfirst($statusValue);
                  $statusBadgeClass = 'bg-warning text-dark';
                  if ($statusValue === 'approved') {
                    $statusBadgeClass = 'bg-success';
                  } elseif ($statusValue === 'rejected') {
                    $statusBadgeClass = 'bg-danger';
                  }
                  $leaveTypeLabel = trim((string) ($historyItem['leave_type'] ?? ''));
                  if ($leaveTypeLabel === '') {
                    $leaveTypeLabel = 'Leave';
                  } else {
                    $leaveTypeLabel = ucfirst($leaveTypeLabel);
                  }
                  $descriptionText = trim((string) ($historyItem['reason'] ?? ''));
                  $teacherRemark = trim((string) ($historyItem['teacher_remark'] ?? ''));
                ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) ($historyItem['application_id'] ?? '-')); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars((string) ($historyItem['student_name'] ?? '-')); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars((string) ($historyItem['roll_no'] ?? '-')); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars((string) ($historyItem['class_name'] ?? '-')); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($leaveTypeLabel); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($descriptionText !== '' ? $descriptionText : '-'); ?></div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars(admin_leave_history_time_label($historyItem['from_date'] ?? '')); ?></div>
                    <div class="text-muted small">to <?php echo htmlspecialchars(admin_leave_history_time_label($historyItem['to_date'] ?? '')); ?></div>
                    <div class="text-muted small"><?php echo (int) ($historyItem['days'] ?? 0); ?> day(s)</div>
                  </td>
                  <td><span class="badge <?php echo $statusBadgeClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars((string) ($historyItem['reviewed_teacher'] ?? '-')); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars(admin_leave_history_datetime_label($historyItem['reviewed_at'] ?? '')); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($teacherRemark !== '' ? $teacherRemark : '-'); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars(admin_leave_history_datetime_label($historyItem['created_at'] ?? '')); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">No leave applications found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

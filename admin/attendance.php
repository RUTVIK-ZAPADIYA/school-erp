<?php
// Admin page for recording and reviewing attendance.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

function attendance_admin_class_name($connection, $classId, $fallbackClassName = '')
{
  $classId = (int) $classId;
  $fallbackClassName = trim((string) $fallbackClassName);

  static $cache = [];
  static $classNameColumn = null;
  static $classNameColumnResolved = false;

  $cacheKey = $classId . '|' . $fallbackClassName;
  if (isset($cache[$cacheKey])) {
    return $cache[$cacheKey];
  }

  if (!$classNameColumnResolved) {
    $classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
    $classNameColumnResolved = true;
  }

  $resolvedName = $fallbackClassName !== '' ? $fallbackClassName : '-';

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

function attendance_admin_student_details($connection, $studentId, $studentUserId)
{
  $studentId = (int) $studentId;
  $studentUserId = (int) $studentUserId;

  static $cache = [];
  static $studentsHasUserId = null;
  static $studentsHasClassId = null;
  static $studentsHasClass = null;
  static $rollColumn = null;

  $cacheKey = $studentId . '|' . $studentUserId;
  if (isset($cache[$cacheKey])) {
    return $cache[$cacheKey];
  }

  if ($studentsHasUserId === null) {
    $studentsHasUserId = admin_column_exists($connection, 'students', 'user_id');
    $studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
    $studentsHasClass = admin_column_exists($connection, 'students', 'class');
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
      $studentByIdStmt = $connection->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
      if ($studentByIdStmt) {
        $studentByIdStmt->bind_param('i', $studentId);
        $studentByIdStmt->execute();
        $studentByIdResult = $studentByIdStmt->get_result();
        $studentRow = $studentByIdResult ? $studentByIdResult->fetch_assoc() : null;
        $studentByIdStmt->close();
      }
    }

    if (!$studentRow && $studentsHasUserId && $studentUserId > 0) {
      $studentByUserStmt = $connection->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
      if ($studentByUserStmt) {
        $studentByUserStmt->bind_param('i', $studentUserId);
        $studentByUserStmt->execute();
        $studentByUserResult = $studentByUserStmt->get_result();
        $studentRow = $studentByUserResult ? $studentByUserResult->fetch_assoc() : null;
        $studentByUserStmt->close();
      }
    }

    if (!$studentRow && $studentUserId > 0) {
      $studentLegacyStmt = $connection->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
      if ($studentLegacyStmt) {
        $studentLegacyStmt->bind_param('i', $studentUserId);
        $studentLegacyStmt->execute();
        $studentLegacyResult = $studentLegacyStmt->get_result();
        $studentRow = $studentLegacyResult ? $studentLegacyResult->fetch_assoc() : null;
        $studentLegacyStmt->close();
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
    $fallbackClassName = $studentsHasClass ? (string) ($studentRow['class'] ?? '') : '';
    $details['class_name'] = attendance_admin_class_name($connection, $classId, $fallbackClassName);
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

function attendance_admin_teacher_name($connection, $reviewedByTeacherId)
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
      $teacherByUserStmt = $connection->prepare('SELECT name FROM teachers WHERE user_id = ? LIMIT 1');
      if ($teacherByUserStmt) {
        $teacherByUserStmt->bind_param('i', $reviewedByTeacherId);
        $teacherByUserStmt->execute();
        $teacherByUserResult = $teacherByUserStmt->get_result();
        $teacherByUserRow = $teacherByUserResult ? $teacherByUserResult->fetch_assoc() : null;
        $teacherByUserStmt->close();
        $candidate = trim((string) ($teacherByUserRow['name'] ?? ''));
        if ($candidate !== '') {
          $resolvedName = $candidate;
        }
      }
    }

    if ($resolvedName === '-') {
      $teacherByIdStmt = $connection->prepare('SELECT name FROM teachers WHERE id = ? LIMIT 1');
      if ($teacherByIdStmt) {
        $teacherByIdStmt->bind_param('i', $reviewedByTeacherId);
        $teacherByIdStmt->execute();
        $teacherByIdResult = $teacherByIdStmt->get_result();
        $teacherByIdRow = $teacherByIdResult ? $teacherByIdResult->fetch_assoc() : null;
        $teacherByIdStmt->close();
        $candidate = trim((string) ($teacherByIdRow['name'] ?? ''));
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

$today = date('Y-m-d');

// Detect which attendance date column is available in the current schema.
$attendanceDateColumn = null;
if (admin_column_exists($connection, 'attendance', 'attendance_date')) {
  $attendanceDateColumn = 'attendance_date';
} elseif (admin_column_exists($connection, 'attendance', 'date')) {
  $attendanceDateColumn = 'date';
}

// Handle delete requests for individual attendance records.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $attendanceId = (int) ($_POST['attendance_id'] ?? 0);

  if ($attendanceId > 0) {
    $deleteStmt = $connection->prepare( 'DELETE FROM attendance WHERE id = ?');
    if (!$deleteStmt) {
      admin_set_flash('danger', 'Unable to process attendance delete request.');
    } else {
      $deleteStmt->bind_param( 'i', $attendanceId);
      if (!$deleteStmt->execute()) {
        admin_set_flash('danger', 'Unable to delete attendance record right now.');
      } elseif ($deleteStmt->affected_rows < 1) {
        admin_set_flash('warning', 'Attendance record was already removed or not found.');
      } else {
        admin_set_flash('success', 'Attendance record deleted successfully.');
      }
      $deleteStmt->close();
    }
  }

  header('Location: attendance.php');
  exit();
}

$presentToday = 0;
$absentToday = 0;
$leaveToday = 0;
$approvedLeaveToday = 0;
$totalMarkedToday = 0;
$studentsOnLeaveToday = [];

// Query today's attendance summary counters for dashboard cards.
if ($attendanceDateColumn !== null) {
  $summarySql = "SELECT
    SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('leave', 'l', 'on leave') THEN 1 ELSE 0 END) AS leave_count,
    COUNT(*) AS total_count
    FROM attendance
    WHERE {$attendanceDateColumn} = ?";

  $summaryStmt = $connection->prepare( $summarySql);
  if ($summaryStmt) {
    $summaryStmt->bind_param( 's', $today);
    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();
    $summaryRow = $summaryResult ? $summaryResult->fetch_assoc() : null;
    $presentToday = (int) ($summaryRow['present_count'] ?? 0);
    $absentToday = (int) ($summaryRow['absent_count'] ?? 0);
    $leaveToday = (int) ($summaryRow['leave_count'] ?? 0);
    $totalMarkedToday = (int) ($summaryRow['total_count'] ?? 0);
    $summaryStmt->close();
  }
}

$overallAttendance = $totalMarkedToday > 0 ? (int) round(($presentToday / $totalMarkedToday) * 100) : 0;

$classStats = [];
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
$studentsHasClass = admin_column_exists($connection, 'students', 'class');
$attendanceHasClassId = admin_column_exists($connection, 'attendance', 'class_id');
$attendanceHasSubjectId = admin_column_exists($connection, 'attendance', 'subject_id');
$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);

// Build approved leave list for today's attendance monitoring.
if (
  admin_table_exists($connection, 'leave_applications')
  && admin_column_exists($connection, 'leave_applications', 'from_date')
  && admin_column_exists($connection, 'leave_applications', 'to_date')
) {
  $hasLeaveStatusColumn = admin_column_exists($connection, 'leave_applications', 'status');
  $hasLeaveStudentIdColumn = admin_column_exists($connection, 'leave_applications', 'student_id');
  $hasLeaveStudentUserIdColumn = admin_column_exists($connection, 'leave_applications', 'student_user_id');

  if ($hasLeaveStudentIdColumn || $hasLeaveStudentUserIdColumn) {
    $studentIdSelect = $hasLeaveStudentIdColumn ? 'student_id' : '0 AS student_id';
    $studentUserIdSelect = $hasLeaveStudentUserIdColumn ? 'student_user_id' : '0 AS student_user_id';
    $leaveTypeSelect = admin_column_exists($connection, 'leave_applications', 'leave_type') ? 'leave_type' : "'' AS leave_type";
    $reasonSelect = admin_column_exists($connection, 'leave_applications', 'reason') ? 'reason' : "'' AS reason";
    $daysSelect = admin_column_exists($connection, 'leave_applications', 'days') ? 'days' : '0 AS days';
    $reviewedBySelect = admin_column_exists($connection, 'leave_applications', 'reviewed_by_teacher_id')
      ? 'reviewed_by_teacher_id'
      : '0 AS reviewed_by_teacher_id';

    if (admin_column_exists($connection, 'leave_applications', 'teacher_remark')) {
      $remarkSelect = 'teacher_remark';
    } elseif (admin_column_exists($connection, 'leave_applications', 'admin_remark')) {
      $remarkSelect = 'admin_remark AS teacher_remark';
    } else {
      $remarkSelect = "'' AS teacher_remark";
    }

    $statusFilterSql = $hasLeaveStatusColumn
      ? "AND LOWER(COALESCE(status, '')) IN ('approved', 'approve', 'accepted')"
      : '';

    $leaveTodaySql = "SELECT id, {$studentIdSelect}, {$studentUserIdSelect}, {$leaveTypeSelect}, {$reasonSelect}, from_date, to_date, {$daysSelect}, {$remarkSelect}, {$reviewedBySelect}
      FROM leave_applications
      WHERE ? BETWEEN from_date AND to_date {$statusFilterSql}
      ORDER BY from_date ASC, id DESC";

    $leaveTodayStmt = $connection->prepare($leaveTodaySql);
    if ($leaveTodayStmt) {
      $leaveTodayStmt->bind_param('s', $today);
      $leaveTodayStmt->execute();
      $leaveTodayResult = $leaveTodayStmt->get_result();

      while ($leaveTodayResult && ($leaveTodayRow = $leaveTodayResult->fetch_assoc())) {
        $studentDetails = attendance_admin_student_details(
          $connection,
          (int) ($leaveTodayRow['student_id'] ?? 0),
          (int) ($leaveTodayRow['student_user_id'] ?? 0)
        );

        $studentsOnLeaveToday[] = [
          'student_name' => (string) ($studentDetails['student_name'] ?? '-'),
          'roll_no' => (string) ($studentDetails['roll_no'] ?? '-'),
          'class_name' => (string) ($studentDetails['class_name'] ?? '-'),
          'leave_type' => (string) ($leaveTodayRow['leave_type'] ?? ''),
          'reason' => (string) ($leaveTodayRow['reason'] ?? ''),
          'from_date' => (string) ($leaveTodayRow['from_date'] ?? ''),
          'to_date' => (string) ($leaveTodayRow['to_date'] ?? ''),
          'days' => (int) ($leaveTodayRow['days'] ?? 0),
          'teacher_remark' => (string) ($leaveTodayRow['teacher_remark'] ?? ''),
          'reviewed_teacher' => attendance_admin_teacher_name($connection, (int) ($leaveTodayRow['reviewed_by_teacher_id'] ?? 0)),
        ];
      }

      $leaveTodayStmt->close();
    }
  }
}

$approvedLeaveToday = count($studentsOnLeaveToday);

// Build class-wise attendance metrics with schema-aware joins.
if ($classNameColumn !== null) {
  $classStmt = $connection->prepare("SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classStmt) {
    $classStmt->execute();
    $classResult = $classStmt->get_result();

    if ($classResult) {
      while ($classRow = $classResult->fetch_assoc()) {
        $classId = (int) $classRow['id'];
        $className = (string) ($classRow['class_name'] ?? '');

        $totalStudents = 0;
        if ($studentsHasClassId) {
          $studentCountStmt = $connection->prepare( 'SELECT COUNT(*) AS total FROM students WHERE class_id = ?');
          if ($studentCountStmt) {
            $studentCountStmt->bind_param( 'i', $classId);
            $studentCountStmt->execute();
            $studentCountResult = $studentCountStmt->get_result();
            $studentCountRow = $studentCountResult ? $studentCountResult->fetch_assoc() : null;
            $totalStudents = (int) ($studentCountRow['total'] ?? 0);
            $studentCountStmt->close();
          }
        }

        if ($totalStudents === 0 && $studentsHasClass && $className !== '') {
          $studentNameCountStmt = $connection->prepare( 'SELECT COUNT(*) AS total FROM students WHERE class = ?');
          if ($studentNameCountStmt) {
            $studentNameCountStmt->bind_param( 's', $className);
            $studentNameCountStmt->execute();
            $studentNameCountResult = $studentNameCountStmt->get_result();
            $studentNameCountRow = $studentNameCountResult ? $studentNameCountResult->fetch_assoc() : null;
            $totalStudents = (int) ($studentNameCountRow['total'] ?? 0);
            $studentNameCountStmt->close();
          }
        }

        $presentCount = 0;
        $absentCount = 0;

        if ($attendanceDateColumn !== null) {
          if ($attendanceHasClassId) {
            $attendanceStmt = $connection->prepare(
              "SELECT
                SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_count
               FROM attendance
               WHERE class_id = ? AND {$attendanceDateColumn} = ?"
            );
            if ($attendanceStmt) {
              $attendanceStmt->bind_param( 'is', $classId, $today);
              $attendanceStmt->execute();
              $attendanceResult = $attendanceStmt->get_result();
              $attendanceRow = $attendanceResult ? $attendanceResult->fetch_assoc() : null;
              $presentCount = (int) ($attendanceRow['present_count'] ?? 0);
              $absentCount = (int) ($attendanceRow['absent_count'] ?? 0);
              $attendanceStmt->close();
            }
          } elseif ($studentsHasClassId) {
            $attendanceStmt = $connection->prepare(
              "SELECT
                SUM(CASE WHEN LOWER(COALESCE(a.status, '')) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN LOWER(COALESCE(a.status, '')) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_count
               FROM attendance a
               INNER JOIN students s ON s.id = a.student_id
               WHERE s.class_id = ? AND a.{$attendanceDateColumn} = ?"
            );
            if ($attendanceStmt) {
              $attendanceStmt->bind_param( 'is', $classId, $today);
              $attendanceStmt->execute();
              $attendanceResult = $attendanceStmt->get_result();
              $attendanceRow = $attendanceResult ? $attendanceResult->fetch_assoc() : null;
              $presentCount = (int) ($attendanceRow['present_count'] ?? 0);
              $absentCount = (int) ($attendanceRow['absent_count'] ?? 0);
              $attendanceStmt->close();
            }
          }
        }

        $attendancePct = 0;
        if ($totalStudents > 0) {
          $attendancePct = (int) round(($presentCount / $totalStudents) * 100);
        }

        $classStats[] = [
          'class_name' => $className,
          'total_students' => $totalStudents,
          'present_count' => $presentCount,
          'absent_count' => $absentCount,
          'attendance_pct' => $attendancePct,
        ];
      }
    }

    $classStmt->close();
  }
}

$recentAttendance = [];
// Fetch recent attendance rows for tabular review and actions.
if ($attendanceDateColumn !== null && admin_table_exists($connection, 'attendance')) {
  $classJoinOn = '1 = 0';
  if ($attendanceHasClassId) {
    $classJoinOn = 'c.id = a.class_id';
  } elseif ($studentsHasClassId) {
    $classJoinOn = 'c.id = s.class_id';
  }

  $classDisplayExpr = "'-'";
  if ($classNameColumn !== null && $studentsHasClass) {
    $classDisplayExpr = "COALESCE(NULLIF(c.{$classNameColumn}, ''), NULLIF(s.class, ''), '-')";
  } elseif ($classNameColumn !== null) {
    $classDisplayExpr = "COALESCE(NULLIF(c.{$classNameColumn}, ''), '-')";
  } elseif ($studentsHasClass) {
    $classDisplayExpr = "COALESCE(NULLIF(s.class, ''), '-')";
  }

  $subjectJoinSql = '';
  $subjectDisplayExpr = "'-'";
  if ($attendanceHasSubjectId && $subjectNameColumn !== null) {
    $subjectJoinSql = 'LEFT JOIN subjects sub ON sub.id = a.subject_id';
    $subjectDisplayExpr = "COALESCE(NULLIF(sub.{$subjectNameColumn}, ''), '-')";
  }

  $recentSql = "SELECT
      a.id,
      COALESCE(NULLIF(s.name, ''), '-') AS student_name,
      {$classDisplayExpr} AS class_name,
      {$subjectDisplayExpr} AS subject_name,
      COALESCE(NULLIF(a.status, ''), 'absent') AS attendance_status,
      a.`{$attendanceDateColumn}` AS attendance_date
    FROM attendance a
    LEFT JOIN students s ON s.id = a.student_id
    LEFT JOIN classes c ON {$classJoinOn}
    {$subjectJoinSql}
    ORDER BY a.`{$attendanceDateColumn}` DESC, a.id DESC
    LIMIT 100";

  $recentStmt = $connection->prepare($recentSql);
  if ($recentStmt) {
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    if ($recentResult) {
      while ($recentRow = $recentResult->fetch_assoc()) {
        $recentAttendance[] = $recentRow;
      }
    }
    $recentStmt->close();
  }
}

$flash = admin_pull_flash();
?>
<!-- Render attendance dashboards, summaries, and recent records. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Overview</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
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
      <h2><i class="fas fa-calendar-check"></i> Attendance Overview</h2>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="summary-item"><div class="summary-value"><?php echo (int) $overallAttendance; ?>%</div><div class="summary-label">Overall Attendance</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $presentToday; ?></div><div class="summary-label">Present Today</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $absentToday; ?></div><div class="summary-label">Absent Today</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $leaveToday; ?></div><div class="summary-label">On Leave</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $approvedLeaveToday; ?></div><div class="summary-label">Approved Leave Today</div></div>
    </div>
    <div class="content-card">
      <h5 class="mb-4">Class-wise Attendance</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Class</th><th>Total Students</th><th>Present</th><th>Absent</th><th>Attendance %</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($classStats)): ?>
              <?php foreach ($classStats as $classStat): ?>
                <tr>
                  <td><?php echo htmlspecialchars($classStat['class_name'] !== '' ? $classStat['class_name'] : '-'); ?></td>
                  <td><?php echo (int) $classStat['total_students']; ?></td>
                  <td><?php echo (int) $classStat['present_count']; ?></td>
                  <td><?php echo (int) $classStat['absent_count']; ?></td>
                  <td><?php echo (int) $classStat['attendance_pct']; ?>%</td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No attendance data available.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="content-card mt-4">
      <h5 class="mb-4">Students On Approved Leave Today</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Student</th><th>Roll No</th><th>Class</th><th>Leave Type</th><th>Description</th><th>Leave Dates</th><th>Reviewed By</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($studentsOnLeaveToday)): ?>
              <?php foreach ($studentsOnLeaveToday as $leaveRow): ?>
                <?php
                  $leaveTypeLabel = trim((string) ($leaveRow['leave_type'] ?? ''));
                  if ($leaveTypeLabel === '') {
                    $leaveTypeLabel = 'Leave';
                  } else {
                    $leaveTypeLabel = ucfirst($leaveTypeLabel);
                  }
                  $descriptionText = trim((string) ($leaveRow['reason'] ?? ''));
                  $remarkText = trim((string) ($leaveRow['teacher_remark'] ?? ''));
                ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) ($leaveRow['student_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($leaveRow['roll_no'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($leaveRow['class_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars($leaveTypeLabel); ?></td>
                  <td>
                    <div><?php echo htmlspecialchars($descriptionText !== '' ? $descriptionText : '-'); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($remarkText !== '' ? $remarkText : '-'); ?></div>
                  </td>
                  <td>
                    <div><?php echo !empty($leaveRow['from_date']) ? htmlspecialchars(date('M d, Y', strtotime((string) $leaveRow['from_date']))) : '-'; ?></div>
                    <div class="text-muted small">to <?php echo !empty($leaveRow['to_date']) ? htmlspecialchars(date('M d, Y', strtotime((string) $leaveRow['to_date']))) : '-'; ?></div>
                    <div class="text-muted small"><?php echo (int) ($leaveRow['days'] ?? 0); ?> day(s)</div>
                  </td>
                  <td><?php echo htmlspecialchars((string) ($leaveRow['reviewed_teacher'] ?? '-')); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No students are on approved leave today.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="content-card mt-4">
      <h5 class="mb-4">Recent Attendance Records</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Date</th><th>Student</th><th>Class</th><th>Subject</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($recentAttendance)): ?>
              <?php foreach ($recentAttendance as $attendanceRow): ?>
                <?php
                  $status = strtolower(trim((string) ($attendanceRow['attendance_status'] ?? 'absent')));
                  $statusText = ucfirst($status);
                  $statusClass = 'bg-secondary';
                  if ($status === 'present') {
                    $statusClass = 'bg-success';
                  } elseif ($status === 'absent') {
                    $statusClass = 'bg-danger';
                  } elseif ($status === 'late') {
                    $statusClass = 'bg-warning text-dark';
                  } elseif ($status === 'leave') {
                    $statusClass = 'bg-info text-dark';
                  }
                ?>
                <tr>
                  <td><?php echo !empty($attendanceRow['attendance_date']) ? htmlspecialchars(date('M d, Y', strtotime((string) $attendanceRow['attendance_date']))) : '-'; ?></td>
                  <td><?php echo htmlspecialchars((string) ($attendanceRow['student_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($attendanceRow['class_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($attendanceRow['subject_name'] ?? '-')); ?></td>
                  <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="edit-attendance.php?id=<?php echo (int) $attendanceRow['id']; ?>" title="Edit Attendance">
                      <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" action="" style="display:inline-block;" novalidate>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="attendance_id" value="<?php echo (int) $attendanceRow['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this attendance record?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No attendance records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

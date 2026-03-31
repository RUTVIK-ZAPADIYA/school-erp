<?php
// Admin page for recording and reviewing attendance.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

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
$totalMarkedToday = 0;

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

// Build class-wise attendance metrics with schema-aware joins.
if ($classNameColumn !== null) {
  $classResult = $connection->query( "SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
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

  $recentResult = $connection->query($recentSql);
  if ($recentResult) {
    while ($recentRow = $recentResult->fetch_assoc()) {
      $recentAttendance[] = $recentRow;
    }
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
                    <form method="POST" action="" style="display:inline-block;">
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

$today = date('Y-m-d');

$attendanceDateColumn = null;
if (admin_column_exists($connection, 'attendance', 'attendance_date')) {
  $attendanceDateColumn = 'attendance_date';
} elseif (admin_column_exists($connection, 'attendance', 'date')) {
  $attendanceDateColumn = 'date';
}

$presentToday = 0;
$absentToday = 0;
$leaveToday = 0;
$totalMarkedToday = 0;

if ($attendanceDateColumn !== null) {
  $summarySql = "SELECT
    SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('leave', 'l', 'on leave') THEN 1 ELSE 0 END) AS leave_count,
    COUNT(*) AS total_count
    FROM attendance
    WHERE {$attendanceDateColumn} = ?";

  $summaryStmt = mysqli_prepare($connection, $summarySql);
  if ($summaryStmt) {
    mysqli_stmt_bind_param($summaryStmt, 's', $today);
    mysqli_stmt_execute($summaryStmt);
    $summaryResult = mysqli_stmt_get_result($summaryStmt);
    $summaryRow = $summaryResult ? mysqli_fetch_assoc($summaryResult) : null;
    $presentToday = (int) ($summaryRow['present_count'] ?? 0);
    $absentToday = (int) ($summaryRow['absent_count'] ?? 0);
    $leaveToday = (int) ($summaryRow['leave_count'] ?? 0);
    $totalMarkedToday = (int) ($summaryRow['total_count'] ?? 0);
    mysqli_stmt_close($summaryStmt);
  }
}

$overallAttendance = $totalMarkedToday > 0 ? (int) round(($presentToday / $totalMarkedToday) * 100) : 0;

$classStats = [];
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
$studentsHasClass = admin_column_exists($connection, 'students', 'class');
$attendanceHasClassId = admin_column_exists($connection, 'attendance', 'class_id');

if ($classNameColumn !== null) {
  $classResult = mysqli_query($connection, "SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classResult) {
    while ($classRow = mysqli_fetch_assoc($classResult)) {
      $classId = (int) $classRow['id'];
      $className = (string) ($classRow['class_name'] ?? '');

      $totalStudents = 0;
      if ($studentsHasClassId) {
        $studentCountStmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM students WHERE class_id = ?');
        if ($studentCountStmt) {
          mysqli_stmt_bind_param($studentCountStmt, 'i', $classId);
          mysqli_stmt_execute($studentCountStmt);
          $studentCountResult = mysqli_stmt_get_result($studentCountStmt);
          $studentCountRow = $studentCountResult ? mysqli_fetch_assoc($studentCountResult) : null;
          $totalStudents = (int) ($studentCountRow['total'] ?? 0);
          mysqli_stmt_close($studentCountStmt);
        }
      }

      if ($totalStudents === 0 && $studentsHasClass && $className !== '') {
        $studentNameCountStmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM students WHERE class = ?');
        if ($studentNameCountStmt) {
          mysqli_stmt_bind_param($studentNameCountStmt, 's', $className);
          mysqli_stmt_execute($studentNameCountStmt);
          $studentNameCountResult = mysqli_stmt_get_result($studentNameCountStmt);
          $studentNameCountRow = $studentNameCountResult ? mysqli_fetch_assoc($studentNameCountResult) : null;
          $totalStudents = (int) ($studentNameCountRow['total'] ?? 0);
          mysqli_stmt_close($studentNameCountStmt);
        }
      }

      $presentCount = 0;
      $absentCount = 0;

      if ($attendanceDateColumn !== null) {
        if ($attendanceHasClassId) {
          $attendanceStmt = mysqli_prepare(
            $connection,
            "SELECT
              SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_count,
              SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_count
             FROM attendance
             WHERE class_id = ? AND {$attendanceDateColumn} = ?"
          );
          if ($attendanceStmt) {
            mysqli_stmt_bind_param($attendanceStmt, 'is', $classId, $today);
            mysqli_stmt_execute($attendanceStmt);
            $attendanceResult = mysqli_stmt_get_result($attendanceStmt);
            $attendanceRow = $attendanceResult ? mysqli_fetch_assoc($attendanceResult) : null;
            $presentCount = (int) ($attendanceRow['present_count'] ?? 0);
            $absentCount = (int) ($attendanceRow['absent_count'] ?? 0);
            mysqli_stmt_close($attendanceStmt);
          }
        } elseif ($studentsHasClassId) {
          $attendanceStmt = mysqli_prepare(
            $connection,
            "SELECT
              SUM(CASE WHEN LOWER(COALESCE(a.status, '')) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_count,
              SUM(CASE WHEN LOWER(COALESCE(a.status, '')) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_count
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id
             WHERE s.class_id = ? AND a.{$attendanceDateColumn} = ?"
          );
          if ($attendanceStmt) {
            mysqli_stmt_bind_param($attendanceStmt, 'is', $classId, $today);
            mysqli_stmt_execute($attendanceStmt);
            $attendanceResult = mysqli_stmt_get_result($attendanceStmt);
            $attendanceRow = $attendanceResult ? mysqli_fetch_assoc($attendanceResult) : null;
            $presentCount = (int) ($attendanceRow['present_count'] ?? 0);
            $absentCount = (int) ($attendanceRow['absent_count'] ?? 0);
            mysqli_stmt_close($attendanceStmt);
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
?>
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
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

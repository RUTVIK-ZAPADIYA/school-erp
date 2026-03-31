<?php
// Admin reports page for summarized academic and fee data.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

// Core entity counts for academic summary cards.
$studentCount = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM students', 0);
$teacherCount = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM teachers', 0);
$classCount = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM classes', 0);
$examCount = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM exams', 0);

// Financial totals for collected and pending fee amounts.
$feesCollected = (float) admin_scalar_value(
  $connection,
  "SELECT COALESCE(SUM(amount), 0) FROM fees WHERE LOWER(COALESCE(status, '')) IN ('paid', 'completed')",
  0
);
$feesPending = (float) admin_scalar_value(
  $connection,
  "SELECT COALESCE(SUM(amount), 0) FROM fees WHERE LOWER(COALESCE(status, '')) IN ('pending', 'partial', 'overdue')",
  0
);

// Attendance completion percentage used by the attendance report tile.
$attendancePresent = (int) admin_scalar_value(
  $connection,
  "SELECT COUNT(*) FROM attendance WHERE LOWER(COALESCE(status, '')) IN ('present', 'p')",
  0
);
$attendanceTotal = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM attendance', 0);
$attendancePct = $attendanceTotal > 0 ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0;
?>
<!-- Render report cards for operational metrics and analytics. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 25px 30px; border-radius: 10px; margin-bottom: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 5px solid #3498db; }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; display: flex; align-items: center; }
    .header h2 i { margin-right: 15px; color: #3498db; }
    .report-card { background: white; padding: 30px 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; cursor: pointer; height: 100%; }
    .report-icon { width: 70px; height: 70px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
    .report-icon i { font-size: 2rem; color: #3498db; }
    .report-title { font-size: 1.25rem; font-weight: 600; color: #2c3e50; margin-bottom: 12px; }
    .report-desc { color: #7f8c8d; font-size: 0.95rem; line-height: 1.6; }
    .report-metric { color: #1f4ea3; font-size: 1.4rem; font-weight: 800; margin-bottom: 6px; }
    .report-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chart-line"></i> Reports & Analytics</h2>
    </div>
    <div class="report-row">
      <div class="report-card">
        <div class="report-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="report-title">Student Report</div>
        <div class="report-metric"><?php echo (int) $studentCount; ?> Students</div>
        <div class="report-desc">Live student count from the database with real enrollment visibility.</div>
      </div>
      <div class="report-card">
        <div class="report-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="report-title">Teacher Report</div>
        <div class="report-metric"><?php echo (int) $teacherCount; ?> Teachers</div>
        <div class="report-desc">Track currently available teaching staff from teacher records.</div>
      </div>
      <div class="report-card">
        <div class="report-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="report-title">Financial Report</div>
        <div class="report-metric">₹<?php echo number_format($feesCollected, 2); ?></div>
        <div class="report-desc">Collected amount with ₹<?php echo number_format($feesPending, 2); ?> still pending.</div>
      </div>
      <div class="report-card">
        <div class="report-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="report-title">Attendance Report</div>
        <div class="report-metric"><?php echo (int) $attendancePct; ?>%</div>
        <div class="report-desc">Overall attendance based on all marked attendance records.</div>
      </div>
      <div class="report-card">
        <div class="report-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="report-title">Exam Report</div>
        <div class="report-metric"><?php echo (int) $examCount; ?> Exams</div>
        <div class="report-desc">Scheduled and completed exams available for analysis.</div>
      </div>
      <div class="report-card">
        <div class="report-icon"><i class="fas fa-school"></i></div>
        <div class="report-title">Class Report</div>
        <div class="report-metric"><?php echo (int) $classCount; ?> Classes</div>
        <div class="report-desc">Class structure and coverage across the academic system.</div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

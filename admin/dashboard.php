<?php
// Admin dashboard page for overall school metrics.
require_once __DIR__ . '/auth.php';
include '../includes/db_connect.php';

// Utility helpers keep dashboard queries resilient across schema variations.
function tableExists($conn, $tableName)
{
  $safeTable = $conn->real_escape_string( $tableName);
  $result = $conn->query( "SHOW TABLES LIKE '{$safeTable}'");
  return $result && $result->num_rows > 0;
}

function columnExists($conn, $tableName, $columnName)
{
  if (!tableExists($conn, $tableName)) {
    return false;
  }
  $safeTable = $conn->real_escape_string( $tableName);
  $safeColumn = $conn->real_escape_string( $columnName);
  $result = $conn->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
  return $result && $result->num_rows > 0;
}

function scalarValue($conn, $sql, $defaultValue = 0)
{
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return $defaultValue;
  }

  if (!$stmt->execute()) {
    $stmt->close();
    return $defaultValue;
  }

  $result = $stmt->get_result();
  if (!$result) {
    $stmt->close();
    return $defaultValue;
  }

  $row = $result->fetch_row();
  $stmt->close();
  if (!$row || !isset($row[0])) {
    return $defaultValue;
  }
  return $row[0];
}

function formatCompactCurrency($amount)
{
  $amount = (float) $amount;
  if ($amount >= 1000000000) {
    return '₹' . round($amount / 1000000000, 2) . 'B';
  }
  if ($amount >= 1000000) {
    return '₹' . round($amount / 1000000, 2) . 'M';
  }
  if ($amount >= 1000) {
    return '₹' . round($amount / 1000, 1) . 'K';
  }
  return '₹' . number_format($amount, 0);
}

// Load top-level KPIs for students, teachers, and staffing ratio.
$totalStudents = tableExists($conn, 'students') ? (int) scalarValue($conn, "SELECT COUNT(*) FROM students", 0) : 0;
$totalTeachers = tableExists($conn, 'teachers') ? (int) scalarValue($conn, "SELECT COUNT(*) FROM teachers", 0) : 0;

$teacherRatio = $totalTeachers > 0 ? '1:' . max(1, (int) round($totalStudents / $totalTeachers)) : 'N/A';

$feesPaid = 0.0;
$feesPending = 0.0;
$tuitionPct = 0;
$sportsPct = 0;
$labPct = 0;
$infraPct = 0;
$tuitionAmount = 0.0;
$sportsAmount = 0.0;
$labAmount = 0.0;
$infraAmount = 0.0;

// Aggregate fee totals and revenue stream distribution.
if (tableExists($conn, 'fees')) {
  $feesPaid = (float) scalarValue(
    $conn,
    "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(status,'')) IN ('paid','completed')",
    0
  );
  $feesPending = (float) scalarValue(
    $conn,
    "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(status,'')) IN ('pending','unpaid','due')",
    0
  );

  $tuitionAmount = (float) scalarValue($conn, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%tuition%'", 0);
  $sportsAmount = (float) scalarValue($conn, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%sport%'", 0);
  $labAmount = (float) scalarValue($conn, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%lab%' OR LOWER(COALESCE(fee_type,'')) LIKE '%tech%'", 0);
  $infraAmount = (float) scalarValue($conn, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%infra%'", 0);

  $streamTotal = $tuitionAmount + $sportsAmount + $labAmount + $infraAmount;
  if ($streamTotal > 0) {
    $tuitionPct = (int) round(($tuitionAmount / $streamTotal) * 100);
    $sportsPct = (int) round(($sportsAmount / $streamTotal) * 100);
    $labPct = (int) round(($labAmount / $streamTotal) * 100);
    $infraPct = max(0, 100 - ($tuitionPct + $sportsPct + $labPct));
  }
}

$totalFeeTarget = $feesPaid + $feesPending;
$feeCollectionPct = $totalFeeTarget > 0 ? (int) round(($feesPaid / $totalFeeTarget) * 100) : 0;

$attendancePct = 0.0;
$attendanceCount = 0;
if (tableExists($conn, 'attendance')) {
  $presentCount = (int) scalarValue(
    $conn,
    "SELECT COUNT(*) FROM attendance WHERE LOWER(COALESCE(status,'')) IN ('present','p')",
    0
  );
  $attendanceCount = (int) scalarValue($conn, "SELECT COUNT(*) FROM attendance", 0);
  if ($attendanceCount > 0) {
    $attendancePct = round(($presentCount / $attendanceCount) * 100, 1);
  }
}

$currentMonthEnrollments = 0;
$previousMonthEnrollments = 0;
if (tableExists($conn, 'students') && columnExists($conn, 'students', 'created_at')) {
  $currentMonthEnrollments = (int) scalarValue(
    $conn,
    "SELECT COUNT(*)
     FROM students
     WHERE YEAR(created_at) = YEAR(CURDATE())
       AND MONTH(created_at) = MONTH(CURDATE())",
    0
  );
  $previousMonthEnrollments = (int) scalarValue(
    $conn,
    "SELECT COUNT(*)
     FROM students
     WHERE YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
       AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))",
    0
  );
}

if ($previousMonthEnrollments > 0) {
  $enrollmentGrowthPct = (int) round((($currentMonthEnrollments - $previousMonthEnrollments) / $previousMonthEnrollments) * 100);
} elseif ($currentMonthEnrollments > 0) {
  $enrollmentGrowthPct = 100;
} else {
  $enrollmentGrowthPct = 0;
}

$attendanceProgress = max(0, min(100, (int) round($attendancePct)));
$feeCollectionProgress = max(0, min(100, (int) $feeCollectionPct));

$academicYearStart = (int) date('Y');
$academicYearLabel = $academicYearStart . '/' . substr((string) ($academicYearStart + 1), -2);
$todayDisplay = date('d M Y');

if ($feeCollectionPct >= 85) {
  $feeHealth = 'On Track';
  $feeHealthClass = 'health-good';
} elseif ($feeCollectionPct >= 60) {
  $feeHealth = 'Monitor';
  $feeHealthClass = 'health-watch';
} else {
  $feeHealth = 'Needs Action';
  $feeHealthClass = 'health-alert';
}

if ($attendancePct >= 90) {
  $attendanceHealth = 'Strong';
  $attendanceHealthClass = 'health-good';
} elseif ($attendancePct >= 75) {
  $attendanceHealth = 'Moderate';
  $attendanceHealthClass = 'health-watch';
} else {
  $attendanceHealth = 'Low';
  $attendanceHealthClass = 'health-alert';
}

$ratioPerTeacher = $totalTeachers > 0 ? ($totalStudents / $totalTeachers) : 0;
if ($totalTeachers <= 0) {
  $ratioHealth = 'No Data';
  $ratioHealthClass = 'health-muted';
} elseif ($ratioPerTeacher <= 20) {
  $ratioHealth = 'Balanced';
  $ratioHealthClass = 'health-good';
} elseif ($ratioPerTeacher <= 25) {
  $ratioHealth = 'Watch';
  $ratioHealthClass = 'health-watch';
} else {
  $ratioHealth = 'Overloaded';
  $ratioHealthClass = 'health-alert';
}
?>
<!-- Render dashboard cards, charts, and summary panels. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { box-sizing: border-box; }
    :root {
      --ink-900: #17243d;
      --ink-700: #364766;
      --ink-500: #60708e;
      --bg-soft: #f2f6fb;
      --surface: #ffffff;
      --brand: #0d6efd;
      --brand-strong: #0a58ca;
      --brand-tint: #cfe2ff;
      --accent: #f1b24a;
      --danger: #d64545;
      --success: #2d9b66;
      --warning: #c98515;
      --radius-md: 12px;
      --radius-lg: 18px;
      --shadow-sm: 0 6px 18px rgba(23, 36, 61, 0.08);
      --shadow-md: 0 14px 34px rgba(23, 36, 61, 0.1);
    }
    body {
      background: radial-gradient(circle at top right, #e4f9f2, transparent 32%), var(--bg-soft);
      color: var(--ink-900);
      font-family: 'Manrope', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    .dashboard-shell {
      margin-left: 280px;
      padding: 28px;
      min-height: 100vh;
    }
    .dashboard-inner {
      max-width: 1400px;
      margin: 0 auto;
    }
    .headline-panel {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
      border-radius: 18px;
      padding: 32px;
      margin-bottom: 28px;
      box-shadow: var(--shadow-md);
      color: white;
      position: relative;
      overflow: hidden;
    }
    .headline-panel h1,
    .headline-panel p,
    .headline-panel .headline-meta {
      color: white !important;
    }
    .headline-panel::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
      pointer-events: none;
    }
    .title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
    }
    .title-row h1 {
      margin: 0;
      font-size: 2.2rem;
      font-weight: 800;
      color: #ffffff !important;
      line-height: 1.2;
      letter-spacing: -0.3px;
    }
    .title-row p {
      margin: 6px 0 0;
      color: rgba(255, 255, 255, 0.85) !important;
      font-weight: 500;
      font-size: 0.95rem;
    }
    .headline-meta {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 10px;
      background: rgba(255, 255, 255, 0.15);
      color: white;
      border-radius: 50px;
      padding: 6px 14px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .title-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .btn-soft {
      border: 1px solid rgba(255, 255, 255, 0.4);
      background: rgba(255, 255, 255, 0.18);
      color: white !important;
      font-weight: 700;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 0.85rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-soft:hover {
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.6);
      transform: translateY(-1px);
      color: white !important;
    }
    .quick-actions-grid {
      margin-top: 16px;
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 10px;
      position: relative;
      z-index: 1;
    }
    .quick-link {
      border: 1px solid #d9e3f0;
      border-radius: 12px;
      background: var(--surface);
      color: var(--ink-900);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.8rem;
      padding: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      min-height: 90px;
      box-shadow: var(--shadow-sm);
      transition: all 0.2s ease;
      text-align: center;
    }
    .quick-link:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(13, 110, 253, 0.12);
      border-color: var(--brand);
    }
    .quick-link-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: var(--brand-tint);
      color: var(--brand);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }
    .insight-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
    }
    .insight-pill {
      border: 1px solid #e0e6ed;
      background: var(--surface);
      border-radius: 12px;
      padding: 12px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow-sm);
      transition: all 0.2s ease;
    }
    .insight-pill:hover {
      box-shadow: 0 8px 20px rgba(13, 110, 253, 0.1);
      border-color: var(--brand);
    }
    .insight-label {
      color: var(--ink-500);
      font-size: 0.8rem;
      font-weight: 600;
    }
    .insight-value {
      font-size: 0.8rem;
      font-weight: 700;
      border-radius: 50px;
      padding: 4px 10px;
      min-width: 70px;
      text-align: center;
    }
    .health-good {
      color: var(--success);
      background: #d1fae5;
    }
    .health-watch {
      color: var(--warning);
      background: #fff6e5;
    }
    .health-alert {
      color: var(--danger);
      background: #fdecef;
    }
    .health-muted {
      color: var(--ink-500);
      background: #f0f4f8;
    }
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 32px;
    }
    .metric-card {
      border: 1px solid #e8eef8;
      border-radius: 14px;
      background: var(--surface);
      padding: 18px;
      box-shadow: var(--shadow-sm);
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
    }
    .metric-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--brand);
      transform: scaleX(0);
      transition: transform 0.2s ease;
    }
    .metric-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(13, 110, 253, 0.12);
      border-color: var(--brand);
    }
    .metric-card:hover::before {
      transform: scaleX(1);
    }
    .metric-card.metric-enroll .metric-icon {
      background: var(--brand-tint);
      color: var(--brand);
    }
    .metric-card.metric-fees .metric-icon {
      background: #fff5e6;
      color: var(--accent);
    }
    .metric-card.metric-ratio .metric-icon {
      background: #e8f4f8;
      color: #0d99ff;
    }
    .metric-card.metric-performance .metric-icon {
      background: #d1fae5;
      color: var(--success);
    }
    .metric-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }
    .metric-icon {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }
    .metric-chip {
      border-radius: 50px;
      padding: 3px 10px;
      font-size: 0.7rem;
      font-weight: 700;
      color: var(--brand);
      background: var(--brand-tint);
    }
    .metric-label {
      color: var(--ink-500);
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 4px;
    }
    .metric-value {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1;
      color: var(--ink-900);
      margin-bottom: 6px;
    }
    .metric-foot {
      margin-top: 6px;
      color: var(--ink-500);
      font-size: 0.75rem;
      font-weight: 500;
    }
    .metric-progress {
      margin-top: 10px;
      width: 100%;
      height: 6px;
      border-radius: 999px;
      overflow: hidden;
      background: #e8eef8;
    }
    .metric-progress span {
      display: block;
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, var(--brand) 0%, var(--brand-strong) 100%);
      transition: width 0.5s ease;
    }
    .focus-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
      margin-bottom: 32px;
    }
    .focus-card {
      border: 1px solid #e8eef8;
      border-radius: 14px;
      background: var(--surface);
      box-shadow: var(--shadow-sm);
      padding: 18px;
      transition: all 0.2s ease;
    }
    .focus-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(13, 110, 253, 0.12);
      border-color: var(--brand);
    }
    .focus-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 12px;
    }
    .focus-title {
      color: var(--ink-500);
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .focus-value {
      color: var(--ink-900);
      font-size: 1.3rem;
      font-weight: 800;
    }
    .focus-track {
      width: 100%;
      height: 8px;
      border-radius: 999px;
      overflow: hidden;
      background: #e8eef8;
      margin-bottom: 10px;
    }
    .focus-track span {
      display: block;
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, var(--brand) 0%, var(--brand-strong) 100%);
      transition: width 0.5s ease;
    }
    .focus-note {
      color: var(--ink-500);
      font-size: 0.75rem;
      font-weight: 500;
    }
    .section-kicker {
      margin: 32px 0 16px;
      color: var(--ink-500);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.6px;
      text-transform: uppercase;
    }
    .panel {
      border: 1px solid #e8eef8;
      border-radius: 14px;
      background: var(--surface);
      box-shadow: var(--shadow-sm);
      padding: 24px;
      height: 100%;
      transition: all 0.2s ease;
    }
    .panel:hover {
      box-shadow: 0 12px 30px rgba(13, 110, 253, 0.1);
      border-color: var(--brand);
    }
    .panel h4 {
      margin: 0 0 6px;
      color: var(--ink-900);
      font-size: 1rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .panel h4::before {
      content: '';
      width: 3px;
      height: 18px;
      background: var(--brand);
      border-radius: 2px;
    }
    .panel small {
      color: var(--ink-500);
      font-weight: 500;
      display: block;
      margin-bottom: 12px;
      font-size: 0.8rem;
    }
    .summary-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      padding: 22px 18px;
      border-bottom: 1px solid #f5f7fa;
      background: #fafbfc;
      border-radius: 8px;
      margin-bottom: 12px;
      transition: all 0.2s ease;
    }
    .summary-item:hover {
      background: #f0f4f8;
      border-color: #e8eef8;
    }
    .summary-item:last-child {
      border-bottom: 1px solid #f5f7fa;
      margin-bottom: 0;
    }
    .summary-label {
      color: var(--ink-700);
      font-size: 0.87rem;
      font-weight: 600;
      flex: 1;
    }
    .summary-value {
      color: var(--ink-900);
      font-size: 0.95rem;
      font-weight: 700;
      text-align: right;
    }
    @media (max-width: 992px) {
      .dashboard-shell {
        margin-left: 0;
        padding: 72px 14px 20px;
      }
      .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }
      .focus-grid {
        grid-template-columns: 1fr;
      }
      .headline-panel {
        padding: 24px;
      }
      .insight-grid {
        grid-template-columns: 1fr;
      }
      .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      .title-actions {
        justify-content: flex-start;
        width: 100%;
      }
    }
    @media (max-width: 600px) {
      .title-row {
        flex-direction: column;
      }
      .metrics-grid {
        grid-template-columns: 1fr;
      }
      .quick-actions-grid {
        grid-template-columns: 1fr;
      }
      .title-row h1 {
        font-size: 1.6rem;
      }
      .headline-panel {
        padding: 18px;
      }
      .panel {
        padding: 16px;
      }
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <?php include '../includes/error-modal.php'; ?>

  <div class="dashboard-shell">
    <div class="dashboard-inner">
    <div class="headline-panel">
      <div class="title-row">
        <div>
          <h1>Scholar Metric Insights</h1>
          <p>Institutional performance overview for Academic Year <?php echo htmlspecialchars($academicYearLabel); ?></p>
          <span class="headline-meta"><i class="fas fa-calendar-day"></i> Updated <?php echo htmlspecialchars($todayDisplay); ?></span>
        </div>
        <div class="title-actions">
          <a href="add-student.php" class="btn-soft"><i class="fas fa-user-plus"></i> Add Student</a>
          <a href="add-teacher.php" class="btn-soft"><i class="fas fa-chalkboard-user"></i> Add Teacher</a>
          <a href="reports.php" class="btn-soft"><i class="fas fa-chart-column"></i> View Reports</a>
        </div>
      </div>

      <div class="insight-grid">
        <div class="insight-pill">
          <span class="insight-label">Fee Collection Health</span>
          <span class="insight-value <?php echo $feeHealthClass; ?>"><?php echo htmlspecialchars($feeHealth); ?></span>
        </div>
        <div class="insight-pill">
          <span class="insight-label">Attendance Health</span>
          <span class="insight-value <?php echo $attendanceHealthClass; ?>"><?php echo htmlspecialchars($attendanceHealth); ?></span>
        </div>
        <div class="insight-pill">
          <span class="insight-label">Classroom Capacity</span>
          <span class="insight-value <?php echo $ratioHealthClass; ?>"><?php echo htmlspecialchars($ratioHealth); ?></span>
        </div>
      </div>

      <div class="quick-actions-grid">
        <a href="students.php" class="quick-link">
          <span class="quick-link-icon"><i class="fas fa-users"></i></span>
          Manage Students
        </a>
        <a href="teachers.php" class="quick-link">
          <span class="quick-link-icon"><i class="fas fa-person-chalkboard"></i></span>
          Manage Teachers
        </a>
        <a href="fees.php" class="quick-link">
          <span class="quick-link-icon"><i class="fas fa-receipt"></i></span>
          Fee Records
        </a>
        <a href="attendance.php" class="quick-link">
          <span class="quick-link-icon"><i class="fas fa-clipboard-check"></i></span>
          Attendance
        </a>
        <a href="notices.php" class="quick-link">
          <span class="quick-link-icon"><i class="fas fa-bullhorn"></i></span>
          Notices
        </a>
      </div>
    </div>

    <div class="metrics-grid">
      <div class="metric-card metric-enroll">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-user-graduate"></i></span>
          <span class="metric-chip"><?php echo ($enrollmentGrowthPct >= 0 ? '+' : '') . $enrollmentGrowthPct; ?>%</span>
        </div>
        <div class="metric-label">Total Enrollment</div>
        <div class="metric-value"><?php echo number_format($totalStudents); ?></div>
        <div class="metric-foot">Students currently in the system</div>
      </div>
      <div class="metric-card metric-fees">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-money-bill-wave"></i></span>
          <span class="metric-chip"><?php echo $feeCollectionPct; ?>% Target</span>
        </div>
        <div class="metric-label">Fee Collection</div>
        <div class="metric-value"><?php echo formatCompactCurrency($feesPaid); ?></div>
        <div class="metric-foot">Expected: <?php echo formatCompactCurrency($totalFeeTarget); ?> total</div>
        <div class="metric-progress"><span style="width: <?php echo $feeCollectionProgress; ?>%;"></span></div>
      </div>
      <div class="metric-card metric-ratio">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-users"></i></span>
          <span class="metric-chip <?php echo $ratioHealthClass; ?>"><?php echo htmlspecialchars($ratioHealth); ?></span>
        </div>
        <div class="metric-label">Teacher-Student Ratio</div>
        <div class="metric-value"><?php echo htmlspecialchars($teacherRatio); ?></div>
        <div class="metric-foot">Target ratio is 1:20</div>
      </div>
      <div class="metric-card metric-performance">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-chart-line"></i></span>
          <span class="metric-chip <?php echo $attendanceHealthClass; ?>"><?php echo htmlspecialchars($attendanceHealth); ?></span>
        </div>
        <div class="metric-label">Academic Performance</div>
        <div class="metric-value"><?php echo $attendancePct; ?>%</div>
        <div class="metric-foot">Attendance-based performance indicator</div>
        <div class="metric-progress"><span style="width: <?php echo $attendanceProgress; ?>%;"></span></div>
      </div>
    </div>

    <div class="focus-grid">
      <div class="focus-card">
        <div class="focus-head">
          <span class="focus-title">Fee Target Completion</span>
          <span class="focus-value"><?php echo $feeCollectionPct; ?>%</span>
        </div>
        <div class="focus-track"><span style="width: <?php echo $feeCollectionProgress; ?>%;"></span></div>
        <div class="focus-note">Collected <?php echo formatCompactCurrency($feesPaid); ?> from <?php echo formatCompactCurrency($totalFeeTarget); ?> target</div>
      </div>
      <div class="focus-card">
        <div class="focus-head">
          <span class="focus-title">Attendance Stability</span>
          <span class="focus-value"><?php echo $attendancePct; ?>%</span>
        </div>
        <div class="focus-track"><span style="width: <?php echo $attendanceProgress; ?>%;"></span></div>
        <div class="focus-note"><?php echo number_format($attendanceCount); ?> attendance records captured in current dataset</div>
      </div>
    </div>

    <div class="section-kicker"><i class="fas fa-users"></i> Enrollment and Revenue Pulse</div>
    <div class="row">
      <div class="col-lg-8 mb-3">
        <div class="panel">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4><i class="fas fa-chart-line"></i> Enrollment Snapshot</h4>
              <small>Live metrics from student records</small>
            </div>
            <a href="students.php" class="btn btn-sm" style="background: var(--brand); color: white !important; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 700; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">View Students</a>
          </div>

          <div class="summary-item">
            <span class="summary-label">Current Month Enrollments</span>
            <span class="summary-value"><?php echo number_format($currentMonthEnrollments); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Previous Month Enrollments</span>
            <span class="summary-value"><?php echo number_format($previousMonthEnrollments); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Monthly Growth</span>
            <span class="summary-value <?php echo $enrollmentGrowthPct >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($enrollmentGrowthPct >= 0 ? '+' : '') . $enrollmentGrowthPct; ?>%</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Total Active Students</span>
            <span class="summary-value"><?php echo number_format($totalStudents); ?></span>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mb-3">
        <div class="panel h-100">
          <h4><i class="fas fa-money-bill-wave"></i> Fee Stream Distribution</h4>
          <small>Calculated from fee type records</small>

          <div class="summary-item">
            <span class="summary-label">Tuition Fees</span>
            <span class="summary-value"><?php echo formatCompactCurrency($tuitionAmount); ?> (<?php echo $tuitionPct; ?>%)</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Sports & Extra-curricular</span>
            <span class="summary-value"><?php echo formatCompactCurrency($sportsAmount); ?> (<?php echo $sportsPct; ?>%)</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Laboratory & Tech</span>
            <span class="summary-value"><?php echo formatCompactCurrency($labAmount); ?> (<?php echo $labPct; ?>%)</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Infrastructure Development</span>
            <span class="summary-value"><?php echo formatCompactCurrency($infraAmount); ?> (<?php echo $infraPct; ?>%)</span>
          </div>

          <div class="mt-4 p-4 rounded-3" style="background: linear-gradient(135deg, var(--danger) 0%, #c53c3c 100%); border: none; color: white;">
            <div class="small fw-semibold"><i class="fas fa-exclamation-circle"></i> Unpaid Balance</div>
            <div class="h4 mb-0 mt-2 fw-bold"><?php echo '₹' . number_format($feesPending, 0); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="section-kicker"><i class="fas fa-building"></i> Institution Operations</div>
    <div class="row">
      <div class="col-lg-12 mb-3">
        <div class="panel h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4><i class="fas fa-gauge-high"></i> Operational Snapshot</h4>
              <small>Live institutional indicators from current data</small>
            </div>
            <a href="reports.php" class="btn btn-sm" style="background: var(--brand); color: white !important; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 700; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">View Detailed Reports</a>
          </div>

          <div class="summary-item">
            <span class="summary-label">Attendance Records Captured</span>
            <span class="summary-value"><?php echo number_format($attendanceCount); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Attendance Performance</span>
            <span class="summary-value"><?php echo $attendancePct; ?>%</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Total Teachers</span>
            <span class="summary-value"><?php echo number_format($totalTeachers); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Total Collected Fees</span>
            <span class="summary-value"><?php echo formatCompactCurrency($feesPaid); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Notice Board</span>
            <span class="summary-value"><a href="notices.php" class="text-decoration-none">Manage Notices</a></span>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

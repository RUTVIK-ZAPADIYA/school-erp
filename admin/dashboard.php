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
  $result = $conn->query( $sql);
  if (!$result) {
    return $defaultValue;
  }
  $row = $result->fetch_row();
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
    body {
      background:
        radial-gradient(circle at 8% 12%, rgba(58, 123, 231, 0.14), transparent 30%),
        radial-gradient(circle at 90% 4%, rgba(16, 185, 129, 0.1), transparent 28%),
        #eef3f9;
      color: #172746;
    }
    .dashboard-shell {
      margin-left: 280px;
      padding: 24px;
      min-height: 100vh;
    }
    .dashboard-inner {
      max-width: 1260px;
      margin: 0 auto;
    }
    .topbar {
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid #d6e4f4;
      border-radius: 18px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      box-shadow: 0 12px 26px rgba(23, 36, 61, 0.08);
      margin-bottom: 18px;
      position: sticky;
      top: 14px;
      z-index: 20;
      backdrop-filter: blur(8px);
    }
    .topbar-search {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #f5f8fc;
      border: 1px solid #dbe8f5;
      border-radius: 12px;
      padding: 8px 12px;
      min-width: 320px;
      flex: 1;
      max-width: 460px;
    }
    .topbar-search i {
      color: #6f84a6;
      font-size: 0.9rem;
    }
    .topbar-search input {
      border: 0;
      outline: 0;
      background: transparent;
      width: 100%;
      color: #1f2f4d;
      font-size: 0.92rem;
      font-weight: 600;
    }
    .topbar-links {
      display: flex;
      gap: 14px;
      align-items: center;
    }
    .topbar-link {
      text-decoration: none;
      color: #536b91;
      font-size: 0.87rem;
      font-weight: 700;
      padding-bottom: 5px;
      border-bottom: 2px solid transparent;
    }
    .topbar-link.active {
      color: #18356b;
      border-color: #2a61d8;
    }
    .headline-panel {
      border: 1px solid #d7e4f2;
      border-radius: 18px;
      padding: 18px;
      background: linear-gradient(120deg, #ffffff 0%, #f2f7ff 62%, #eefaf5 100%);
      box-shadow: 0 14px 30px rgba(23, 36, 61, 0.08);
      margin-bottom: 16px;
    }
    .title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .title-row h1 {
      margin: 0;
      font-size: 2.05rem;
      font-weight: 800;
      color: #141f36;
      line-height: 1.15;
    }
    .title-row p {
      margin: 4px 0 0;
      color: #607493;
      font-weight: 600;
      font-size: 0.93rem;
    }
    .headline-meta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
      background: #e9f1fc;
      color: #244777;
      border-radius: 999px;
      padding: 6px 12px;
      font-size: 0.75rem;
      font-weight: 700;
    }
    .title-actions {
      display: flex;
      gap: 10px;
    }
    .btn-soft {
      border: 1px solid #d6e3f2;
      background: #f4f8fd;
      color: #334a6f;
      font-weight: 700;
      border-radius: 10px;
      padding: 8px 14px;
      font-size: 0.85rem;
    }
    .insight-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }
    .insight-pill {
      border: 1px solid #d9e7f6;
      background: rgba(255, 255, 255, 0.85);
      border-radius: 12px;
      padding: 10px 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }
    .insight-label {
      color: #4f668d;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .insight-value {
      font-size: 0.78rem;
      font-weight: 800;
      border-radius: 999px;
      padding: 3px 10px;
    }
    .health-good {
      color: #196b47;
      background: #e6f7ef;
    }
    .health-watch {
      color: #7a5a1e;
      background: #fff6e5;
    }
    .health-alert {
      color: #8a2737;
      background: #fdecef;
    }
    .health-muted {
      color: #486182;
      background: #eaf1fb;
    }
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 16px;
    }
    .metric-card {
      border: 1px solid #dbe8f5;
      border-radius: 14px;
      background: #fff;
      padding: 14px;
      box-shadow: 0 8px 18px rgba(23, 36, 61, 0.06);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 24px rgba(23, 36, 61, 0.12);
    }
    .metric-card.metric-enroll .metric-icon {
      background: #ebf3ff;
      color: #1f4fb5;
    }
    .metric-card.metric-fees .metric-icon {
      background: #e9f9ef;
      color: #1f7c4e;
    }
    .metric-card.metric-ratio .metric-icon {
      background: #fff2e7;
      color: #9a5418;
    }
    .metric-card.metric-performance .metric-icon {
      background: #efeafe;
      color: #5a3ba5;
    }
    .metric-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }
    .metric-icon {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #edf3ff;
      color: #2d57b0;
      font-size: 0.9rem;
    }
    .metric-chip {
      border-radius: 999px;
      padding: 2px 9px;
      font-size: 0.68rem;
      font-weight: 700;
      color: #375175;
      background: #eaf2fb;
    }
    .metric-label {
      color: #5a6d8d;
      font-size: 0.78rem;
      font-weight: 700;
      margin-bottom: 2px;
    }
    .metric-value {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1;
      color: #18233c;
    }
    .metric-foot {
      margin-top: 6px;
      color: #8394ad;
      font-size: 0.72rem;
      font-weight: 600;
    }
    .section-kicker {
      margin: 4px 0 10px;
      color: #3d557b;
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }
    .panel {
      border: 1px solid #dbe8f5;
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 10px 22px rgba(23, 36, 61, 0.07);
      padding: 18px;
      height: 100%;
    }
    .panel h4 {
      margin: 0;
      color: #1a2a49;
      font-size: 1.05rem;
      font-weight: 800;
    }
    .panel small {
      color: #7a8ea9;
      font-weight: 600;
    }
    .summary-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid #e8eff8;
    }
    .summary-item:last-child {
      border-bottom: 0;
      padding-bottom: 0;
    }
    .summary-label {
      color: #5b7091;
      font-size: 0.85rem;
      font-weight: 700;
    }
    .summary-value {
      color: #162948;
      font-size: 0.92rem;
      font-weight: 800;
    }
    .fab-quick {
      position: fixed;
      right: 18px;
      bottom: 16px;
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      background: #12356f;
      color: #eaf2ff;
      border: 0;
      box-shadow: 0 12px 24px rgba(18, 53, 111, 0.34);
      z-index: 1003;
    }
    @media (max-width: 992px) {
      .dashboard-shell {
        margin-left: 0;
        padding: 72px 14px 20px;
      }
      .metrics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
      .topbar {
        flex-wrap: wrap;
      }
      .topbar-search {
        max-width: 100%;
        min-width: 100%;
      }
      .headline-panel {
        padding: 14px;
      }
      .insight-grid {
        grid-template-columns: 1fr;
      }
      .title-actions {
        width: 100%;
        justify-content: flex-start;
      }
    }
    @media (max-width: 600px) {
      .title-row {
        flex-direction: column;
      }
      .metrics-grid {
        grid-template-columns: 1fr;
      }
      .title-row h1 {
        font-size: 1.6rem;
      }
      .topbar-links {
        width: 100%;
        justify-content: space-between;
      }
      .topbar-link {
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="dashboard-shell">
    <div class="dashboard-inner">
    <div class="topbar">
      <div class="topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search institutional records...">
      </div>
      <div class="topbar-links">
        <a href="#" class="topbar-link active">Dashboard</a>
        <a href="reports.php" class="topbar-link">Reports</a>
        <a href="settings.php" class="topbar-link">Settings</a>
      </div>
    </div>

    <div class="headline-panel">
      <div class="title-row">
        <div>
          <h1>Scholar Metric Insights</h1>
          <p>Institutional performance overview for Academic Year <?php echo htmlspecialchars($academicYearLabel); ?></p>
          <span class="headline-meta"><i class="fas fa-calendar-day"></i> Updated <?php echo htmlspecialchars($todayDisplay); ?></span>
        </div>
        <div class="title-actions">
          <button class="btn-soft"><i class="fas fa-download"></i> Download Summary</button>
          <button class="btn btn-primary"><i class="fas fa-bolt"></i> Generate Report</button>
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
      </div>
    </div>

    <div class="section-kicker">Enrollment and Revenue Pulse</div>
    <div class="row">
      <div class="col-lg-8 mb-3">
        <div class="panel">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h4>Enrollment Snapshot</h4>
              <small>Live metrics from student records</small>
            </div>
            <a href="students.php" class="small fw-bold">View Students</a>
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
          <h4>Fee Stream Distribution</h4>
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

          <div class="mt-4 p-3 rounded-3" style="background:#fff2f2;border:1px solid #ffd4d4;">
            <div class="small fw-semibold text-danger"><i class="fas fa-triangle-exclamation"></i> Unpaid Balance</div>
            <div class="h4 mb-0 mt-1 text-danger fw-bold"><?php echo '₹' . number_format($feesPending, 0); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="section-kicker">Institution Operations</div>
    <div class="row">
      <div class="col-lg-12 mb-3">
        <div class="panel h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <h4>Operational Snapshot</h4>
              <small>Live institutional indicators from current data</small>
            </div>
            <a href="reports.php" class="small fw-bold">View Detailed Reports</a>
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
        </div>
      </div>
    </div>
    </div>
  </div>

  <button class="fab-quick" title="Quick actions">
    <i class="fas fa-calendar-week"></i>
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';

function tableExists($connection, $tableName)
{
  $safeTable = mysqli_real_escape_string($connection, $tableName);
  $result = mysqli_query($connection, "SHOW TABLES LIKE '{$safeTable}'");
  return $result && mysqli_num_rows($result) > 0;
}

function columnExists($connection, $tableName, $columnName)
{
  if (!tableExists($connection, $tableName)) {
    return false;
  }
  $safeTable = mysqli_real_escape_string($connection, $tableName);
  $safeColumn = mysqli_real_escape_string($connection, $columnName);
  $result = mysqli_query($connection, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
  return $result && mysqli_num_rows($result) > 0;
}

function scalarValue($connection, $sql, $defaultValue = 0)
{
  $result = mysqli_query($connection, $sql);
  if (!$result) {
    return $defaultValue;
  }
  $row = mysqli_fetch_row($result);
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

$totalStudents = tableExists($connection, 'students') ? (int) scalarValue($connection, "SELECT COUNT(*) FROM students", 0) : 0;
$totalTeachers = tableExists($connection, 'teachers') ? (int) scalarValue($connection, "SELECT COUNT(*) FROM teachers", 0) : 0;

$teacherRatio = $totalTeachers > 0 ? '1:' . max(1, (int) round($totalStudents / $totalTeachers)) : 'N/A';

$feesPaid = 0.0;
$feesPending = 0.0;
$tuitionPct = 68;
$sportsPct = 15;
$labPct = 12;
$infraPct = 5;

if (tableExists($connection, 'fees')) {
  $feesPaid = (float) scalarValue(
    $connection,
    "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(status,'')) IN ('paid','completed')",
    0
  );
  $feesPending = (float) scalarValue(
    $connection,
    "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(status,'')) IN ('pending','unpaid','due')",
    0
  );

  $tuitionAmount = (float) scalarValue($connection, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%tuition%'", 0);
  $sportsAmount = (float) scalarValue($connection, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%sport%'", 0);
  $labAmount = (float) scalarValue($connection, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%lab%' OR LOWER(COALESCE(fee_type,'')) LIKE '%tech%'", 0);
  $infraAmount = (float) scalarValue($connection, "SELECT COALESCE(SUM(amount),0) FROM fees WHERE LOWER(COALESCE(fee_type,'')) LIKE '%infra%'", 0);

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

$attendancePct = 84.2;
if (tableExists($connection, 'attendance')) {
  $presentCount = (int) scalarValue(
    $connection,
    "SELECT COUNT(*) FROM attendance WHERE LOWER(COALESCE(status,'')) IN ('present','p')",
    0
  );
  $attendanceCount = (int) scalarValue($connection, "SELECT COUNT(*) FROM attendance", 0);
  if ($attendanceCount > 0) {
    $attendancePct = round(($presentCount / $attendanceCount) * 100, 1);
  }
}

$currentYear = date('Y');
$monthLabels = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
$monthValues = array_fill(0, 12, 0);

if (tableExists($connection, 'students') && columnExists($connection, 'students', 'created_at')) {
  $monthlyResult = mysqli_query(
    $connection,
    "SELECT MONTH(created_at) AS month_no, COUNT(*) AS total
     FROM students
     WHERE YEAR(created_at) = {$currentYear}
     GROUP BY MONTH(created_at)"
  );
  if ($monthlyResult) {
    while ($row = mysqli_fetch_assoc($monthlyResult)) {
      $monthIndex = (int) $row['month_no'] - 1;
      if ($monthIndex >= 0 && $monthIndex < 12) {
        $monthValues[$monthIndex] = (int) $row['total'];
      }
    }
  }
}

if (array_sum($monthValues) === 0) {
  $monthValues = [8, 10, 9, 13, 16, 15, 19, 22, 24, 21, 20, 22];
}

$maxMonthValue = max($monthValues);
$maxMonthIndex = (int) array_search($maxMonthValue, $monthValues, true);
$lastMonth = (int) date('n') - 1;
$previousMonth = max(0, $lastMonth - 1);
$currentMonthValue = $monthValues[$lastMonth];
$previousMonthValue = $monthValues[$previousMonth] > 0 ? $monthValues[$previousMonth] : 1;
$enrollmentGrowthPct = (int) round((($currentMonthValue - $previousMonthValue) / $previousMonthValue) * 100);
?>
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
    .dashboard-shell {
      margin-left: 280px;
      padding: 24px;
    }
    .topbar {
      background: #ffffff;
      border: 1px solid #dbe8f5;
      border-radius: 16px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      box-shadow: 0 8px 18px rgba(23, 36, 61, 0.07);
      margin-bottom: 18px;
    }
    .topbar-search {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #f2f6fb;
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
    .title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }
    .title-row h1 {
      margin: 0;
      font-size: 2rem;
      font-weight: 800;
      color: #141f36;
    }
    .title-row p {
      margin: 2px 0 0;
      color: #607493;
      font-weight: 600;
      font-size: 0.93rem;
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
    .panel {
      border: 1px solid #dbe8f5;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 18px rgba(23, 36, 61, 0.06);
      padding: 16px;
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
    .chart-bars {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      align-items: end;
      gap: 8px;
      min-height: 180px;
      margin-top: 12px;
    }
    .bar {
      background: #dbe6f7;
      border-radius: 8px 8px 0 0;
      position: relative;
      transition: 0.25s ease;
    }
    .bar:hover,
    .bar.active {
      background: #0f2f66;
    }
    .bar-label {
      position: absolute;
      bottom: -20px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.58rem;
      font-weight: 700;
      color: #7488a4;
    }
    .stream-item {
      margin-top: 14px;
    }
    .stream-line {
      height: 6px;
      border-radius: 20px;
      background: #e4edf8;
      overflow: hidden;
      margin-top: 6px;
    }
    .stream-fill {
      height: 100%;
      background: linear-gradient(90deg, #12356f, #2f61be);
    }
    .donut {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      margin: 10px auto 12px;
      background: conic-gradient(#0f2f66 0 84%, #f2a94f 84% 90%, #e6edf7 90% 100%);
      display: grid;
      place-items: center;
    }
    .donut-core {
      width: 108px;
      height: 108px;
      border-radius: 50%;
      background: #fff;
      display: grid;
      place-items: center;
      text-align: center;
    }
    .donut-core strong {
      font-size: 2rem;
      line-height: 1;
      color: #102b59;
    }
    .dept-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      padding: 9px 0;
      border-bottom: 1px solid #e8eff8;
    }
    .dept-item:last-child {
      border-bottom: 0;
      padding-bottom: 0;
    }
    .dept-badge {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 0.74rem;
      color: #18356b;
      background: #e7eefb;
      margin-right: 10px;
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
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="dashboard-shell">
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

    <div class="title-row">
      <div>
        <h1>Scholar Metric Insights</h1>
        <p>Institutional Performance Overview - Academic Year 2023/24</p>
      </div>
      <div class="title-actions">
        <button class="btn-soft"><i class="fas fa-download"></i> Export PDF</button>
        <button class="btn btn-primary"><i class="fas fa-bolt"></i> Generate Report</button>
      </div>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-user-graduate"></i></span>
          <span class="metric-chip"><?php echo ($enrollmentGrowthPct >= 0 ? '+' : '') . $enrollmentGrowthPct; ?>%</span>
        </div>
        <div class="metric-label">Total Enrollment</div>
        <div class="metric-value"><?php echo number_format($totalStudents); ?></div>
        <div class="metric-foot">Students currently in the system</div>
      </div>
      <div class="metric-card">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-money-bill-wave"></i></span>
          <span class="metric-chip"><?php echo $feeCollectionPct; ?>% Target</span>
        </div>
        <div class="metric-label">Fee Collection</div>
        <div class="metric-value"><?php echo formatCompactCurrency($feesPaid); ?></div>
        <div class="metric-foot">Expected: <?php echo formatCompactCurrency($totalFeeTarget); ?> total</div>
      </div>
      <div class="metric-card">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-users"></i></span>
          <span class="metric-chip" style="background:#fdeaea;color:#ab2f2f;">Alert</span>
        </div>
        <div class="metric-label">Teacher-Student Ratio</div>
        <div class="metric-value"><?php echo htmlspecialchars($teacherRatio); ?></div>
        <div class="metric-foot">Target ratio is 1:20</div>
      </div>
      <div class="metric-card">
        <div class="metric-head">
          <span class="metric-icon"><i class="fas fa-chart-line"></i></span>
          <span class="metric-chip">Record</span>
        </div>
        <div class="metric-label">Academic Performance</div>
        <div class="metric-value"><?php echo $attendancePct; ?>%</div>
        <div class="metric-foot">Attendance-based performance indicator</div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 mb-3">
        <div class="panel">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h4>Enrollment Growth</h4>
              <small>Last 12 months data analysis</small>
            </div>
            <div class="d-flex gap-2">
              <button class="btn-soft py-1 px-2">2023</button>
              <button class="btn-soft py-1 px-2">2022</button>
            </div>
          </div>

          <div class="chart-bars" aria-label="Enrollment Growth Chart">
            <?php foreach ($monthValues as $index => $value): ?>
              <?php
                $height = $maxMonthValue > 0 ? max(12, (int) round(($value / $maxMonthValue) * 100)) : 12;
                $activeClass = $index === $maxMonthIndex ? 'active' : '';
              ?>
              <div class="bar <?php echo $activeClass; ?>" style="height:<?php echo $height; ?>%;">
                <span class="bar-label"><?php echo $monthLabels[$index]; ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mb-3">
        <div class="panel h-100">
          <h4>Revenue Streams</h4>
          <small>Fee distribution breakdown</small>

          <div class="stream-item">
            <div class="d-flex justify-content-between"><span class="small fw-semibold">Tuition Fees</span><span class="small"><?php echo $tuitionPct; ?>%</span></div>
            <div class="stream-line"><div class="stream-fill" style="width:<?php echo $tuitionPct; ?>%;"></div></div>
          </div>
          <div class="stream-item">
            <div class="d-flex justify-content-between"><span class="small fw-semibold">Sports & Extra-curricular</span><span class="small"><?php echo $sportsPct; ?>%</span></div>
            <div class="stream-line"><div class="stream-fill" style="width:<?php echo $sportsPct; ?>%;"></div></div>
          </div>
          <div class="stream-item">
            <div class="d-flex justify-content-between"><span class="small fw-semibold">Laboratory & Tech</span><span class="small"><?php echo $labPct; ?>%</span></div>
            <div class="stream-line"><div class="stream-fill" style="width:<?php echo $labPct; ?>%;"></div></div>
          </div>
          <div class="stream-item">
            <div class="d-flex justify-content-between"><span class="small fw-semibold">Infrastructure Dev.</span><span class="small"><?php echo $infraPct; ?>%</span></div>
            <div class="stream-line"><div class="stream-fill" style="width:<?php echo $infraPct; ?>%;"></div></div>
          </div>

          <div class="mt-4 p-3 rounded-3" style="background:#fff2f2;border:1px solid #ffd4d4;">
            <div class="small fw-semibold text-danger"><i class="fas fa-triangle-exclamation"></i> Unpaid Balance</div>
            <div class="h4 mb-0 mt-1 text-danger fw-bold"><?php echo '$' . number_format($feesPending, 0); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-5 mb-3">
        <div class="panel h-100">
          <h4>Regional Benchmarking</h4>
          <div class="donut">
            <div class="donut-core">
              <div>
                <strong>A+</strong>
                <div class="small text-muted fw-semibold">NATIONAL PERCENTILE: 94TH</div>
              </div>
            </div>
          </div>
          <div class="small fw-semibold text-secondary mb-1"><i class="fas fa-circle" style="font-size:9px;color:#0f2f66;"></i> Scholar Metric Mean Score (84.2%)</div>
          <div class="small fw-semibold text-secondary"><i class="fas fa-circle" style="font-size:9px;color:#f2a94f;"></i> Regional Milestone Gap (+2.4%)</div>
        </div>
      </div>

      <div class="col-lg-7 mb-3">
        <div class="panel h-100">
          <div class="d-flex justify-content-between align-items-center">
            <h4>Departmental Standings</h4>
            <a href="reports.php" class="small fw-bold">View All Departments</a>
          </div>

          <div class="dept-item">
            <div class="d-flex align-items-center">
              <span class="dept-badge">ST</span>
              <div>
                <div class="fw-bold">Science & Technology</div>
                <small>Dean: Dr. Marcus Thorne</small>
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold">4,230 Students</div>
              <small class="text-success">+ 4.2% Growth</small>
            </div>
          </div>

          <div class="dept-item">
            <div class="d-flex align-items-center">
              <span class="dept-badge" style="background:#f5f1e4;color:#695a28;">HU</span>
              <div>
                <div class="fw-bold">Humanities & Arts</div>
                <small>Dean: Prof. Elena Vance</small>
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold">3,120 Students</div>
              <small class="text-muted">- Stable</small>
            </div>
          </div>

          <div class="dept-item">
            <div class="d-flex align-items-center">
              <span class="dept-badge" style="background:#e9f0f5;color:#3f5f76;">BS</span>
              <div>
                <div class="fw-bold">Business School</div>
                <small>Dean: Sarah Jenkins, MBA</small>
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold">2,840 Students</div>
              <small class="text-success">+ 1.8% Growth</small>
            </div>
          </div>

          <div class="dept-item">
            <div class="d-flex align-items-center">
              <span class="dept-badge" style="background:#fdecef;color:#ac2f4a;">MD</span>
              <div>
                <div class="fw-bold">Medical Sciences</div>
                <small>Dean: Dr. Julian Reyes</small>
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold">2,292 Students</div>
              <small class="text-danger">- 1.2% Attrition</small>
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

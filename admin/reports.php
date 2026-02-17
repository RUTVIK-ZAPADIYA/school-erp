<?php session_start(); $_SESSION['admin_id'] = 1; $_SESSION['admin_name'] = 'Admin'; ?>
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
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .report-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; cursor: pointer; transition: transform 0.3s; }
    .report-card:hover { transform: translateY(-5px); }
    .report-icon { width: 60px; height: 60px; background: #e3f2fd; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .report-icon i { font-size: 1.8rem; color: #3498db; }
    .report-title { font-size: 1.2rem; font-weight: 600; color: #2c3e50; margin-bottom: 10px; }
    .report-desc { color: #7f8c8d; font-size: 0.9rem; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chart-line"></i> Reports & Analytics</h2>
    </div>
    <div class="row">
      <div class="col-md-4"><div class="report-card"><div class="report-icon"><i class="fas fa-user-graduate"></i></div><div class="report-title">Student Report</div><div class="report-desc">View detailed student performance and attendance reports</div></div></div>
      <div class="col-md-4"><div class="report-card"><div class="report-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="report-title">Teacher Report</div><div class="report-desc">Analyze teacher performance and class statistics</div></div></div>
      <div class="col-md-4"><div class="report-card"><div class="report-icon"><i class="fas fa-dollar-sign"></i></div><div class="report-title">Financial Report</div><div class="report-desc">Track fee collection and financial analytics</div></div></div>
      <div class="col-md-4"><div class="report-card"><div class="report-icon"><i class="fas fa-calendar-check"></i></div><div class="report-title">Attendance Report</div><div class="report-desc">Monthly and yearly attendance statistics</div></div></div>
      <div class="col-md-4"><div class="report-card"><div class="report-icon"><i class="fas fa-chart-bar"></i></div><div class="report-title">Exam Report</div><div class="report-desc">Exam results and grade distribution analysis</div></div></div>
      <div class="col-md-4"><div class="report-card"><div class="report-icon"><i class="fas fa-school"></i></div><div class="report-title">Class Report</div><div class="report-desc">Class-wise performance and statistics</div></div></div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

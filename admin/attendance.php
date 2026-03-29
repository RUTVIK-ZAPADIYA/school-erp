<?php require_once __DIR__ . '/auth.php'; ?>
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
      <div class="summary-item"><div class="summary-value">92%</div><div class="summary-label">Overall Attendance</div></div>
      <div class="summary-item"><div class="summary-value">1150</div><div class="summary-label">Present Today</div></div>
      <div class="summary-item"><div class="summary-value">100</div><div class="summary-label">Absent Today</div></div>
      <div class="summary-item"><div class="summary-value">15</div><div class="summary-label">On Leave</div></div>
    </div>
    <div class="content-card">
      <h5 class="mb-4">Class-wise Attendance</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Class</th><th>Total Students</th><th>Present</th><th>Absent</th><th>Attendance %</th></tr>
          </thead>
          <tbody>
            <tr><td>Grade 10A</td><td>35</td><td>33</td><td>2</td><td>94%</td></tr>
            <tr><td>Grade 10B</td><td>32</td><td>30</td><td>2</td><td>94%</td></tr>
            <tr><td>Grade 12</td><td>28</td><td>25</td><td>3</td><td>89%</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

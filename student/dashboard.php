<?php
session_start();
if ((!isset($_SESSION['student_id']) || !isset($_SESSION['student_name'])) && isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student') {
  $_SESSION['student_id'] = (int) $_SESSION['user_id'];
  $_SESSION['student_name'] = $_SESSION['name'] ?? 'Student';
}
if (!isset($_SESSION['student_id'])) {
  header("Location: ../login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard</title>
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
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #3498db; }
    .stat-card.success { border-left-color: #27ae60; }
    .stat-card.warning { border-left-color: #f39c12; }
    .stat-card.danger { border-left-color: #e74c3c; }
    .stat-icon { width: 50px; height: 50px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .stat-icon i { font-size: 1.5rem; color: #3498db; }
    .stat-card.success .stat-icon { background: #e8f8f5; }
    .stat-card.success .stat-icon i { color: #27ae60; }
    .stat-card.warning .stat-icon { background: #fef5e7; }
    .stat-card.warning .stat-icon i { color: #f39c12; }
    .stat-card.danger .stat-icon { background: #fadbd8; }
    .stat-card.danger .stat-icon i { color: #e74c3c; }
    .stat-value { font-size: 2rem; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
    .stat-label { color: #7f8c8d; font-size: 0.9rem; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .content-card h5 { color: #2c3e50; font-weight: 600; margin-bottom: 20px; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-home"></i> Dashboard</h2>
    </div>
    
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-value">85%</div>
        <div class="stat-label">Attendance</div>
      </div>
      <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-value">78.5</div>
        <div class="stat-label">Average Marks</div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-value">₹500</div>
        <div class="stat-label">Pending Fees</div>
      </div>
      <div class="stat-card danger">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value">2</div>
        <div class="stat-label">Leave Applications</div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6">
        <div class="content-card">
          <h5><i class="fas fa-bell"></i> Recent Notifications</h5>
          <div class="list-group">
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Assignment Due</strong>
                <small class="text-muted">2 hours ago</small>
              </div>
              <p class="mb-0 text-muted">Mathematics assignment due tomorrow</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Fee Reminder</strong>
                <small class="text-muted">1 day ago</small>
              </div>
              <p class="mb-0 text-muted">Semester fee payment pending</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Exam Schedule</strong>
                <small class="text-muted">3 days ago</small>
              </div>
              <p class="mb-0 text-muted">Mid-term exams starting next week</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="content-card">
          <h5><i class="fas fa-calendar"></i> Upcoming Events</h5>
          <div class="list-group">
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Sports Day</strong>
                <span class="badge bg-primary">15 Dec</span>
              </div>
              <p class="mb-0 text-muted">Annual sports competition</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Science Fair</strong>
                <span class="badge bg-success">20 Dec</span>
              </div>
              <p class="mb-0 text-muted">Project exhibition</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Winter Break</strong>
                <span class="badge bg-warning">25 Dec</span>
              </div>
              <p class="mb-0 text-muted">Holiday starts</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

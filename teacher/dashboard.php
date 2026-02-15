<?php
session_start();
$_SESSION['teacher_id'] = 1;
$_SESSION['teacher_name'] = 'Prof. Priya Patel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
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
      <h2><i class="fas fa-home"></i> Teacher Dashboard</h2>
    </div>
    
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value">120</div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-book"></i></div>
        <div class="stat-value">5</div>
        <div class="stat-label">Classes Today</div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-value">12</div>
        <div class="stat-label">Pending Assignments</div>
      </div>
      <div class="stat-card danger">
        <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="stat-value">8</div>
        <div class="stat-label">Absent Today</div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6">
        <div class="content-card">
          <h5><i class="fas fa-calendar-alt"></i> Today's Schedule</h5>
          <div class="list-group">
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Mathematics - Grade 10A</strong>
                <span class="badge bg-primary">09:00 AM</span>
              </div>
              <p class="mb-0 text-muted">Room 101</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Mathematics - Grade 10B</strong>
                <span class="badge bg-primary">11:00 AM</span>
              </div>
              <p class="mb-0 text-muted">Room 101</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Advanced Math - Grade 12</strong>
                <span class="badge bg-success">02:00 PM</span>
              </div>
              <p class="mb-0 text-muted">Room 205</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="content-card">
          <h5><i class="fas fa-bell"></i> Recent Activities</h5>
          <div class="list-group">
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Assignment Submitted</strong>
                <small class="text-muted">1 hour ago</small>
              </div>
              <p class="mb-0 text-muted">25 students submitted Math homework</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Grade Updated</strong>
                <small class="text-muted">2 hours ago</small>
              </div>
              <p class="mb-0 text-muted">Mid-term exam grades published</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>New Message</strong>
                <small class="text-muted">3 hours ago</small>
              </div>
              <p class="mb-0 text-muted">Parent inquiry about student progress</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

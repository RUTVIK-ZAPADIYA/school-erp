<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-home"></i> Admin Dashboard</h2>
    </div>
    
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-value">1,250</div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-value">85</div>
        <div class="stat-label">Total Teachers</div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-value">$125K</div>
        <div class="stat-label">Pending Fees</div>
      </div>
      <div class="stat-card danger">
        <div class="stat-icon"><i class="fas fa-school"></i></div>
        <div class="stat-value">45</div>
        <div class="stat-label">Total Classes</div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6">
        <div class="content-card">
          <h5><i class="fas fa-chart-line"></i> Recent Activities</h5>
          <div class="list-group">
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>New Student Admission</strong>
                <small class="text-muted">10 min ago</small>
              </div>
              <p class="mb-0 text-muted">John Smith enrolled in Grade 10A</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Fee Payment Received</strong>
                <small class="text-muted">1 hour ago</small>
              </div>
              <p class="mb-0 text-muted">$5000 received from 10 students</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Teacher Added</strong>
                <small class="text-muted">2 hours ago</small>
              </div>
              <p class="mb-0 text-muted">New Physics teacher joined</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="content-card">
          <h5><i class="fas fa-exclamation-triangle"></i> Pending Actions</h5>
          <div class="list-group">
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Leave Applications</strong>
                <span class="badge bg-warning">15 Pending</span>
              </div>
              <p class="mb-0 text-muted">Review student leave requests</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Fee Reminders</strong>
                <span class="badge bg-danger">45 Overdue</span>
              </div>
              <p class="mb-0 text-muted">Send payment reminders</p>
            </div>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>Exam Schedule</strong>
                <span class="badge bg-primary">Upcoming</span>
              </div>
              <p class="mb-0 text-muted">Finalize mid-term exam dates</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

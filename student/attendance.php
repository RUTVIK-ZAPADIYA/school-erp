<?php
session_start();
$_SESSION['student_id'] = 1;
$_SESSION['student_name'] = 'Rahul Sharma';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .attendance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .summary-item { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
    .summary-value { font-size: 2rem; font-weight: 700; color: #3498db; }
    .summary-label { color: #7f8c8d; margin-top: 5px; }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-calendar-check"></i> View Attendance</h2>
    </div>
    
    <div class="attendance-summary">
      <div class="summary-item">
        <div class="summary-value">85%</div>
        <div class="summary-label">Overall Attendance</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">42</div>
        <div class="summary-label">Present Days</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">8</div>
        <div class="summary-label">Absent Days</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">50</div>
        <div class="summary-label">Total Days</div>
      </div>
    </div>
    
    <div class="content-card">
      <h5 class="mb-4">Monthly Attendance Record</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Time</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Dec 10, 2024</td>
              <td>Mathematics</td>
              <td><span class="badge bg-success">Present</span></td>
              <td>09:00 AM</td>
              <td>On time</td>
            </tr>
            <tr>
              <td>Dec 10, 2024</td>
              <td>Physics</td>
              <td><span class="badge bg-success">Present</span></td>
              <td>11:00 AM</td>
              <td>On time</td>
            </tr>
            <tr>
              <td>Dec 09, 2024</td>
              <td>Chemistry</td>
              <td><span class="badge bg-danger">Absent</span></td>
              <td>-</td>
              <td>Medical leave</td>
            </tr>
            <tr>
              <td>Dec 09, 2024</td>
              <td>English</td>
              <td><span class="badge bg-success">Present</span></td>
              <td>02:00 PM</td>
              <td>On time</td>
            </tr>
            <tr>
              <td>Dec 08, 2024</td>
              <td>Mathematics</td>
              <td><span class="badge bg-success">Present</span></td>
              <td>09:00 AM</td>
              <td>On time</td>
            </tr>
            <tr>
              <td>Dec 08, 2024</td>
              <td>Computer Science</td>
              <td><span class="badge bg-warning">Late</span></td>
              <td>10:15 AM</td>
              <td>15 min late</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
  <title>View Marks</title>
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
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .marks-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .summary-item { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
    .summary-value { font-size: 2rem; font-weight: 700; color: #3498db; }
    .summary-label { color: #7f8c8d; margin-top: 5px; }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .progress { height: 25px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chart-bar"></i> View Marks</h2>
    </div>
    
    <div class="marks-summary">
      <div class="summary-item">
        <div class="summary-value">78.5</div>
        <div class="summary-label">Average Marks</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">92</div>
        <div class="summary-label">Highest Score</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">65</div>
        <div class="summary-label">Lowest Score</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">B+</div>
        <div class="summary-label">Overall Grade</div>
      </div>
    </div>
    
    <div class="content-card">
      <h5 class="mb-4">Subject-wise Performance</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Total Marks</th>
              <th>Obtained Marks</th>
              <th>Percentage</th>
              <th>Grade</th>
              <th>Progress</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Mathematics</strong></td>
              <td>100</td>
              <td>85</td>
              <td>85%</td>
              <td><span class="badge bg-success">A</span></td>
              <td><div class="progress"><div class="progress-bar bg-success" style="width: 85%">85%</div></div></td>
            </tr>
            <tr>
              <td><strong>Physics</strong></td>
              <td>100</td>
              <td>78</td>
              <td>78%</td>
              <td><span class="badge bg-primary">B+</span></td>
              <td><div class="progress"><div class="progress-bar bg-primary" style="width: 78%">78%</div></div></td>
            </tr>
            <tr>
              <td><strong>Chemistry</strong></td>
              <td>100</td>
              <td>92</td>
              <td>92%</td>
              <td><span class="badge bg-success">A+</span></td>
              <td><div class="progress"><div class="progress-bar bg-success" style="width: 92%">92%</div></div></td>
            </tr>
            <tr>
              <td><strong>English</strong></td>
              <td>100</td>
              <td>75</td>
              <td>75%</td>
              <td><span class="badge bg-primary">B</span></td>
              <td><div class="progress"><div class="progress-bar bg-primary" style="width: 75%">75%</div></div></td>
            </tr>
            <tr>
              <td><strong>Computer Science</strong></td>
              <td>100</td>
              <td>88</td>
              <td>88%</td>
              <td><span class="badge bg-success">A</span></td>
              <td><div class="progress"><div class="progress-bar bg-success" style="width: 88%">88%</div></div></td>
            </tr>
            <tr>
              <td><strong>Biology</strong></td>
              <td>100</td>
              <td>65</td>
              <td>65%</td>
              <td><span class="badge bg-warning">C+</span></td>
              <td><div class="progress"><div class="progress-bar bg-warning" style="width: 65%">65%</div></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

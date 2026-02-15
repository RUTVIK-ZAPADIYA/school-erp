<?php session_start(); $_SESSION['admin_id'] = 1; $_SESSION['admin_name'] = 'Admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Exams</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .btn-add { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-file-alt"></i> Manage Exams</h2>
      <button class="btn-add"><i class="fas fa-plus"></i> Schedule New Exam</button>
    </div>
    <div class="content-card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Exam Name</th><th>Class</th><th>Subject</th><th>Date</th><th>Duration</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <tr><td>Mid-term Exam</td><td>Grade 10A</td><td>Mathematics</td><td>Dec 20, 2024</td><td>2 Hours</td><td><span class="badge bg-warning">Upcoming</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button></td></tr>
            <tr><td>Final Exam</td><td>Grade 10B</td><td>Physics</td><td>Jan 15, 2025</td><td>3 Hours</td><td><span class="badge bg-primary">Scheduled</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button></td></tr>
            <tr><td>Quiz</td><td>Grade 12</td><td>Chemistry</td><td>Dec 12, 2024</td><td>1 Hour</td><td><span class="badge bg-success">Completed</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

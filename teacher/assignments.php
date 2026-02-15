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
  <title>Assignments</title>
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
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .btn-create { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; margin-bottom: 20px; }
    .btn-create:hover { background: #2980b9; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-tasks"></i> Assignments</h2>
    </div>
    
    <button class="btn-create"><i class="fas fa-plus"></i> Create New Assignment</button>
    
    <div class="content-card">
      <h5 class="mb-4">Active Assignments</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Assignment Title</th>
              <th>Class</th>
              <th>Subject</th>
              <th>Due Date</th>
              <th>Submissions</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Algebra Problems Set 1</td>
              <td>Grade 10A</td>
              <td>Mathematics</td>
              <td>Dec 15, 2024</td>
              <td>25/30</td>
              <td><span class="badge bg-success">Active</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>Calculus Assignment</td>
              <td>Grade 12</td>
              <td>Advanced Math</td>
              <td>Dec 20, 2024</td>
              <td>18/25</td>
              <td><span class="badge bg-success">Active</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>Geometry Worksheet</td>
              <td>Grade 10B</td>
              <td>Mathematics</td>
              <td>Dec 12, 2024</td>
              <td>28/30</td>
              <td><span class="badge bg-warning">Due Soon</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>Trigonometry Quiz</td>
              <td>Grade 10A</td>
              <td>Mathematics</td>
              <td>Dec 10, 2024</td>
              <td>30/30</td>
              <td><span class="badge bg-secondary">Completed</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

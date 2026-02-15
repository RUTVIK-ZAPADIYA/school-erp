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
  <title>My Students</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .search-box { margin-bottom: 20px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-users"></i> My Students</h2>
    </div>
    
    <div class="content-card">
      <div class="search-box">
        <input type="text" class="form-control" placeholder="Search students...">
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Class</th>
              <th>Attendance</th>
              <th>Average Grade</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>001</td>
              <td>Rahul Sharma</td>
              <td>Grade 10A</td>
              <td>92%</td>
              <td>85</td>
              <td><span class="badge bg-success">Active</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>002</td>
              <td>Priya Verma</td>
              <td>Grade 10A</td>
              <td>88%</td>
              <td>78</td>
              <td><span class="badge bg-success">Active</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>003</td>
              <td>Amit Kumar</td>
              <td>Grade 10B</td>
              <td>95%</td>
              <td>92</td>
              <td><span class="badge bg-success">Active</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>004</td>
              <td>Sneha Reddy</td>
              <td>Grade 10B</td>
              <td>75%</td>
              <td>68</td>
              <td><span class="badge bg-warning">Warning</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>005</td>
              <td>Arjun Singh</td>
              <td>Grade 12</td>
              <td>90%</td>
              <td>88</td>
              <td><span class="badge bg-success">Active</span></td>
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

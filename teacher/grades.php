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
  <title>Manage Grades</title>
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
    .grade-input { width: 80px; padding: 5px; border: 1px solid #ddd; border-radius: 5px; }
    .btn-submit { background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 8px; }
    .btn-submit:hover { background: #2980b9; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chart-bar"></i> Manage Grades</h2>
    </div>
    
    <div class="content-card">
      <div class="row mb-4">
        <div class="col-md-4">
          <label class="form-label">Select Class</label>
          <select class="form-select">
            <option>Grade 10A</option>
            <option>Grade 10B</option>
            <option>Grade 12</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Exam Type</label>
          <select class="form-select">
            <option>Mid-term</option>
            <option>Final</option>
            <option>Quiz</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Subject</label>
          <select class="form-select">
            <option>Mathematics</option>
            <option>Physics</option>
            <option>Chemistry</option>
          </select>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Total Marks</th>
              <th>Obtained Marks</th>
              <th>Grade</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>001</td>
              <td>Rahul Sharma</td>
              <td>100</td>
              <td><input type="number" class="grade-input" value="85"></td>
              <td>A</td>
              <td><input type="text" class="form-control form-control-sm" placeholder="Remarks"></td>
            </tr>
            <tr>
              <td>002</td>
              <td>Priya Verma</td>
              <td>100</td>
              <td><input type="number" class="grade-input" value="78"></td>
              <td>B+</td>
              <td><input type="text" class="form-control form-control-sm" placeholder="Remarks"></td>
            </tr>
            <tr>
              <td>003</td>
              <td>Amit Kumar</td>
              <td>100</td>
              <td><input type="number" class="grade-input" value="92"></td>
              <td>A+</td>
              <td><input type="text" class="form-control form-control-sm" placeholder="Remarks"></td>
            </tr>
            <tr>
              <td>004</td>
              <td>Sneha Reddy</td>
              <td>100</td>
              <td><input type="number" class="grade-input" value="68"></td>
              <td>C+</td>
              <td><input type="text" class="form-control form-control-sm" placeholder="Remarks"></td>
            </tr>
            <tr>
              <td>005</td>
              <td>Arjun Singh</td>
              <td>100</td>
              <td><input type="number" class="grade-input" value="88"></td>
              <td>A</td>
              <td><input type="text" class="form-control form-control-sm" placeholder="Remarks"></td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <button class="btn-submit"><i class="fas fa-save"></i> Save Grades</button>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

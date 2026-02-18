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
  <title>Mark Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .form-select { padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .btn-submit { background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 8px; }
    .btn-submit:hover { background: #2980b9; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-calendar-check"></i> Mark Attendance</h2>
    </div>
    
    <div class="content-card">
      <form id="attendanceForm">
        <div class="row mb-4">
          <div class="col-md-4">
            <label class="form-label">Select Class</label>
            <select class="form-select" name="class" data-validation="required select">
              <option value="">-- Select Class --</option>
              <option>Grade 10A</option>
              <option>Grade 10B</option>
              <option>Grade 12</option>
            </select>
            <div id="class_error"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Select Date</label>
            <input type="date" class="form-control" name="date" data-validation="required" value="<?php echo date('Y-m-d'); ?>">
            <div id="date_error"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Subject</label>
            <select class="form-select" name="subject" data-validation="required select">
              <option value="">-- Select Subject --</option>
              <option>Mathematics</option>
              <option>Physics</option>
              <option>Chemistry</option>
            </select>
            <div id="subject_error"></div>
          </div>
        </div>
      
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Present</th>
              <th>Absent</th>
              <th>Late</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>001</td>
              <td>satyam</td>
              <td><input type="radio" name="attendance_1" value="present" checked></td>
              <td><input type="radio" name="attendance_1" value="absent"></td>
              <td><input type="radio" name="attendance_1" value="late"></td>
            </tr>
            <tr>
              <td>002</td>
              <td>Rutvik Shira</td>
              <td><input type="radio" name="attendance_2" value="present" checked></td>
              <td><input type="radio" name="attendance_2" value="absent"></td>
              <td><input type="radio" name="attendance_2" value="late"></td>
            </tr>
            <tr>
              <td>003</td>
              <td>Hardip Zapadiya</td>
              <td><input type="radio" name="attendance_3" value="present" checked></td>
              <td><input type="radio" name="attendance_3" value="absent"></td>
              <td><input type="radio" name="attendance_3" value="late"></td>
            </tr>
            <tr>
              <td>004</td>
              <td>Deep Ramani</td>
              <td><input type="radio" name="attendance_4" value="present"></td>
              <td><input type="radio" name="attendance_4" value="absent" checked></td>
              <td><input type="radio" name="attendance_4" value="late"></td>
            </tr>
            <tr>
              <td>005</td>
              <td>Pranshu jr.</td>
              <td><input type="radio" name="attendance_5" value="present" checked></td>
              <td><input type="radio" name="attendance_5" value="absent"></td>
              <td><input type="radio" name="attendance_5" value="late"></td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Submit Attendance</button>
      </form>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

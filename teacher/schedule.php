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
  <title>Class Schedule</title>
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
    .schedule-table { width: 100%; }
    .schedule-table th { background: #2c3e50; color: white; padding: 15px; text-align: center; }
    .schedule-table td { padding: 15px; border: 1px solid #ddd; vertical-align: top; }
    .class-slot { background: #e3f2fd; padding: 10px; border-radius: 5px; margin-bottom: 10px; border-left: 3px solid #3498db; }
    .class-slot strong { color: #2c3e50; display: block; margin-bottom: 5px; }
    .class-slot small { color: #7f8c8d; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-calendar-alt"></i> Class Schedule</h2>
    </div>
    
    <div class="content-card">
      <table class="schedule-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>09:00 - 10:00</strong></td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 10A - Room 101</small>
              </div>
            </td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 10B - Room 101</small>
              </div>
            </td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 10A - Room 101</small>
              </div>
            </td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 10B - Room 101</small>
              </div>
            </td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 12 - Room 205</small>
              </div>
            </td>
          </tr>
          <tr>
            <td><strong>11:00 - 12:00</strong></td>
            <td>
              <div class="class-slot">
                <strong>Advanced Math</strong>
                <small>Grade 12 - Room 205</small>
              </div>
            </td>
            <td></td>
            <td>
              <div class="class-slot">
                <strong>Advanced Math</strong>
                <small>Grade 12 - Room 205</small>
              </div>
            </td>
            <td></td>
            <td></td>
          </tr>
          <tr>
            <td><strong>02:00 - 03:00</strong></td>
            <td></td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 10A - Room 101</small>
              </div>
            </td>
            <td></td>
            <td>
              <div class="class-slot">
                <strong>Mathematics</strong>
                <small>Grade 10A - Room 101</small>
              </div>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

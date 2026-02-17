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
  <title>Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fcfbfb; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom: 3px solid #f7d794; }
    .header h2 { color: #192a56; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-top: 3px solid #f7d794; }
    .profile-header { text-align: center; padding: 30px; background: #f8f9fa; border-radius: 10px; margin-bottom: 30px; }
    .profile-avatar { width: 120px; height: 120px; background: #f7d794; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .profile-avatar i { font-size: 4rem; color: #192a56; }
    .profile-name { font-size: 1.8rem; font-weight: 700; color: #192a56; margin-bottom: 5px; }
    .profile-id { color: #7f8c8d; }
    .info-row { display: flex; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
    .info-label { font-weight: 600; color: #192a56; width: 200px; }
    .info-value { color: #7f8c8d; }
    .btn-edit { background: #f7d794; color: #192a56; padding: 10px 25px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-edit:hover { background: #e5c682; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user"></i> My Profile</h2>
    </div>
    
    <div class="content-card">
      <div class="profile-header">
        <div class="profile-avatar"><i class="fas fa-user"></i></div>
        <div class="profile-name">Rahul Sharma</div>
        <div class="profile-id">Student ID: STU2024001</div>
      </div>
      
      <h5 class="mb-3">Personal Information</h5>
      <div class="info-row">
        <div class="info-label">Full Name</div>
        <div class="info-value">Rahul Sharma</div>
      </div>
      <div class="info-row">
        <div class="info-label">Email</div>
        <div class="info-value">rahulsharma@school.com</div>
      </div>
      <div class="info-row">
        <div class="info-label">Phone Number</div>
        <div class="info-value">+91 9999999999</div>
      </div>
      <div class="info-row">
        <div class="info-label">Date of Birth</div>
        <div class="info-value">January 15, 2005</div>
      </div>
      <div class="info-row">
        <div class="info-label">Gender</div>
        <div class="info-value">Male</div>
      </div>
      <div class="info-row">
        <div class="info-label">Address</div>
        <div class="info-value">Rajkot</div>
      </div>
      
      <h5 class="mt-4 mb-3">Academic Information</h5>
      <div class="info-row">
        <div class="info-label">Class</div>
        <div class="info-value">Grade 12</div>
      </div>
      <div class="info-row">
        <div class="info-label">Section</div>
        <div class="info-value">A</div>
      </div>
      <div class="info-row">
        <div class="info-label">Roll Number</div>
        <div class="info-value">12</div>
      </div>
      <div class="info-row">
        <div class="info-label">Admission Date</div>
        <div class="info-value">August 1, 2020</div>
      </div>
      <div class="info-row">
        <div class="info-label">Academic Year</div>
        <div class="info-value">2024-2025</div>
      </div>
      
      <div class="mt-4">
        <button class="btn-edit"><i class="fas fa-edit"></i> Edit Profile</button>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.nav-item').forEach(item => {
      if (item.href === window.location.href) item.classList.add('active');
    });
  </script>
</body>
</html>

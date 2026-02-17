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
  <title>Admin Profile</title>
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
    .profile-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 3px solid #f7d794; }
    .profile-header { text-align: center; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0; margin-bottom: 30px; }
    .profile-avatar { width: 100px; height: 100px; background: #f7d794; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .profile-avatar i { font-size: 50px; color: #192a56; }
    .profile-name { color: #192a56; font-size: 24px; font-weight: 700; margin-bottom: 5px; }
    .profile-role { color: #7f8c8d; font-size: 14px; }
    .info-section { margin-bottom: 30px; }
    .info-section h5 { color: #192a56; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f7d794; }
    .info-row { display: flex; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #7f8c8d; width: 150px; font-size: 14px; }
    .info-value { color: #192a56; font-weight: 600; }
    .btn-edit { background: #f7d794; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-edit:hover { background: #e5c682; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user"></i> My Profile</h2>
    </div>
    
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-avatar">
          <i class="fas fa-user-shield"></i>
        </div>
        <div class="profile-name">Admin User</div>
        <div class="profile-role">System Administrator</div>
      </div>
      
      <div class="info-section">
        <h5>Personal Information</h5>
        <div class="info-row">
          <div class="info-label">Full Name</div>
          <div class="info-value">Admin User</div>
        </div>
        <div class="info-row">
          <div class="info-label">Email</div>
          <div class="info-value">admin@school.com</div>
        </div>
        <div class="info-row">
          <div class="info-label">Phone</div>
          <div class="info-value">+1 234 567 8900</div>
        </div>
      </div>
      
      <div class="info-section">
        <h5>Account Details</h5>
        <div class="info-row">
          <div class="info-label">Admin ID</div>
          <div class="info-value">ADM001</div>
        </div>
        <div class="info-row">
          <div class="info-label">Role</div>
          <div class="info-value">System Administrator</div>
        </div>
        <div class="info-row">
          <div class="info-label">Joined Date</div>
          <div class="info-value">January 1, 2024</div>
        </div>
        <div class="info-row">
          <div class="info-label">Status</div>
          <div class="info-value"><span class="badge bg-success">Active</span></div>
        </div>
      </div>
      
      <div class="text-center">
        <button class="btn-edit"><i class="fas fa-edit"></i> Edit Profile</button>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

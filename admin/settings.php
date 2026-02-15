<?php session_start(); $_SESSION['admin_id'] = 1; $_SESSION['admin_name'] = 'Admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .form-label { color: #2d3748; font-weight: 500; margin-bottom: 8px; }
    .form-control { padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .btn-save { background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 8px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-cog"></i> System Settings</h2>
    </div>
    <div class="content-card">
      <h5 class="mb-4">School Information</h5>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">School Name</label>
          <input type="text" class="form-control" value="ABC School">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" value="info@school.com">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control" value="+1 234 567 8900">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Address</label>
          <input type="text" class="form-control" value="123 School St">
        </div>
      </div>
      <button class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

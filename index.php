<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #ecf0f1; }
    .hero { background: #2c3e50; color: white; padding: 100px 0; text-align: center; }
    .hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 20px; }
    .hero p { font-size: 1.2rem; color: #bdc3c7; margin-bottom: 30px; }
    .btn-primary { background-color: #3498db; border: none; padding: 12px 30px; font-weight: 600; transition: all 0.3s; }
    .btn-primary:hover { background-color: #2980b9; transform: translateY(-2px); }
    .feature-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px; transition: transform 0.3s; }
    .feature-card:hover { transform: translateY(-5px); }
    .feature-icon { font-size: 3rem; color: #3498db; margin-bottom: 20px; }
    .section-title { color: #2c3e50; font-weight: 700; margin-bottom: 50px; text-align: center; }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>
  
  <div class="hero">
    <div class="container">
      <h1>Welcome to School ERP System</h1>
      <p>Streamline your school management with our comprehensive solution</p>
      <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
    </div>
  </div>

  <div class="container" style="margin-top: 80px;">
    <h2 class="section-title">Our Features</h2>
    <div class="row">
      <div class="col-md-4">
        <div class="feature-card text-center">
          <div class="feature-icon"><i class="fas fa-users"></i></div>
          <h4 style="color: #2c3e50; margin-bottom: 15px;">Student Management</h4>
          <p style="color: #7f8c8d;">Efficiently manage student records, attendance, and performance tracking.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center">
          <div class="feature-icon"><i class="fas fa-chalkboard-teacher"></i></div>
          <h4 style="color: #2c3e50; margin-bottom: 15px;">Teacher Portal</h4>
          <p style="color: #7f8c8d;">Empower teachers with tools for grading, scheduling, and communication.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center">
          <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
          <h4 style="color: #2c3e50; margin-bottom: 15px;">Analytics & Reports</h4>
          <p style="color: #7f8c8d;">Generate comprehensive reports and insights for better decision making.</p>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

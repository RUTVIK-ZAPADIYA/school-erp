<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/public-pages.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>
  
  <div class="hero">
    <div class="container hero-content text-center">
      <h1>Transform Your School Management</h1>
      <p>Streamline operations, enhance communication, and boost efficiency with our comprehensive ERP solution</p>
      <div class="hero-buttons">
        <a href="register.php" class="btn btn-primary btn-hero"><i class="fas fa-rocket"></i> Get Started Free</a>
        <a href="about.php" class="btn btn-outline btn-hero"><i class="fas fa-play-circle"></i> Learn More</a>
      </div>
    </div>
  </div>

  <div class="stats-section">
    <div class="container">
      <div class="row">
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-school"></i> 500+</div>
            <div class="stat-label">Schools</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-users"></i> 50K+</div>
            <div class="stat-label">Students</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-chalkboard-teacher"></i> 5K+</div>
            <div class="stat-label">Teachers</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-star"></i> 4.9</div>
            <div class="stat-label">Rating</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="features-section">
    <div class="container">
      <h2 class="section-title">Powerful Features</h2>
      <p class="section-subtitle">Everything you need to manage your school efficiently</p>
      <div class="row g-5">
        <div class="col-lg-4 col-md-6">
          <div class="feature-card text-center">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <h4>Student Management</h4>
            <p>Efficiently manage student records, attendance, grades, and performance tracking in one centralized system.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="feature-card text-center">
            <div class="feature-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <h4>Teacher Portal</h4>
            <p>Empower teachers with intuitive tools for grading, lesson planning, scheduling, and parent communication.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="feature-card text-center">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <h4>Analytics & Reports</h4>
            <p>Generate comprehensive reports and gain valuable insights for data-driven decision making.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="feature-card text-center">
            <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
            <h4>Attendance Tracking</h4>
            <p>Automated attendance system with real-time notifications to parents and detailed reporting.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="feature-card text-center">
            <div class="feature-icon"><i class="fas fa-dollar-sign"></i></div>
            <h4>Fee Management</h4>
            <p>Streamline fee collection, generate invoices, and track payments with automated reminders.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="feature-card text-center">
            <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
            <h4>Mobile Access</h4>
            <p>Access the system anytime, anywhere with our responsive design and mobile-friendly interface.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="cta-section">
    <div class="container">
      <h2>Ready to Get Started?</h2>
      <p>Join hundreds of schools already using our platform</p>
      <a href="register.php" class="btn btn-primary btn-hero"><i class="fas fa-user-plus"></i> Create Free Account</a>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

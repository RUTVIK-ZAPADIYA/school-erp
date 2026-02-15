<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* ========================================
       GLOBAL STYLES & RESETS
       ======================================== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
      overflow-x: hidden;
      line-height: 1.6;
      color: #333;
    }

    /* ========================================
       ANIMATIONS
       ======================================== */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ========================================
       HERO SECTION
       ======================================== */
    .hero {
      background-color: #2c3e50;
      color: white;
      padding: 120px 0 100px;
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%233498db" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
      background-size: cover;
    }

    .hero-content {
      position: relative;
      z-index: 2;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      animation: fadeInUp 1s ease-out;
      line-height: 1.2;
    }

    .hero p {
      font-size: 1.3rem;
      color: #bdc3c7;
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
      animation: fadeInUp 1.2s ease-out;
      line-height: 1.6;
    }

    .hero-buttons {
      animation: fadeInUp 1.4s ease-out;
    }

    /* ========================================
       BUTTONS
       ======================================== */
    .btn-hero {
      padding: 14px 35px;
      font-weight: 600;
      font-size: 1.1rem;
      border-radius: 50px;
      transition: all 0.3s ease;
      margin: 0 10px;
      display: inline-block;
      text-decoration: none;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #3498db;
      border: none;
      color: white;
    }

    .btn-primary:hover {
      background-color: #2980b9;
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
      color: white;
      text-decoration: none;
    }

    .btn-outline {
      background: transparent;
      border: 2px solid #3498db;
      color: #3498db;
    }

    .btn-outline:hover {
      background: #3498db;
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
      text-decoration: none;
    }

    /* ========================================
       STATS SECTION
       ======================================== */
    .stats-section {
      background-color: white;
      padding: 60px 0;
      margin-top: -50px;
      position: relative;
      z-index: 3;
      box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
    }

    .stat-item {
      text-align: center;
      padding: 20px;
      transition: all 0.3s ease;
    }

    .stat-item:hover {
      transform: translateY(-8px);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #3498db;
      margin-bottom: 10px;
    }

    .stat-label {
      color: #7f8c8d;
      font-size: 1rem;
      font-weight: 500;
    }

    /* ========================================
       FEATURES SECTION
       ======================================== */
    .features-section {
      padding: 80px 0;
    }

    .section-title {
      color: #2c3e50;
      font-weight: 700;
      font-size: 2.5rem;
      margin-bottom: 15px;
      text-align: center;
    }

    .section-subtitle {
      color: #7f8c8d;
      text-align: center;
      margin-bottom: 60px;
      font-size: 1.1rem;
      line-height: 1.6;
    }

    .feature-card {
      background-color: white;
      padding: 40px 30px;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
      transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
      border: 2px solid transparent;
      height: 100%;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(52, 152, 219, 0.2);
      border-color: #3498db;
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background-color: #e3f2fd;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 25px;
      transition: all 0.3s ease;
    }

    .feature-card:hover .feature-icon {
      background-color: #3498db;
      transform: scale(1.1);
    }

    .feature-icon i {
      font-size: 2rem;
      color: #3498db;
      transition: color 0.3s ease;
    }

    .feature-card:hover .feature-icon i {
      color: white;
    }

    .feature-card h4 {
      color: #2c3e50;
      margin-bottom: 15px;
      font-weight: 600;
      font-size: 1.3rem;
    }

    .feature-card p {
      color: #7f8c8d;
      line-height: 1.7;
      margin: 0;
      font-size: 0.95rem;
    }

    /* ========================================
       CTA SECTION
       ======================================== */
    .cta-section {
      background-color: #2c3e50;
      color: white;
      padding: 80px 0;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .cta-section h2 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      position: relative;
      z-index: 2;
    }

    .cta-section p {
      font-size: 1.2rem;
      color: #bdc3c7;
      margin-bottom: 40px;
      position: relative;
      z-index: 2;
      line-height: 1.6;
    }

    /* ========================================
       RESPONSIVE DESIGN - TABLETS & MOBILE
       ======================================== */
    @media (max-width: 768px) {
      .hero h1 {
        font-size: 2.2rem;
      }

      .hero p {
        font-size: 1.1rem;
      }

      .btn-hero {
        padding: 12px 25px;
        font-size: 1rem;
        margin: 5px;
      }

      .stat-number {
        font-size: 2rem;
      }

      .section-title {
        font-size: 2rem;
      }

      .feature-card {
        padding: 30px 20px;
      }

      .cta-section h2 {
        font-size: 2rem;
      }

      .cta-section p {
        font-size: 1rem;
      }
    }
  </style>
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
      <div class="row">
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

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/public-pages.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <section class="page-header">
    <div class="container">
      <h1 class="display-5 fw-bold mb-3">About School ERP</h1>
      <p class="lead mb-0">A complete digital backbone for modern schools, built for administrators, teachers, students, and parents.</p>
    </div>
  </section>

  <section class="content-section">
    <div class="container">
      <div class="content-card">
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="about-card">
              <span class="about-icon"><i class="fas fa-bullseye"></i></span>
              <h4>Our Mission</h4>
              <p class="mb-0">To simplify school operations and make academic management transparent, fast, and data-driven.</p>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="about-card">
              <span class="about-icon"><i class="fas fa-shield-alt"></i></span>
              <h4>Reliable Platform</h4>
              <p class="mb-0">Role-based access, centralized records, and streamlined workflows reduce manual effort and mistakes.</p>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="about-card">
              <span class="about-icon"><i class="fas fa-chart-line"></i></span>
              <h4>Growth Focused</h4>
              <p class="mb-0">Actionable insights across attendance, grades, fees, and reports help schools improve outcomes.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card p-0">
        <div class="about-lead-panel text-center">
          <h3 class="mb-3">Built For Every Stakeholder</h3>
          <p class="mb-4">Admins gain full operational control, teachers get practical productivity tools, and students get clear visibility into attendance, marks, and fee status.</p>
          <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-1"></i>Login to Continue</a>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

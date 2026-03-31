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
  <style>
    .about-hero {
      padding: 92px 0 72px;
      background: linear-gradient(135deg, #0f1e39, #1e3a66);
      color: #ffffff;
    }
    .about-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
      padding: 24px;
      height: 100%;
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <section class="about-hero text-center">
    <div class="container">
      <h1 class="display-5 fw-bold mb-3">About School ERP</h1>
      <p class="lead mb-0">A complete digital backbone for modern schools, built for admins, teachers, and students.</p>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="about-card">
            <h4><i class="fas fa-bullseye me-2 text-primary"></i>Our Mission</h4>
            <p class="mb-0">To simplify school operations and make academic management transparent, fast, and data-driven.</p>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="about-card">
            <h4><i class="fas fa-shield-alt me-2 text-primary"></i>Reliable Platform</h4>
            <p class="mb-0">Role-based access, centralized records, and streamlined workflows reduce manual effort and mistakes.</p>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="about-card">
            <h4><i class="fas fa-chart-line me-2 text-primary"></i>Growth Focused</h4>
            <p class="mb-0">Actionable insights across attendance, grades, fees, and reports help schools improve outcomes.</p>
          </div>
        </div>
      </div>

      <div class="row mt-5">
        <div class="col-lg-8 mx-auto text-center">
          <h3 class="mb-3">Built For Every Stakeholder</h3>
          <p class="text-muted">Admins get complete control, teachers get productivity tools, and students get clear visibility into attendance, marks, and fees.</p>
          <a href="login.php" class="btn btn-primary mt-2"><i class="fas fa-sign-in-alt me-1"></i>Login to Continue</a>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

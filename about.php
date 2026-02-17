<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About Us - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #ecf0f1; }
    .page-header { background: #192a56; color: white; padding: 80px 0; text-align: center; }
    .page-header h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; }
    .page-header p { font-size: 1.2rem; color: #bdc3c7; }
    .content-section { padding: 60px 0; }
    .about-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
    .about-card h3 { color: #192a56; margin-bottom: 20px; font-weight: 700; }
    .about-card p { color: #7f8c8d; line-height: 1.8; }
    .team-member { text-align: center; margin-bottom: 30px; }
    .team-avatar { width: 120px; height: 120px; background: #f7d794; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .team-avatar i { font-size: 50px; color: #192a56; }
    .team-member h5 { color: #192a56; font-weight: 600; margin-bottom: 5px; }
    .team-member p { color: #7f8c8d; font-size: 14px; }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>
  
  <div class="page-header">
    <div class="container">
      <h1>About Us</h1>
      <p>Learn more about our School ERP System</p>
    </div>
  </div>

  <div class="content-section">
    <div class="container">
      <div class="about-card">
        <h3><i class="fas fa-bullseye" style="color: #f7d794;"></i> Our Mission</h3>
        <p>To provide educational institutions with a comprehensive, user-friendly ERP system that streamlines administrative tasks, enhances communication, and improves overall efficiency in school management.</p>
      </div>
      
      <div class="about-card">
        <h3><i class="fas fa-eye" style="color: #f7d794;"></i> Our Vision</h3>
        <p>To become the leading school management solution globally, empowering schools with innovative technology that transforms education administration and creates better learning environments.</p>
      </div>
      
      <div class="about-card">
        <h3><i class="fas fa-star" style="color: #f7d794;"></i> Why Choose Us</h3>
        <p>Our system offers comprehensive features including student management, attendance tracking, grade management, parent communication, financial management, and detailed analytics. We provide 24/7 support and regular updates to ensure your school runs smoothly.</p>
      </div>

      <h2 style="text-align: center; color: #192a56; font-weight: 700; margin: 60px 0 40px;">Our Team</h2>
      <div class="row">
        <div class="col-md-3">
          <div class="team-member">
            <div class="team-avatar"><i class="fas fa-user"></i></div>
            <h5>RUTVIK ZAPADIYA</h5>
            <p>CEO & Founder</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="team-member">
            <div class="team-avatar"><i class="fas fa-user"></i></div>
            <h5>AMMAR BHARMAL</h5>
            <p>CTO</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="team-member">
            <div class="team-avatar"><i class="fas fa-user"></i></div>
            <h5>DWIJ MALAVIYA</h5>
            <p>Lead Developer</p>
          </div>
        </div>
       
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

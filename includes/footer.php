<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<footer class="site-footer">
  <div class="footer-glow"></div>
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand mb-3">
          <span class="footer-brand-icon"><i class="fas fa-graduation-cap"></i></span>
          <h5>School ERP</h5>
        </div>
        <p class="footer-muted">A smart school management platform that keeps administration, teachers, students, and parents connected in one place.</p>
      </div>

      <div class="col-lg-2 col-md-6">
        <h6 class="footer-title">Quick Links</h6>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php">About Us</a></li>
          <li><a href="gallery.php">Gallery</a></li>
          <li><a href="register.php">Register</a></li>
          <li><a href="login.php">Login</a></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-6">
        <h6 class="footer-title">Core Modules</h6>
        <ul class="footer-links">
          <li><span>Student Management</span></li>
          <li><span>Teacher Portal</span></li>
          <li><span>Attendance Tracking</span></li>
          <li><span>Fee Management</span></li>
          <li><span>Reports and Analytics</span></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-6">
        <h6 class="footer-title">Contact</h6>
        <div class="footer-contact-item">
          <i class="fas fa-envelope"></i>
          <a href="mailto:info@schoolerp.com">info@schoolerp.com</a>
        </div>
        <div class="footer-contact-item">
          <i class="fas fa-phone"></i>
          <a href="tel:+919999999999">+91 9999999999</a>
        </div>
        <div class="footer-contact-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>Rajkot City</span>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2026 School ERP System. All rights reserved.</p>
    </div>
  </div>
</footer>

<style>
  .site-footer {
    position: relative;
    margin-top: 72px;
    color: #d4deee;
    background: linear-gradient(165deg, #101b33 0%, #152846 55%, #1a3459 100%);
    overflow: hidden;
  }

  .footer-glow {
    position: absolute;
    top: -80px;
    right: -30px;
    width: 260px;
    height: 260px;
    background: radial-gradient(circle, rgba(31, 201, 166, 0.25), transparent 65%);
    pointer-events: none;
  }

  .footer-brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
  }

  .footer-brand h5 {
    margin: 0;
    color: #f2f9ff;
    font-weight: 800;
  }

  .footer-brand-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(140deg, #2ac8a2, #17856d);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ecfffb;
    box-shadow: 0 10px 20px rgba(19, 167, 133, 0.32);
  }

  .footer-title {
    color: #f1f7ff;
    margin-bottom: 18px;
    font-weight: 700;
  }

  .footer-muted {
    color: #b7c6da;
    line-height: 1.75;
    max-width: 380px;
  }

  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 10px;
  }

  .footer-links a,
  .footer-links span {
    color: #c6d3e6;
    text-decoration: none;
    font-weight: 500;
  }

  .footer-links a:hover {
    color: #f0fffb;
  }

  .footer-contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    color: #c8d4e8;
  }

  .footer-contact-item i {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #d7fff6;
    background: rgba(42, 200, 162, 0.24);
  }

  .footer-contact-item a {
    color: #c8d4e8;
    text-decoration: none;
  }

  .footer-contact-item a:hover {
    color: #f0fffb;
  }

  .footer-bottom {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    color: #9fb3cf;
    font-size: 0.95rem;
  }

  .footer-bottom p {
    margin: 0;
  }

  @media (max-width: 768px) {
    .site-footer {
      margin-top: 52px;
    }

    .footer-bottom {
      text-align: center;
    }
  }
</style>

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
    background: radial-gradient(circle, rgba(13, 110, 253, 0.2), transparent 65%);
    pointer-events: none;
  }

  .site-footer a {
    color: #ffffff !important;
  }

  .site-footer a:hover {
    color: #ffffff !important;
  }

  .footer-brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
  }

  .footer-brand h5 {
    margin: 0;
    color: #ffffff;
    font-weight: 800;
  }

  .footer-brand-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(13, 110, 253, 0.32);
  }

  .footer-title {
    color: #ffffff;
    margin-bottom: 18px;
    font-weight: 700;
  }

  .footer-muted {
    color: #ffffff;
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
    color: #ffffff !important;
    text-decoration: none;
    font-weight: 500;
  }

  .footer-links a:hover {
    color: #ffffff !important;
  }

  .footer-contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    color: #ffffff;
  }

  .footer-contact-item i {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    background: #0d6efd;
    font-size: 1.1rem;
    box-shadow: 0 6px 14px rgba(13, 110, 253, 0.3);
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .footer-contact-item:hover i {
    background: #0a58ca;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(13, 110, 253, 0.4);
  }

  .footer-contact-item a {
    color: #ffffff !important;
    text-decoration: none;
  }

  .footer-contact-item a:hover {
    color: #ffffff;
  }

  .footer-bottom {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    color: #ffffff;
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

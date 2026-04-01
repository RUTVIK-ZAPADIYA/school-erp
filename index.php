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
      <span class="hero-kicker"><i class="fas fa-school"></i> School Dashboard</span>
      <h1>Welcome to Your School Portal</h1>
      <p>Access your academic records, attendance, grades, fees, and more—all in one secure platform.</p>
      <div class="hero-buttons">
        <a href="register.php" class="btn btn-primary btn-hero"><i class="fas fa-user-plus"></i> Create Account</a>
        <a href="login.php" class="btn btn-outline btn-hero"><i class="fas fa-sign-in-alt"></i> Login</a>
      </div>
    </div>
  </div>

  <div class="stats-section">
    <div class="container">
      <div class="row g-3">
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-users"></i> 487</div>
            <div class="stat-label">Total Students</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-chalkboard-teacher"></i> 42</div>
            <div class="stat-label">Teachers</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-book"></i> 28</div>
            <div class="stat-label">Classes</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-percentage"></i> 92%</div>
            <div class="stat-label">Avg. Attendance</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="features-section">
    <div class="container">
      <div class="content-card">
        <h2 class="section-title">Portal Features</h2>
        <p class="section-subtitle">Access your personalized dashboard based on your role—students, teachers, and administrators each have dedicated tools.</p>
        <div class="row g-4">
          <div class="col-lg-4 col-md-6">
            <div class="feature-card text-center">
              <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
              <h4>Student Portal</h4>
              <p>Track attendance, view marks, access assignments, manage fee status, and communicate with teachers easily.</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="feature-card text-center">
              <div class="feature-icon"><i class="fas fa-chalkboard"></i></div>
              <h4>Teacher Dashboard</h4>
              <p>Manage classes, record attendance, post grades, assign work, and track student progress in one place.</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="feature-card text-center">
              <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
              <h4>Admin Analytics</h4>
              <p>View school-wide insights, generate reports, manage budgets, and oversee all operational activities.</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="feature-card text-center">
              <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
              <h4>Attendance System</h4>
              <p>Mark daily attendance, track absence patterns, and receive automated alerts for low attendance rates.</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="feature-card text-center">
              <div class="feature-icon"><i class="fas fa-receipt"></i></div>
              <h4>Fee Management</h4>
              <p>Check fee status, generate payment receipts, track dues, and pay online securely through the system.</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="feature-card text-center">
              <div class="feature-icon"><i class="fas fa-envelope"></i></div>
              <h4>Notifications</h4>
              <p>Get real-time updates on attendance, exam schedules, fee reminders, and important school announcements.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="quick-access-section">
    <div class="container">
      <div class="content-card">
        <h2 class="section-title">Quick Access by Role</h2>
        <p class="section-subtitle">Choose your role to access the features you need most.</p>
        <div class="row g-3">
          <div class="col-md-6 col-lg-3">
            <div class="role-card">
              <div class="role-icon"><i class="fas fa-user-graduate"></i></div>
              <h5>Student</h5>
              <p>View marks, attendance, assignments, and fee details</p>
              <a href="login.php" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right"></i> Login as Student</a>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="role-card">
              <div class="role-icon"><i class="fas fa-chalkboard-user"></i></div>
              <h5>Teacher</h5>
              <p>Manage classes, grades, attendance, and schedules</p>
              <a href="login.php" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right"></i> Login as Teacher</a>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="role-card">
              <div class="role-icon"><i class="fas fa-user-tie"></i></div>
              <h5>Administrator</h5>
              <p>Oversee school operations, reports, and analytics</p>
              <a href="login.php" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right"></i> Login as Admin</a>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="role-card">
              <div class="role-icon"><i class="fas fa-users"></i></div>
              <h5>New User</h5>
              <p>Don't have an account? Register now to get started</p>
              <a href="register.php" class="btn btn-sm btn-primary"><i class="fas fa-user-plus"></i> Register Now</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="announcements-section">
    <div class="container">
      <div class="content-card">
        <h2 class="section-title">Latest Announcements</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="announcement-card">
              <div class="announcement-date"><i class="fas fa-calendar"></i> Mar 28, 2026</div>
              <h5>Exam Schedule Released</h5>
              <p>The final examination schedule for all classes has been published. Students can view it in their portal.</p>
              <a href="#" class="text-primary">Read more →</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="announcement-card">
              <div class="announcement-date"><i class="fas fa-calendar"></i> Mar 25, 2026</div>
              <h5>Fee Payment Deadline</h5>
              <p>Please note that the fee payment deadline for Q2 is March 31, 2026. Submit online to avoid late fees.</p>
              <a href="#" class="text-primary">Read more →</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="announcement-card">
              <div class="announcement-date"><i class="fas fa-calendar"></i> Mar 20, 2026</div>
              <h5>Sports Day Celebration</h5>
              <p>Join us for our annual sports day on April 5, 2026. All students and staff are invited to participate.</p>
              <a href="#" class="text-primary">Read more →</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="announcement-card">
              <div class="announcement-date"><i class="fas fa-calendar"></i> Mar 15, 2026</div>
              <h5>Annual Report Available</h5>
              <p>The school's annual performance report is now available for download in the resources section.</p>
              <a href="#" class="text-primary">Read more →</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="testimonials-section">
    <div class="container">
      <div class="content-card">
        <h2 class="section-title">What Users Say</h2>
        <p class="section-subtitle">Hear from students, teachers, and administrators about their experience.</p>
        <div class="row g-4">
          <div class="col-lg-4 col-md-6">
            <div class="testimonial-card">
              <div class="stars"><i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i></div>
              <p>"The system is very user-friendly. I can easily check my attendance and marks anytime, anywhere."</p>
              <div class="testimonial-author">
                <div class="author-avatar">AR</div>
                <div>
                  <strong>Aarav Rajpurohit</strong>
                  <small>Student, Class 10</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="testimonial-card">
              <div class="stars"><i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i></div>
              <p>"Managing grades and attendance has become so efficient. The dashboard saves me hours each day."</p>
              <div class="testimonial-author">
                <div class="author-avatar">RP</div>
                <div>
                  <strong>Rajesh Patel</strong>
                  <small>Mathematics Teacher</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="testimonial-card">
              <div class="stars"><i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i></div>
              <p>"Comprehensive analytics and reports help us make better decisions. Highly recommended for schools!"</p>
              <div class="testimonial-author">
                <div class="author-avatar">MP</div>
                <div>
                  <strong>Meera Sharma</strong>
                  <small>School Principal</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="resources-section">
    <div class="container">
      <div class="content-card">
        <h2 class="section-title">Help & Resources</h2>
        <p class="section-subtitle">Find guides, FAQs, and support documents to help you get started.</p>
        <div class="row g-3">
          <div class="col-md-3 col-sm-6">
            <div class="resource-card text-center">
              <div class="resource-icon"><i class="fas fa-book"></i></div>
              <h6>User Manual</h6>
              <p class="small">Step-by-step guide to using the system</p>
              <a href="#" class="text-primary small">Download PDF <i class="fas fa-download"></i></a>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="resource-card text-center">
              <div class="resource-icon"><i class="fas fa-circle-question"></i></div>
              <h6>FAQ</h6>
              <p class="small">Frequently asked questions answered</p>
              <a href="#" class="text-primary small">View FAQ <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="resource-card text-center">
              <div class="resource-icon"><i class="fas fa-video"></i></div>
              <h6>Video Tutorials</h6>
              <p class="small">Watch how to use key features</p>
              <a href="#" class="text-primary small">Watch Videos <i class="fas fa-play"></i></a>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="resource-card text-center">
              <div class="resource-icon"><i class="fas fa-headset"></i></div>
              <h6>Support</h6>
              <p class="small">Contact our support team</p>
              <a href="#" class="text-primary small">Get Help <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="cta-section">
    <div class="container">
      <div class="content-card text-center">
        <h2>Ready to Get Started?</h2>
        <p>Join our school community today and experience seamless educational management.</p>
        <div class="mt-4">
          <a href="register.php" class="btn btn-primary btn-hero me-2"><i class="fas fa-user-plus"></i> Register Now</a>
          <a href="login.php" class="btn btn-primary btn-hero"><i class="fas fa-sign-in-alt"></i> Login to Portal</a>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

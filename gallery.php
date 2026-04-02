<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gallery - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/public-pages.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <section class="page-header">
    <div class="container">
      <h1 class="display-6 fw-bold mb-3">School Gallery</h1>
      <p class="lead mb-0">A visual snapshot of school life, collaboration, and campus moments powered by School ERP.</p>
    </div>
  </section>

  <section class="content-section">
    <div class="container">
      <div class="content-card">
        <div class="row g-4">
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card">
              <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=80" alt="Classroom session">
              <div class="gallery-content">
                <span class="gallery-tag"><i class="fas fa-chalkboard"></i> Academics</span>
                <h5>Interactive Classroom Session</h5>
                <p class="gallery-caption">Dynamic teaching sessions focused on participation and concept clarity.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card">
              <img src="Images/7.jpg" alt="Campus event">
              <div class="gallery-content">
                <span class="gallery-tag"><i class="fas fa-calendar-days"></i> Events</span>
                <h5>Annual Campus Event</h5>
                <p class="gallery-caption">Celebrating culture, talent, and achievements with the whole school community.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card">
              <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=900&q=80" alt="Library learning">
              <div class="gallery-content">
                <span class="gallery-tag"><i class="fas fa-users"></i> Collaboration</span>
                <h5>Collaborative Learning Space</h5>
                <p class="gallery-caption">Students learn together through group discussions, activities, and peer support.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card">
              <img src="https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=900&q=80" alt="Library books">
              <div class="gallery-content">
                <span class="gallery-tag"><i class="fas fa-book-open"></i> Resources</span>
                <h5>Digital + Physical Library</h5>
                <p class="gallery-caption">Balanced access to traditional books and digital reference material.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card">
              <img src="https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=900&q=80" alt="Computer lab">
              <div class="gallery-content">
                <span class="gallery-tag"><i class="fas fa-laptop-code"></i> Technology</span>
                <h5>Technology Enabled Labs</h5>
                <p class="gallery-caption">Hands-on practical learning through computer and science lab experiences.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card">
              <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=900&q=80" alt="Teacher with students">
              <div class="gallery-content">
                <span class="gallery-tag"><i class="fas fa-hand-holding-heart"></i> Mentorship</span>
                <h5>Student Mentorship Moments</h5>
                <p class="gallery-caption">Supportive teacher guidance that nurtures confidence and growth.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

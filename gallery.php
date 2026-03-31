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
  <style>
    .gallery-hero {
      padding: 88px 0 66px;
      background: linear-gradient(135deg, #13213a, #1a3459);
      color: #ffffff;
    }
    .gallery-card {
      border: 0;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.1);
      background: #ffffff;
    }
    .gallery-card img {
      height: 220px;
      object-fit: cover;
      width: 100%;
    }
    .gallery-caption {
      padding: 14px 16px;
      font-weight: 600;
      color: #0f172a;
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <section class="gallery-hero text-center">
    <div class="container">
      <h1 class="display-6 fw-bold mb-3">School Gallery</h1>
      <p class="lead mb-0">A quick look at campus life, events, and classrooms powered by School ERP.</p>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="gallery-card">
            <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=80" alt="Classroom session">
            <div class="gallery-caption">Interactive Classroom Session</div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="gallery-card">
            <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=900&q=80" alt="Campus event">
            <div class="gallery-caption">Annual Campus Event</div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="gallery-card">
            <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=900&q=80" alt="Library learning">
            <div class="gallery-caption">Collaborative Learning Space</div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="gallery-card">
            <img src="https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=900&q=80" alt="Library books">
            <div class="gallery-caption">Digital + Physical Library</div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="gallery-card">
            <img src="https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=900&q=80" alt="Computer lab">
            <div class="gallery-caption">Technology Enabled Labs</div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="gallery-card">
            <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=900&q=80" alt="Teacher with students">
            <div class="gallery-caption">Student Mentorship Moments</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

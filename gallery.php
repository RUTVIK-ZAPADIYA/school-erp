<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Photo Gallery</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#ecf0f1; }
    .page-header { background:#192a56; color:#fff; padding:60px 0; text-align:center; }
    .page-header h1 { margin:0; font-size:2.5rem; font-weight:700; }
    .carousel-wrapper { max-width:1100px; margin:40px auto; }
    .carousel-item { border-radius:10px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.12); }
    .carousel-item img {
      width:100%;
      height:480px;
      object-fit:cover;
      display:block;
    }
    .carousel-caption {
      background:rgba(25,42,86,0.75);
      border-radius:8px;
      padding:8px 12px;
      bottom:18px;
    }
    @media (max-width:992px) { .carousel-item img { height:380px; } }
    @media (max-width:576px) { .carousel-item img { height:220px; } }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <header class="page-header">
    <div class="container">
      <h1>Photo Gallery</h1>
      <p style="color:#bdc3c7; margin-top:6px;">Explore our school moments and events</p>
    </div>
  </header>

  <main class="carousel-wrapper">
    <!-- Bootstrap carousel: autoplay enabled, pauses on hover -->
    <div id="lightCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000" data-bs-pause="hover">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="Images/1.jpg" alt="Annual Day Celebration" loading="lazy">
          <div class="carousel-caption d-none d-md-block">
            <h5>Annual Day Celebration</h5>
            <p>December 2023</p>
          </div>
        </div>

        <div class="carousel-item">
          <img src="Images/2.jpg" alt="Sports Day Event" loading="lazy">
          <div class="carousel-caption d-none d-md-block">
            <h5>Sports Day Event</h5>
            <p>November 2023</p>
          </div>
        </div>

        <div class="carousel-item">
          <img src="Images/3.jpg" alt="Science Exhibition" loading="lazy">
          <div class="carousel-caption d-none d-md-block">
            <h5>Science Exhibition</h5>
            <p>October 2023</p>
          </div>
        </div>

      

        <div class="carousel-item">
          <img src="Images/4.jpg" alt="Graduation Ceremony" loading="lazy">
          <div class="carousel-caption d-none d-md-block">
            <h5>Graduation Ceremony</h5>
            <p>July 2023</p>
          </div>
        </div>

        

        <div class="carousel-item">
          <img src="Images/5.jpg" alt="Music Festival" loading="lazy">
          <div class="carousel-caption d-none d-md-block">
            <h5>Music Festival</h5>
            <p>May 2023</p>
          </div>
        </div>

        <div class="carousel-item">
          <img src="Images/6.jpg" alt="Field Trip" loading="lazy">
          <div class="carousel-caption d-none d-md-block">
            <h5>Field Trip</h5>
            <p>April 2023</p>
          </div>
        </div>


      <!-- Controls (kept minimal) -->
      <button class="carousel-control-prev" type="button" data-bs-target="#lightCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#lightCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
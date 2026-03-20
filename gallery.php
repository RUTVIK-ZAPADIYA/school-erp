<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Photo Gallery</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/public-pages.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <header class="page-header">
    <div class="container">
      <h1>Photo Gallery</h1>
      <p>Explore our school moments and events</p>
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
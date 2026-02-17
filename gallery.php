<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Photo Gallery - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #ecf0f1; }
    .page-header { background: #192a56; color: white; padding: 80px 0; text-align: center; }
    .page-header h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; }
    .page-header p { font-size: 1.2rem; color: #bdc3c7; }

    /* Carousel container */
    .gallery-section { padding: 60px 0; }
    .carousel-container { max-width: 1100px; margin: 0 auto; }

    /* Carousel image sizing and style */
    .carousel-item { border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
    .carousel-item img {
      width: 100%;
      height: 480px; /* desktop height */
      object-fit: cover; /* ensures proper crop without distortion */
      transition: transform 0.15s ease-out;
      will-change: transform;
      cursor: pointer;
      display: block;
    }

    /* Overlay inside carousel (title/date) */
    .carousel-caption {
      background: rgba(25,42,86,0.75);
      border-radius: 8px;
      padding: 10px 14px;
      bottom: 18px;
      transform: translateY(0);
    }
    .carousel-caption h5 { margin: 0; font-weight: 600; }
    .carousel-caption p { margin: 0; color: #d1d8e0; font-size: 0.9rem; }

    /* Thumbnails row */
    .thumbs { margin-top: 18px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
    .thumb {
      width: 120px;
      height: 70px;
      overflow: hidden;
      border-radius: 6px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      cursor: pointer;
      border: 2px solid transparent;
      transition: transform 0.15s, border-color 0.15s;
    }
    .thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .thumb.active, .thumb:hover { transform: translateY(-4px); border-color: #192a56; }

    /* Responsive heights */
    @media (max-width: 992px) {
      .carousel-item img { height: 380px; }
    }
    @media (max-width: 576px) {
      .carousel-item img { height: 220px; }
      .thumb { width: 80px; height: 50px; }
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <div class="page-header">
    <div class="container">
      <h1>Photo Gallery</h1>
      <p>Explore our school moments and events</p>
    </div>
  </div>

  <div class="gallery-section">
    <div class="container carousel-container">
      <!-- Bootstrap Carousel -->
      <div id="schoolCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-inner">
          <!-- Slide 0 -->
          <div class="carousel-item active">
            <img src="Images/1.jpg" alt="Annual Day Celebration" data-title="Annual Day Celebration" data-date="December 2023">
            <div class="carousel-caption d-none d-md-block">
              <h5>Annual Day Celebration</h5>
              <p>December 2023</p>
            </div>
          </div>
          <!-- Slide 1 -->
          <div class="carousel-item">
            <img src="Images/2.jpg" alt="Sports Day Event" data-title="Sports Day Event" data-date="November 2023">
            <div class="carousel-caption d-none d-md-block">
              <h5>Sports Day Event</h5>
              <p>November 2023</p>
            </div>
          </div>
          <!-- Slide 2 -->
          <div class="carousel-item">
            <img src="Images/3.jpg" alt="Science Exhibition" data-title="Science Exhibition" data-date="October 2023">
            <div class="carousel-caption d-none d-md-block">
              <h5>Science Exhibition</h5>
              <p>October 2023</p>
            </div>
          </div>
          <!-- Add more slides as needed -->
        </div>

        <!-- Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#schoolCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#schoolCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>

      <!-- Thumbnails -->
      <div class="thumbs" id="carouselThumbs" aria-hidden="false">
        <div class="thumb active" data-bs-slide-to="0" data-bs-target="#schoolCarousel">
          <img src="Images/1.jpg" alt="Annual Day">
        </div>
        <div class="thumb" data-bs-slide-to="1" data-bs-target="#schoolCarousel">
          <img src="Images/2.jpg" alt="Sports Day">
        </div>
        <div class="thumb" data-bs-slide-to="2" data-bs-target="#schoolCarousel">
          <img src="Images/3.jpg" alt="Science Exhibition">
        </div>
        <!-- Add more thumbs matching slides -->
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Keep thumbnails in sync with carousel
    const carouselElement = document.getElementById('schoolCarousel');
    const thumbs = document.querySelectorAll('.thumb');
    const carousel = new bootstrap.Carousel(carouselElement, { interval: 5000, ride: false });

    // When carousel slide changes, update active thumbnail
    carouselElement.addEventListener('slid.bs.carousel', function (e) {
      const idx = e.to;
      thumbs.forEach((t, i) => t.classList.toggle('active', i === idx));
    });

    // Clicking a thumbnail moves to that slide
    thumbs.forEach((t, i) => {
      t.addEventListener('click', () => {
        carousel.to(i);
        thumbs.forEach(x => x.classList.remove('active'));
        t.classList.add('active');
      });
    });

    // Subtle cursor-follow parallax on active slide image
    const carouselInner = document.querySelector('.carousel-inner');
    carouselInner.addEventListener('mousemove', function (ev) {
      const activeImg = carouselInner.querySelector('.carousel-item.active img');
      if (!activeImg) return;
      const rect = activeImg.getBoundingClientRect();
      const cx = rect.left + rect.width / 2;
      const cy = rect.top + rect.height / 2;
      const dx = (ev.clientX - cx) / (rect.width / 2); // -1 .. 1
      const dy = (ev.clientY - cy) / (rect.height / 2);
      const maxTranslate = 12; // px
      activeImg.style.transform = `translate(${dx * maxTranslate}px, ${dy * maxTranslate}px) scale(1.02)`;
    });

    // Reset transform when mouse leaves carousel
    carouselInner.addEventListener('mouseleave', function () {
      const imgs = carouselInner.querySelectorAll('img');
      imgs.forEach(img => img.style.transform = 'translate(0,0) scale(1)');
    });

    // Make images keyboard accessible and clickable with Enter
    document.querySelectorAll('.carousel-item img').forEach(img => {
      img.setAttribute('tabindex', '0');
      img.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          // toggle caption visibility or open modal if you want
          // For now, move to next slide
          carousel.next();
        }
      });
    });
  </script>
</body>
</html>
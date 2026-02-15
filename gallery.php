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
    .page-header { background: #2c3e50; color: white; padding: 80px 0; text-align: center; }
    .page-header h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; }
    .page-header p { font-size: 1.2rem; color: #bdc3c7; }
    .gallery-section { padding: 60px 0; }
    .gallery-item { position: relative; margin-bottom: 30px; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; cursor: pointer; }
    .gallery-item:hover { transform: scale(1.05); }
    .gallery-img { width: 100%; height: 250px; background: #3498db; display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem; }
    .gallery-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(44,62,80,0.9); color: white; padding: 15px; transform: translateY(100%); transition: transform 0.3s; }
    .gallery-item:hover .gallery-overlay { transform: translateY(0); }
    .gallery-overlay h5 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .gallery-overlay p { margin: 5px 0 0; font-size: 0.9rem; color: #bdc3c7; }
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
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Annual Day Celebration</h5>
              <p>December 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Sports Day Event</h5>
              <p>November 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Science Exhibition</h5>
              <p>October 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Cultural Program</h5>
              <p>September 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Independence Day</h5>
              <p>August 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Graduation Ceremony</h5>
              <p>July 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Art Competition</h5>
              <p>June 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Music Festival</h5>
              <p>May 2023</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="gallery-item">
            <div class="gallery-img"><i class="fas fa-image"></i></div>
            <div class="gallery-overlay">
              <h5>Field Trip</h5>
              <p>April 2023</p>
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

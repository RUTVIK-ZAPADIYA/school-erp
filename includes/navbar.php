<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #2c3e50; box-shadow: 0 2px 15px rgba(0,0,0,0.1); padding: 15px 0;">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php" style="font-weight: 700; font-size: 1.5rem; color: #fff !important;">
      <div style="width: 45px; height: 45px; background: #3498db; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
        <i class="fas fa-graduation-cap" style="font-size: 1.3rem;"></i>
      </div>
      <span>School ERP</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border: none; padding: 8px;">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link px-3" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link px-3" href="about.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link px-3" href="gallery.php">Gallery</a></li>
        <li class="nav-item"><a class="nav-link px-3" href="register.php">Register</a></li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
          <a class="btn text-white px-4 py-2" href="login.php" style="background-color: #3498db; border: none; border-radius: 25px; font-weight: 600; transition: all 0.3s;">
            <i class="fas fa-sign-in-alt"></i> Login
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<style>
  .navbar { transition: all 0.3s; }
  .nav-link { color: #ecf0f1 !important; transition: all 0.3s; font-weight: 500; position: relative; }
  .nav-link:hover { color: #3498db !important; }
  .nav-link::after { content: ''; position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: #3498db; transition: all 0.3s; transform: translateX(-50%); }
  .nav-link:hover::after { width: 80%; }
  .btn:hover { background-color: #2980b9 !important; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(52,152,219,0.3); }
  @media (max-width: 991px) {
    .nav-link::after { display: none; }
    .navbar-nav { padding: 15px 0; }
  }
</style>

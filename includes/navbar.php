<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

<nav class="navbar navbar-expand-lg sticky-top main-nav">
  <div class="container">
    <a class="navbar-brand brand-mark" href="login.php">
      <span class="brand-icon"><i class="fas fa-graduation-cap"></i></span>
      <span>School ERP</span>
    </a>
    <button class="navbar-toggler nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
          <a class="btn nav-login-btn" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
  .main-nav {
    font-family: 'Manrope', sans-serif;
    backdrop-filter: blur(6px);
    background: linear-gradient(120deg, rgba(19, 31, 55, 0.96), rgba(16, 24, 45, 0.96));
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 24px rgba(9, 18, 35, 0.2);
    padding: 12px 0;
  }

  .brand-mark {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: #ffffff !important;
    font-weight: 800;
    letter-spacing: 0.2px;
    font-size: 1.2rem;
  }

  .brand-icon {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(140deg, #0d6efd, #0f4c81);
    box-shadow: 0 10px 20px rgba(7, 22, 50, 0.28);
    color: #ffffff;
  }

  .nav-link {
    color: #ffffff !important;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.2s ease;
  }

  .nav-link:hover,
  .nav-link:focus,
  .nav-link.active {
    color: #ffffff !important;
    background: rgba(13, 110, 253, 0.2) !important;
  }

  .nav-login-btn {
    background: #0d6efd !important;
    color: #ffffff !important;
    border: none;
    border-radius: 999px;
    padding: 10px 22px;
    font-weight: 700;
    box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
  }

  .nav-login-btn:hover {
    color: #ffffff !important;
    background: #0a58ca !important;
    transform: translateY(-1px);
    box-shadow: 0 14px 24px rgba(13, 110, 253, 0.3);
  }

  .nav-toggle {
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 10px;
    padding: 6px 10px;
  }

  .nav-toggle:focus {
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.3);
  }

  @media (max-width: 991px) {
    .main-nav .navbar-collapse {
      margin-top: 12px;
      padding: 14px;
      border-radius: 12px;
      background: rgba(13, 20, 37, 0.92);
    }

    .nav-link {
      padding: 9px 12px;
    }
  }
</style>

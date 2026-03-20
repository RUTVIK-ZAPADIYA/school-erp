<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - School ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/css/auth-pages.css">
  </head>
  <body class="auth-layout">
      <div class="auth-card auth-sm">
        <div class="logo-section">
          <div class="logo-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h1>School ERP System</h1>
          <p class="subtitle">Sign in to access your account</p>
        </div>
        
        <form method="POST" action="">
          <div class="input-group-custom">
            <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" data-validation="required,min" data-min="3">
            <i class="fas fa-user input-icon"></i>
            <div id="username_error" class="invalid-feedback"></div>
          </div>
          
          <div class="input-group-custom">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" data-validation="required,min" data-min="6">
            <i class="fas fa-lock input-icon"></i>
            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            <div id="password_error" class="invalid-feedback"></div>
          </div>
          
          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" name="remember" id="remember">
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-password">Forgot Password?</a>
          </div>
          
          <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Sign In
          </button>
        </form>
        
        <div class="divider">
          <span>OR</span>
        </div>
        
        <div class="signup-link">
          Don't have an account? <a href="register.php">Sign Up</a>
        </div>
      </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="js/validate.js"></script>
    <script>
      // Password toggle functionality
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      
      togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
      });
    </script>
  </body>
</html>
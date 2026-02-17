<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - School ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        background: #192a56;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 20px;
      }
      .login-wrapper {
        width: 100%;
        max-width: 440px;
      }
      .login-container {
        background: #ffffff;
        padding: 50px 45px;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      }
      .logo-section {
        text-align: center;
        margin-bottom: 35px;
      }
      .logo-icon {
        width: 70px;
        height: 70px;
        background: #f7d794;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        box-shadow: 0 8px 20px rgba(247, 215, 148, 0.3);
      }
      .logo-icon i {
        font-size: 32px;
        color: #192a56;
      }
      .login-container h1 {
        font-size: 28px;
        color: #2d3748;
        text-align: center;
        margin-bottom: 8px;
        font-weight: 700;
      }
      .subtitle {
        text-align: center;
        color: #718096;
        margin-bottom: 35px;
        font-size: 14px;
      }
      .input-group-custom {
        position: relative;
        margin-bottom: 25px;
      }
      .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
        font-size: 16px;
        z-index: 1;
      }
      .form-control {
        padding: 14px 16px 14px 45px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        color: #2d3748;
        transition: all 0.3s ease;
        background-color: #f7fafc;
        width: 100%;
      }
      .form-control::placeholder {
        color: #cbd5e0;
      }
      .form-control:focus {
        border-color: #f7d794;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(247, 215, 148, 0.2);
        outline: none;
      }
      .form-control:focus + .input-icon {
        color: #f7d794;
      }
      .form-control.is-invalid {
        border-color: #e74c3c;
      }
      .form-control.is-invalid:focus {
        box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
      }
      .invalid-feedback {
        display: block;
        color: #e74c3c;
        font-size: 13px;
        margin-top: 5px;
      }
      .password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
        cursor: pointer;
        font-size: 16px;
        z-index: 2;
        transition: color 0.3s ease;
      }
      .password-toggle:hover {
        color: #f7d794;
      }
      .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        font-size: 14px;
      }
      .remember-me {
        display: flex;
        align-items: center;
        color: #4a5568;
      }
      .remember-me input {
        margin-right: 8px;
        width: 16px;
        height: 16px;
        cursor: pointer;
      }
      .forgot-password {
        color: #f7d794;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
      }
      .forgot-password:hover {
        color: #e5c682;
        text-decoration: underline;
      }
      .btn-login {
        width: 100%;
        padding: 14px;
        background: #f7d794;
        color: #192a56;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-bottom: 25px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(247, 215, 148, 0.3);
      }
      .btn-login:hover {
        background: #e5c682;
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(247, 215, 148, 0.4);
      }
      .btn-login:active {
        transform: translateY(0);
      }
      .divider {
        text-align: center;
        margin: 25px 0;
        position: relative;
      }
      .divider::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 100%;
        height: 1px;
        background: #e2e8f0;
      }
      .divider span {
        background: #ffffff;
        padding: 0 15px;
        color: #a0aec0;
        font-size: 13px;
        position: relative;
        z-index: 1;
      }
      .signup-link {
        text-align: center;
        color: #718096;
        font-size: 14px;
      }
      .signup-link a {
        color: #f7d794;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
      }
      .signup-link a:hover {
        color: #e5c682;
        text-decoration: underline;
      }
      @media (max-width: 480px) {
        .login-container {
          padding: 40px 30px;
        }
        .login-container h1 {
          font-size: 24px;
        }
      }
    </style>
  </head>
  <body>
    <div class="login-wrapper">
      <div class="login-container">
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
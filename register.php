<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body { background: #192a56; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px 20px; }
    .register-container { max-width: 600px; margin: 0 auto; background: white; padding: 50px 45px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .logo-section { text-align: center; margin-bottom: 35px; }
    .logo-icon { width: 70px; height: 70px; background: #f7d794; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(247,215,148,0.3); }
    .logo-icon i { font-size: 32px; color: #192a56; }
    h1 { font-size: 28px; color: #2d3748; text-align: center; margin-bottom: 8px; font-weight: 700; }
    .subtitle { text-align: center; color: #718096; margin-bottom: 35px; font-size: 14px; }
    .form-label { color: #2d3748; font-weight: 500; margin-bottom: 8px; }
    .form-control { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.3s; background-color: #f7fafc; }
    .form-control:focus { border-color: #f7d794; background-color: white; box-shadow: 0 0 0 4px rgba(247,215,148,0.2); outline: none; }
    .form-control.is-invalid { border-color: #e74c3c; }
    .form-control.is-invalid:focus { box-shadow: 0 0 0 4px rgba(231,76,60,0.1); }
    .invalid-feedback { display: block; color: #e74c3c; font-size: 13px; margin-top: 5px; }
    .btn-register { width: 100%; padding: 14px; background: #f7d794; color: #192a56; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; margin-top: 20px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(247,215,148,0.3); }
    .btn-register:hover { background: #e5c682; transform: translateY(-2px); box-shadow: 0 6px 25px rgba(247,215,148,0.4); }
    .login-link { text-align: center; color: #718096; font-size: 14px; margin-top: 20px; }
    .login-link a { color: #f7d794; text-decoration: none; font-weight: 600; }
    .login-link a:hover { color: #e5c682; text-decoration: underline; }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="logo-section">
      <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
      <h1>Create Account</h1>
      <p class="subtitle">Join our School ERP System</p>
    </div>
    
    <form method="POST" action="" id="registerForm" novalidate>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">First Name</label>
          <input type="text" class="form-control" id="first_name" name="first_name" data-validation="required,min,alphabetic" data-min="2">
          <div id="first_name_error" class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" class="form-control" id="last_name" name="last_name" data-validation="required,min,alphabetic" data-min="2">
          <div id="last_name_error" class="invalid-feedback"></div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" data-validation="required,email">
        <div id="email_error" class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone Number</label>
        <input type="tel" class="form-control" id="phone" name="phone" data-validation="required,number" data-min="10" data-max="15">
        <div id="phone_error" class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" data-validation="required,min" data-min="4">
        <div id="username_error" class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" data-validation="required,min,strongPassword" data-min="8">
        <div id="password_error" class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" data-validation="required,confirmPassword">
        <div id="confirm_password_error" class="invalid-feedback"></div>
      </div>
      <button type="submit" class="btn-register"><i class="fas fa-user-plus"></i> Register</button>
    </form>
    
    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/validate.js"></script>
</body>
</html>

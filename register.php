<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="assets/css/auth-pages.css">
</head>
<body class="auth-layout">
  <div class="auth-card">
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

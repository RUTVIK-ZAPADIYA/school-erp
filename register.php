<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body { background: #2c3e50; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px 20px; }
    .register-container { max-width: 600px; margin: 0 auto; background: white; padding: 50px 45px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .logo-section { text-align: center; margin-bottom: 35px; }
    .logo-icon { width: 70px; height: 70px; background: #3498db; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(52,152,219,0.3); }
    .logo-icon i { font-size: 32px; color: white; }
    h1 { font-size: 28px; color: #2d3748; text-align: center; margin-bottom: 8px; font-weight: 700; }
    .subtitle { text-align: center; color: #718096; margin-bottom: 35px; font-size: 14px; }
    .form-label { color: #2d3748; font-weight: 500; margin-bottom: 8px; }
    .form-control { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.3s; background-color: #f7fafc; }
    .form-control:focus { border-color: #3498db; background-color: white; box-shadow: 0 0 0 4px rgba(52,152,219,0.1); outline: none; }
    .form-control.is-invalid { border-color: #e74c3c; }
    .form-control.is-invalid:focus { box-shadow: 0 0 0 4px rgba(231,76,60,0.1); }
    .invalid-feedback { display: block; color: #e74c3c; font-size: 13px; margin-top: 5px; }
    .btn-register { width: 100%; padding: 14px; background: #3498db; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; margin-top: 20px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(52,152,219,0.3); }
    .btn-register:hover { background: #2980b9; transform: translateY(-2px); box-shadow: 0 6px 25px rgba(52,152,219,0.4); }
    .login-link { text-align: center; color: #718096; font-size: 14px; margin-top: 20px; }
    .login-link a { color: #3498db; text-decoration: none; font-weight: 600; }
    .login-link a:hover { color: #2980b9; text-decoration: underline; }
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
          <input type="text" class="form-control" id="first_name" name="first_name" required>
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" class="form-control" id="last_name" name="last_name" required>
          <div class="invalid-feedback"></div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required>
        <div class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone Number</label>
        <input type="tel" class="form-control" id="phone" name="phone" required>
        <div class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
        <div class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <div class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        <div class="invalid-feedback"></div>
      </div>
      <button type="submit" class="btn-register"><i class="fas fa-user-plus"></i> Register</button>
    </form>
    
    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const registerForm = document.getElementById('registerForm');
    
    registerForm.addEventListener('submit', function(e) {
      e.preventDefault();
      let isValid = true;
      
      const firstName = document.getElementById('first_name');
      const lastName = document.getElementById('last_name');
      const email = document.getElementById('email');
      const phone = document.getElementById('phone');
      const username = document.getElementById('username');
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirm_password');
      
      // Clear previous errors
      [firstName, lastName, email, phone, username, password, confirmPassword].forEach(field => {
        field.classList.remove('is-invalid');
        field.nextElementSibling.textContent = '';
      });
      
      // Validate first name
      if (firstName.value.trim() === '') {
        firstName.classList.add('is-invalid');
        firstName.nextElementSibling.textContent = 'First name is required';
        isValid = false;
      } else if (firstName.value.trim().length < 2) {
        firstName.classList.add('is-invalid');
        firstName.nextElementSibling.textContent = 'First name must be at least 2 characters';
        isValid = false;
      }
      
      // Validate last name
      if (lastName.value.trim() === '') {
        lastName.classList.add('is-invalid');
        lastName.nextElementSibling.textContent = 'Last name is required';
        isValid = false;
      } else if (lastName.value.trim().length < 2) {
        lastName.classList.add('is-invalid');
        lastName.nextElementSibling.textContent = 'Last name must be at least 2 characters';
        isValid = false;
      }
      
      // Validate email
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email.value.trim() === '') {
        email.classList.add('is-invalid');
        email.nextElementSibling.textContent = 'Email is required';
        isValid = false;
      } else if (!emailPattern.test(email.value)) {
        email.classList.add('is-invalid');
        email.nextElementSibling.textContent = 'Please enter a valid email address';
        isValid = false;
      }
      
      // Validate phone
      const phonePattern = /^[0-9]{10,15}$/;
      if (phone.value.trim() === '') {
        phone.classList.add('is-invalid');
        phone.nextElementSibling.textContent = 'Phone number is required';
        isValid = false;
      } else if (!phonePattern.test(phone.value.replace(/[\s-]/g, ''))) {
        phone.classList.add('is-invalid');
        phone.nextElementSibling.textContent = 'Please enter a valid phone number (10-15 digits)';
        isValid = false;
      }
      
      // Validate username
      if (username.value.trim() === '') {
        username.classList.add('is-invalid');
        username.nextElementSibling.textContent = 'Username is required';
        isValid = false;
      } else if (username.value.trim().length < 4) {
        username.classList.add('is-invalid');
        username.nextElementSibling.textContent = 'Username must be at least 4 characters';
        isValid = false;
      } else if (!/^[a-zA-Z0-9_]+$/.test(username.value)) {
        username.classList.add('is-invalid');
        username.nextElementSibling.textContent = 'Username can only contain letters, numbers, and underscores';
        isValid = false;
      }
      
      // Validate password
      if (password.value === '') {
        password.classList.add('is-invalid');
        password.nextElementSibling.textContent = 'Password is required';
        isValid = false;
      } else if (password.value.length < 8) {
        password.classList.add('is-invalid');
        password.nextElementSibling.textContent = 'Password must be at least 8 characters';
        isValid = false;
      } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/.test(password.value)) {
        password.classList.add('is-invalid');
        password.nextElementSibling.textContent = 'Password must contain uppercase, lowercase, and number';
        isValid = false;
      }
      
      // Validate confirm password
      if (confirmPassword.value === '') {
        confirmPassword.classList.add('is-invalid');
        confirmPassword.nextElementSibling.textContent = 'Please confirm your password';
        isValid = false;
      } else if (password.value !== confirmPassword.value) {
        confirmPassword.classList.add('is-invalid');
        confirmPassword.nextElementSibling.textContent = 'Passwords do not match';
        isValid = false;
      }
      
      if (isValid) {
        this.submit();
      }
    });
  </script>
</body>
</html>

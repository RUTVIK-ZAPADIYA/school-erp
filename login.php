<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - School ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
      body {
        background: linear-gradient(135deg, #e8f4f8 0%, #f0e6ff 50%, #ffe6f0 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      .login-container {
        width: 100%;
        max-width: 450px;
        background: rgba(255, 255, 255, 0.95);
        padding: 60px 40px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.6);
      }
      .login-container h1 {
        font-size: 32px;
        color: #4a5568;
        text-align: center;
        margin-bottom: 15px;
        font-weight: 700;
        letter-spacing: -0.5px;
      }
      .login-container .subtitle {
        text-align: center;
        color: #a0aec0;
        margin-bottom: 40px;
        font-size: 15px;
        line-height: 1.6;
      }
      .form-control {
        padding: 13px 16px;
        margin-bottom: 20px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 15px;
        color: #2d3748;
        transition: all 0.3s ease;
        background-color: #f7fafc;
      }
      .form-control::placeholder {
        color: #cbd5e0;
      }
      .form-control:focus {
        border-color: #81e6d9;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(129, 230, 217, 0.1);
        outline: none;
      }
      .btn-login {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, #667eea 0%, #64b5f6 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
      }
      .btn-login:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #42a5f5 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
      }
      .btn-login:active {
        transform: translateY(0);
      }
      .login-footer {
        text-align: center;
        color: #a0aec0;
        font-size: 14px;
      }
      .login-footer a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
      }
      .login-footer a:hover {
        color: #5a67d8;
        text-decoration: underline;
      }
    </style>
  </head>
  <body>
    <div class="login-container">
      <h1>Welcome, Log into you account</h1>
      <p class="subtitle">It is our great pleasure to have<br>you on board!</p>
      
      <form method="POST" action="">
        <input type="text" class="form-control" id="username" name="username" placeholder="Enter Username" required>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" required>
        <button type="submit" class="btn-login">Login</button>
      </form>
      
      <div class="login-footer">
        Already have an account? <a href="form.php">Sign up</a>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>
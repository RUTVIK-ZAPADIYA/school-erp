<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - School ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
      body {
        background: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
      }
      .login-container {
        width: 100%;
        max-width: 450px;
        background-color: white;
        padding: 60px 40px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      }
      .login-container h1 {
        font-size: 32px;
        color: #333;
        text-align: center;
        margin-bottom: 15px;
        font-weight: 600;
      }
      .login-container .subtitle {
        text-align: center;
        color: #999;
        margin-bottom: 40px;
        font-size: 16px;
      }
      .form-control {
        padding: 12px 15px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 15px;
      }
      .form-control::placeholder {
        color: #999;
      }
      .form-control:focus {
        border-color: #1e90ff;
        box-shadow: 0 0 0 0.2rem rgba(30, 144, 255, 0.25);
      }
      .btn-login {
        width: 100%;
        padding: 12px;
        background-color: #1e90ff;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-bottom: 20px;
      }
      .btn-login:hover {
        background-color: #1873cc;
        color: white;
        text-decoration: none;
      }
      .login-footer {
        text-align: center;
        color: #666;
        font-size: 14px;
      }
      .login-footer a {
        color: #1e90ff;
        text-decoration: none;
        font-weight: 600;
      }
      .login-footer a:hover {
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
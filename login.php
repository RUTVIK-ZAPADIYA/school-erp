<?php
session_start();

include 'includes/db_connect.php';

function clear_role_sessions()
{
  unset($_SESSION['admin_id'], $_SESSION['admin_name']);
  unset($_SESSION['student_id'], $_SESSION['student_name']);
}

function redirect_by_role($role)
{
  if ($role === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
  }
  if ($role === 'teacher') {
    header('Location: teacher/dashboard.php');
    exit();
  }
  if ($role === 'student') {
    header('Location: student/dashboard.php');
    exit();
  }
}

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
  redirect_by_role($_SESSION['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string) ($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = 'Please enter username/email and password.';
  } else {
    $sql = 'SELECT id, username, password, role, name, email FROM users WHERE username = ? OR email = ? LIMIT 1';
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
      $error = 'Login is temporarily unavailable. Please try again.';
    } else {
      mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $storedPassword = (string) ($user['password'] ?? '');

        $isPasswordValid = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

        if ($isPasswordValid) {
          // Upgrade plain-text legacy passwords to hashed form.
          if (strpos($storedPassword, '$2y$') !== 0 && strpos($storedPassword, '$argon2') !== 0) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
            if ($updateStmt) {
              $userIdForUpdate = (int) $user['id'];
              mysqli_stmt_bind_param($updateStmt, 'si', $newHash, $userIdForUpdate);
              mysqli_stmt_execute($updateStmt);
              mysqli_stmt_close($updateStmt);
            }
          }

          session_regenerate_id(true);
          clear_role_sessions();

          $_SESSION['user_id'] = (int) $user['id'];
          $_SESSION['username'] = $user['username'];
          $_SESSION['role'] = $user['role'];
          $_SESSION['name'] = $user['name'];

          if ($user['role'] === 'admin') {
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_name'] = $user['name'];
          } elseif ($user['role'] === 'student') {
            $_SESSION['student_id'] = (int) $user['id'];
            $_SESSION['student_name'] = $user['name'];
          }

          mysqli_stmt_close($stmt);
          redirect_by_role($user['role']);
          $error = 'Your account role is not recognized.';
        } else {
          $error = 'Invalid password.';
        }
      } else {
        $error = 'User not found.';
      }

      mysqli_stmt_close($stmt);
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - School ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Validation Plugin -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>
    <link rel="stylesheet" href="assets/css/auth-pages.css">
    <style>
      .error {
        color: #dc3545 !important;
        font-size: 0.875rem !important;
        margin-top: 0.5rem !important;
        display: block !important;
      }
      input.error {
        border-color: #dc3545 !important;
        background-color: #fff5f5 !important;
      }
      .form-control:focus {
        border-color: #2a7f62;
        box-shadow: 0 0 0 0.2rem rgba(42, 127, 98, 0.25);
      }
    </style>
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

        <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
          <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="" novalidate>
          <div class="input-group-custom">
            <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required>
            <i class="fas fa-user input-icon"></i>
          </div>

          <div class="input-group-custom">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            <i class="fas fa-lock input-icon"></i>
            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
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
    <script>
      $(document).ready(function() {
        // Initialize jQuery Validation
        $('#loginForm').validate({
          rules: {
            username: {
              required: true,
              minlength: 3
            },
            password: {
              required: true,
              minlength: 6
            }
          },
          messages: {
            username: {
              required: "Please enter your username or email",
              minlength: "Username must be at least 3 characters"
            },
            password: {
              required: "Please enter your password",
              minlength: "Password must be at least 6 characters"
            }
          },
          errorElement: 'div',
          errorClass: 'error',
          highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid');
          },
          unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
          },
          submitHandler: function(form) {
            form.submit();
          }
        });

        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);
          this.classList.toggle('fa-eye');
          this.classList.toggle('fa-eye-slash');
        });
      });
    </script>
  </body>
</html>
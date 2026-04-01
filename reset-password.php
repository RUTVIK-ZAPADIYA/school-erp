<?php
session_start();
include 'includes/db_connect.php';

$message = '';
$messageType = '';
$tokenValid = false;
$token = trim((string) ($_GET['token'] ?? ''));

// Verify token exists and is not expired
if ($token) {
  $sql = 'SELECT id, email, name FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1';
  $stmt = $conn->prepare($sql);

  if ($stmt) {
    $stmt->bind_param('s', $token);
    if (!$stmt->execute()) {
      error_log('Reset token validation execute failed: ' . $stmt->error);
      $message = 'An error occurred. Please request a new password reset link.';
      $messageType = 'error';
    } else {
      $result = $stmt->get_result();

      if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $tokenValid = true;
        $userId = (int) $user['id'];
      } else {
        error_log('Reset token invalid/expired for token prefix: ' . substr($token, 0, 12));
        $message = 'Invalid or expired password reset link. Please request a new one.';
        $messageType = 'error';
      }
    }

    $stmt->close();
  } else {
    error_log('Reset token validation prepare failed: ' . $conn->error);
    $message = 'An error occurred. Please request a new password reset link.';
    $messageType = 'error';
  }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
  $password = (string) ($_POST['password'] ?? '');
  $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

  if ($password === '' || $confirmPassword === '') {
    $message = 'Please enter and confirm your password.';
    $messageType = 'error';
  } elseif ($password !== $confirmPassword) {
    $message = 'Passwords do not match.';
    $messageType = 'error';
  } elseif (strlen($password) < 6) {
    $message = 'Password must be at least 6 characters long.';
    $messageType = 'error';
  } else {
    // Hash password and update user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $updateSql = 'UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?';
    $updateStmt = $conn->prepare($updateSql);

    if ($updateStmt) {
      $updateStmt->bind_param('si', $hashedPassword, $userId);
      $updateStmt->execute();
      $updateStmt->close();

      $message = 'Password reset successfully! You can now sign in with your new password.';
      $messageType = 'success';
      $tokenValid = false; // Clear form after success
    } else {
      $message = 'An error occurred while resetting your password. Please try again.';
      $messageType = 'error';
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - School ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>
    <link rel="stylesheet" href="assets/css/auth-pages.css">
  </head>
  <body class="auth-layout">
      <div class="auth-card auth-sm">
        <p class="auth-head-kicker"><i class="fas fa-lock-open"></i> Set New Password</p>
        <div class="logo-section">
          <div class="logo-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h1>School ERP System</h1>
          <p class="subtitle">Create a strong password for your account</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" role="alert">
          <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($tokenValid): ?>
        <form id="resetPasswordForm" method="POST" action="" novalidate>
          <div class="input-group-custom">
            <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required>
            <i class="fas fa-lock input-icon"></i>
            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
          </div>

          <div class="input-group-custom">
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            <i class="fas fa-lock input-icon"></i>
            <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
          </div>

          <button type="submit" class="btn-login">
            <i class="fas fa-check"></i> Reset Password
          </button>
        </form>
        <?php else: ?>
        <div class="alert alert-info" role="alert">
          <i class="fas fa-info-circle"></i> Please request a new password reset link.
        </div>
        <?php endif; ?>

        <div class="divider">
          <span>OR</span>
        </div>

        <div class="signup-link">
          Return to <a href="login.php">Sign In</a>
        </div>
      </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
      $(document).ready(function() {
        <?php if ($tokenValid): ?>
        $('#resetPasswordForm').validate({
          rules: {
            password: {
              required: true,
              minlength: 6
            },
            confirm_password: {
              required: true,
              minlength: 6,
              equalTo: '#password'
            }
          },
          messages: {
            password: {
              required: "Please enter a new password",
              minlength: "Password must be at least 6 characters"
            },
            confirm_password: {
              required: "Please confirm your password",
              minlength: "Password must be at least 6 characters",
              equalTo: "Passwords do not match"
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
        <?php endif; ?>

        // Password toggle for new password
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
          togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
          });
        }

        // Password toggle for confirm password
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (toggleConfirmPassword && confirmPasswordInput) {
          toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
          });
        }
      });
    </script>
  </body>
</html>

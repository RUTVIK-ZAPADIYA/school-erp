<?php
session_start();
include 'includes/db_connect.php';
include 'includes/email-helper.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if ($email === '') {
    $message = 'Please enter your email address.';
    $messageType = 'error';
  } else {
    $sql = 'SELECT id, email, name FROM users WHERE email = ? LIMIT 1';
    $stmt = $conn->prepare($sql);

    if ($stmt) {
      $stmt->bind_param('s', $email);
      if (!$stmt->execute()) {
        error_log('Failed to execute forgot-password user lookup: ' . $stmt->error);
        $message = 'An error occurred. Please try again.';
        $messageType = 'error';
        $stmt->close();
      } else {
      $result = $stmt->get_result();

      if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate password reset token
        $resetToken = bin2hex(random_bytes(32));

        // Update user with reset token and DB-driven expiry to avoid timezone mismatch.
        $updateSql = 'UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?';
        $updateStmt = $conn->prepare($updateSql);

        if ($updateStmt) {
          $userId = (int) $user['id'];
          $updateStmt->bind_param('si', $resetToken, $userId);
          if ($updateStmt->execute()) {
            // Send password reset email
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $scheme = $isHttps ? 'https' : 'http';
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
            if ($basePath === '/' || $basePath === '.') {
              $basePath = '';
            }

            $resetLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/reset-password.php?token=' . urlencode($resetToken);
            $emailSent = sendPasswordResetEmail($user['email'], $user['name'], $resetLink);

            if ($emailSent) {
              $message = 'Password reset link has been sent to your email address.';
              $messageType = 'success';
            } else {
              $message = 'Email could not be sent. Please try again later.';
              $messageType = 'error';
            }
          } else {
            error_log('Failed to store reset token for user ID ' . $userId . ': ' . $updateStmt->error);
            $message = 'An error occurred. Please try again.';
            $messageType = 'error';
          }

          $updateStmt->close();
        } else {
          error_log('Failed to prepare reset token update statement: ' . $conn->error);
          $message = 'An error occurred. Please try again.';
          $messageType = 'error';
        }
      } else {
        $message = 'If an account exists with this email, you will receive a password reset link.';
        $messageType = 'success';
      }

      $stmt->close();
      }
    } else {
      error_log('Failed to prepare forgot-password user lookup: ' . $conn->error);
      $message = 'An error occurred. Please try again.';
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
    <title>Forgot Password - School ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <link rel="stylesheet" href="assets/css/auth-pages.css">
  </head>
  <body class="auth-layout">
      <div class="auth-card auth-sm">
        <p class="auth-head-kicker"><i class="fas fa-key"></i> Reset Password</p>
        <div class="logo-section">
          <div class="logo-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h1>School ERP System</h1>
          <p class="subtitle">Enter your email to receive a password reset link</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" role="alert">
          <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <form id="forgotPasswordForm" method="POST" action="" novalidate>
          <div class="input-group-custom">
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
            <i class="fas fa-envelope input-icon"></i>
          </div>

          <button type="submit" class="btn-login">
            <i class="fas fa-paper-plane"></i> Send Reset Link
          </button>
        </form>

        <div class="divider">
          <span>OR</span>
        </div>

        <div class="signup-link">
          Remember your password? <a href="login.php">Sign In</a>
        </div>
      </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
      $(document).ready(function() {
        $('#forgotPasswordForm').validate({
          rules: {
            email: {
              required: true,
              email: true
            }
          },
          messages: {
            email: {
              required: "Please enter your email address",
              email: "Please enter a valid email address"
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
      });
    </script>
  </body>
</html>

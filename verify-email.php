<?php
session_start();
include 'includes/db_connect.php';

$message = '';
$messageType = '';
$tokenValid = false;
$token = trim($_GET['token'] ?? '');
$userId = null;

// Verify token exists and is not expired
if ($token) {
  $sql = 'SELECT id, email, name FROM users WHERE email_verification_token = ? AND email_verification_expiry > NOW() LIMIT 1';
  $stmt = $conn->prepare($sql);

  if ($stmt) {
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
      $tokenValid = true;
      $user = $result->fetch_assoc();
      $userId = (int) $user['id'];
      $userEmail = $user['email'];
    } else {
      $message = 'Invalid or expired email verification link. Please request a new one.';
      $messageType = 'error';
    }

    $stmt->close();
  }
} else {
  $message = 'No verification token provided.';
  $messageType = 'error';
  // Redirect to forgot-password since this page is only reachable via email link.
  header('Location: forgot-password.php');
  exit();
}

// Handle email verification confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
  // Mark email as verified
  $updateSql = 'UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expiry = NULL WHERE id = ?';
  $updateStmt = $conn->prepare($updateSql);

  if ($updateStmt) {
    $updateStmt->bind_param('i', $userId);
    $updateStmt->execute();
    $updateStmt->close();

    // Generate password reset token
    $resetToken = bin2hex(random_bytes(32));

    // Use DB-side time to avoid timezone mismatch during reset-token validation.
    $resetSql = 'UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?';
    $resetStmt = $conn->prepare($resetSql);

    if ($resetStmt) {
      $resetStmt->bind_param('si', $resetToken, $userId);
      $resetStmt->execute();
      $resetStmt->close();

      // Redirect using relative path so deployments in any subfolder/protocol work.
      header('Location: reset-password.php?token=' . urlencode($resetToken));
      exit();
    } else {
      $message = 'An error occurred during verification. Please try again.';
      $messageType = 'error';
    }
  } else {
    $message = 'An error occurred during verification. Please try again.';
    $messageType = 'error';
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - School ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth-pages.css">
  </head>
  <body class="auth-layout">
      <div class="auth-card auth-sm">
        <p class="auth-head-kicker"><i class="fas fa-envelope"></i> Verify Email</p>
        <div class="logo-section">
          <div class="logo-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h1>School ERP System</h1>
          <p class="subtitle">Complete the email verification to reset your password</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" role="alert">
          <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($tokenValid): ?>
        <div class="verification-info">
          <p>Email: <strong><?php echo htmlspecialchars($userEmail); ?></strong></p>
          <p class="info-text">Click the button below to verify your email and proceed to set a new password.</p>
        </div>

        <form method="POST" action="">
          <button type="submit" class="btn-login">
            <i class="fas fa-check-circle"></i> Verify Email & Continue
          </button>
        </form>
        <?php else: ?>
        <div class="alert alert-info" role="alert">
          <i class="fas fa-info-circle"></i> Please request a new password reset to verify your email.
        </div>

        <a href="forgot-password.php" class="btn btn-primary w-100">
          <i class="fas fa-arrow-left"></i> Back to Forgot Password
        </a>
        <?php endif; ?>

        <div class="divider">
          <span>OR</span>
        </div>

        <div class="signup-link">
          Return to <a href="login.php">Sign In</a>
        </div>
      </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <style>
      .verification-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
      }

      .verification-info p {
        margin-bottom: 8px;
        color: #495057;
      }

      .verification-info p:last-child {
        margin-bottom: 0;
      }

      .info-text {
        font-size: 0.9rem !important;
        line-height: 1.5;
      }
    </style>
  </body>
</html>

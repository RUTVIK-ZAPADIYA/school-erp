<?php
session_start();

include 'includes/db_connect.php';

function redirect_if_logged_in()
{
  if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    return;
  }

  if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
  }
  if ($_SESSION['role'] === 'teacher') {
    header('Location: teacher/dashboard.php');
    exit();
  }
  if ($_SESSION['role'] === 'student') {
    header('Location: student/dashboard.php');
    exit();
  }
}

redirect_if_logged_in();

$success = null;
$error = null;

$form = [
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'phone' => '',
  'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['first_name'] = trim($_POST['first_name'] ?? '');
  $form['last_name'] = trim($_POST['last_name'] ?? '');
  $form['email'] = trim($_POST['email'] ?? '');
  $form['phone'] = trim($_POST['phone'] ?? '');
  $form['username'] = trim($_POST['username'] ?? '');
  $password = (string) ($_POST['password'] ?? '');
  $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

  if ($form['first_name'] === '' || $form['last_name'] === '' || $form['email'] === '' || $form['username'] === '') {
    $error = 'Please fill all required fields.';
  } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } elseif (strlen($form['username']) < 4) {
    $error = 'Username must be at least 4 characters long.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters long.';
  } elseif ($password !== $confirmPassword) {
    $error = 'Password and confirm password do not match.';
  } else {
    $checkSql = 'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1';
    $checkStmt = mysqli_prepare($conn, $checkSql);

    if (!$checkStmt) {
      $error = 'Registration is temporarily unavailable. Please try again.';
    } else {
      mysqli_stmt_bind_param($checkStmt, 'ss', $form['username'], $form['email']);
      mysqli_stmt_execute($checkStmt);
      $checkResult = mysqli_stmt_get_result($checkStmt);

      if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $error = 'Username or email already exists.';
      } else {
        $name = trim($form['first_name'] . ' ' . $form['last_name']);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';

        $insertSql = 'INSERT INTO users (username, password, role, name, email, phone) VALUES (?, ?, ?, ?, ?, ?)';
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if (!$insertStmt) {
          $error = 'Failed to create account. Please try again.';
        } else {
          mysqli_stmt_bind_param($insertStmt, 'ssssss', $form['username'], $hash, $role, $name, $form['email'], $form['phone']);

          if (mysqli_stmt_execute($insertStmt)) {
            $success = 'Account created successfully. You can now log in.';
            $form = [
              'first_name' => '',
              'last_name' => '',
              'email' => '',
              'phone' => '',
              'username' => '',
            ];
          } else {
            $error = 'Failed to create account. Please try again.';
          }

          mysqli_stmt_close($insertStmt);
        }
      }

      mysqli_stmt_close($checkStmt);
    }
  }
}
?>
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

    <?php if ($success): ?>
    <div class="alert alert-success" role="alert">
      <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="registerForm" novalidate>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">First Name</label>
          <input type="text" class="form-control" id="first_name" name="first_name" data-validation="required,min,alphabetic" data-min="2" value="<?php echo htmlspecialchars($form['first_name']); ?>">
          <div id="first_name_error" class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" class="form-control" id="last_name" name="last_name" data-validation="required,min,alphabetic" data-min="2" value="<?php echo htmlspecialchars($form['last_name']); ?>">
          <div id="last_name_error" class="invalid-feedback"></div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" data-validation="required,email" value="<?php echo htmlspecialchars($form['email']); ?>">
        <div id="email_error" class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone Number</label>
        <input type="tel" class="form-control" id="phone" name="phone" data-validation="required,number" data-min="10" data-max="15" value="<?php echo htmlspecialchars($form['phone']); ?>">
        <div id="phone_error" class="invalid-feedback"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" data-validation="required,min" data-min="4" value="<?php echo htmlspecialchars($form['username']); ?>">
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

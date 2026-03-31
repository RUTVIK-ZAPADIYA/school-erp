<?php
session_start();

include 'includes/db_connect.php';

function register_table_exists($conn, $tableName)
{
    $safeTable = $conn->real_escape_string( $tableName);
    $result = $conn->query( "SHOW TABLES LIKE '{$safeTable}'");

    return $result && $result->num_rows > 0;
}

function register_column_exists($conn, $tableName, $columnName)
{
    if (!register_table_exists($conn, $tableName)) {
        return false;
    }

    $safeTable = $conn->real_escape_string( $tableName);
    $safeColumn = $conn->real_escape_string( $columnName);
    $result = $conn->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

    return $result && $result->num_rows > 0;
}

function register_bind_dynamic($stmt, $types, array &$params)
{
    if ($types === '') {
        return true;
    }

  $bindArgs = [$types];
    foreach ($params as $index => &$value) {
        $bindArgs[] = &$value;
    }

  return call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

$errors = [];
$success = '';
$form = [
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim((string) ($_POST['name'] ?? ''));
    $form['username'] = trim((string) ($_POST['username'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($form['name'] === '' || $form['username'] === '' || $form['email'] === '' || $password === '') {
        $errors[] = 'All required fields must be filled.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($form['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $checkStmt = $conn->prepare( 'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        if (!$checkStmt) {
            $errors[] = 'Registration is temporarily unavailable.';
        } else {
            $checkStmt->bind_param( 'ss', $form['username'], $form['email']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult && $checkResult->num_rows > 0) {
                $errors[] = 'Username or email already exists.';
            }
            $checkStmt->close();
        }
    }

    if (empty($errors)) {
        $role = 'student';
        $status = 'Active';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $conn->prepare(
            'INSERT INTO users (username, password, role, name, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$insertStmt) {
            $errors[] = 'Unable to create account right now.';
        } else {
            $insertStmt->bind_param(
                'sssssss',
                $form['username'],
                $passwordHash,
                $role,
                $form['name'],
                $form['email'],
                $form['phone'],
                $status
            );

            if ($insertStmt->execute()) {
                $newUserId = (int) $conn->insert_id;

                if (
                    register_table_exists($conn, 'students')
                    && register_column_exists($conn, 'students', 'roll_no')
                    && register_column_exists($conn, 'students', 'name')
                ) {
                    $rollNo = 'STU' . str_pad((string) $newUserId, 4, '0', STR_PAD_LEFT);

                    if (register_column_exists($conn, 'students', 'roll_no')) {
                        $rollCheckStmt = $conn->prepare( 'SELECT id FROM students WHERE roll_no = ? LIMIT 1');
                        if ($rollCheckStmt) {
                            $rollCheckStmt->bind_param( 's', $rollNo);
                            $rollCheckStmt->execute();
                            $rollCheckResult = $rollCheckStmt->get_result();
                            if ($rollCheckResult && $rollCheckResult->num_rows > 0) {
                                $rollNo = 'STU' . $newUserId . rand(10, 99);
                            }
                            $rollCheckStmt->close();
                        }
                    }

                    $studentColumns = ['roll_no', 'name'];
                    $studentValues = [$rollNo, $form['name']];
                    $studentTypes = 'ss';

                    if (register_column_exists($conn, 'students', 'email')) {
                        $studentColumns[] = 'email';
                        $studentValues[] = $form['email'];
                        $studentTypes .= 's';
                    }

                    if (register_column_exists($conn, 'students', 'phone')) {
                        $studentColumns[] = 'phone';
                        $studentValues[] = $form['phone'];
                        $studentTypes .= 's';
                    }

                    if (register_column_exists($conn, 'students', 'status')) {
                        $studentColumns[] = 'status';
                        $studentValues[] = 'Active';
                        $studentTypes .= 's';
                    }

                    $studentSql = 'INSERT INTO students (' . implode(', ', $studentColumns) . ') VALUES (' . implode(', ', array_fill(0, count($studentColumns), '?')) . ')';
                    $studentStmt = $conn->prepare( $studentSql);
                    if ($studentStmt) {
                        register_bind_dynamic($studentStmt, $studentTypes, $studentValues);
                        $studentStmt->execute();
                        $studentStmt->close();
                    }
                }

                $success = 'Account created successfully. You can login now.';
                $form = ['name' => '', 'username' => '', 'email' => '', 'phone' => ''];
            } else {
                $errors[] = 'Unable to register right now. Please try again.';
            }

            $insertStmt->close();
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
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/auth-pages.css">
  <style>
    .register-wrap {
      max-width: 760px;
      margin: 110px auto 40px;
    }
    .register-card {
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
      padding: 28px;
      background: #ffffff;
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <div class="container register-wrap">
    <div class="register-card">
      <h2 class="mb-2">Create Student Account</h2>
      <p class="text-muted mb-4">Use this form to register and access the Student Portal.</p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php" class="alert-link">Login here</a>.</div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($form['name']); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Username *</label>
            <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($form['username']); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($form['email']); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($form['phone']); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Password *</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password *</label>
            <input type="password" class="form-control" name="confirm_password" required>
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i>Create Account</button>
          <a href="login.php" class="btn btn-outline-secondary">Already have an account?</a>
        </div>
      </form>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

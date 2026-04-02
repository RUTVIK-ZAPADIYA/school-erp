<?php
// Admin page for registering teacher records.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'teachers', 'first_name', "VARCHAR(100) NULL");
admin_ensure_column($connection, 'teachers', 'last_name', "VARCHAR(100) NULL");
admin_ensure_column($connection, 'teachers', 'qualification', "VARCHAR(150) NULL");
admin_ensure_column($connection, 'teachers', 'experience', 'INT NULL');
admin_ensure_column($connection, 'teachers', 'joining_date', 'DATE NULL');
admin_ensure_column($connection, 'teachers', 'address', 'TEXT NULL');
admin_ensure_column($connection, 'teachers', 'salary', 'DECIMAL(10,2) NULL');
admin_ensure_column($connection, 'teachers', 'username', "VARCHAR(100) NULL");

// Load subject options for assignment in the teacher profile.
$subjects = [];
$subjectColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
if ($subjectColumn !== null) {
  $subjectStmt = $connection->prepare(
    "SELECT DISTINCT {$subjectColumn} AS subject_name FROM subjects WHERE {$subjectColumn} IS NOT NULL AND {$subjectColumn} != '' ORDER BY {$subjectColumn} ASC"
  );
  if ($subjectStmt) {
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    if ($subjectResult) {
      while ($subjectRow = $subjectResult->fetch_assoc()) {
        $subjects[] = $subjectRow['subject_name'];
      }
    }
    $subjectStmt->close();
  }
}

if (empty($subjects)) {
  $subjects = ['Mathematics', 'Physics', 'Chemistry', 'English', 'History'];
}

$formData = [
  'first_name' => '',
  'last_name' => '',
  'username' => '',
  'email' => '',
  'phone' => '',
  'subject' => '',
  'qualification' => '',
  'experience' => '',
  'joining_date' => '',
  'address' => '',
  'salary' => '',
  'status' => 'Active',
];

$errorMessage = '';
$passwordValue = '';
$confirmPasswordValue = '';

// Handle teacher form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $passwordValue = (string) ($_POST['password'] ?? '');
  $confirmPasswordValue = (string) ($_POST['confirm_password'] ?? '');

  $formData['status'] = admin_normalize_status($formData['status'], 'Active');

  // Validate required fields and credential constraints.
  if (
    $formData['first_name'] === '' ||
    $formData['last_name'] === '' ||
    $formData['username'] === '' ||
    $formData['email'] === '' ||
    $formData['phone'] === '' ||
    $formData['subject'] === '' ||
    $formData['qualification'] === '' ||
    $formData['experience'] === '' ||
    $formData['joining_date'] === '' ||
    $formData['address'] === '' ||
    $formData['salary'] === '' ||
    $passwordValue === '' ||
    $confirmPasswordValue === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['experience']) || (int) $formData['experience'] < 0) {
    $errorMessage = 'Experience must be a valid non-negative number.';
  } elseif (!is_numeric($formData['salary']) || (float) $formData['salary'] <= 0) {
    $errorMessage = 'Salary must be a valid amount greater than zero.';
  } elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $formData['username'])) {
    $errorMessage = 'Username must be 3-30 characters and contain only letters, numbers, dot, underscore, or hyphen.';
  } elseif (strlen($passwordValue) < 6) {
    $errorMessage = 'Password must be at least 6 characters long.';
  } elseif ($passwordValue !== $confirmPasswordValue) {
    $errorMessage = 'Password and confirm password must match.';
  }

  // Verify teacher-specific uniqueness checks first.
  if ($errorMessage === '') {
    $teachersHasEmail = admin_column_exists($connection, 'teachers', 'email');
    if ($teachersHasEmail) {
      $emailCheckStmt = $connection->prepare( 'SELECT id FROM teachers WHERE email = ? LIMIT 1');
      if ($emailCheckStmt) {
        $emailCheckStmt->bind_param( 's', $formData['email']);
        $emailCheckStmt->execute();
        $emailCheckResult = $emailCheckStmt->get_result();
        if ($emailCheckResult && $emailCheckResult->num_rows > 0) {
          $errorMessage = 'A teacher with this email already exists.';
        }
        $emailCheckStmt->close();
      }
    }
  }

  if ($errorMessage === '') {
    if (admin_column_exists($connection, 'teachers', 'username')) {
      $usernameCheckStmt = $connection->prepare( 'SELECT id FROM teachers WHERE username = ? LIMIT 1');
      if ($usernameCheckStmt) {
        $usernameCheckStmt->bind_param( 's', $formData['username']);
        $usernameCheckStmt->execute();
        $usernameCheckResult = $usernameCheckStmt->get_result();
        if ($usernameCheckResult && $usernameCheckResult->num_rows > 0) {
          $errorMessage = 'A teacher with this username already exists.';
        }
        $usernameCheckStmt->close();
      }
    }
  }

  // Verify conflicts against shared user accounts.
  if ($errorMessage === '') {
    $userCheckStmt = $connection->prepare( 'SELECT id FROM users WHERE username = ? OR email = ? OR username = ? OR email = ? LIMIT 1');
    if (!$userCheckStmt) {
      $errorMessage = 'Unable to validate teacher login account right now.';
    } else {
      $userCheckStmt->bind_param( 'ssss', $formData['username'], $formData['username'], $formData['email'], $formData['email']);
      $userCheckStmt->execute();
      $userCheckResult = $userCheckStmt->get_result();
      if ($userCheckResult && $userCheckResult->num_rows > 0) {
        $errorMessage = 'A user account with this username or email already exists.';
      }
      $userCheckStmt->close();
    }
  }

  // Create user and teacher rows in one transaction for consistency.
  if ($errorMessage === '') {
    $teacherName = trim($formData['first_name'] . ' ' . $formData['last_name']);
    $loginIdentifier = $formData['username'];
    $passwordHash = password_hash($passwordValue, PASSWORD_DEFAULT);
    $teacherRole = 'teacher';
    $teacherUserId = 0;
    $transactionStarted = false;

    if ($connection->begin_transaction()) {
      $transactionStarted = true;
    }

    $userInsertStmt = $connection->prepare(
      'INSERT INTO users (username, password, role, name, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$userInsertStmt) {
      $errorMessage = 'Unable to create teacher login account right now.';
    } else {
      $userInsertStmt->bind_param(
        'sssssss',
        $loginIdentifier,
        $passwordHash,
        $teacherRole,
        $teacherName,
        $formData['email'],
        $formData['phone'],
        $formData['status']
      );
      if (!$userInsertStmt->execute()) {
        $errorMessage = 'Failed to create teacher login account. This email may already be in use.';
      } else {
        $teacherUserId = (int) $connection->insert_id;
      }
      $userInsertStmt->close();
    }

    admin_ensure_column($connection, 'teachers', 'user_id', 'INT NULL');

    $insertColumns = ['name', 'user_id'];
    $insertValues = ['?'];
    $insertValues[] = '?';
    $insertTypes = 'si';
    $insertParams = [$teacherName];
    $insertParams[] = $teacherUserId;

    if (admin_column_exists($connection, 'teachers', 'first_name')) {
      $insertColumns[] = 'first_name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['first_name'];
    }
    if (admin_column_exists($connection, 'teachers', 'last_name')) {
      $insertColumns[] = 'last_name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['last_name'];
    }
    if (admin_column_exists($connection, 'teachers', 'email')) {
      $insertColumns[] = 'email';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['email'];
    }
    if (admin_column_exists($connection, 'teachers', 'username')) {
      $insertColumns[] = 'username';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['username'];
    }
    if (admin_column_exists($connection, 'teachers', 'phone')) {
      $insertColumns[] = 'phone';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['phone'];
    }
    if (admin_column_exists($connection, 'teachers', 'subject')) {
      $insertColumns[] = 'subject';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['subject'];
    }
    if (admin_column_exists($connection, 'teachers', 'qualification')) {
      $insertColumns[] = 'qualification';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['qualification'];
    }
    if (admin_column_exists($connection, 'teachers', 'experience')) {
      $insertColumns[] = 'experience';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['experience'];
    }
    if (admin_column_exists($connection, 'teachers', 'joining_date')) {
      $insertColumns[] = 'joining_date';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['joining_date'];
    }
    if (admin_column_exists($connection, 'teachers', 'address')) {
      $insertColumns[] = 'address';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['address'];
    }
    if (admin_column_exists($connection, 'teachers', 'salary')) {
      $insertColumns[] = 'salary';
      $insertValues[] = '?';
      $insertTypes .= 'd';
      $insertParams[] = (float) $formData['salary'];
    }
    if (admin_column_exists($connection, 'teachers', 'status')) {
      $insertColumns[] = 'status';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['status'];
    }

    $insertSql = 'INSERT INTO teachers (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    if ($errorMessage === '') {
      $insertStmt = $connection->prepare( $insertSql);

      if (!$insertStmt) {
        $errorMessage = 'Unable to save teacher right now.';
      } else {
        if (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
          $errorMessage = 'Unable to bind insert parameters.';
        } elseif (!$insertStmt->execute()) {
          $errorMessage = 'Failed to add teacher. Please try again.';
        }
        $insertStmt->close();
      }
    }

    if ($errorMessage === '') {
      if ($transactionStarted && !$connection->commit()) {
        $errorMessage = 'Unable to finalize teacher creation. Please try again.';
      }
    }

    if ($errorMessage !== '') {
      if ($transactionStarted) {
        $connection->rollback();
      } elseif ($teacherUserId > 0) {
        $cleanupStmt = $connection->prepare( "DELETE FROM users WHERE id = ? AND role = 'teacher' LIMIT 1");
        if ($cleanupStmt) {
          $cleanupStmt->bind_param( 'i', $teacherUserId);
          $cleanupStmt->execute();
          $cleanupStmt->close();
        }
      }
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Teacher added successfully. Login username: ' . $formData['username']);
    header('Location: add-teacher.php');
    exit();
  }
}

$flash = admin_pull_flash();
?>
<!-- Render the add teacher form with server-side feedback. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Teacher</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fcfbfb; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom: 3px solid #f7d794; }
    .header h2 { color: #192a56; margin: 0; font-weight: 700; }
    .form-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 3px solid #f7d794; }
    .form-label { color: #192a56; font-weight: 600; margin-bottom: 8px; }
    .form-control, .form-select { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .form-control:focus, .form-select:focus { border-color: #f7d794; box-shadow: 0 0 0 3px rgba(247,215,148,0.25); }
    .btn-submit { background: #f7d794; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-submit:hover { background: #e5c682; }
    .btn-cancel { background: #e2e8f0; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; margin-left: 10px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chalkboard-teacher"></i> Add New Teacher</h2>
    </div>
    
    <div class="form-card">
      <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($flash['message']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">First Name *</label>
            <input type="text" class="form-control" name="first_name" data-validation="required,alphabetic,min" data-min="2" value="<?php echo htmlspecialchars($formData['first_name']); ?>">
            <div id="first_name_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Last Name *</label>
            <input type="text" class="form-control" name="last_name" data-validation="required,alphabetic,min" data-min="2" value="<?php echo htmlspecialchars($formData['last_name']); ?>">
            <div id="last_name_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Email *</label>
            <input type="text" class="form-control" name="email" data-validation="required,email" value="<?php echo htmlspecialchars($formData['email']); ?>">
            <div id="email_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Username *</label>
            <input type="text" class="form-control" name="username" data-validation="required,min,max" data-min="3" data-max="30" value="<?php echo htmlspecialchars($formData['username']); ?>">
            <div id="username_error" class="invalid-feedback"></div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number *</label>
            <input type="text" class="form-control" name="phone" data-validation="required,number,min" data-min="10" value="<?php echo htmlspecialchars($formData['phone']); ?>">
            <div id="phone_error" class="invalid-feedback"></div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Password *</label>
            <input type="password" class="form-control" name="password" data-validation="required,min" data-min="6" autocomplete="new-password">
            <div id="password_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Confirm Password *</label>
            <input type="password" class="form-control" name="confirm_password" data-validation="required,min" data-min="6" autocomplete="new-password">
            <div id="confirm_password_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Subject *</label>
            <select class="form-select" name="subject" data-validation="required,select">
              <option value="">Select Subject</option>
              <?php foreach ($subjects as $subjectName): ?>
                <option value="<?php echo htmlspecialchars($subjectName); ?>" <?php echo $formData['subject'] === $subjectName ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($subjectName); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="subject_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Qualification *</label>
            <input type="text" class="form-control" name="qualification" placeholder="e.g., M.Sc, B.Ed" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['qualification']); ?>">
            <div id="qualification_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Experience (Years) *</label>
            <input type="text" class="form-control" name="experience" data-validation="required,number,min" data-min="0" value="<?php echo htmlspecialchars($formData['experience']); ?>">
            <div id="experience_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Date of Joining *</label>
            <input type="date" class="form-control" name="joining_date" data-validation="required" value="<?php echo htmlspecialchars($formData['joining_date']); ?>">
            <div id="joining_date_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Address *</label>
          <textarea class="form-control" name="address" rows="3" data-validation="required,min" data-min="5"><?php echo htmlspecialchars($formData['address']); ?></textarea>
          <div id="address_error" class="invalid-feedback"></div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Salary *</label>
            <input type="text" class="form-control" name="salary" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['salary']); ?>">
            <div id="salary_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" data-validation="required,select">
              <option value="">Select Status</option>
              <option value="Active" <?php echo $formData['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
              <option value="Inactive" <?php echo $formData['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <div id="status_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Teacher</button>
          <a href="teachers.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

<?php
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
admin_ensure_column($connection, 'teachers', 'user_id', 'INT NULL');
admin_ensure_column($connection, 'teachers', 'username', "VARCHAR(100) NULL");

$subjects = [];
$subjectColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
if ($subjectColumn !== null) {
  $subjectResult = $connection->query(
    "SELECT DISTINCT {$subjectColumn} AS subject_name FROM subjects WHERE {$subjectColumn} IS NOT NULL AND {$subjectColumn} != '' ORDER BY {$subjectColumn} ASC"
  );
  if ($subjectResult) {
    while ($subjectRow = $subjectResult->fetch_assoc()) {
      $subjects[] = $subjectRow['subject_name'];
    }
  }
}

if (empty($subjects)) {
  $subjects = ['Mathematics', 'Physics', 'Chemistry', 'English', 'History'];
}

$teacherId = (int) ($_GET['id'] ?? $_POST['teacher_id'] ?? 0);
$errorMessage = '';

$teacherRow = null;
if ($teacherId > 0) {
  $teacherStmt = $connection->prepare('SELECT * FROM teachers WHERE id = ? LIMIT 1');
  if ($teacherStmt) {
    $teacherStmt->bind_param('i', $teacherId);
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();
    $teacherRow = $teacherResult ? $teacherResult->fetch_assoc() : null;
    $teacherStmt->close();
  }
}

if (!$teacherRow) {
  $errorMessage = 'Teacher not found.';
}

$fullName = trim((string) ($teacherRow['name'] ?? ''));
$nameParts = preg_split('/\s+/', $fullName, 2);
$derivedFirstName = $nameParts[0] ?? '';
$derivedLastName = $nameParts[1] ?? '';

$formData = [
  'first_name' => trim((string) ($teacherRow['first_name'] ?? $derivedFirstName)),
  'last_name' => trim((string) ($teacherRow['last_name'] ?? $derivedLastName)),
  'username' => trim((string) ($teacherRow['username'] ?? ($teacherRow['email'] ?? ''))),
  'email' => trim((string) ($teacherRow['email'] ?? '')),
  'phone' => trim((string) ($teacherRow['phone'] ?? '')),
  'subject' => trim((string) ($teacherRow['subject'] ?? '')),
  'qualification' => trim((string) ($teacherRow['qualification'] ?? '')),
  'experience' => (string) ($teacherRow['experience'] ?? ''),
  'joining_date' => trim((string) ($teacherRow['joining_date'] ?? '')),
  'address' => trim((string) ($teacherRow['address'] ?? '')),
  'salary' => (string) ($teacherRow['salary'] ?? ''),
  'status' => admin_normalize_status($teacherRow['status'] ?? 'Active', 'Active'),
];

$usersTableAvailable = admin_table_exists($connection, 'users');
$teachersHasUserId = admin_column_exists($connection, 'teachers', 'user_id');

$linkedUserId = 0;
if ($usersTableAvailable && $teacherRow) {
  if ($teachersHasUserId && isset($teacherRow['user_id']) && (int) $teacherRow['user_id'] > 0) {
    $linkedUserId = (int) $teacherRow['user_id'];
  }

  if ($linkedUserId <= 0) {
    $lookupEmail = trim((string) ($teacherRow['email'] ?? ''));
    $lookupUsername = trim((string) ($teacherRow['username'] ?? ''));
    if ($lookupUsername === '' && $lookupEmail !== '') {
      $lookupUsername = strtolower($lookupEmail);
    }

    if ($lookupEmail !== '' || $lookupUsername !== '') {
      $userLookupStmt = $connection->prepare("SELECT id FROM users WHERE role = 'teacher' AND (username = ? OR email = ? OR username = ? OR email = ?) LIMIT 1");
      if ($userLookupStmt) {
        $userLookupStmt->bind_param('ssss', $lookupUsername, $lookupUsername, $lookupEmail, $lookupEmail);
        $userLookupStmt->execute();
        $userLookupResult = $userLookupStmt->get_result();
        $userLookupRow = $userLookupResult ? $userLookupResult->fetch_assoc() : null;
        if ($userLookupRow) {
          $linkedUserId = (int) ($userLookupRow['id'] ?? 0);
        }
        $userLookupStmt->close();
      }
    }
  }
}

$passwordValue = '';
$confirmPasswordValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacherRow) {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $passwordValue = (string) ($_POST['password'] ?? '');
  $confirmPasswordValue = (string) ($_POST['confirm_password'] ?? '');

  $formData['status'] = admin_normalize_status($formData['status'], 'Active');

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
    $formData['salary'] === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['experience']) || (int) $formData['experience'] < 0) {
    $errorMessage = 'Experience must be a valid non-negative number.';
  } elseif (!is_numeric($formData['salary']) || (float) $formData['salary'] <= 0) {
    $errorMessage = 'Salary must be a valid amount greater than zero.';
  } elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $formData['username'])) {
    $errorMessage = 'Username must be 3-30 characters and contain only letters, numbers, dot, underscore, or hyphen.';
  }

  if ($errorMessage === '' && ($passwordValue !== '' || $confirmPasswordValue !== '')) {
    if (strlen($passwordValue) < 6) {
      $errorMessage = 'New password must be at least 6 characters long.';
    } elseif ($passwordValue !== $confirmPasswordValue) {
      $errorMessage = 'New password and confirm password must match.';
    }
  }

  if ($errorMessage === '' && admin_column_exists($connection, 'teachers', 'email')) {
    $emailCheckStmt = $connection->prepare('SELECT id FROM teachers WHERE email = ? AND id != ? LIMIT 1');
    if ($emailCheckStmt) {
      $emailCheckStmt->bind_param('si', $formData['email'], $teacherId);
      $emailCheckStmt->execute();
      $emailCheckResult = $emailCheckStmt->get_result();
      if ($emailCheckResult && $emailCheckResult->num_rows > 0) {
        $errorMessage = 'A teacher with this email already exists.';
      }
      $emailCheckStmt->close();
    }
  }

  if ($errorMessage === '' && admin_column_exists($connection, 'teachers', 'username')) {
    $usernameCheckStmt = $connection->prepare('SELECT id FROM teachers WHERE username = ? AND id != ? LIMIT 1');
    if ($usernameCheckStmt) {
      $usernameCheckStmt->bind_param('si', $formData['username'], $teacherId);
      $usernameCheckStmt->execute();
      $usernameCheckResult = $usernameCheckStmt->get_result();
      if ($usernameCheckResult && $usernameCheckResult->num_rows > 0) {
        $errorMessage = 'A teacher with this username already exists.';
      }
      $usernameCheckStmt->close();
    }
  }

  $loginIdentifier = $formData['username'];

  if ($errorMessage === '' && $usersTableAvailable && ($linkedUserId > 0 || $passwordValue !== '')) {
    if ($linkedUserId > 0) {
      $userDupStmt = $connection->prepare('SELECT id FROM users WHERE (username = ? OR email = ? OR username = ? OR email = ?) AND id != ? LIMIT 1');
      if ($userDupStmt) {
        $userDupStmt->bind_param('ssssi', $loginIdentifier, $loginIdentifier, $formData['email'], $formData['email'], $linkedUserId);
        $userDupStmt->execute();
        $userDupResult = $userDupStmt->get_result();
        if ($userDupResult && $userDupResult->num_rows > 0) {
          $errorMessage = 'Another user account already uses this username or email.';
        }
        $userDupStmt->close();
      }
    } else {
      $userDupStmt = $connection->prepare('SELECT id FROM users WHERE username = ? OR email = ? OR username = ? OR email = ? LIMIT 1');
      if ($userDupStmt) {
        $userDupStmt->bind_param('ssss', $loginIdentifier, $loginIdentifier, $formData['email'], $formData['email']);
        $userDupStmt->execute();
        $userDupResult = $userDupStmt->get_result();
        if ($userDupResult && $userDupResult->num_rows > 0) {
          $errorMessage = 'A user account already uses this username or email.';
        }
        $userDupStmt->close();
      }
    }
  }

  if ($errorMessage === '' && !$usersTableAvailable && $passwordValue !== '') {
    $errorMessage = 'Users table is unavailable. Cannot update login password right now.';
  }

  if ($errorMessage === '') {
    $teacherName = trim($formData['first_name'] . ' ' . $formData['last_name']);
    $updatedUserId = $linkedUserId;
    $transactionStarted = false;

    if ($connection->begin_transaction()) {
      $transactionStarted = true;
    }

    if ($usersTableAvailable && $linkedUserId > 0) {
      if ($passwordValue !== '') {
        $passwordHash = password_hash($passwordValue, PASSWORD_DEFAULT);
        $userUpdateStmt = $connection->prepare("UPDATE users SET username = ?, name = ?, email = ?, phone = ?, status = ?, role = 'teacher', password = ? WHERE id = ?");
        if (!$userUpdateStmt) {
          $errorMessage = 'Unable to update linked login account right now.';
        } else {
          $userUpdateStmt->bind_param(
            'ssssssi',
            $loginIdentifier,
            $teacherName,
            $formData['email'],
            $formData['phone'],
            $formData['status'],
            $passwordHash,
            $linkedUserId
          );
          if (!$userUpdateStmt->execute()) {
            $errorMessage = 'Failed to update linked login account.';
          }
          $userUpdateStmt->close();
        }
      } else {
        $userUpdateStmt = $connection->prepare("UPDATE users SET username = ?, name = ?, email = ?, phone = ?, status = ?, role = 'teacher' WHERE id = ?");
        if (!$userUpdateStmt) {
          $errorMessage = 'Unable to update linked login account right now.';
        } else {
          $userUpdateStmt->bind_param(
            'sssssi',
            $loginIdentifier,
            $teacherName,
            $formData['email'],
            $formData['phone'],
            $formData['status'],
            $linkedUserId
          );
          if (!$userUpdateStmt->execute()) {
            $errorMessage = 'Failed to update linked login account.';
          }
          $userUpdateStmt->close();
        }
      }
    } elseif ($usersTableAvailable && $passwordValue !== '') {
      $passwordHash = password_hash($passwordValue, PASSWORD_DEFAULT);
      $teacherRole = 'teacher';
      $userInsertStmt = $connection->prepare(
        'INSERT INTO users (username, password, role, name, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
      );
      if (!$userInsertStmt) {
        $errorMessage = 'Unable to create linked login account right now.';
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
          $errorMessage = 'Failed to create linked login account.';
        } else {
          $updatedUserId = (int) $connection->insert_id;
        }
        $userInsertStmt->close();
      }
    }

    if ($errorMessage === '') {
      $updateFields = [];
      $updateTypes = '';
      $updateParams = [];

      if (admin_column_exists($connection, 'teachers', 'name')) {
        $updateFields[] = 'name = ?';
        $updateTypes .= 's';
        $updateParams[] = $teacherName;
      }
      if (admin_column_exists($connection, 'teachers', 'first_name')) {
        $updateFields[] = 'first_name = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['first_name'];
      }
      if (admin_column_exists($connection, 'teachers', 'last_name')) {
        $updateFields[] = 'last_name = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['last_name'];
      }
      if (admin_column_exists($connection, 'teachers', 'email')) {
        $updateFields[] = 'email = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['email'];
      }
      if (admin_column_exists($connection, 'teachers', 'username')) {
        $updateFields[] = 'username = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['username'];
      }
      if (admin_column_exists($connection, 'teachers', 'phone')) {
        $updateFields[] = 'phone = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['phone'];
      }
      if (admin_column_exists($connection, 'teachers', 'subject')) {
        $updateFields[] = 'subject = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['subject'];
      }
      if (admin_column_exists($connection, 'teachers', 'qualification')) {
        $updateFields[] = 'qualification = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['qualification'];
      }
      if (admin_column_exists($connection, 'teachers', 'experience')) {
        $updateFields[] = 'experience = ?';
        $updateTypes .= 'i';
        $updateParams[] = (int) $formData['experience'];
      }
      if (admin_column_exists($connection, 'teachers', 'joining_date')) {
        $updateFields[] = 'joining_date = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['joining_date'];
      }
      if (admin_column_exists($connection, 'teachers', 'address')) {
        $updateFields[] = 'address = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['address'];
      }
      if (admin_column_exists($connection, 'teachers', 'salary')) {
        $updateFields[] = 'salary = ?';
        $updateTypes .= 'd';
        $updateParams[] = (float) $formData['salary'];
      }
      if (admin_column_exists($connection, 'teachers', 'status')) {
        $updateFields[] = 'status = ?';
        $updateTypes .= 's';
        $updateParams[] = $formData['status'];
      }
      if ($teachersHasUserId && $updatedUserId > 0) {
        $updateFields[] = 'user_id = ?';
        $updateTypes .= 'i';
        $updateParams[] = $updatedUserId;
      }

      if (empty($updateFields)) {
        $errorMessage = 'No editable teacher fields were found.';
      } else {
        $updateSql = 'UPDATE teachers SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $updateTypes .= 'i';
        $updateParams[] = $teacherId;

        $updateStmt = $connection->prepare($updateSql);
        if (!$updateStmt) {
          $errorMessage = 'Unable to update teacher right now.';
        } elseif (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
          $errorMessage = 'Unable to bind teacher parameters.';
        } elseif (!$updateStmt->execute()) {
          $errorMessage = 'Failed to update teacher. Please try again.';
        }

        if ($updateStmt) {
          $updateStmt->close();
        }
      }
    }

    if ($errorMessage === '') {
      if ($transactionStarted && !$connection->commit()) {
        $errorMessage = 'Unable to finalize teacher update. Please try again.';
      }
    }

    if ($errorMessage !== '' && $transactionStarted) {
      $connection->rollback();
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Teacher updated successfully.');
    header('Location: teachers.php');
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Teacher</title>
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
      <h2><i class="fas fa-edit"></i> Edit Teacher</h2>
    </div>

    <div class="form-card">
      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($teacherRow): ?>
        <form method="POST" action="">
          <input type="hidden" name="teacher_id" value="<?php echo (int) $teacherId; ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">First Name *</label>
              <input type="text" class="form-control" name="first_name" data-validation="required,alphabetic,min" data-min="2" value="<?php echo htmlspecialchars($formData['first_name']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Last Name *</label>
              <input type="text" class="form-control" name="last_name" data-validation="required,alphabetic,min" data-min="2" value="<?php echo htmlspecialchars($formData['last_name']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email *</label>
              <input type="text" class="form-control" name="email" data-validation="required,email" value="<?php echo htmlspecialchars($formData['email']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Username *</label>
              <input type="text" class="form-control" name="username" data-validation="required,min,max" data-min="3" data-max="30" value="<?php echo htmlspecialchars($formData['username']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number *</label>
              <input type="text" class="form-control" name="phone" data-validation="required,number,min" data-min="10" value="<?php echo htmlspecialchars($formData['phone']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">New Password (Optional)</label>
              <input type="password" class="form-control" name="password" data-validation="min" data-min="6" autocomplete="new-password">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" name="confirm_password" data-validation="min" data-min="6" autocomplete="new-password">
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
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Qualification *</label>
              <input type="text" class="form-control" name="qualification" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['qualification']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Experience (Years) *</label>
              <input type="text" class="form-control" name="experience" data-validation="required,number,min" data-min="0" value="<?php echo htmlspecialchars($formData['experience']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date of Joining *</label>
              <input type="date" class="form-control" name="joining_date" data-validation="required" value="<?php echo htmlspecialchars($formData['joining_date']); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Address *</label>
            <textarea class="form-control" name="address" rows="3" data-validation="required,min" data-min="5"><?php echo htmlspecialchars($formData['address']); ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Salary *</label>
              <input type="text" class="form-control" name="salary" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['salary']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status *</label>
              <select class="form-select" name="status" data-validation="required,select">
                <option value="Active" <?php echo $formData['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $formData['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Teacher</button>
            <a href="teachers.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Teacher not found. <a href="teachers.php">Back to teachers</a></div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

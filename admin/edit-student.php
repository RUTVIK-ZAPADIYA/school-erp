<?php
// Admin page for editing student profiles and enrollment data.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

$message = '';
$message_type = '';
$student = null;

$studentsHasClass = admin_column_exists($connection, 'students', 'class');
$studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
$studentsHasEmail = admin_column_exists($connection, 'students', 'email');
$studentsHasPhone = admin_column_exists($connection, 'students', 'phone');
$studentsHasStatus = admin_column_exists($connection, 'students', 'status');

admin_ensure_column($connection, 'students', 'user_id', 'INT NULL');
admin_ensure_column($connection, 'students', 'username', 'VARCHAR(100) NULL');

$studentsHasUserId = admin_column_exists($connection, 'students', 'user_id');
$studentsHasUsername = admin_column_exists($connection, 'students', 'username');
$usersTableAvailable = admin_table_exists($connection, 'users');
$usersHasEmail = admin_column_exists($connection, 'users', 'email');
$usersHasPhone = admin_column_exists($connection, 'users', 'phone');
$usersHasStatus = admin_column_exists($connection, 'users', 'status');

// Track linked login account details for synchronized updates.
$studentLogin = [
  'user_id' => 0,
  'username' => '',
  'has_account' => false,
];

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classOptions = [];

// Load class choices for the student edit datalist.
if ($classNameColumn !== null) {
  $classStmt = $connection->prepare("SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classStmt) {
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    if ($classResult) {
      while ($classRow = $classResult->fetch_assoc()) {
        $classOptions[] = $classRow;
      }
    }
    $classStmt->close();
  }
}

function fetch_student_by_id($connection, $studentId)
{
  $stmt = $connection->prepare( 'SELECT * FROM students WHERE id = ? LIMIT 1');
  if (!$stmt) {
    return null;
  }

  $stmt->bind_param( 'i', $studentId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();

  return $row ?: null;
}

function resolve_student_login_account($connection, array $student)
{
  $account = [
    'user_id' => 0,
    'username' => trim((string) ($student['username'] ?? '')),
    'has_account' => false,
  ];

  if (!admin_table_exists($connection, 'users')) {
    return $account;
  }

  $studentUserId = (int) ($student['user_id'] ?? 0);
  if ($studentUserId > 0) {
    $userByIdStmt = $connection->prepare("SELECT id, username FROM users WHERE id = ? AND role = 'student' LIMIT 1");
    if ($userByIdStmt) {
      $userByIdStmt->bind_param('i', $studentUserId);
      $userByIdStmt->execute();
      $userByIdResult = $userByIdStmt->get_result();
      $userByIdRow = $userByIdResult ? $userByIdResult->fetch_assoc() : null;
      if ($userByIdRow) {
        $account['user_id'] = (int) ($userByIdRow['id'] ?? 0);
        $account['username'] = trim((string) ($userByIdRow['username'] ?? $account['username']));
        $account['has_account'] = true;
      }
      $userByIdStmt->close();
    }
  }

  if (!$account['has_account']) {
    $lookupUsername = trim((string) ($student['username'] ?? ''));
    $lookupEmail = trim((string) ($student['email'] ?? ''));

    if ($lookupUsername !== '' || $lookupEmail !== '') {
      $lookupStmt = $connection->prepare("SELECT id, username FROM users WHERE role = 'student' AND (username = ? OR email = ? OR username = ? OR email = ?) LIMIT 1");
      if ($lookupStmt) {
        $lookupStmt->bind_param('ssss', $lookupUsername, $lookupUsername, $lookupEmail, $lookupEmail);
        $lookupStmt->execute();
        $lookupResult = $lookupStmt->get_result();
        $lookupRow = $lookupResult ? $lookupResult->fetch_assoc() : null;
        if ($lookupRow) {
          $account['user_id'] = (int) ($lookupRow['id'] ?? 0);
          $account['username'] = trim((string) ($lookupRow['username'] ?? $account['username']));
          $account['has_account'] = true;
        }
        $lookupStmt->close();
      }
    }
  }

  return $account;
}

// Load the student record for initial page render.
if (isset($_GET['id'])) {
  $studentId = (int) $_GET['id'];
  if ($studentId > 0) {
    $student = fetch_student_by_id($connection, $studentId);
    if ($student) {
      $studentLogin = resolve_student_login_account($connection, $student);
    }
  }

  if (!$student) {
    $message = 'Student not found!';
    $message_type = 'danger';
  }
}

// Handle submitted student profile and login updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $studentId = (int) ($_POST['student_id'] ?? 0);
  $student = fetch_student_by_id($connection, $studentId);
  if ($student) {
    $studentLogin = resolve_student_login_account($connection, $student);
  }

  $rollNo = trim((string) ($_POST['roll_no'] ?? ''));
  $name = trim((string) ($_POST['name'] ?? ''));
  $class = trim((string) ($_POST['class'] ?? ''));
  $username = trim((string) ($_POST['username'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $status = admin_normalize_status($_POST['status'] ?? 'Active', 'Active');

  // Validate required fields, username rules, and optional password reset input.
  if (!$student) {
    $message = 'Student not found!';
    $message_type = 'danger';
  } elseif ($rollNo === '' || $name === '' || ($studentsHasClass && $class === '') || $username === '') {
    $message = 'Please fill in all required fields!';
    $message_type = 'danger';
  } elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
    $message = 'Username must be 3-30 characters and contain only letters, numbers, dot, underscore, or hyphen.';
    $message_type = 'danger';
  } elseif (($password !== '' || $confirmPassword !== '') && strlen($password) < 6) {
    $message = 'New password must be at least 6 characters long.';
    $message_type = 'danger';
  } elseif (($password !== '' || $confirmPassword !== '') && $password !== $confirmPassword) {
    $message = 'New password and confirm password must match.';
    $message_type = 'danger';
  } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Please provide a valid email address.';
    $message_type = 'danger';
  } else {
    $duplicateStmt = $connection->prepare( 'SELECT id FROM students WHERE roll_no = ? AND id != ? LIMIT 1');
    if ($duplicateStmt) {
      $duplicateStmt->bind_param( 'si', $rollNo, $studentId);
      $duplicateStmt->execute();
      $duplicateResult = $duplicateStmt->get_result();
      $duplicateExists = $duplicateResult && $duplicateResult->num_rows > 0;
      $duplicateStmt->close();
    } else {
      $duplicateExists = false;
    }

    if ($duplicateExists) {
      $message = 'Roll number already exists!';
      $message_type = 'danger';
    }

    if ($message === '' && $studentsHasUsername) {
      $usernameDuplicateStmt = $connection->prepare( 'SELECT id FROM students WHERE username = ? AND id != ? LIMIT 1');
      if ($usernameDuplicateStmt) {
        $usernameDuplicateStmt->bind_param( 'si', $username, $studentId);
        $usernameDuplicateStmt->execute();
        $usernameDuplicateResult = $usernameDuplicateStmt->get_result();
        if ($usernameDuplicateResult && $usernameDuplicateResult->num_rows > 0) {
          $message = 'A student with this username already exists.';
          $message_type = 'danger';
        }
        $usernameDuplicateStmt->close();
      }
    }

    $linkedUserId = (int) ($studentLogin['user_id'] ?? 0);

    // Validate uniqueness against shared user accounts when available.
    if ($message === '' && $usersTableAvailable) {
      if ($linkedUserId > 0) {
        $userDupStmt = $connection->prepare( 'SELECT id FROM users WHERE (username = ? OR email = ? OR username = ? OR email = ?) AND id != ? LIMIT 1');
        if ($userDupStmt) {
          $userDupStmt->bind_param( 'ssssi', $username, $username, $email, $email, $linkedUserId);
          $userDupStmt->execute();
          $userDupResult = $userDupStmt->get_result();
          if ($userDupResult && $userDupResult->num_rows > 0) {
            $message = 'Another user account already uses this username or email.';
            $message_type = 'danger';
          }
          $userDupStmt->close();
        }
      } else {
        if ($password === '') {
          $message = 'Set an initial password to create this student login account.';
          $message_type = 'danger';
        } else {
          $userDupStmt = $connection->prepare( 'SELECT id FROM users WHERE username = ? OR email = ? OR username = ? OR email = ? LIMIT 1');
          if ($userDupStmt) {
            $userDupStmt->bind_param( 'ssss', $username, $username, $email, $email);
            $userDupStmt->execute();
            $userDupResult = $userDupStmt->get_result();
            if ($userDupResult && $userDupResult->num_rows > 0) {
              $message = 'A user account already uses this username or email.';
              $message_type = 'danger';
            }
            $userDupStmt->close();
          }
        }
      }
    }

    // Resolve or create class mapping and update records in one transaction.
    if ($message === '') {
      $classId = null;

      if ($studentsHasClassId && $class !== '' && $classNameColumn !== null) {
        $classFindSql = "SELECT id FROM classes WHERE {$classNameColumn} = ? LIMIT 1";
        $classFindStmt = $connection->prepare( $classFindSql);
        if ($classFindStmt) {
          $classFindStmt->bind_param( 's', $class);
          $classFindStmt->execute();
          $classFindResult = $classFindStmt->get_result();
          $classFindRow = $classFindResult ? $classFindResult->fetch_assoc() : null;
          if ($classFindRow) {
            $classId = (int) $classFindRow['id'];
          }
          $classFindStmt->close();
        }

        if ($classId === null) {
          $classInsertColumns = [$classNameColumn];
          $classInsertValues = ['?'];
          $classInsertTypes = 's';
          $classInsertParams = [$class];

          if ($classNameColumn === 'name' && admin_column_exists($connection, 'classes', 'class_name')) {
            $classInsertColumns[] = 'class_name';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertParams[] = $class;
          }
          if ($classNameColumn === 'class_name' && admin_column_exists($connection, 'classes', 'name')) {
            $classInsertColumns[] = 'name';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertParams[] = $class;
          }
          if (admin_column_exists($connection, 'classes', 'status')) {
            $classInsertColumns[] = 'status';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertParams[] = 'Active';
          }

          $classInsertSql = 'INSERT INTO classes (' . implode(', ', $classInsertColumns) . ') VALUES (' . implode(', ', $classInsertValues) . ')';
          $classInsertStmt = $connection->prepare( $classInsertSql);
          if ($classInsertStmt && admin_bind_dynamic_params($classInsertStmt, $classInsertTypes, $classInsertParams) && $classInsertStmt->execute()) {
            $insertedClassId = (int) $connection->insert_id;
            if ($insertedClassId > 0) {
              $classId = $insertedClassId;
            }
          }
          if ($classInsertStmt) {
            $classInsertStmt->close();
          }
        }
      }

      $studentRole = 'student';
      $updatedUserId = $linkedUserId;
      $transactionStarted = false;

      // Keep linked user and student updates atomic.
      if ($connection->begin_transaction()) {
        $transactionStarted = true;
      }

      if ($usersTableAvailable && $linkedUserId > 0) {
        if ($password !== '') {
          $passwordHash = password_hash($password, PASSWORD_DEFAULT);
          $userUpdateSql = "UPDATE users SET username = ?, name = ?, email = ?, phone = ?, status = ?, role = 'student', password = ? WHERE id = ?";
          $userUpdateStmt = $connection->prepare( $userUpdateSql);
          if (!$userUpdateStmt) {
            $message = 'Unable to update linked login account right now.';
            $message_type = 'danger';
          } else {
            $userUpdateStmt->bind_param('ssssssi', $username, $name, $email, $phone, $status, $passwordHash, $linkedUserId);
            if (!$userUpdateStmt->execute()) {
              $message = 'Failed to update linked login account.';
              $message_type = 'danger';
            }
            $userUpdateStmt->close();
          }
        } else {
          $userUpdateSql = "UPDATE users SET username = ?, name = ?, email = ?, phone = ?, status = ?, role = 'student' WHERE id = ?";
          $userUpdateStmt = $connection->prepare( $userUpdateSql);
          if (!$userUpdateStmt) {
            $message = 'Unable to update linked login account right now.';
            $message_type = 'danger';
          } else {
            $userUpdateStmt->bind_param('sssssi', $username, $name, $email, $phone, $status, $linkedUserId);
            if (!$userUpdateStmt->execute()) {
              $message = 'Failed to update linked login account.';
              $message_type = 'danger';
            }
            $userUpdateStmt->close();
          }
        }
      } elseif ($usersTableAvailable && $password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userInsertColumns = ['username', 'password', 'role', 'name'];
        $userInsertValues = ['?', '?', '?', '?'];
        $userInsertTypes = 'ssss';
        $userInsertParams = [$username, $passwordHash, $studentRole, $name];

        if ($usersHasEmail) {
          $userInsertColumns[] = 'email';
          $userInsertValues[] = '?';
          $userInsertTypes .= 's';
          $userInsertParams[] = $email !== '' ? $email : null;
        }

        if ($usersHasPhone) {
          $userInsertColumns[] = 'phone';
          $userInsertValues[] = '?';
          $userInsertTypes .= 's';
          $userInsertParams[] = $phone !== '' ? $phone : null;
        }

        if ($usersHasStatus) {
          $userInsertColumns[] = 'status';
          $userInsertValues[] = '?';
          $userInsertTypes .= 's';
          $userInsertParams[] = $status;
        }

        $userInsertSql = 'INSERT INTO users (' . implode(', ', $userInsertColumns) . ') VALUES (' . implode(', ', $userInsertValues) . ')';
        $userInsertStmt = $connection->prepare( $userInsertSql);

        if (!$userInsertStmt) {
          $message = 'Unable to create linked login account right now.';
          $message_type = 'danger';
        } elseif (!admin_bind_dynamic_params($userInsertStmt, $userInsertTypes, $userInsertParams) || !$userInsertStmt->execute()) {
          $message = 'Failed to create linked login account.';
          $message_type = 'danger';
        } else {
          $updatedUserId = (int) $connection->insert_id;
        }

        if ($userInsertStmt) {
          $userInsertStmt->close();
        }
      }

      // Update the student profile row with resolved schema fields.
      $updateFields = ['roll_no = ?', 'name = ?'];
      $updateTypes = 'ss';
      $updateParams = [$rollNo, $name];

      if ($studentsHasUsername) {
        $updateFields[] = 'username = ?';
        $updateTypes .= 's';
        $updateParams[] = $username;
      }

      if ($studentsHasUserId && $updatedUserId > 0) {
        $updateFields[] = 'user_id = ?';
        $updateTypes .= 'i';
        $updateParams[] = $updatedUserId;
      }

      if ($studentsHasClass) {
        $updateFields[] = 'class = ?';
        $updateTypes .= 's';
        $updateParams[] = $class;
      }

      if ($studentsHasClassId) {
        if ($classId === null) {
          $updateFields[] = 'class_id = NULL';
        } else {
          $updateFields[] = 'class_id = ?';
          $updateTypes .= 'i';
          $updateParams[] = $classId;
        }
      }

      if ($studentsHasEmail) {
        $updateFields[] = 'email = ?';
        $updateTypes .= 's';
        $updateParams[] = $email;
      }

      if ($studentsHasPhone) {
        $updateFields[] = 'phone = ?';
        $updateTypes .= 's';
        $updateParams[] = $phone;
      }

      if ($studentsHasStatus) {
        $updateFields[] = 'status = ?';
        $updateTypes .= 's';
        $updateParams[] = $status;
      }

      $updateSql = 'UPDATE students SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
      $updateTypes .= 'i';
      $updateParams[] = $studentId;

      $updateStmt = $connection->prepare( $updateSql);

      if ($updateStmt && admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams) && $updateStmt->execute()) {
        if ($transactionStarted && !$connection->commit()) {
          $message = 'Unable to finalize student update. Please try again.';
          $message_type = 'danger';
        } else {
          $message = 'Student updated successfully!';
          $message_type = 'success';
          $student = fetch_student_by_id($connection, $studentId);
          if ($student) {
            $studentLogin = resolve_student_login_account($connection, $student);
          }
        }
      } else {
        $message = 'Failed to update student. Please try again.';
        $message_type = 'danger';
      }

      if ($updateStmt) {
        $updateStmt->close();
      }

      if ($message_type === 'danger' && $transactionStarted) {
        $connection->rollback();
      }
    }
  }
}
?>
<!-- Render the student edit form and operation status messages. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Student</title>
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
      <h2><i class="fas fa-edit"></i> Edit Student</h2>
    </div>
    
    <div class="form-card">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo $message; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <?php if ($student): ?>
        <form method="POST" action="" novalidate>
          <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Roll Number *</label>
              <input type="text" class="form-control" name="roll_no" data-validation="required,min" data-min="1" value="<?php echo htmlspecialchars($student['roll_no']); ?>">
              <div id="roll_no_error" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Student Name *</label>
              <input type="text" class="form-control" name="name" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($student['name']); ?>">
              <div id="name_error" class="invalid-feedback"></div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class *</label>
              <input list="class-list" type="text" class="form-control" name="class" placeholder="e.g., Grade 10A" data-validation="required" value="<?php echo htmlspecialchars((string) ($student['class'] ?? '')); ?>">
              <datalist id="class-list">
                <?php foreach ($classOptions as $classOption): ?>
                  <option value="<?php echo htmlspecialchars((string) $classOption['class_name']); ?>"></option>
                <?php endforeach; ?>
              </datalist>
              <div id="class_error" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Username *</label>
              <input type="text" class="form-control" name="username" data-validation="required,min,max" data-min="3" data-max="30" value="<?php echo htmlspecialchars((string) ($_POST['username'] ?? ($studentLogin['username'] !== '' ? $studentLogin['username'] : ($student['username'] ?? '')))); ?>">
              <div id="username_error" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">New Password</label>
              <input type="password" class="form-control" name="password" minlength="6" placeholder="Leave blank to keep current password">
              <div id="password_error" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" name="confirm_password" minlength="6" placeholder="Re-enter new password">
              <div id="confirm_password_error" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars((string) ($student['email'] ?? '')); ?>">
              <div id="email_error" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars((string) ($student['phone'] ?? '')); ?>">
              <div id="phone_error" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="Active" <?php echo (($student['status'] ?? 'Active') == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo (($student['status'] ?? 'Active') == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-end">
              <small class="text-muted">Use password fields only when resetting student login password.</small>
            </div>
          </div>
          
          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Student</button>
            <a href="students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Student not found. <a href="students.php">Go back to students list</a></div>
      <?php endif; ?>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

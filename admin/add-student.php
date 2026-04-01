<?php
// Admin page for registering new students.
require_once __DIR__ . '/auth.php';

include '../dbconfig.php';

// Local helpers keep this page compatible with varying schema states.
function table_exists($connection, $tableName)
{
  $safeTable = $connection->real_escape_string( $tableName);
  $result = $connection->query( "SHOW TABLES LIKE '{$safeTable}'");
  return $result && $result->num_rows > 0;
}

function column_exists($connection, $tableName, $columnName)
{
  if (!table_exists($connection, $tableName)) {
    return false;
  }

  $safeTable = $connection->real_escape_string( $tableName);
  $safeColumn = $connection->real_escape_string( $columnName);
  $result = $connection->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
  return $result && $result->num_rows > 0;
}

function bind_dynamic_params($stmt, $types, array &$params)
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

function ensure_column($connection, $tableName, $columnName, $definition)
{
  if (!column_exists($connection, $tableName, $columnName)) {
    $connection->query("ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
  }
}

$message = '';
$message_type = '';
$classes = [];

// Ensure expected student columns are available before insert logic runs.
if (table_exists($connection, 'students')) {
  ensure_column($connection, 'students', 'user_id', 'INT NULL');
  ensure_column($connection, 'students', 'username', 'VARCHAR(100) NULL');
}

$classesHasName = column_exists($connection, 'classes', 'name');
$classesHasClassName = column_exists($connection, 'classes', 'class_name');

// Load class suggestions for the student form datalist.
if (table_exists($connection, 'classes') && ($classesHasName || $classesHasClassName)) {
  if ($classesHasName && $classesHasClassName) {
    $classQuerySql = "SELECT COALESCE(NULLIF(name, ''), class_name) AS class_name FROM classes ORDER BY id ASC";
  } elseif ($classesHasName) {
    $classQuerySql = "SELECT name AS class_name FROM classes ORDER BY id ASC";
  } else {
    $classQuerySql = "SELECT class_name AS class_name FROM classes ORDER BY id ASC";
  }

  $classQuery = $connection->query( $classQuerySql);
  if ($classQuery) {
    while ($row = $classQuery->fetch_assoc()) {
      if (!empty($row['class_name'])) {
        $classes[] = $row['class_name'];
      }
        }
    }
}

    // Handle student form submission and lifecycle operations.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = trim($_POST['roll_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm_password = (string) ($_POST['confirm_password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

    // Validate required fields, identifiers, and credential rules.
    if ($roll_no === '' || $name === '' || $class === '' || $username === '' || $password === '' || $confirm_password === '') {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
        $message = 'Username must be 3-30 characters and contain only letters, numbers, dot, underscore, or hyphen.';
        $message_type = 'danger';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'Password and confirm password must match.';
        $message_type = 'danger';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    } else {
        $checkStmt = $connection->prepare( 'SELECT id FROM students WHERE roll_no = ? LIMIT 1');
        if ($checkStmt) {
            $checkStmt->bind_param( 's', $roll_no);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $exists = $checkResult && $checkResult->num_rows > 0;
            $checkStmt->close();
        } else {
            $exists = false;
        }

        if ($exists) {
            $message = 'Roll number already exists!';
            $message_type = 'danger';
        }
    }

      // Check username uniqueness in the students table when supported.
    if ($message === '' && column_exists($connection, 'students', 'username')) {
      $usernameStmt = $connection->prepare( 'SELECT id FROM students WHERE username = ? LIMIT 1');
      if ($usernameStmt) {
        $usernameStmt->bind_param( 's', $username);
        $usernameStmt->execute();
        $usernameResult = $usernameStmt->get_result();
        if ($usernameResult && $usernameResult->num_rows > 0) {
          $message = 'A student with this username already exists.';
          $message_type = 'danger';
        }
        $usernameStmt->close();
      }
    }

    // Check credentials against the shared users table before account creation.
    if ($message === '' && table_exists($connection, 'users')) {
      if ($email !== '') {
        $userCheckSql = 'SELECT id FROM users WHERE username = ? OR email = ? OR username = ? OR email = ? LIMIT 1';
        $userCheckStmt = $connection->prepare( $userCheckSql);
        if ($userCheckStmt) {
          $userCheckStmt->bind_param( 'ssss', $username, $username, $email, $email);
          $userCheckStmt->execute();
          $userCheckResult = $userCheckStmt->get_result();
          if ($userCheckResult && $userCheckResult->num_rows > 0) {
            $message = 'A user account with this username or email already exists.';
            $message_type = 'danger';
          }
          $userCheckStmt->close();
        }
      } else {
        $userCheckSql = 'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1';
        $userCheckStmt = $connection->prepare( $userCheckSql);
        if ($userCheckStmt) {
          $userCheckStmt->bind_param( 'ss', $username, $username);
          $userCheckStmt->execute();
          $userCheckResult = $userCheckStmt->get_result();
          if ($userCheckResult && $userCheckResult->num_rows > 0) {
            $message = 'A user account with this username already exists.';
            $message_type = 'danger';
          }
          $userCheckStmt->close();
        }
      }
    }

    // Resolve class references and insert user/student records transactionally.
    if ($message === '') {
      $studentsHasClass = column_exists($connection, 'students', 'class');
      $studentsHasClassId = column_exists($connection, 'students', 'class_id');
      $studentsHasEmail = column_exists($connection, 'students', 'email');
      $studentsHasPhone = column_exists($connection, 'students', 'phone');
      $studentsHasStatus = column_exists($connection, 'students', 'status');
      $studentsHasUserId = column_exists($connection, 'students', 'user_id');
      $studentsHasUsername = column_exists($connection, 'students', 'username');
      $usersHasEmail = column_exists($connection, 'users', 'email');
      $usersHasPhone = column_exists($connection, 'users', 'phone');
      $usersHasStatus = column_exists($connection, 'users', 'status');

      $classesHasStatus = column_exists($connection, 'classes', 'status');
      $class_id = null;

      if ($class !== '' && table_exists($connection, 'classes') && ($classesHasName || $classesHasClassName)) {
        $classWhere = [];
        $classBindTypes = '';
        $classBindValues = [];

        if ($classesHasName) {
          $classWhere[] = 'name = ?';
          $classBindTypes .= 's';
          $classBindValues[] = $class;
        }

        if ($classesHasClassName) {
          $classWhere[] = 'class_name = ?';
          $classBindTypes .= 's';
          $classBindValues[] = $class;
        }

        if (!empty($classWhere)) {
          $findClassSql = 'SELECT id FROM classes WHERE ' . implode(' OR ', $classWhere) . ' LIMIT 1';
          $classStmt = $connection->prepare( $findClassSql);
          if ($classStmt && bind_dynamic_params($classStmt, $classBindTypes, $classBindValues)) {
            $classStmt->execute();
            $classResult = $classStmt->get_result();
            if ($classResult && $classResult->num_rows > 0) {
              $classRow = $classResult->fetch_assoc();
              $class_id = (int) ($classRow['id'] ?? 0);
            }
            $classStmt->close();
          }
        }

        // If class doesn't exist yet, create it to avoid FK issues on class_id.
        if ($class_id === null) {
          $classInsertColumns = [];
          $classInsertValues = [];
          $classInsertTypes = '';
          $classInsertBindValues = [];

          if ($classesHasName) {
            $classInsertColumns[] = 'name';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertBindValues[] = $class;
          }

          if ($classesHasClassName) {
            $classInsertColumns[] = 'class_name';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertBindValues[] = $class;
          }

          if ($classesHasStatus) {
            $classInsertColumns[] = 'status';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertBindValues[] = 'Active';
          }

          if (!empty($classInsertColumns)) {
            $createClassSql = 'INSERT INTO classes (' . implode(', ', $classInsertColumns) . ') VALUES (' . implode(', ', $classInsertValues) . ')';
            $createClassStmt = $connection->prepare( $createClassSql);

            if ($createClassStmt && bind_dynamic_params($createClassStmt, $classInsertTypes, $classInsertBindValues)) {
              if ($createClassStmt->execute()) {
                $newClassId = (int) $connection->insert_id;
                if ($newClassId > 0) {
                  $class_id = $newClassId;
                }
              }
              $createClassStmt->close();
            }
          }
        }
      }

      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $studentRole = 'student';
      $studentUserId = 0;
      $transactionStarted = false;

      // Keep login and profile inserts in a single transaction.
      if ($connection->begin_transaction()) {
        $transactionStarted = true;
      }

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
        $message = 'Unable to create student login account right now.';
        $message_type = 'danger';
      } elseif (!bind_dynamic_params($userInsertStmt, $userInsertTypes, $userInsertParams) || !$userInsertStmt->execute()) {
        $message = 'Failed to create student login account. Username or email may already exist.';
        $message_type = 'danger';
      } else {
        $studentUserId = (int) $connection->insert_id;
      }

      if ($userInsertStmt) {
        $userInsertStmt->close();
      }

      // Insert the student profile row after account creation succeeds.
      if ($message === '') {
        $insertColumns = ['roll_no', 'name'];
        $insertValues = ['?', '?'];
        $insertTypes = 'ss';
        $insertBindValues = [$roll_no, $name];

        if ($studentsHasUserId) {
          $insertColumns[] = 'user_id';
          $insertValues[] = '?';
          $insertTypes .= 'i';
          $insertBindValues[] = $studentUserId;
        }

        if ($studentsHasUsername) {
          $insertColumns[] = 'username';
          $insertValues[] = '?';
          $insertTypes .= 's';
          $insertBindValues[] = $username;
        }

        if ($studentsHasClass) {
          $insertColumns[] = 'class';
          $insertValues[] = '?';
          $insertTypes .= 's';
          $insertBindValues[] = $class;
        }

        if ($studentsHasClassId) {
          $insertColumns[] = 'class_id';
          if ($class_id === null) {
            $insertValues[] = 'NULL';
          } else {
            $insertValues[] = '?';
            $insertTypes .= 'i';
            $insertBindValues[] = $class_id;
          }
        }

        if ($studentsHasEmail) {
          $insertColumns[] = 'email';
          $insertValues[] = '?';
          $insertTypes .= 's';
          $insertBindValues[] = $email !== '' ? $email : null;
        }

        if ($studentsHasPhone) {
          $insertColumns[] = 'phone';
          $insertValues[] = '?';
          $insertTypes .= 's';
          $insertBindValues[] = $phone !== '' ? $phone : null;
        }

        if ($studentsHasStatus) {
          $insertColumns[] = 'status';
          $insertValues[] = '?';
          $insertTypes .= 's';
          $insertBindValues[] = $status;
        }

        $insertSql = 'INSERT INTO students (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
        $insertStmt = $connection->prepare( $insertSql);

        if (!$insertStmt) {
          $message = 'Failed to prepare student enrollment query.';
          $message_type = 'danger';
        } elseif (!bind_dynamic_params($insertStmt, $insertTypes, $insertBindValues) || !$insertStmt->execute()) {
          $message = 'Error adding student: ' . $connection->error;
          $message_type = 'danger';
        }

        if ($insertStmt) {
          $insertStmt->close();
        }
      }

      if ($message === '' && $transactionStarted) {
        if (!$connection->commit()) {
          $message = 'Unable to finalize student creation. Please try again.';
          $message_type = 'danger';
        }
      }

      if ($message !== '') {
        if ($transactionStarted) {
          $connection->rollback();
        }
      } else {
        $message = 'Student added successfully. Login username: ' . $username;
        $message_type = 'success';
        $_POST = [];
      }
    }
}
?>
<!-- Render the add student form and inline status messaging. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Student</title>
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
    .btn-cancel { background: #e2e8f0; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; margin-left: 10px; text-decoration: none; display: inline-block; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user-plus"></i> Add New Student</h2>
    </div>

    <div class="form-card">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Roll Number *</label>
            <input type="text" class="form-control" name="roll_no" required value="<?php echo htmlspecialchars($_POST['roll_no'] ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Student Name *</label>
            <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Class *</label>
            <input list="class-list" class="form-control" name="class" placeholder="e.g., Grade 10A" required value="<?php echo htmlspecialchars($_POST['class'] ?? ''); ?>">
            <datalist id="class-list">
              <?php foreach ($classes as $className): ?>
              <option value="<?php echo htmlspecialchars($className); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Username *</label>
            <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="e.g., stu_rahul01">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Password *</label>
            <input type="password" class="form-control" name="password" required minlength="6" placeholder="Minimum 6 characters">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Confirm Password *</label>
            <input type="password" class="form-control" name="confirm_password" required minlength="6" placeholder="Re-enter password">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="Active" <?php echo (($_POST['status'] ?? 'Active') === 'Active') ? 'selected' : ''; ?>>Active</option>
              <option value="Inactive" <?php echo (($_POST['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Student</button>
          <a href="students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>
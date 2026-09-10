<?php
// Admin page for managing application users and account access.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/db_helpers.php';

$connection = $conn ?? null;

if (!($connection instanceof mysqli) && !($connection instanceof SchoolErpDemoConnection)) {
  die('Database connection is not available.');
}

if (!admin_table_exists($connection, 'users')) {
  die('Users table is not available. Please run setup first.');
}

admin_ensure_column($connection, 'users', 'status', "VARCHAR(20) DEFAULT 'Active'");
admin_ensure_column($connection, 'users', 'phone', 'VARCHAR(30) NULL');
admin_ensure_column($connection, 'users', 'email', 'VARCHAR(150) NULL');

$usersHasEmail = admin_column_exists($connection, 'users', 'email');
$usersHasPhone = admin_column_exists($connection, 'users', 'phone');
$usersHasStatus = admin_column_exists($connection, 'users', 'status');
$usersHasUpdatedAt = admin_column_exists($connection, 'users', 'updated_at');
$usersHasCreatedAt = admin_column_exists($connection, 'users', 'created_at');

$currentAdminUserId = (int) ($_SESSION['admin_id'] ?? 0);
$roleOptions = ['admin', 'teacher', 'student'];
$statusOptions = ['Active', 'Inactive'];

function admin_manage_normalize_role($role)
{
  $normalized = strtolower(trim((string) $role));
  if (in_array($normalized, ['admin', 'teacher', 'student'], true)) {
    return $normalized;
  }

  return 'student';
}

function admin_manage_normalize_status($status)
{
  return strtolower(trim((string) $status)) === 'inactive' ? 'Inactive' : 'Active';
}

function admin_manage_valid_username($username)
{
  return (bool) preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username);
}

function admin_manage_fetch_user_snapshot($connection, $userId, $usersHasEmail, $usersHasPhone, $usersHasStatus)
{
  $selectColumns = [
    'role',
    'username',
    'name',
    $usersHasEmail ? 'email' : "'' AS email",
    $usersHasPhone ? 'phone' : "'' AS phone",
    $usersHasStatus ? 'status' : "'Active' AS status",
  ];

  $snapshotSql = 'SELECT ' . implode(', ', $selectColumns) . ' FROM users WHERE id = ? LIMIT 1';
  $snapshotStmt = $connection->prepare($snapshotSql);
  if (!$snapshotStmt) {
    return null;
  }

  $snapshotStmt->bind_param('i', $userId);
  $snapshotStmt->execute();
  $snapshotResult = $snapshotStmt->get_result();
  $snapshotRow = $snapshotResult ? $snapshotResult->fetch_assoc() : null;
  $snapshotStmt->close();

  return $snapshotRow ?: null;
}

function admin_manage_generate_student_roll_no($connection, $userId)
{
  $baseRoll = 'STU' . str_pad((string) max(1, (int) $userId), 4, '0', STR_PAD_LEFT);
  $candidateRoll = $baseRoll;
  $suffix = 1;

  while ($suffix <= 9999) {
    $rollStmt = $connection->prepare('SELECT id FROM students WHERE roll_no = ? LIMIT 1');
    if (!$rollStmt) {
      return $candidateRoll;
    }

    $rollStmt->bind_param('s', $candidateRoll);
    $rollStmt->execute();
    $rollResult = $rollStmt->get_result();
    $exists = $rollResult && $rollResult->num_rows > 0;
    $rollStmt->close();

    if (!$exists) {
      return $candidateRoll;
    }

    $candidateRoll = $baseRoll . '-' . $suffix;
    $suffix++;
  }

  return $baseRoll . '-' . time();
}

function admin_manage_sync_role_profile($connection, $userId, $role, $username, $name, $email, $phone, $status, &$errorMessage)
{
  $errorMessage = '';

  $userId = (int) $userId;
  if ($userId <= 0) {
    $errorMessage = 'Invalid user id for profile sync.';
    return false;
  }

  $targetRole = admin_manage_normalize_role($role);
  $displayName = trim((string) $name);
  if ($displayName === '') {
    $displayName = 'User ' . $userId;
  }

  $usernameValue = trim((string) $username);
  $emailValue = trim((string) $email);
  $phoneValue = trim((string) $phone);
  $statusValue = admin_manage_normalize_status($status);

  if ($targetRole === 'admin') {
    return true;
  }

  if ($targetRole === 'student') {
    if (!admin_table_exists($connection, 'students')) {
      $errorMessage = 'Students table is not available.';
      return false;
    }

    admin_ensure_column($connection, 'students', 'user_id', 'INT NULL');
    admin_ensure_column($connection, 'students', 'username', 'VARCHAR(100) NULL');
    admin_ensure_column($connection, 'students', 'email', 'VARCHAR(150) NULL');
    admin_ensure_column($connection, 'students', 'phone', 'VARCHAR(30) NULL');
    admin_ensure_column($connection, 'students', 'status', "VARCHAR(20) DEFAULT 'Active'");

    $studentsHasUserId = admin_column_exists($connection, 'students', 'user_id');
    $studentsHasUsername = admin_column_exists($connection, 'students', 'username');
    $studentsHasEmail = admin_column_exists($connection, 'students', 'email');
    $studentsHasPhone = admin_column_exists($connection, 'students', 'phone');
    $studentsHasStatus = admin_column_exists($connection, 'students', 'status');
    $studentsHasClass = admin_column_exists($connection, 'students', 'class');

    $studentId = 0;

    if ($studentsHasUserId) {
      $studentByUserStmt = $connection->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
      if ($studentByUserStmt) {
        $studentByUserStmt->bind_param('i', $userId);
        $studentByUserStmt->execute();
        $studentByUserResult = $studentByUserStmt->get_result();
        $studentByUserRow = $studentByUserResult ? $studentByUserResult->fetch_assoc() : null;
        $studentId = (int) ($studentByUserRow['id'] ?? 0);
        $studentByUserStmt->close();
      }
    }

    if ($studentId <= 0 && $studentsHasUsername && $usernameValue !== '') {
      $studentByUsernameStmt = $connection->prepare('SELECT id FROM students WHERE username = ? LIMIT 1');
      if ($studentByUsernameStmt) {
        $studentByUsernameStmt->bind_param('s', $usernameValue);
        $studentByUsernameStmt->execute();
        $studentByUsernameResult = $studentByUsernameStmt->get_result();
        $studentByUsernameRow = $studentByUsernameResult ? $studentByUsernameResult->fetch_assoc() : null;
        $studentId = (int) ($studentByUsernameRow['id'] ?? 0);
        $studentByUsernameStmt->close();
      }
    }

    if ($studentId <= 0 && $studentsHasEmail && $emailValue !== '') {
      $studentByEmailStmt = $connection->prepare('SELECT id FROM students WHERE email = ? LIMIT 1');
      if ($studentByEmailStmt) {
        $studentByEmailStmt->bind_param('s', $emailValue);
        $studentByEmailStmt->execute();
        $studentByEmailResult = $studentByEmailStmt->get_result();
        $studentByEmailRow = $studentByEmailResult ? $studentByEmailResult->fetch_assoc() : null;
        $studentId = (int) ($studentByEmailRow['id'] ?? 0);
        $studentByEmailStmt->close();
      }
    }

    if ($studentId > 0) {
      $studentUpdateFields = ['name = ?'];
      $studentUpdateTypes = 's';
      $studentUpdateParams = [$displayName];

      if ($studentsHasUserId) {
        $studentUpdateFields[] = 'user_id = ?';
        $studentUpdateTypes .= 'i';
        $studentUpdateParams[] = $userId;
      }

      if ($studentsHasUsername) {
        $studentUpdateFields[] = 'username = ?';
        $studentUpdateTypes .= 's';
        $studentUpdateParams[] = $usernameValue !== '' ? $usernameValue : null;
      }

      if ($studentsHasEmail) {
        $studentUpdateFields[] = 'email = ?';
        $studentUpdateTypes .= 's';
        $studentUpdateParams[] = $emailValue !== '' ? $emailValue : null;
      }

      if ($studentsHasPhone) {
        $studentUpdateFields[] = 'phone = ?';
        $studentUpdateTypes .= 's';
        $studentUpdateParams[] = $phoneValue !== '' ? $phoneValue : null;
      }

      if ($studentsHasStatus) {
        $studentUpdateFields[] = 'status = ?';
        $studentUpdateTypes .= 's';
        $studentUpdateParams[] = $statusValue;
      }

      $studentUpdateSql = 'UPDATE students SET ' . implode(', ', $studentUpdateFields) . ' WHERE id = ? LIMIT 1';
      $studentUpdateTypes .= 'i';
      $studentUpdateParams[] = $studentId;

      $studentUpdateStmt = $connection->prepare($studentUpdateSql);
      if (!$studentUpdateStmt) {
        $errorMessage = 'Unable to sync student profile.';
        return false;
      }

      if (!admin_bind_dynamic_params($studentUpdateStmt, $studentUpdateTypes, $studentUpdateParams) || !$studentUpdateStmt->execute()) {
        $syncError = trim((string) $studentUpdateStmt->error);
        $studentUpdateStmt->close();
        $errorMessage = $syncError !== '' ? ('Unable to sync student profile. ' . $syncError) : 'Unable to sync student profile.';
        return false;
      }

      $studentUpdateStmt->close();
      return true;
    }

    $studentInsertColumns = ['roll_no', 'name'];
    $studentInsertTypes = 'ss';
    $studentInsertParams = [admin_manage_generate_student_roll_no($connection, $userId), $displayName];

    if ($studentsHasUserId) {
      $studentInsertColumns[] = 'user_id';
      $studentInsertTypes .= 'i';
      $studentInsertParams[] = $userId;
    }

    if ($studentsHasUsername) {
      $studentInsertColumns[] = 'username';
      $studentInsertTypes .= 's';
      $studentInsertParams[] = $usernameValue !== '' ? $usernameValue : null;
    }

    if ($studentsHasClass) {
      $studentInsertColumns[] = 'class';
      $studentInsertTypes .= 's';
      $studentInsertParams[] = 'Unassigned';
    }

    if ($studentsHasEmail) {
      $studentInsertColumns[] = 'email';
      $studentInsertTypes .= 's';
      $studentInsertParams[] = $emailValue !== '' ? $emailValue : null;
    }

    if ($studentsHasPhone) {
      $studentInsertColumns[] = 'phone';
      $studentInsertTypes .= 's';
      $studentInsertParams[] = $phoneValue !== '' ? $phoneValue : null;
    }

    if ($studentsHasStatus) {
      $studentInsertColumns[] = 'status';
      $studentInsertTypes .= 's';
      $studentInsertParams[] = $statusValue;
    }

    $studentEscapedColumns = [];
    foreach ($studentInsertColumns as $studentColumn) {
      $studentEscapedColumns[] = '`' . $studentColumn . '`';
    }

    $studentInsertSql = 'INSERT INTO students (' . implode(', ', $studentEscapedColumns) . ') VALUES (' . implode(', ', array_fill(0, count($studentInsertColumns), '?')) . ')';
    $studentInsertStmt = $connection->prepare($studentInsertSql);
    if (!$studentInsertStmt) {
      $errorMessage = 'Unable to create linked student profile.';
      return false;
    }

    if (!admin_bind_dynamic_params($studentInsertStmt, $studentInsertTypes, $studentInsertParams) || !$studentInsertStmt->execute()) {
      $syncError = trim((string) $studentInsertStmt->error);
      $studentInsertStmt->close();
      $errorMessage = $syncError !== '' ? ('Unable to create linked student profile. ' . $syncError) : 'Unable to create linked student profile.';
      return false;
    }

    $studentInsertStmt->close();
    return true;
  }

  if ($targetRole === 'teacher') {
    if (!admin_table_exists($connection, 'teachers')) {
      $errorMessage = 'Teachers table is not available.';
      return false;
    }

    admin_ensure_column($connection, 'teachers', 'user_id', 'INT NULL');
    admin_ensure_column($connection, 'teachers', 'username', 'VARCHAR(100) NULL');
    admin_ensure_column($connection, 'teachers', 'email', 'VARCHAR(150) NULL');
    admin_ensure_column($connection, 'teachers', 'phone', 'VARCHAR(30) NULL');
    admin_ensure_column($connection, 'teachers', 'status', "VARCHAR(20) DEFAULT 'Active'");

    $teachersHasUserId = admin_column_exists($connection, 'teachers', 'user_id');
    $teachersHasUsername = admin_column_exists($connection, 'teachers', 'username');
    $teachersHasEmail = admin_column_exists($connection, 'teachers', 'email');
    $teachersHasPhone = admin_column_exists($connection, 'teachers', 'phone');
    $teachersHasStatus = admin_column_exists($connection, 'teachers', 'status');
    $teachersHasSubject = admin_column_exists($connection, 'teachers', 'subject');
    $teachersHasFirstName = admin_column_exists($connection, 'teachers', 'first_name');
    $teachersHasLastName = admin_column_exists($connection, 'teachers', 'last_name');

    $nameParts = preg_split('/\s+/', $displayName, 2);
    $firstName = trim((string) ($nameParts[0] ?? $displayName));
    $lastName = trim((string) ($nameParts[1] ?? ''));

    $teacherId = 0;

    if ($teachersHasUserId) {
      $teacherByUserStmt = $connection->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
      if ($teacherByUserStmt) {
        $teacherByUserStmt->bind_param('i', $userId);
        $teacherByUserStmt->execute();
        $teacherByUserResult = $teacherByUserStmt->get_result();
        $teacherByUserRow = $teacherByUserResult ? $teacherByUserResult->fetch_assoc() : null;
        $teacherId = (int) ($teacherByUserRow['id'] ?? 0);
        $teacherByUserStmt->close();
      }
    }

    if ($teacherId <= 0 && $teachersHasUsername && $usernameValue !== '') {
      $teacherByUsernameStmt = $connection->prepare('SELECT id FROM teachers WHERE username = ? LIMIT 1');
      if ($teacherByUsernameStmt) {
        $teacherByUsernameStmt->bind_param('s', $usernameValue);
        $teacherByUsernameStmt->execute();
        $teacherByUsernameResult = $teacherByUsernameStmt->get_result();
        $teacherByUsernameRow = $teacherByUsernameResult ? $teacherByUsernameResult->fetch_assoc() : null;
        $teacherId = (int) ($teacherByUsernameRow['id'] ?? 0);
        $teacherByUsernameStmt->close();
      }
    }

    if ($teacherId <= 0 && $teachersHasEmail && $emailValue !== '') {
      $teacherByEmailStmt = $connection->prepare('SELECT id FROM teachers WHERE email = ? LIMIT 1');
      if ($teacherByEmailStmt) {
        $teacherByEmailStmt->bind_param('s', $emailValue);
        $teacherByEmailStmt->execute();
        $teacherByEmailResult = $teacherByEmailStmt->get_result();
        $teacherByEmailRow = $teacherByEmailResult ? $teacherByEmailResult->fetch_assoc() : null;
        $teacherId = (int) ($teacherByEmailRow['id'] ?? 0);
        $teacherByEmailStmt->close();
      }
    }

    if ($teacherId > 0) {
      $teacherUpdateFields = ['name = ?'];
      $teacherUpdateTypes = 's';
      $teacherUpdateParams = [$displayName];

      if ($teachersHasUserId) {
        $teacherUpdateFields[] = 'user_id = ?';
        $teacherUpdateTypes .= 'i';
        $teacherUpdateParams[] = $userId;
      }

      if ($teachersHasFirstName) {
        $teacherUpdateFields[] = 'first_name = ?';
        $teacherUpdateTypes .= 's';
        $teacherUpdateParams[] = $firstName;
      }

      if ($teachersHasLastName) {
        $teacherUpdateFields[] = 'last_name = ?';
        $teacherUpdateTypes .= 's';
        $teacherUpdateParams[] = $lastName !== '' ? $lastName : null;
      }

      if ($teachersHasUsername) {
        $teacherUpdateFields[] = 'username = ?';
        $teacherUpdateTypes .= 's';
        $teacherUpdateParams[] = $usernameValue !== '' ? $usernameValue : null;
      }

      if ($teachersHasEmail) {
        $teacherUpdateFields[] = 'email = ?';
        $teacherUpdateTypes .= 's';
        $teacherUpdateParams[] = $emailValue !== '' ? $emailValue : null;
      }

      if ($teachersHasPhone) {
        $teacherUpdateFields[] = 'phone = ?';
        $teacherUpdateTypes .= 's';
        $teacherUpdateParams[] = $phoneValue !== '' ? $phoneValue : null;
      }

      if ($teachersHasStatus) {
        $teacherUpdateFields[] = 'status = ?';
        $teacherUpdateTypes .= 's';
        $teacherUpdateParams[] = $statusValue;
      }

      $teacherUpdateSql = 'UPDATE teachers SET ' . implode(', ', $teacherUpdateFields) . ' WHERE id = ? LIMIT 1';
      $teacherUpdateTypes .= 'i';
      $teacherUpdateParams[] = $teacherId;

      $teacherUpdateStmt = $connection->prepare($teacherUpdateSql);
      if (!$teacherUpdateStmt) {
        $errorMessage = 'Unable to sync teacher profile.';
        return false;
      }

      if (!admin_bind_dynamic_params($teacherUpdateStmt, $teacherUpdateTypes, $teacherUpdateParams) || !$teacherUpdateStmt->execute()) {
        $syncError = trim((string) $teacherUpdateStmt->error);
        $teacherUpdateStmt->close();
        $errorMessage = $syncError !== '' ? ('Unable to sync teacher profile. ' . $syncError) : 'Unable to sync teacher profile.';
        return false;
      }

      $teacherUpdateStmt->close();
      return true;
    }

    $teacherInsertColumns = ['name'];
    $teacherInsertTypes = 's';
    $teacherInsertParams = [$displayName];

    if ($teachersHasUserId) {
      $teacherInsertColumns[] = 'user_id';
      $teacherInsertTypes .= 'i';
      $teacherInsertParams[] = $userId;
    }

    if ($teachersHasFirstName) {
      $teacherInsertColumns[] = 'first_name';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = $firstName;
    }

    if ($teachersHasLastName) {
      $teacherInsertColumns[] = 'last_name';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = $lastName !== '' ? $lastName : null;
    }

    if ($teachersHasUsername) {
      $teacherInsertColumns[] = 'username';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = $usernameValue !== '' ? $usernameValue : null;
    }

    if ($teachersHasEmail) {
      $teacherInsertColumns[] = 'email';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = $emailValue !== '' ? $emailValue : null;
    }

    if ($teachersHasPhone) {
      $teacherInsertColumns[] = 'phone';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = $phoneValue !== '' ? $phoneValue : null;
    }

    if ($teachersHasSubject) {
      $teacherInsertColumns[] = 'subject';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = 'General';
    }

    if ($teachersHasStatus) {
      $teacherInsertColumns[] = 'status';
      $teacherInsertTypes .= 's';
      $teacherInsertParams[] = $statusValue;
    }

    $teacherEscapedColumns = [];
    foreach ($teacherInsertColumns as $teacherColumn) {
      $teacherEscapedColumns[] = '`' . $teacherColumn . '`';
    }

    $teacherInsertSql = 'INSERT INTO teachers (' . implode(', ', $teacherEscapedColumns) . ') VALUES (' . implode(', ', array_fill(0, count($teacherInsertColumns), '?')) . ')';
    $teacherInsertStmt = $connection->prepare($teacherInsertSql);
    if (!$teacherInsertStmt) {
      $errorMessage = 'Unable to create linked teacher profile.';
      return false;
    }

    if (!admin_bind_dynamic_params($teacherInsertStmt, $teacherInsertTypes, $teacherInsertParams) || !$teacherInsertStmt->execute()) {
      $syncError = trim((string) $teacherInsertStmt->error);
      $teacherInsertStmt->close();
      $errorMessage = $syncError !== '' ? ('Unable to create linked teacher profile. ' . $syncError) : 'Unable to create linked teacher profile.';
      return false;
    }

    $teacherInsertStmt->close();
    return true;
  }

  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = trim((string) ($_POST['action'] ?? ''));

  if ($action === 'create') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $role = admin_manage_normalize_role($_POST['role'] ?? 'student');
    $status = admin_manage_normalize_status($_POST['status'] ?? 'Active');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($username === '' || $name === '' || $password === '' || $confirmPassword === '') {
      admin_set_flash('danger', 'Please fill all required fields for user creation.');
    } elseif (!admin_manage_valid_username($username)) {
      admin_set_flash('danger', 'Username must be 3-30 characters and use letters, numbers, dot, underscore, or hyphen.');
    } elseif (strlen($password) < 8) {
      admin_set_flash('danger', 'Password must be at least 8 characters long.');
    } elseif ($password !== $confirmPassword) {
      admin_set_flash('danger', 'Password and confirm password do not match.');
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      admin_set_flash('danger', 'Please provide a valid email address.');
    } elseif (!in_array($role, $roleOptions, true)) {
      admin_set_flash('danger', 'Invalid role selected.');
    } elseif (!in_array($status, $statusOptions, true)) {
      admin_set_flash('danger', 'Invalid status selected.');
    } else {
      $duplicateSql = 'SELECT id FROM users WHERE username = ?';
      $duplicateTypes = 's';
      $duplicateParams = [$username];

      if ($usersHasEmail && $email !== '') {
        $duplicateSql .= ' OR email = ?';
        $duplicateTypes .= 's';
        $duplicateParams[] = $email;
      }

      $duplicateSql .= ' LIMIT 1';
      $duplicateStmt = $connection->prepare($duplicateSql);
      $duplicateExists = false;

      if ($duplicateStmt && admin_bind_dynamic_params($duplicateStmt, $duplicateTypes, $duplicateParams)) {
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        $duplicateExists = $duplicateResult && $duplicateResult->num_rows > 0;
        $duplicateStmt->close();
      }

      if ($duplicateExists) {
        admin_set_flash('danger', 'A user already exists with the same username or email.');
      } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertColumns = ['username', 'password', 'role', 'name'];
        $insertTypes = 'ssss';
        $insertParams = [$username, $passwordHash, $role, $name];

        if ($usersHasEmail) {
          $insertColumns[] = 'email';
          $insertTypes .= 's';
          $insertParams[] = ($email !== '') ? $email : null;
        }

        if ($usersHasPhone) {
          $insertColumns[] = 'phone';
          $insertTypes .= 's';
          $insertParams[] = ($phone !== '') ? $phone : null;
        }

        if ($usersHasStatus) {
          $insertColumns[] = 'status';
          $insertTypes .= 's';
          $insertParams[] = $status;
        }

        $escapedColumns = [];
        foreach ($insertColumns as $columnName) {
          $escapedColumns[] = '`' . $columnName . '`';
        }

        $insertSql = 'INSERT INTO users (' . implode(', ', $escapedColumns) . ') VALUES (' . implode(', ', array_fill(0, count($insertColumns), '?')) . ')';
        $insertStmt = $connection->prepare($insertSql);

        if (!$insertStmt) {
          admin_set_flash('danger', 'Unable to create user right now.');
        } elseif (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
          admin_set_flash('danger', 'Unable to process user creation fields.');
          $insertStmt->close();
        } elseif ($insertStmt->execute()) {
          $newUserId = (int) $connection->insert_id;
          $insertStmt->close();

          $syncError = '';
          if (!admin_manage_sync_role_profile($connection, $newUserId, $role, $username, $name, $email, $phone, $status, $syncError)) {
            admin_set_flash('danger', 'User account created, but profile sync failed. ' . $syncError);
          } else {
            admin_set_flash('success', 'User account created successfully.');
          }
        } else {
          $insertError = trim((string) $insertStmt->error);
          admin_set_flash('danger', $insertError !== '' ? ('Unable to create user. ' . $insertError) : 'Unable to create user right now.');
          $insertStmt->close();
        }
      }
    }

    header('Location: manage-users.php');
    exit();
  }

  if ($action === 'update') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $username = trim((string) ($_POST['username'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $role = admin_manage_normalize_role($_POST['role'] ?? 'student');
    $status = admin_manage_normalize_status($_POST['status'] ?? 'Active');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmNewPassword = (string) ($_POST['confirm_new_password'] ?? '');

    if ($userId <= 0) {
      admin_set_flash('danger', 'Invalid user selected for update.');
      header('Location: manage-users.php');
      exit();
    }

    if ($username === '' || $name === '') {
      admin_set_flash('danger', 'Username and full name are required.');
      header('Location: manage-users.php');
      exit();
    }

    if (!admin_manage_valid_username($username)) {
      admin_set_flash('danger', 'Username must be 3-30 characters and use letters, numbers, dot, underscore, or hyphen.');
      header('Location: manage-users.php');
      exit();
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      admin_set_flash('danger', 'Please provide a valid email address.');
      header('Location: manage-users.php');
      exit();
    }

    if (!in_array($role, $roleOptions, true)) {
      admin_set_flash('danger', 'Invalid role selected.');
      header('Location: manage-users.php');
      exit();
    }

    if (!in_array($status, $statusOptions, true)) {
      admin_set_flash('danger', 'Invalid status selected.');
      header('Location: manage-users.php');
      exit();
    }

    if ($newPassword !== '' && strlen($newPassword) < 8) {
      admin_set_flash('danger', 'New password must be at least 8 characters long.');
      header('Location: manage-users.php');
      exit();
    }

    if (($newPassword !== '' || $confirmNewPassword !== '') && $newPassword !== $confirmNewPassword) {
      admin_set_flash('danger', 'New password and confirm password do not match.');
      header('Location: manage-users.php');
      exit();
    }

    if ($userId === $currentAdminUserId && $role !== 'admin') {
      admin_set_flash('danger', 'You cannot change your own admin role from this screen.');
      header('Location: manage-users.php');
      exit();
    }

    if ($userId === $currentAdminUserId && $status !== 'Active') {
      admin_set_flash('danger', 'You cannot deactivate your own active admin session account.');
      header('Location: manage-users.php');
      exit();
    }

    $duplicateSql = 'SELECT id FROM users WHERE (username = ?';
    $duplicateTypes = 's';
    $duplicateParams = [$username];

    if ($usersHasEmail && $email !== '') {
      $duplicateSql .= ' OR email = ?';
      $duplicateTypes .= 's';
      $duplicateParams[] = $email;
    }

    $duplicateSql .= ') AND id != ? LIMIT 1';
    $duplicateTypes .= 'i';
    $duplicateParams[] = $userId;

    $duplicateStmt = $connection->prepare($duplicateSql);
    $duplicateExists = false;

    if ($duplicateStmt && admin_bind_dynamic_params($duplicateStmt, $duplicateTypes, $duplicateParams)) {
      $duplicateStmt->execute();
      $duplicateResult = $duplicateStmt->get_result();
      $duplicateExists = $duplicateResult && $duplicateResult->num_rows > 0;
      $duplicateStmt->close();
    }

    if ($duplicateExists) {
      admin_set_flash('danger', 'Another user already uses this username or email.');
      header('Location: manage-users.php');
      exit();
    }

    $updateFields = ['username = ?', 'name = ?', 'role = ?'];
    $updateTypes = 'sss';
    $updateParams = [$username, $name, $role];

    if ($usersHasEmail) {
      $updateFields[] = 'email = ?';
      $updateTypes .= 's';
      $updateParams[] = ($email !== '') ? $email : null;
    }

    if ($usersHasPhone) {
      $updateFields[] = 'phone = ?';
      $updateTypes .= 's';
      $updateParams[] = ($phone !== '') ? $phone : null;
    }

    if ($usersHasStatus) {
      $updateFields[] = 'status = ?';
      $updateTypes .= 's';
      $updateParams[] = $status;
    }

    if ($newPassword !== '') {
      $updateFields[] = 'password = ?';
      $updateTypes .= 's';
      $updateParams[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if ($usersHasUpdatedAt) {
      $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
    }

    $updateSql = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ? LIMIT 1';
    $updateTypes .= 'i';
    $updateParams[] = $userId;

    $updateStmt = $connection->prepare($updateSql);

    if (!$updateStmt) {
      admin_set_flash('danger', 'Unable to process update right now.');
    } elseif (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
      admin_set_flash('danger', 'Unable to process update fields.');
      $updateStmt->close();
    } elseif ($updateStmt->execute()) {
      $updateStmt->close();

      if ($userId === $currentAdminUserId) {
        $_SESSION['name'] = $name;
        $_SESSION['admin_name'] = $name;
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $username;
      }

      $syncError = '';
      if (!admin_manage_sync_role_profile($connection, $userId, $role, $username, $name, $email, $phone, $status, $syncError)) {
        admin_set_flash('danger', 'User account updated, but profile sync failed. ' . $syncError);
      } else {
        admin_set_flash('success', 'User account updated successfully.');
      }
    } else {
      $updateError = trim((string) $updateStmt->error);
      admin_set_flash('danger', $updateError !== '' ? ('Unable to update user. ' . $updateError) : 'Unable to update user right now.');
      $updateStmt->close();
    }

    header('Location: manage-users.php');
    exit();
  }

  if ($action === 'change_role') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $newRole = admin_manage_normalize_role($_POST['role'] ?? 'student');

    if ($userId <= 0) {
      admin_set_flash('danger', 'Invalid user selected for role update.');
    } elseif (!in_array($newRole, $roleOptions, true)) {
      admin_set_flash('danger', 'Invalid role selected.');
    } elseif ($userId === $currentAdminUserId && $newRole !== 'admin') {
      admin_set_flash('danger', 'You cannot change your own admin role.');
    } else {
      $roleStmt = $connection->prepare('UPDATE users SET role = ? WHERE id = ? LIMIT 1');
      if ($roleStmt) {
        $roleStmt->bind_param('si', $newRole, $userId);
        if ($roleStmt->execute()) {
          $roleStmt->close();

          $userSnapshot = admin_manage_fetch_user_snapshot($connection, $userId, $usersHasEmail, $usersHasPhone, $usersHasStatus);
          if (!$userSnapshot) {
            admin_set_flash('danger', 'User role updated, but profile sync could not load user details.');
          } else {
            $syncError = '';
            if (!admin_manage_sync_role_profile(
              $connection,
              $userId,
              $newRole,
              (string) ($userSnapshot['username'] ?? ''),
              (string) ($userSnapshot['name'] ?? ''),
              (string) ($userSnapshot['email'] ?? ''),
              (string) ($userSnapshot['phone'] ?? ''),
              (string) ($userSnapshot['status'] ?? 'Active'),
              $syncError
            )) {
              admin_set_flash('danger', 'User role updated, but profile sync failed. ' . $syncError);
            } else {
              admin_set_flash('success', 'User role updated successfully.');
            }
          }
        } else {
          admin_set_flash('danger', 'Unable to update user role right now.');
          $roleStmt->close();
        }
      } else {
        admin_set_flash('danger', 'Unable to process role update request.');
      }
    }

    header('Location: manage-users.php');
    exit();
  }

  if ($action === 'toggle_status') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $targetStatus = admin_manage_normalize_status($_POST['target_status'] ?? 'Active');

    if (!$usersHasStatus) {
      admin_set_flash('danger', 'Status column is not available in users table.');
    } elseif ($userId <= 0) {
      admin_set_flash('danger', 'Invalid user selected for status update.');
    } elseif (!in_array($targetStatus, $statusOptions, true)) {
      admin_set_flash('danger', 'Invalid status selected.');
    } elseif ($userId === $currentAdminUserId && $targetStatus !== 'Active') {
      admin_set_flash('danger', 'You cannot deactivate your own admin session account.');
    } else {
      $statusStmt = $connection->prepare('UPDATE users SET status = ? WHERE id = ? LIMIT 1');
      if ($statusStmt) {
        $statusStmt->bind_param('si', $targetStatus, $userId);
        if ($statusStmt->execute()) {
          $statusStmt->close();

          $userSnapshot = admin_manage_fetch_user_snapshot($connection, $userId, $usersHasEmail, $usersHasPhone, $usersHasStatus);
          if (!$userSnapshot) {
            admin_set_flash('success', 'User status updated to ' . $targetStatus . '.');
          } else {
            $syncError = '';
            if (!admin_manage_sync_role_profile(
              $connection,
              $userId,
              (string) ($userSnapshot['role'] ?? ''),
              (string) ($userSnapshot['username'] ?? ''),
              (string) ($userSnapshot['name'] ?? ''),
              (string) ($userSnapshot['email'] ?? ''),
              (string) ($userSnapshot['phone'] ?? ''),
              $targetStatus,
              $syncError
            )) {
              admin_set_flash('danger', 'User status updated, but profile sync failed. ' . $syncError);
            } else {
              admin_set_flash('success', 'User status updated to ' . $targetStatus . '.');
            }
          }
        } else {
          admin_set_flash('danger', 'Unable to update user status right now.');
          $statusStmt->close();
        }
      } else {
        admin_set_flash('danger', 'Unable to process status update request.');
      }
    }

    header('Location: manage-users.php');
    exit();
  }

  if ($action === 'delete') {
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
      admin_set_flash('danger', 'Invalid user selected for deletion.');
      header('Location: manage-users.php');
      exit();
    }

    if ($userId === $currentAdminUserId) {
      admin_set_flash('danger', 'You cannot delete your own active admin account.');
      header('Location: manage-users.php');
      exit();
    }

    $deleteError = '';
    $transactionStarted = false;

    if ($connection->begin_transaction()) {
      $transactionStarted = true;
    }

    if ($deleteError === '' && admin_table_exists($connection, 'students') && admin_column_exists($connection, 'students', 'user_id')) {
      $studentRefStmt = $connection->prepare('UPDATE students SET user_id = NULL WHERE user_id = ?');
      if ($studentRefStmt) {
        $studentRefStmt->bind_param('i', $userId);
        if (!$studentRefStmt->execute()) {
          $deleteError = 'Unable to clear linked student profile references.';
        }
        $studentRefStmt->close();
      }
    }

    if ($deleteError === '' && admin_table_exists($connection, 'teachers') && admin_column_exists($connection, 'teachers', 'user_id')) {
      $teacherRefStmt = $connection->prepare('UPDATE teachers SET user_id = NULL WHERE user_id = ?');
      if ($teacherRefStmt) {
        $teacherRefStmt->bind_param('i', $userId);
        if (!$teacherRefStmt->execute()) {
          $deleteError = 'Unable to clear linked teacher profile references.';
        }
        $teacherRefStmt->close();
      }
    }

    if ($deleteError === '') {
      $deleteStmt = $connection->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
      if (!$deleteStmt) {
        $deleteError = 'Unable to process delete request right now.';
      } else {
        $deleteStmt->bind_param('i', $userId);
        if (!$deleteStmt->execute()) {
          $deleteError = 'Unable to delete user account right now.';
        } elseif ($deleteStmt->affected_rows < 1) {
          $deleteError = 'User record was not found.';
        }
        $deleteStmt->close();
      }
    }

    if ($deleteError === '' && $transactionStarted && !$connection->commit()) {
      $deleteError = 'Unable to finalize user deletion. Please try again.';
    }

    if ($deleteError !== '') {
      if ($transactionStarted) {
        $connection->rollback();
      }
      admin_set_flash('danger', $deleteError);
    } else {
      admin_set_flash('success', 'User account deleted successfully.');
    }

    header('Location: manage-users.php');
    exit();
  }
}

$search = trim((string) ($_GET['search'] ?? ''));
$users = [];

$selectColumns = [
  'id',
  'username',
  'name',
  'role',
  $usersHasEmail ? 'email' : "NULL AS email",
  $usersHasPhone ? 'phone' : "NULL AS phone",
  $usersHasStatus ? 'status' : "'Active' AS status",
  $usersHasCreatedAt ? 'created_at' : 'NULL AS created_at',
];

$userSql = 'SELECT ' . implode(', ', $selectColumns) . ' FROM users';

if ($search !== '') {
  $searchLike = '%' . $search . '%';
  $whereParts = ['username LIKE ?', 'name LIKE ?', 'role LIKE ?'];
  $searchTypes = 'sss';
  $searchParams = [$searchLike, $searchLike, $searchLike];

  if ($usersHasEmail) {
    $whereParts[] = 'email LIKE ?';
    $searchTypes .= 's';
    $searchParams[] = $searchLike;
  }

  if ($usersHasPhone) {
    $whereParts[] = 'phone LIKE ?';
    $searchTypes .= 's';
    $searchParams[] = $searchLike;
  }

  if ($usersHasStatus) {
    $whereParts[] = 'status LIKE ?';
    $searchTypes .= 's';
    $searchParams[] = $searchLike;
  }

  $userSql .= ' WHERE ' . implode(' OR ', $whereParts);
  $userSql .= ' ORDER BY id DESC';

  $searchStmt = $connection->prepare($userSql);
  if ($searchStmt && admin_bind_dynamic_params($searchStmt, $searchTypes, $searchParams)) {
    $searchStmt->execute();
    $searchResult = $searchStmt->get_result();
    if ($searchResult) {
      while ($userRow = $searchResult->fetch_assoc()) {
        $users[] = $userRow;
      }
    }
    $searchStmt->close();
  }
} else {
  $userSql .= ' ORDER BY id DESC';
  $listStmt = $connection->prepare($userSql);
  if ($listStmt) {
    $listStmt->execute();
    $userResult = $listStmt->get_result();
    if ($userResult) {
      while ($userRow = $userResult->fetch_assoc()) {
        $users[] = $userRow;
      }
    }
    $listStmt->close();
  }
}

$flash = admin_pull_flash();

$userSummary = [
  'total' => count($users),
  'admin' => 0,
  'teacher' => 0,
  'student' => 0,
  'active' => 0,
  'inactive' => 0,
];

foreach ($users as $summaryUser) {
  $summaryRole = admin_manage_normalize_role($summaryUser['role'] ?? 'student');
  if (isset($userSummary[$summaryRole])) {
    $userSummary[$summaryRole]++;
  }

  $summaryStatus = admin_manage_normalize_status($summaryUser['status'] ?? 'Active');
  if ($summaryStatus === 'Inactive') {
    $userSummary['inactive']++;
  } else {
    $userSummary['active']++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    :root {
      --surface: #ffffff;
      --surface-soft: #f8fafc;
      --text: #10223d;
      --text-muted: #5b6b83;
      --border: #dbe5f2;
      --brand: #1d4ed8;
      --brand-dark: #1e3a8a;
      --success: #0f766e;
      --danger: #b91c1c;
      --shadow: 0 20px 45px rgba(12, 25, 47, 0.08);
      --shadow-soft: 0 10px 24px rgba(12, 25, 47, 0.05);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Manrope', sans-serif;
      background:
        radial-gradient(circle at 2% 8%, rgba(29, 78, 216, 0.12), transparent 27%),
        radial-gradient(circle at 96% 2%, rgba(15, 118, 110, 0.12), transparent 24%),
        #eef3f9;
      color: var(--text);
    }

    .main-content {
      margin-left: 280px;
      padding: 30px;
    }

    .users-shell {
      max-width: 1320px;
      margin: 0 auto;
    }

    .hero-panel {
      border-radius: 20px;
      background: #2563eb;
      box-shadow: var(--shadow);
      padding: 26px 28px;
      color: #ffffff !important;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }

    .hero-panel h1,
    .hero-panel h2,
    .hero-panel h3,
    .hero-panel h4,
    .hero-panel p,
    .hero-panel span {
      color: #ffffff !important;
    }

    .hero-kicker {
      margin: 0 0 6px;
      font-size: 0.75rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      font-weight: 700;
      color: #ffffff !important;
    }

    .hero-title {
      margin: 0;
      font-size: 1.65rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #ffffff !important;
    }

    .hero-subtitle {
      margin: 6px 0 0;
      font-size: 0.92rem;
      color: #ffffff !important;
    }

    .btn-create-user {
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.55);
      font-weight: 700;
      border-radius: 12px;
      padding: 10px 16px;
      box-shadow: 0 12px 26px rgba(0, 0, 0, 0.14);
    }

    .btn-create-user:hover,
    .btn-create-user:focus {
      background: rgba(255, 255, 255, 0.28);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.72);
    }

    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
      gap: 12px;
      margin-bottom: 16px;
    }

    .metric-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 14px;
      box-shadow: var(--shadow-soft);
    }

    .metric-label {
      margin: 0;
      font-size: 0.76rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
    }

    .metric-value {
      margin: 6px 0 0;
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.02em;
    }

    .modern-alert {
      border-radius: 12px;
      border-left: 4px solid rgba(2, 132, 199, 0.55);
      box-shadow: var(--shadow-soft);
    }

    .content-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: var(--shadow);
      padding: 16px;
    }

    .table-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }

    .search-form {
      flex: 1;
      min-width: 260px;
      max-width: 460px;
    }

    .search-input-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 8px 12px;
      background: var(--surface-soft);
    }

    .search-input-wrap i {
      color: #7185a5;
      font-size: 0.9rem;
    }

    .search-input-wrap input {
      border: 0;
      background: transparent;
      width: 100%;
      outline: 0;
      color: var(--text);
      font-weight: 600;
      font-size: 0.9rem;
    }

    .toolbar-note {
      font-size: 0.8rem;
      color: var(--text-muted);
      font-weight: 600;
    }

    .table {
      margin-bottom: 0;
    }

    .table th {
      background: #f2f6fc;
      color: #1c3559;
      font-weight: 700;
      letter-spacing: 0.02em;
      border-bottom: 1px solid #d7e2f1;
      white-space: nowrap;
      font-size: 0.79rem;
      text-transform: uppercase;
      padding: 12px;
    }

    .table td {
      padding: 12px;
      border-color: #e5edf7;
      font-size: 0.89rem;
      color: #273b5a;
      vertical-align: middle;
    }

    .table tbody tr {
      transition: background 0.2s ease;
    }

    .table tbody tr:hover {
      background: #f7faff;
    }

    .user-code {
      font-weight: 800;
      color: #1f3d66;
      letter-spacing: 0.02em;
    }

    .role-chip {
      text-transform: capitalize;
      font-weight: 700;
    }

    .role-select {
      min-width: 130px;
      border-radius: 10px;
      border-color: #cfdbec;
      font-size: 0.82rem;
      font-weight: 600;
    }

    .status-badge {
      padding: 5px 10px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      line-height: 1;
    }

    .status-active {
      background: #dcfce7;
      color: #166534;
    }

    .status-inactive {
      background: #fee2e2;
      color: #991b1b;
    }

    .quick-role-form {
      min-width: 190px;
    }

    .action-stack {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .btn-icon {
      border-radius: 10px;
      min-width: 35px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-outline-danger-soft {
      color: var(--danger);
      border: 1px solid #f5c2c7;
      background: #fff5f5;
    }

    .btn-outline-danger-soft:hover,
    .btn-outline-danger-soft:focus {
      background: #ef4444;
      border-color: #ef4444;
      color: #ffffff;
    }

    .btn-outline-success-soft {
      color: var(--success);
      border: 1px solid #99f6e4;
      background: #f0fdfa;
    }

    .btn-outline-success-soft:hover,
    .btn-outline-success-soft:focus {
      background: #0f766e;
      border-color: #0f766e;
      color: #ffffff;
    }

    .modal-content {
      border: 0;
      border-radius: 16px;
      box-shadow: 0 22px 44px rgba(15, 23, 42, 0.2);
      overflow: hidden;
    }

    .modal-header {
      background: #f8fbff;
      border-bottom: 1px solid var(--border);
    }

    .modal-title {
      font-weight: 800;
      color: #17335d;
    }

    .modal-footer {
      border-top: 1px solid var(--border);
      background: #f8fbff;
    }

    .form-control,
    .form-select {
      border-radius: 10px;
      border-color: #cfdbec;
      font-size: 0.9rem;
      font-weight: 600;
      color: #203a5f;
      box-shadow: none;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #1d4ed8;
      box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.14);
    }

    @media (max-width: 991px) {
      .main-content {
        margin-left: 0;
        padding: 84px 18px 18px;
      }

      .hero-panel {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="users-shell">
      <div class="hero-panel">
        <div>
          <p class="hero-kicker" style="color: #ffffff !important;">Access Management</p>
          <h2 class="hero-title" style="color: #ffffff !important;">Manage Users</h2>
          <p class="hero-subtitle" style="color: #ffffff !important;">Create accounts, control user status, and manage role permissions securely.</p>
        </div>
        <button class="btn btn-create-user" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="fas fa-user-plus"></i> Add New User
        </button>
      </div>

      <div class="metrics-grid">
        <div class="metric-card">
          <p class="metric-label">Total Users</p>
          <p class="metric-value"><?php echo (int) $userSummary['total']; ?></p>
        </div>
        <div class="metric-card">
          <p class="metric-label">Admins</p>
          <p class="metric-value"><?php echo (int) $userSummary['admin']; ?></p>
        </div>
        <div class="metric-card">
          <p class="metric-label">Teachers</p>
          <p class="metric-value"><?php echo (int) $userSummary['teacher']; ?></p>
        </div>
        <div class="metric-card">
          <p class="metric-label">Students</p>
          <p class="metric-value"><?php echo (int) $userSummary['student']; ?></p>
        </div>
        <div class="metric-card">
          <p class="metric-label">Active Accounts</p>
          <p class="metric-value"><?php echo (int) $userSummary['active']; ?></p>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert modern-alert alert-<?php echo htmlspecialchars((string) $flash['type']); ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars((string) $flash['message']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="content-card">
        <div class="table-toolbar">
          <form method="GET" action="" class="search-form" novalidate>
            <div class="search-input-wrap">
              <i class="fas fa-search" aria-hidden="true"></i>
              <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by username, name, role, email, phone, or status">
            </div>
          </form>
          <div class="toolbar-note">Quickly update role and status from each user row.</div>
        </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($users)): ?>
              <?php foreach ($users as $user): ?>
                <?php
                  $userId = (int) ($user['id'] ?? 0);
                  $role = admin_manage_normalize_role($user['role'] ?? 'student');
                  $status = admin_manage_normalize_status($user['status'] ?? 'Active');
                  $isCurrentAdmin = $userId === $currentAdminUserId;
                  $createdAt = trim((string) ($user['created_at'] ?? ''));
                  $createdAtLabel = $createdAt !== '' ? date('d M Y', strtotime($createdAt)) : '-';
                  $statusClass = $status === 'Active' ? 'status-active' : 'status-inactive';
                  $statusToggleTarget = $status === 'Active' ? 'Inactive' : 'Active';
                ?>
                <tr>
                  <td class="user-code"><?php echo 'USR' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT); ?></td>
                  <td><?php echo htmlspecialchars((string) ($user['name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($user['username'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($user['email'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($user['phone'] ?? '-')); ?></td>
                  <td>
                    <form method="POST" class="quick-role-form d-flex gap-2" novalidate>
                      <input type="hidden" name="action" value="change_role">
                      <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                      <select class="form-select form-select-sm role-select" name="role" <?php echo $isCurrentAdmin ? 'disabled' : ''; ?>>
                        <?php foreach ($roleOptions as $roleOption): ?>
                          <option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $role === $roleOption ? 'selected' : ''; ?>>
                            <?php echo ucfirst($roleOption); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <?php if (!$isCurrentAdmin): ?>
                        <button type="submit" class="btn btn-sm btn-outline-primary btn-icon" title="Save role">
                          <i class="fas fa-check"></i>
                        </button>
                      <?php else: ?>
                        <span class="badge bg-info role-chip">Current Admin</span>
                      <?php endif; ?>
                    </form>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                      <?php if ($usersHasStatus): ?>
                        <form method="POST" novalidate>
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                          <input type="hidden" name="target_status" value="<?php echo htmlspecialchars($statusToggleTarget); ?>">
                          <button
                            type="submit"
                            class="btn btn-sm <?php echo $status === 'Active' ? 'btn-outline-danger-soft' : 'btn-outline-success-soft'; ?>"
                            <?php echo ($isCurrentAdmin && $statusToggleTarget === 'Inactive') ? 'disabled title="Current admin cannot be deactivated"' : ''; ?>>
                            <?php echo $status === 'Active' ? 'Set Inactive' : 'Set Active'; ?>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($createdAtLabel); ?></td>
                  <td>
                    <div class="action-stack">
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-primary btn-icon edit-user-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal"
                        data-user-id="<?php echo $userId; ?>"
                        data-username="<?php echo htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES); ?>"
                        data-name="<?php echo htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES); ?>"
                        data-email="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES); ?>"
                        data-phone="<?php echo htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES); ?>"
                        data-role="<?php echo htmlspecialchars($role, ENT_QUOTES); ?>"
                        data-status="<?php echo htmlspecialchars($status, ENT_QUOTES); ?>"
                        data-is-current-admin="<?php echo $isCurrentAdmin ? '1' : '0'; ?>">
                        <i class="fas fa-edit"></i>
                      </button>

                      <form method="POST" onsubmit="return confirm('Delete this user account? This cannot be undone.');" novalidate>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" <?php echo $isCurrentAdmin ? 'disabled title="Current admin cannot be deleted"' : ''; ?>>
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No users found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  </div>

  <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" novalidate>
          <div class="modal-header">
            <h5 class="modal-title">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="create">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Username *</label>
                <input type="text" class="form-control" name="username" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Role *</label>
                <select class="form-select" name="role" required>
                  <?php foreach ($roleOptions as $roleOption): ?>
                    <option value="<?php echo htmlspecialchars($roleOption); ?>"><?php echo ucfirst($roleOption); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Status *</label>
                <select class="form-select" name="status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone">
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
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" novalidate>
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div id="edit-lock-hint" class="alert alert-info d-none">
              This is your current admin session account. Role and status cannot be changed here.
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Username *</label>
                <input type="text" class="form-control" id="edit_username" name="username" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" class="form-control" id="edit_name" name="name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Role *</label>
                <select class="form-select" id="edit_role" name="role" required>
                  <?php foreach ($roleOptions as $roleOption): ?>
                    <option value="<?php echo htmlspecialchars($roleOption); ?>"><?php echo ucfirst($roleOption); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Status *</label>
                <select class="form-select" id="edit_status" name="status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="edit_email" name="email">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" id="edit_phone" name="phone">
              </div>
              <div class="col-md-6">
                <label class="form-label">New Password (optional)</label>
                <input type="password" class="form-control" id="edit_new_password" name="new_password">
              </div>
              <div class="col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="edit_confirm_new_password" name="confirm_new_password">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      const editButtons = document.querySelectorAll('.edit-user-btn');
      const editUserId = document.getElementById('edit_user_id');
      const editUsername = document.getElementById('edit_username');
      const editName = document.getElementById('edit_name');
      const editEmail = document.getElementById('edit_email');
      const editPhone = document.getElementById('edit_phone');
      const editRole = document.getElementById('edit_role');
      const editStatus = document.getElementById('edit_status');
      const editLockHint = document.getElementById('edit-lock-hint');
      const editNewPassword = document.getElementById('edit_new_password');
      const editConfirmPassword = document.getElementById('edit_confirm_new_password');

      editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          const isCurrentAdmin = String(button.getAttribute('data-is-current-admin')) === '1';

          editUserId.value = button.getAttribute('data-user-id') || '';
          editUsername.value = button.getAttribute('data-username') || '';
          editName.value = button.getAttribute('data-name') || '';
          editEmail.value = button.getAttribute('data-email') || '';
          editPhone.value = button.getAttribute('data-phone') || '';
          editRole.value = button.getAttribute('data-role') || 'student';
          editStatus.value = button.getAttribute('data-status') || 'Active';

          editRole.disabled = isCurrentAdmin;
          editStatus.disabled = isCurrentAdmin;
          editLockHint.classList.toggle('d-none', !isCurrentAdmin);

          editNewPassword.value = '';
          editConfirmPassword.value = '';
        });
      });
    })();
  </script>
</body>
</html>

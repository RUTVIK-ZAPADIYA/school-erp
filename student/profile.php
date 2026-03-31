<?php
require_once __DIR__ . '/auth.php';

$studentContext = student_auth_context();
$studentProfileId = (int) ($studentContext['student_id'] ?? 0);
$studentUserId = (int) ($studentContext['user_id'] ?? 0);
$studentSessionName = (string) ($studentContext['student_name'] ?? 'Student');

function table_exists($conn, $tableName)
{
  $safeTable = $conn->real_escape_string( $tableName);
  $result = $conn->query( "SHOW TABLES LIKE '{$safeTable}'");

  return $result && $result->num_rows > 0;
}

function column_exists($conn, $tableName, $columnName)
{
  if (!table_exists($conn, $tableName)) {
    return false;
  }

  $safeTable = $conn->real_escape_string( $tableName);
  $safeColumn = $conn->real_escape_string( $columnName);
  $result = $conn->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

  return $result && $result->num_rows > 0;
}

function fetch_one_row($conn, $sql, $types = '', array $params = [])
{
  $stmt = $conn->prepare( $sql);
  if (!$stmt) {
    return null;
  }

  if ($types !== '') {
    $bindArgs = [$types];
    foreach ($params as $index => &$value) {
      $bindArgs[] = &$value;
    }
    if (!call_user_func_array([$stmt, 'bind_param'], $bindArgs)) {
      $stmt->close();
      return null;
    }
  }

  if (!$stmt->execute()) {
    $stmt->close();
    return null;
  }

  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();

  return $row ?: null;
}

function first_non_empty_value(array $row, array $keys, $defaultValue = '')
{
  foreach ($keys as $key) {
    if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
      return $row[$key];
    }
  }

  return $defaultValue;
}

// Default profile payload, then enrich from users/students tables as available.
$profile = [
  'name' => $studentSessionName,
  'student_id' => $studentUserId,
  'email' => '',
  'phone' => '',
  'dob' => '',
  'gender' => '',
  'address' => '',
  'class' => '',
  'section' => '',
  'roll_number' => '',
  'admission_date' => '',
  'academic_year' => ''
];

$userAccount = null;
if (table_exists($conn, 'users')) {
  $userAccount = fetch_one_row(
    $conn,
    "SELECT id, name, email, phone FROM users WHERE id = ? AND role = 'student' LIMIT 1",
    'i',
    [$studentUserId]
  );
}

if ($userAccount) {
  $profile['name'] = (string) first_non_empty_value($userAccount, ['name'], $profile['name']);
  $profile['email'] = (string) first_non_empty_value($userAccount, ['email'], $profile['email']);
  $profile['phone'] = (string) first_non_empty_value($userAccount, ['phone'], $profile['phone']);
}

$studentRow = null;
if (table_exists($conn, 'students')) {
  if ($studentProfileId > 0 && column_exists($conn, 'students', 'id')) {
    $studentRow = fetch_one_row($conn, 'SELECT * FROM students WHERE id = ? LIMIT 1', 'i', [$studentProfileId]);
  }

  if (column_exists($conn, 'students', 'user_id')) {
    if (!$studentRow && $studentUserId > 0) {
      $studentRow = fetch_one_row($conn, 'SELECT * FROM students WHERE user_id = ? LIMIT 1', 'i', [$studentUserId]);
    }
  }

  if (!$studentRow && $studentUserId > 0 && column_exists($conn, 'students', 'id')) {
    $studentRow = fetch_one_row($conn, 'SELECT * FROM students WHERE id = ? LIMIT 1', 'i', [$studentUserId]);
  }

  if (!$studentRow && !empty($profile['email']) && column_exists($conn, 'students', 'email')) {
    $studentRow = fetch_one_row($conn, 'SELECT * FROM students WHERE email = ? LIMIT 1', 's', [$profile['email']]);
  }

  if (!$studentRow && !empty($profile['name']) && column_exists($conn, 'students', 'name')) {
    $studentRow = fetch_one_row($conn, 'SELECT * FROM students WHERE name = ? ORDER BY id DESC LIMIT 1', 's', [$profile['name']]);
  }
}

if ($studentRow) {
  $profile['name'] = (string) first_non_empty_value($studentRow, ['name'], $profile['name']);
  $profile['student_id'] = (string) first_non_empty_value($studentRow, ['id', 'student_id'], (string) $profile['student_id']);
  $profile['email'] = (string) first_non_empty_value($studentRow, ['email'], $profile['email']);
  $profile['phone'] = (string) first_non_empty_value($studentRow, ['phone'], $profile['phone']);
  $profile['dob'] = (string) first_non_empty_value($studentRow, ['date_of_birth', 'dob'], $profile['dob']);
  $profile['gender'] = (string) first_non_empty_value($studentRow, ['gender'], $profile['gender']);
  $profile['address'] = (string) first_non_empty_value($studentRow, ['address'], $profile['address']);
  $profile['class'] = (string) first_non_empty_value($studentRow, ['class', 'class_name'], $profile['class']);
  $profile['section'] = (string) first_non_empty_value($studentRow, ['section'], $profile['section']);
  $profile['roll_number'] = (string) first_non_empty_value($studentRow, ['roll_no', 'roll_number'], $profile['roll_number']);
  $profile['admission_date'] = (string) first_non_empty_value($studentRow, ['admission_date', 'created_at'], $profile['admission_date']);
  $profile['academic_year'] = (string) first_non_empty_value($studentRow, ['academic_year'], $profile['academic_year']);

  $classId = (int) first_non_empty_value($studentRow, ['class_id'], 0);
  if ($classId > 0 && table_exists($conn, 'classes')) {
    $classNameColumn = column_exists($conn, 'classes', 'name') ? 'name' : (column_exists($conn, 'classes', 'class_name') ? 'class_name' : null);
    $selectParts = [];
    if ($classNameColumn !== null) {
      $selectParts[] = "{$classNameColumn} AS class_name";
    }
    if (column_exists($conn, 'classes', 'section')) {
      $selectParts[] = 'section';
    }

    if (!empty($selectParts)) {
      $classRow = fetch_one_row($conn, 'SELECT ' . implode(', ', $selectParts) . ' FROM classes WHERE id = ? LIMIT 1', 'i', [$classId]);
      if ($classRow) {
        $profile['class'] = (string) first_non_empty_value($classRow, ['class_name'], $profile['class']);
        $profile['section'] = (string) first_non_empty_value($classRow, ['section'], $profile['section']);
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-amber-500" style="font-variation-settings: 'FILL' 1;">account_circle</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">My Profile</h1>
        <p class="text-sm text-stone-500">View and manage your profile information</p>
      </div>
    </div>

    <!-- Profile Card -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 mb-8">
      <!-- Profile Header -->
      <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-8 border-b border-stone-200">
        <div class="flex flex-col items-center">
          <div class="w-24 h-24 bg-amber-200 rounded-full flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-5xl text-amber-700">person</span>
          </div>
          <h2 class="text-2xl font-bold text-stone-900"><?php echo htmlspecialchars($profile['name']); ?></h2>
          <p class="text-stone-500 mt-1">Student ID: <?php echo htmlspecialchars($profile['student_id']); ?></p>
        </div>
      </div>

      <!-- Profile Content -->
      <div class="p-8">
        <!-- Personal Information Section -->
        <div class="mb-8">
          <h3 class="text-lg font-bold text-stone-900 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">person_outline</span>
            Personal Information
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Full Name</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['name']); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Email</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['email'] ?: 'Not provided'); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Phone Number</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['phone'] ?: 'Not provided'); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Date of Birth</p>
              <p class="text-base font-medium text-stone-900"><?php echo $profile['dob'] ? date('d M Y', strtotime($profile['dob'])) : 'Not provided'; ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Gender</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['gender'] ?: 'Not provided'); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Address</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['address'] ?: 'Not provided'); ?></p>
            </div>
          </div>
        </div>

        <!-- Academic Information Section -->
        <div class="mb-8">
          <h3 class="text-lg font-bold text-stone-900 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">school</span>
            Academic Information
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Class</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['class'] ?: 'Not provided'); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Section</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['section'] ?: 'Not provided'); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Roll Number</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['roll_number'] ?: 'Not provided'); ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Admission Date</p>
              <p class="text-base font-medium text-stone-900"><?php echo $profile['admission_date'] ? date('d M Y', strtotime($profile['admission_date'])) : 'Not provided'; ?></p>
            </div>
            <div class="border-b border-stone-200 pb-4">
              <p class="text-sm text-stone-500 mb-1">Academic Year</p>
              <p class="text-base font-medium text-stone-900"><?php echo htmlspecialchars($profile['academic_year'] ?: 'Not provided'); ?></p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-6 border-t border-stone-200">
          <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 flex-shrink-0 mt-0.5">info</span>
            <div>
              <p class="font-semibold text-blue-900">Profile Information</p>
              <p class="text-sm text-blue-700 mt-1">Your profile information is managed by the school administration. To request any changes to your profile, please <a href="contact-admin.php" class="underline font-semibold hover:text-blue-900">contact the admin</a>.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>

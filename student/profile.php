<?php
session_start();
if ((!isset($_SESSION['student_id']) || !isset($_SESSION['student_name'])) && isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student') {
  $_SESSION['student_id'] = (int) $_SESSION['user_id'];
  $_SESSION['student_name'] = $_SESSION['name'] ?? 'Student';
}
if (!isset($_SESSION['student_id'])) {
  header("Location: ../login.php");
  exit();
}

// Include database connection
include '../includes/db_connect.php';

$student_id = $_SESSION['student_id'];

// Get student profile information
$profile = [
    'name' => $_SESSION['student_name'],
    'student_id' => $student_id,
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

try {
    $sql = "SELECT name, email, phone, date_of_birth, gender, address, class, section, roll_number, admission_date, academic_year FROM students WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $profile = [
                'name' => $row['name'] ?? $_SESSION['student_name'],
                'student_id' => $student_id,
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
                'dob' => $row['date_of_birth'] ?? '',
                'gender' => $row['gender'] ?? '',
                'address' => $row['address'] ?? '',
                'class' => $row['class'] ?? '',
                'section' => $row['section'] ?? '',
                'roll_number' => $row['roll_number'] ?? '',
                'admission_date' => $row['admission_date'] ?? '',
                'academic_year' => $row['academic_year'] ?? ''
            ];
        }
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
    error_log("Profile query error: " . $e->getMessage());
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

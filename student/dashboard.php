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

// Get student info
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['name'];

// Get dashboard statistics with error handling
$stats = [
    'attendance' => 0,
    'average_marks' => 0,
    'pending_fees' => 0,
    'leave_applications' => 0
];

try {
    // Get attendance percentage
    $sql_attendance = "SELECT COUNT(*) as total FROM attendance WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $sql_attendance);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $stats['attendance'] = $row['total'] ?? 0;
        mysqli_stmt_close($stmt);
    }

    // Get average marks
    $sql_marks = "SELECT AVG(marks) as avg_marks FROM marks WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $sql_marks);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $stats['average_marks'] = round($row['avg_marks'] ?? 0, 2);
        mysqli_stmt_close($stmt);
    }

    // Get pending fees
    $sql_fees = "SELECT SUM(amount) as total_fees FROM fees WHERE student_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $sql_fees);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $stats['pending_fees'] = round($row['total_fees'] ?? 0, 2);
        mysqli_stmt_close($stmt);
    }

    // Get leave applications
    $sql_leave = "SELECT COUNT(*) as count FROM leave_applications WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $sql_leave);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $stats['leave_applications'] = $row['count'] ?? 0;
        mysqli_stmt_close($stmt);
    }

} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <style>
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', sans-serif; }
  </style>
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-primary">dashboard</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Dashboard</h1>
        <p class="text-sm text-stone-500">Welcome back, <?php echo htmlspecialchars($student_name); ?></p>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Attendance Card -->
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <div class="flex items-center justify-between mb-4">
          <span class="material-symbols-outlined text-3xl text-blue-500" style="font-variation-settings: 'FILL' 1;">check_circle</span>
          <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">Recent</span>
        </div>
        <p class="text-sm text-stone-500 mb-1">Attendance</p>
        <p class="text-2xl font-bold text-stone-900"><?php echo htmlspecialchars($stats['attendance']); ?></p>
        <p class="text-xs text-stone-400 mt-2">Sessions attended</p>
      </div>

      <!-- Average Marks Card -->
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <div class="flex items-center justify-between mb-4">
          <span class="material-symbols-outlined text-3xl text-emerald-500">analytics</span>
          <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">Track</span>
        </div>
        <p class="text-sm text-stone-500 mb-1">Average Marks</p>
        <p class="text-2xl font-bold text-stone-900"><?php echo htmlspecialchars($stats['average_marks']); ?></p>
        <p class="text-xs text-stone-400 mt-2">Overall performance</p>
      </div>

      <!-- Pending Fees Card -->
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <div class="flex items-center justify-between mb-4">
          <span class="material-symbols-outlined text-3xl text-amber-500">account_balance_wallet</span>
          <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">Action</span>
        </div>
        <p class="text-sm text-stone-500 mb-1">Pending Fees</p>
        <p class="text-2xl font-bold text-stone-900">₹<?php echo htmlspecialchars(number_format($stats['pending_fees'], 2)); ?></p>
        <p class="text-xs text-stone-400 mt-2">Amount due</p>
      </div>

      <!-- Leave Applications Card -->
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <div class="flex items-center justify-between mb-4">
          <span class="material-symbols-outlined text-3xl text-violet-500">assignment_ind</span>
          <span class="text-xs font-semibold text-violet-600 bg-violet-50 px-3 py-1 rounded-full">Pending</span>
        </div>
        <p class="text-sm text-stone-500 mb-1">Leave Applications</p>
        <p class="text-2xl font-bold text-stone-900"><?php echo htmlspecialchars($stats['leave_applications']); ?></p>
        <p class="text-xs text-stone-400 mt-2">Submitted</p>
      </div>
    </div>

    <!-- Content Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Quick Actions -->
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined">quick_reference</span>
          Quick Actions
        </h2>
        <div class="space-y-2">
          <a href="attendance.php" class="block p-3 rounded-lg hover:bg-blue-50 text-stone-700 hover:text-blue-600 transition">
            <span class="font-medium">→ View Attendance Records</span>
          </a>
          <a href="marks.php" class="block p-3 rounded-lg hover:bg-emerald-50 text-stone-700 hover:text-emerald-600 transition">
            <span class="font-medium">→ Check Your Marks</span>
          </a>
          <a href="fees.php" class="block p-3 rounded-lg hover:bg-amber-50 text-stone-700 hover:text-amber-600 transition">
            <span class="font-medium">→ View Fee Status</span>
          </a>
          <a href="leave.php" class="block p-3 rounded-lg hover:bg-violet-50 text-stone-700 hover:text-violet-600 transition">
            <span class="font-medium">→ Apply for Leave</span>
          </a>
          <a href="profile.php" class="block p-3 rounded-lg hover:bg-stone-100 text-stone-700 hover:text-stone-900 transition">
            <span class="font-medium">→ Edit Profile</span>
          </a>
        </div>
      </div>

      <!-- Important Info -->
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined">info</span>
          Important Information
        </h2>
        <div class="space-y-3">
          <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm font-medium text-blue-900">📋 Academic Calendar</p>
            <p class="text-xs text-blue-700 mt-1">Check important dates and events</p>
          </div>
          <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
            <p class="text-sm font-medium text-emerald-900">🎯 Performance Tracking</p>
            <p class="text-xs text-emerald-700 mt-1">Monitor your academic progress</p>
          </div>
          <div class="p-3 bg-amber-50 rounded-lg border border-amber-200">
            <p class="text-sm font-medium text-amber-900">💳 Fee Payment</p>
            <p class="text-xs text-amber-700 mt-1">Complete pending payments on time</p>
          </div>
          <div class="p-3 bg-violet-50 rounded-lg border border-violet-200">
            <p class="text-sm font-medium text-violet-900">📑 Leave Requests</p>
            <p class="text-xs text-violet-700 mt-1">Submit leave applications in advance</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Mobile menu toggle if needed
    function toggleSidebar() {
      const sidebar = document.querySelector('aside');
      sidebar?.classList.toggle('active');
    }
  </script>
</body>
</html>

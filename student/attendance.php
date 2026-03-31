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

// Get attendance records
$attendance_records = [];
try {
    $sql = "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 30";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $attendance_records[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
    error_log("Attendance query error: " . $e->getMessage());
}

// Calculate attendance percentage
$total = count($attendance_records);
$present = 0;
foreach ($attendance_records as $record) {
    if ($record['status'] === 'present') $present++;
}
$percentage = $total > 0 ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-blue-500" style="font-variation-settings: 'FILL' 1;">check_circle</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Attendance</h1>
        <p class="text-sm text-stone-500">View your attendance records</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Overall Attendance</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo $percentage; ?>%</p>
        <p class="text-xs text-stone-400 mt-2"><?php echo $present; ?> present out of <?php echo $total; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Days Present</p>
        <p class="text-3xl font-bold text-emerald-600"><?php echo $present; ?></p>
        <p class="text-xs text-stone-400 mt-2">Attended sessions</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Days Absent</p>
        <p class="text-3xl font-bold text-red-600"><?php echo $total - $present; ?></p>
        <p class="text-xs text-stone-400 mt-2">Missed sessions</p>
      </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">table_chart</span>
          Recent Attendance
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Date</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (count($attendance_records) > 0): ?>
              <?php foreach ($attendance_records as $record): ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                  <td class="px-6 py-4">
                    <?php if ($record['status'] === 'present'): ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">done</span>
                        Present
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">close</span>
                        Absent
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="2" class="px-6 py-8 text-center text-stone-500">No attendance records found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>

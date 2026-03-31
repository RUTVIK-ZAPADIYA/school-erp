<?php
// Include auth guard
require_once __DIR__ . '/auth.php';

// Resolve student context
$studentContext = student_auth_context();
$student_id = (int) ($studentContext['student_id'] ?? 0);
// Build leave filter
$studentFilter = student_auth_student_id_filter_sql('student_id');

// Get leave records
$leave_records = [];

try {
  // Query leave history
  $sql = "SELECT application_id, leave_type, from_date, to_date, days, status FROM leave_applications WHERE {$studentFilter['sql']} ORDER BY from_date DESC LIMIT 10";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
    // Bind leave filter
    $filterParams = $studentFilter['params'];
    if (student_auth_bind_dynamic_params($stmt, $studentFilter['types'], $filterParams)) {
      $stmt->execute();
      $result = $stmt->get_result();
      // Collect leave rows
      while ($row = $result->fetch_assoc()) {
        $leave_records[] = $row;
      }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Leave query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Application - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-orange-500" style="font-variation-settings: 'FILL' 1;">event_note</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Leave Application</h1>
        <p class="text-sm text-stone-500">Apply for leave and view history</p>
      </div>
    </div>

    <!-- Leave Application Form -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 mb-8">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">edit_calendar</span>
          Apply for Leave
        </h2>
      </div>
      <div class="p-6">
        <form method="POST" action="" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">Leave Type</label>
              <select name="leave_type" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
                <option value="">Select Type</option>
                <option value="sick">Sick Leave</option>
                <option value="casual">Casual Leave</option>
                <option value="emergency">Emergency Leave</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">Number of Days</label>
              <input type="number" name="days" min="1" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">From Date</label>
              <input type="date" name="from_date" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">To Date</label>
              <input type="date" name="to_date" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-stone-900 mb-2">Reason</label>
            <textarea name="reason" rows="4" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required></textarea>
          </div>

          <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition">
            <span class="material-symbols-outlined">send</span>
            Submit Application
          </button>
        </form>
      </div>
    </div>

    <!-- Leave Application History -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">history</span>
          Leave Application History
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Application ID</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Leave Type</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">From Date</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">To Date</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Days</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Status</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (count($leave_records) > 0): ?>
              <?php foreach ($leave_records as $record): ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-stone-900"><?php echo htmlspecialchars($record['application_id']); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo ucfirst(htmlspecialchars($record['leave_type'])); ?> Leave</td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo date('d M Y', strtotime($record['from_date'])); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo date('d M Y', strtotime($record['to_date'])); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo $record['days']; ?></td>
                  <td class="px-6 py-4">
                    <?php if ($record['status'] === 'approved'): ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">done</span>
                        Approved
                      </span>
                    <?php elseif ($record['status'] === 'rejected'): ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">close</span>
                        Rejected
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        Pending
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4">
                    <button class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition">
                      <span class="material-symbols-outlined text-sm">visibility</span>
                      View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-8 text-center text-stone-500">No leave applications found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>

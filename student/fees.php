<?php
require_once __DIR__ . '/auth.php';

$studentContext = student_auth_context();
$student_id = (int) ($studentContext['student_id'] ?? 0);
$studentFilter = student_auth_student_id_filter_sql('student_id');

// Get fee records
$fee_records = [];
$total_fees = 0;
$paid_amount = 0;

try {
  $sql = "SELECT receipt_no, date, description, amount, status FROM fees WHERE {$studentFilter['sql']} ORDER BY date DESC LIMIT 10";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
    $filterParams = $studentFilter['params'];
    if (student_auth_bind_dynamic_params($stmt, $studentFilter['types'], $filterParams)) {
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $fee_records[] = $row;
        $total_fees += $row['amount'];
        if (strtolower((string) ($row['status'] ?? '')) === 'paid') {
          $paid_amount += $row['amount'];
        }
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Fees query error: " . $e->getMessage());
}

$pending_amount = $total_fees - $paid_amount;
$payment_percentage = $total_fees > 0 ? round(($paid_amount / $total_fees) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fees - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-emerald-500" style="font-variation-settings: 'FILL' 1;">payments</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Fees Management</h1>
        <p class="text-sm text-stone-500">Track your fee payments</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Total Fees</p>
        <p class="text-3xl font-bold text-stone-900">₹<?php echo number_format($total_fees, 2); ?></p>
        <p class="text-xs text-stone-400 mt-2">Overall amount due</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Paid Amount</p>
        <p class="text-3xl font-bold text-emerald-600">₹<?php echo number_format($paid_amount, 2); ?></p>
        <p class="text-xs text-stone-400 mt-2">Amount paid</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Pending Amount</p>
        <p class="text-3xl font-bold text-red-600">₹<?php echo number_format($pending_amount, 2); ?></p>
        <p class="text-xs text-stone-400 mt-2">Amount due</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Payment Status</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo $payment_percentage; ?>%</p>
        <p class="text-xs text-stone-400 mt-2">Completion</p>
      </div>
    </div>

    <!-- Fee Payment History -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">receipt_long</span>
          Fee Payment History
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Receipt No</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Date</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Description</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Amount</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Status</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (count($fee_records) > 0): ?>
              <?php foreach ($fee_records as $record): ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-stone-900"><?php echo htmlspecialchars($record['receipt_no']); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars($record['description']); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700">₹<?php echo number_format($record['amount'], 2); ?></td>
                  <td class="px-6 py-4">
                    <?php if (strtolower((string) ($record['status'] ?? '')) === 'paid'): ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">done</span>
                        Paid
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        Pending
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4">
                    <?php if (strtolower((string) ($record['status'] ?? '')) === 'paid'): ?>
                      <button class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Receipt
                      </button>
                    <?php else: ?>
                      <button class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-emerald-600 hover:bg-emerald-50 rounded transition">
                        <span class="material-symbols-outlined text-sm">credit_card</span>
                        Pay Now
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-stone-500">No fee records found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>

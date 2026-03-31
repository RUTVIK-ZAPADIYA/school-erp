<?php
// Include auth guard
require_once __DIR__ . '/auth.php';

// Resolve student context
$studentContext = student_auth_context();
$student_id = (int) ($studentContext['student_id'] ?? 0);
<<<<<<< Updated upstream
// Build leave filter
$studentFilter = student_auth_student_id_filter_sql('student_id');
=======
$student_user_id = (int) ($studentContext['user_id'] ?? 0);
$leaveFilter = student_auth_link_filter_sql($conn, 'leave_applications');

$allowedLeaveTypes = ['sick', 'casual', 'emergency', 'other'];
$successMessage = '';
$errorMessage = '';
$formValues = [
  'leave_type' => '',
  'days' => '',
  'from_date' => '',
  'to_date' => '',
  'reason' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $formValues['leave_type'] = strtolower(trim((string) ($_POST['leave_type'] ?? '')));
  $formValues['days'] = (int) ($_POST['days'] ?? 0);
  $formValues['from_date'] = trim((string) ($_POST['from_date'] ?? ''));
  $formValues['to_date'] = trim((string) ($_POST['to_date'] ?? ''));
  $formValues['reason'] = trim((string) ($_POST['reason'] ?? ''));

  if (!in_array($formValues['leave_type'], $allowedLeaveTypes, true)) {
    $errorMessage = 'Please select a valid leave type.';
  }

  if ($errorMessage === '' && $formValues['reason'] === '') {
    $errorMessage = 'Please provide a reason for leave.';
  }

  $fromDate = null;
  $toDate = null;
  if ($errorMessage === '') {
    $fromDate = DateTime::createFromFormat('Y-m-d', $formValues['from_date']);
    $toDate = DateTime::createFromFormat('Y-m-d', $formValues['to_date']);

    if (
      !$fromDate
      || !$toDate
      || $fromDate->format('Y-m-d') !== $formValues['from_date']
      || $toDate->format('Y-m-d') !== $formValues['to_date']
    ) {
      $errorMessage = 'Please select valid leave dates.';
    }
  }

  if ($errorMessage === '' && $fromDate instanceof DateTime && $toDate instanceof DateTime) {
    if ($toDate < $fromDate) {
      $errorMessage = 'To Date cannot be earlier than From Date.';
    } else {
      // Keep leave duration consistent with selected date range.
      $formValues['days'] = ((int) $fromDate->diff($toDate)->days) + 1;
    }
  }

  if ($errorMessage === '' && (int) $formValues['days'] < 1) {
    $errorMessage = 'Number of leave days must be at least 1.';
  }

  if ($errorMessage === '') {
    try {
      if (!student_auth_table_exists($conn, 'leave_applications')) {
        $errorMessage = 'Leave applications table is not available. Please contact admin.';
      } else {
        $insertColumns = [];
        $insertTypes = '';
        $insertParams = [];
        $hasLinkedIdentity = false;

        if (student_auth_column_exists($conn, 'leave_applications', 'application_id')) {
          $insertColumns[] = 'application_id';
          $insertTypes .= 's';
          $insertParams[] = 'LA' . date('YmdHis') . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        }

        $hasStudentIdColumn = student_auth_column_exists($conn, 'leave_applications', 'student_id');
        $hasStudentUserIdColumn = student_auth_column_exists($conn, 'leave_applications', 'student_user_id');

        if ($hasStudentIdColumn && $student_id > 0) {
          $insertColumns[] = 'student_id';
          $insertTypes .= 'i';
          $insertParams[] = $student_id;
          $hasLinkedIdentity = true;
        }

        if ($hasStudentUserIdColumn && $student_user_id > 0) {
          $insertColumns[] = 'student_user_id';
          $insertTypes .= 'i';
          $insertParams[] = $student_user_id;
          $hasLinkedIdentity = true;
        }

        if (!$hasLinkedIdentity) {
          $errorMessage = 'Unable to resolve valid student identity for leave submission.';
        }

        foreach (['leave_type', 'from_date', 'to_date', 'days'] as $requiredColumn) {
          if (!student_auth_column_exists($conn, 'leave_applications', $requiredColumn)) {
            $errorMessage = 'Leave table schema is incomplete. Missing column: ' . $requiredColumn;
            break;
          }
        }

        if ($errorMessage === '') {
          $insertColumns[] = 'leave_type';
          $insertTypes .= 's';
          $insertParams[] = $formValues['leave_type'];

          $insertColumns[] = 'from_date';
          $insertTypes .= 's';
          $insertParams[] = $formValues['from_date'];

          $insertColumns[] = 'to_date';
          $insertTypes .= 's';
          $insertParams[] = $formValues['to_date'];

          $insertColumns[] = 'days';
          $insertTypes .= 'i';
          $insertParams[] = (int) $formValues['days'];

          if (student_auth_column_exists($conn, 'leave_applications', 'reason')) {
            $insertColumns[] = 'reason';
            $insertTypes .= 's';
            $insertParams[] = $formValues['reason'];
          }

          if (student_auth_column_exists($conn, 'leave_applications', 'status')) {
            $insertColumns[] = 'status';
            $insertTypes .= 's';
            $insertParams[] = 'pending';
          }

          $escapedColumns = [];
          foreach ($insertColumns as $columnName) {
            $escapedColumns[] = '`' . $columnName . '`';
          }

          $insertSql = 'INSERT INTO leave_applications (' . implode(', ', $escapedColumns) . ') VALUES (' . implode(', ', array_fill(0, count($insertColumns), '?')) . ')';
          $insertStmt = $conn->prepare($insertSql);

          if (!$insertStmt) {
            $errorMessage = 'Unable to submit leave application right now.';
          } else {
            $bindParams = $insertParams;
            if (!student_auth_bind_dynamic_params($insertStmt, $insertTypes, $bindParams)) {
              $errorMessage = 'Unable to process leave application parameters.';
            } elseif ($insertStmt->execute()) {
              $successMessage = 'Leave application submitted successfully.';
              $formValues = [
                'leave_type' => '',
                'days' => '',
                'from_date' => '',
                'to_date' => '',
                'reason' => '',
              ];
            } else {
              $errorMessage = 'Unable to submit leave application right now.';
              error_log('Leave insert error: ' . $insertStmt->error);
            }
            $insertStmt->close();
          }
        }
      }
    } catch (Throwable $e) {
      $errorMessage = 'An unexpected error occurred while submitting leave.';
      error_log('Leave submit error: ' . $e->getMessage());
    }
  }
}
>>>>>>> Stashed changes

// Get leave records
$leave_records = [];

try {
<<<<<<< Updated upstream
  // Query leave history
  $sql = "SELECT application_id, leave_type, from_date, to_date, days, status FROM leave_applications WHERE {$studentFilter['sql']} ORDER BY from_date DESC LIMIT 10";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
    // Bind leave filter
    $filterParams = $studentFilter['params'];
    if (student_auth_bind_dynamic_params($stmt, $studentFilter['types'], $filterParams)) {
=======
  $applicationIdSelect = student_auth_column_exists($conn, 'leave_applications', 'application_id')
    ? 'application_id'
    : (student_auth_column_exists($conn, 'leave_applications', 'id')
      ? "CONCAT('LA', LPAD(id, 6, '0')) AS application_id"
      : "'' AS application_id");

  $leaveTypeSelect = student_auth_column_exists($conn, 'leave_applications', 'leave_type') ? 'leave_type' : "'' AS leave_type";
  $fromDateSelect = student_auth_column_exists($conn, 'leave_applications', 'from_date') ? 'from_date' : 'NULL AS from_date';
  $toDateSelect = student_auth_column_exists($conn, 'leave_applications', 'to_date') ? 'to_date' : 'NULL AS to_date';
  $daysSelect = student_auth_column_exists($conn, 'leave_applications', 'days') ? 'days' : '0 AS days';
  $statusSelect = student_auth_column_exists($conn, 'leave_applications', 'status') ? 'status' : "'pending' AS status";
  $orderByColumn = student_auth_column_exists($conn, 'leave_applications', 'from_date') ? 'from_date' : 'id';

  $sql = "SELECT {$applicationIdSelect}, {$leaveTypeSelect}, {$fromDateSelect}, {$toDateSelect}, {$daysSelect}, {$statusSelect} FROM leave_applications WHERE {$leaveFilter['sql']} ORDER BY {$orderByColumn} DESC LIMIT 10";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
  $filterParams = $leaveFilter['params'];
  if (student_auth_bind_dynamic_params($stmt, $leaveFilter['types'], $filterParams)) {
>>>>>>> Stashed changes
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

    <?php if ($successMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        <?php echo htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <!-- Leave Application Form -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 mb-8">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">edit_calendar</span>
          Apply for Leave
        </h2>
      </div>
      <div class="p-6">
        <form id="leave-application-form" method="POST" action="" class="space-y-6" novalidate>
          <div id="leave-js-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">Leave Type</label>
              <select id="leave_type" name="leave_type" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
                <option value="">Select Type</option>
                <option value="sick" <?php echo $formValues['leave_type'] === 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                <option value="casual" <?php echo $formValues['leave_type'] === 'casual' ? 'selected' : ''; ?>>Casual Leave</option>
                <option value="emergency" <?php echo $formValues['leave_type'] === 'emergency' ? 'selected' : ''; ?>>Emergency Leave</option>
                <option value="other" <?php echo $formValues['leave_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
              </select>
              <p id="leave_type_error" class="hidden mt-1 text-xs font-medium text-red-600"></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">Number of Days</label>
              <input id="days" type="number" name="days" min="1" value="<?php echo htmlspecialchars((string) $formValues['days']); ?>" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
              <p id="days_error" class="hidden mt-1 text-xs font-medium text-red-600"></p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">From Date</label>
              <input id="from_date" type="date" name="from_date" value="<?php echo htmlspecialchars($formValues['from_date']); ?>" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
              <p id="from_date_error" class="hidden mt-1 text-xs font-medium text-red-600"></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-900 mb-2">To Date</label>
              <input id="to_date" type="date" name="to_date" value="<?php echo htmlspecialchars($formValues['to_date']); ?>" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required>
              <p id="to_date_error" class="hidden mt-1 text-xs font-medium text-red-600"></p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-stone-900 mb-2">Reason</label>
            <textarea id="reason" name="reason" rows="4" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" required><?php echo htmlspecialchars($formValues['reason']); ?></textarea>
            <p id="reason_error" class="hidden mt-1 text-xs font-medium text-red-600"></p>
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
                <?php
                  $statusValue = strtolower(trim((string) ($record['status'] ?? 'pending')));
                  $fromDateValue = (string) ($record['from_date'] ?? '');
                  $toDateValue = (string) ($record['to_date'] ?? '');
                  $fromDateLabel = $fromDateValue !== '' ? date('d M Y', strtotime($fromDateValue)) : '-';
                  $toDateLabel = $toDateValue !== '' ? date('d M Y', strtotime($toDateValue)) : '-';
                ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-stone-900"><?php echo htmlspecialchars((string) ($record['application_id'] ?? '-')); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo ucfirst(htmlspecialchars($record['leave_type'])); ?> Leave</td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars($fromDateLabel); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars($toDateLabel); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo $record['days']; ?></td>
                  <td class="px-6 py-4">
                    <?php if ($statusValue === 'approved'): ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">done</span>
                        Approved
                      </span>
                    <?php elseif ($statusValue === 'rejected'): ?>
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

  <script>
    (function () {
      const form = document.getElementById('leave-application-form');
      if (!form) {
        return;
      }

      const fields = {
        leaveType: document.getElementById('leave_type'),
        days: document.getElementById('days'),
        fromDate: document.getElementById('from_date'),
        toDate: document.getElementById('to_date'),
        reason: document.getElementById('reason')
      };

      const summaryError = document.getElementById('leave-js-error');

      function showFieldError(field, message) {
        const errorNode = document.getElementById(field.name + '_error');
        if (errorNode) {
          errorNode.textContent = message;
          errorNode.classList.remove('hidden');
        }

        field.classList.remove('border-stone-300');
        field.classList.add('border-red-500', 'ring-1', 'ring-red-200');
      }

      function clearFieldError(field) {
        const errorNode = document.getElementById(field.name + '_error');
        if (errorNode) {
          errorNode.textContent = '';
          errorNode.classList.add('hidden');
        }

        field.classList.remove('border-red-500', 'ring-1', 'ring-red-200');
        if (!field.classList.contains('border-stone-300')) {
          field.classList.add('border-stone-300');
        }
      }

      function showSummaryError(message) {
        if (!summaryError) {
          return;
        }
        summaryError.textContent = message;
        summaryError.classList.remove('hidden');
      }

      function clearSummaryError() {
        if (!summaryError) {
          return;
        }
        summaryError.textContent = '';
        summaryError.classList.add('hidden');
      }

      function parseDate(value) {
        if (!value) {
          return null;
        }
        const parsed = new Date(value + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) {
          return null;
        }
        return parsed;
      }

      function inclusiveDays(fromDate, toDate) {
        const millisPerDay = 1000 * 60 * 60 * 24;
        return Math.floor((toDate.getTime() - fromDate.getTime()) / millisPerDay) + 1;
      }

      function syncDaysFromDates() {
        const fromDate = parseDate(fields.fromDate.value);
        const toDate = parseDate(fields.toDate.value);

        if (!fromDate || !toDate || toDate < fromDate) {
          return;
        }

        fields.days.value = String(inclusiveDays(fromDate, toDate));
      }

      function validateLeaveForm() {
        let isValid = true;
        clearSummaryError();

        [fields.leaveType, fields.days, fields.fromDate, fields.toDate, fields.reason].forEach(clearFieldError);

        const leaveTypeValue = fields.leaveType.value.trim();
        const fromDateValue = fields.fromDate.value.trim();
        const toDateValue = fields.toDate.value.trim();
        const reasonValue = fields.reason.value.trim();
        const daysValue = fields.days.value.trim();

        if (leaveTypeValue === '') {
          showFieldError(fields.leaveType, 'Please select leave type.');
          isValid = false;
        }

        const fromDate = parseDate(fromDateValue);
        const toDate = parseDate(toDateValue);

        if (!fromDate) {
          showFieldError(fields.fromDate, 'Please select a valid from date.');
          isValid = false;
        }

        if (!toDate) {
          showFieldError(fields.toDate, 'Please select a valid to date.');
          isValid = false;
        }

        let expectedDays = null;
        if (fromDate && toDate) {
          if (toDate < fromDate) {
            showFieldError(fields.toDate, 'To date cannot be earlier than from date.');
            isValid = false;
          } else {
            expectedDays = inclusiveDays(fromDate, toDate);
          }
        }

        const daysNumber = Number.parseInt(daysValue, 10);
        if (!Number.isInteger(daysNumber) || daysNumber < 1) {
          showFieldError(fields.days, 'Days must be at least 1.');
          isValid = false;
        } else if (expectedDays !== null && daysNumber !== expectedDays) {
          showFieldError(fields.days, 'Days must match selected date range (' + expectedDays + ').');
          isValid = false;
        }

        if (reasonValue.length < 5) {
          showFieldError(fields.reason, 'Reason must be at least 5 characters.');
          isValid = false;
        }

        if (!isValid) {
          showSummaryError('Please correct the highlighted fields before submitting the leave application.');
        }

        return isValid;
      }

      fields.fromDate.addEventListener('change', syncDaysFromDates);
      fields.toDate.addEventListener('change', syncDaysFromDates);

      [fields.leaveType, fields.days, fields.fromDate, fields.toDate, fields.reason].forEach(function (field) {
        field.addEventListener('input', function () {
          clearFieldError(field);
          clearSummaryError();
        });
      });

      form.addEventListener('submit', function (event) {
        syncDaysFromDates();
        if (!validateLeaveForm()) {
          event.preventDefault();
        }
      });
    })();
  </script>
</body>
</html>

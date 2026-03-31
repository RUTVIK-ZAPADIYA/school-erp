<?php
// Admin page for creating fee records.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'fees', 'payment_method', "VARCHAR(40) NULL");
admin_ensure_column($connection, 'fees', 'remarks', 'TEXT NULL');
admin_ensure_column($connection, 'fees', 'paid_date', 'DATE NULL');

// Build class lookup map so student rows can show class labels.
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classMap = [];
if ($classNameColumn !== null) {
  $classResult = $connection->query( "SELECT id, {$classNameColumn} AS class_name FROM classes");
  if ($classResult) {
    while ($classRow = $classResult->fetch_assoc()) {
      $classMap[(int) $classRow['id']] = (string) $classRow['class_name'];
    }
  }
}

$students = [];
if (admin_table_exists($connection, 'students')) {
  $studentResult = $connection->query( 'SELECT id, name, class, class_id FROM students ORDER BY name ASC');
  if ($studentResult) {
    while ($studentRow = $studentResult->fetch_assoc()) {
      $studentClass = trim((string) ($studentRow['class'] ?? ''));
      if ($studentClass === '' && isset($studentRow['class_id'])) {
        $studentClassId = (int) $studentRow['class_id'];
        $studentClass = $classMap[$studentClassId] ?? '';
      }
      $studentRow['display_class'] = $studentClass;
      $students[] = $studentRow;
    }
  }
}

$formData = [
  'student_id' => '',
  'fee_type' => '',
  'amount' => '',
  'due_date' => '',
  'payment_status' => '',
  'payment_method' => '',
  'remarks' => '',
];

$errorMessage = '';

// Handle fee form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $status = admin_normalize_status($formData['payment_status'], 'Pending');

  // Validate required fields and amount constraints before save.
  if (
    $formData['student_id'] === '' ||
    $formData['fee_type'] === '' ||
    $formData['amount'] === '' ||
    $formData['due_date'] === '' ||
    $formData['payment_status'] === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['amount']) || (float) $formData['amount'] <= 0) {
    $errorMessage = 'Amount must be greater than zero.';
  }

  // Build and run a schema-aware insert for the new fee record.
  if ($errorMessage === '') {
    $insertColumns = [];
    $insertValues = [];
    $insertTypes = '';
    $insertParams = [];

    if (admin_column_exists($connection, 'fees', 'student_id')) {
      $insertColumns[] = 'student_id';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['student_id'];
    }
    if (admin_column_exists($connection, 'fees', 'fee_type')) {
      $insertColumns[] = 'fee_type';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['fee_type'];
    }
    if (admin_column_exists($connection, 'fees', 'amount')) {
      $insertColumns[] = 'amount';
      $insertValues[] = '?';
      $insertTypes .= 'd';
      $insertParams[] = (float) $formData['amount'];
    }
    if (admin_column_exists($connection, 'fees', 'due_date')) {
      $insertColumns[] = 'due_date';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['due_date'];
    }
    if (admin_column_exists($connection, 'fees', 'status')) {
      $insertColumns[] = 'status';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $status;
    }
    if (admin_column_exists($connection, 'fees', 'payment_method')) {
      $insertColumns[] = 'payment_method';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['payment_method'];
    }
    if (admin_column_exists($connection, 'fees', 'remarks')) {
      $insertColumns[] = 'remarks';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['remarks'];
    }
    if (admin_column_exists($connection, 'fees', 'paid_date')) {
      $insertColumns[] = 'paid_date';
      if ($status === 'Paid') {
        $insertValues[] = '?';
        $insertTypes .= 's';
        $insertParams[] = date('Y-m-d');
      } else {
        $insertValues[] = 'NULL';
      }
    }

    $insertSql = 'INSERT INTO fees (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $insertStmt = $connection->prepare( $insertSql);

    if (!$insertStmt) {
      $errorMessage = 'Unable to save fee record right now.';
    } else {
      if (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
        $errorMessage = 'Unable to bind fee parameters.';
      } elseif (!$insertStmt->execute()) {
        $errorMessage = 'Failed to add fee record. Please try again.';
      }
      $insertStmt->close();
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Fee record added successfully.');
    header('Location: add-fee.php');
    exit();
  }
}

$flash = admin_pull_flash();
?>
<!-- Render the add fee page with persisted form state and alerts. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Fee Record</title>
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
      <h2><i class="fas fa-rupee-sign"></i> Add Fee Record</h2>
    </div>
    
    <div class="form-card">
      <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($flash['message']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Student *</label>
            <select class="form-select" name="student_id" data-validation="required,select">
              <option value="">Select Student</option>
              <?php foreach ($students as $student): ?>
                <option value="<?php echo (int) $student['id']; ?>" <?php echo ((string) $student['id'] === $formData['student_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $student['name']); ?><?php echo $student['display_class'] !== '' ? ' - ' . htmlspecialchars((string) $student['display_class']) : ''; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="student_id_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Fee Type *</label>
            <select class="form-select" name="fee_type" data-validation="required,select">
              <option value="">Select Fee Type</option>
              <option value="Tuition Fee" <?php echo $formData['fee_type'] === 'Tuition Fee' ? 'selected' : ''; ?>>Tuition Fee</option>
              <option value="Exam Fee" <?php echo $formData['fee_type'] === 'Exam Fee' ? 'selected' : ''; ?>>Exam Fee</option>
              <option value="Library Fee" <?php echo $formData['fee_type'] === 'Library Fee' ? 'selected' : ''; ?>>Library Fee</option>
              <option value="Transport Fee" <?php echo $formData['fee_type'] === 'Transport Fee' ? 'selected' : ''; ?>>Transport Fee</option>
              <option value="Other" <?php echo $formData['fee_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
            <div id="fee_type_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Amount *</label>
            <input type="text" class="form-control" name="amount" placeholder="Enter amount" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['amount']); ?>">
            <div id="amount_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Due Date *</label>
            <input type="date" class="form-control" name="due_date" data-validation="required" value="<?php echo htmlspecialchars($formData['due_date']); ?>">
            <div id="due_date_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Payment Status *</label>
            <select class="form-select" name="payment_status" data-validation="required,select">
              <option value="">Select Status</option>
              <option value="Pending" <?php echo $formData['payment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="Paid" <?php echo $formData['payment_status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
              <option value="Partial" <?php echo $formData['payment_status'] === 'Partial' ? 'selected' : ''; ?>>Partial</option>
              <option value="Overdue" <?php echo $formData['payment_status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
            <div id="payment_status_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Payment Method</label>
            <select class="form-select" name="payment_method" data-validation="select">
              <option value="">Select Method</option>
              <option value="Cash" <?php echo $formData['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
              <option value="Online" <?php echo $formData['payment_method'] === 'Online' ? 'selected' : ''; ?>>Online</option>
              <option value="Cheque" <?php echo $formData['payment_method'] === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
              <option value="Card" <?php echo $formData['payment_method'] === 'Card' ? 'selected' : ''; ?>>Card</option>
            </select>
            <div id="payment_method_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Remarks</label>
          <textarea class="form-control" name="remarks" rows="3" placeholder="Additional notes" data-validation="max" data-max="500"><?php echo htmlspecialchars($formData['remarks']); ?></textarea>
          <div id="remarks_error" class="invalid-feedback"></div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Fee Record</button>
          <a href="fees.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

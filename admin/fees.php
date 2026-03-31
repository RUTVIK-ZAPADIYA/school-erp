<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'fees', 'payment_method', "VARCHAR(40) NULL");
admin_ensure_column($connection, 'fees', 'remarks', 'TEXT NULL');
admin_ensure_column($connection, 'fees', 'paid_date', 'DATE NULL');

if (!function_exists('admin_format_currency')) {
  function admin_format_currency($amount)
  {
    return '₹' . number_format((float) $amount, 2);
  }
}

$search = trim((string) ($_GET['search'] ?? ''));
$transactions = [];

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classNameExpression = $classNameColumn !== null ? "c.{$classNameColumn}" : "''";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $feeId = (int) ($_POST['fee_id'] ?? 0);
  if ($feeId > 0) {
    $deleteStmt = mysqli_prepare($connection, 'DELETE FROM fees WHERE id = ?');
    if ($deleteStmt) {
      mysqli_stmt_bind_param($deleteStmt, 'i', $feeId);
      if (mysqli_stmt_execute($deleteStmt)) {
        admin_set_flash('success', 'Fee record deleted successfully.');
      } else {
        admin_set_flash('danger', 'Unable to delete fee record right now.');
      }
      mysqli_stmt_close($deleteStmt);
    }
  }

  header('Location: fees.php');
  exit();
}

$baseSql = "SELECT f.id, f.amount, f.fee_type, f.due_date, f.status, s.name AS student_name, s.class AS student_class, s.class_id, {$classNameExpression} AS mapped_class
      FROM fees f
      LEFT JOIN students s ON s.id = f.student_id
      LEFT JOIN classes c ON c.id = s.class_id";

if ($search !== '') {
  $searchSql = $baseSql . ' WHERE s.name LIKE ? OR f.fee_type LIKE ? OR CAST(f.id AS CHAR) LIKE ? ORDER BY f.id DESC LIMIT 50';
  $searchStmt = mysqli_prepare($connection, $searchSql);
  if ($searchStmt) {
    $searchTerm = '%' . $search . '%';
    mysqli_stmt_bind_param($searchStmt, 'sss', $searchTerm, $searchTerm, $searchTerm);
    mysqli_stmt_execute($searchStmt);
    $searchResult = mysqli_stmt_get_result($searchStmt);
    if ($searchResult) {
      while ($feeRow = mysqli_fetch_assoc($searchResult)) {
        $transactions[] = $feeRow;
      }
    }
    mysqli_stmt_close($searchStmt);
  }
} else {
  $result = mysqli_query($connection, $baseSql . ' ORDER BY f.id DESC LIMIT 50');
  if ($result) {
    while ($feeRow = mysqli_fetch_assoc($result)) {
      $transactions[] = $feeRow;
    }
  }
}

$totalCollected = (float) admin_scalar_value(
  $connection,
  "SELECT COALESCE(SUM(amount), 0) FROM fees WHERE LOWER(COALESCE(status, '')) IN ('paid', 'completed')",
  0
);
$totalPending = (float) admin_scalar_value(
  $connection,
  "SELECT COALESCE(SUM(amount), 0) FROM fees WHERE LOWER(COALESCE(status, '')) IN ('pending', 'partial')",
  0
);
$totalOverdue = (float) admin_scalar_value(
  $connection,
  "SELECT COALESCE(SUM(amount), 0) FROM fees WHERE LOWER(COALESCE(status, '')) = 'overdue'",
  0
);
$totalAmount = (float) admin_scalar_value($connection, 'SELECT COALESCE(SUM(amount), 0) FROM fees', 0);
$collectionRate = $totalAmount > 0 ? (int) round(($totalCollected / $totalAmount) * 100) : 0;

$flash = admin_pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fee Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .summary-item { text-align: center; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .summary-value { font-size: 2rem; font-weight: 700; color: #3498db; }
    .summary-label { color: #7f8c8d; margin-top: 5px; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .search-box { margin-bottom: 20px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-rupee-sign"></i> Fee Management</h2>
      <button class="btn-add" onclick="window.location.href='add-fee.php'" style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;"><i class="fas fa-plus"></i> Add Fee Record</button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="summary-item"><div class="summary-value"><?php echo htmlspecialchars(admin_format_currency($totalCollected)); ?></div><div class="summary-label">Total Collected</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo htmlspecialchars(admin_format_currency($totalPending)); ?></div><div class="summary-label">Pending</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo htmlspecialchars(admin_format_currency($totalOverdue)); ?></div><div class="summary-label">Overdue</div></div>
      <div class="summary-item"><div class="summary-value"><?php echo (int) $collectionRate; ?>%</div><div class="summary-label">Collection Rate</div></div>
    </div>
    <div class="content-card">
      <h5 class="mb-4">Recent Transactions</h5>
      <div class="search-box">
        <form method="GET" action="">
          <input
            type="text"
            class="form-control"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search by student, fee type, or receipt number">
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Receipt No</th><th>Student Name</th><th>Class</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($transactions)): ?>
              <?php foreach ($transactions as $transaction): ?>
                <?php
                  $status = admin_normalize_status($transaction['status'] ?? 'Pending', 'Pending');
                  $statusClass = 'bg-warning';
                  if ($status === 'Paid') {
                      $statusClass = 'bg-success';
                  } elseif ($status === 'Overdue') {
                      $statusClass = 'bg-danger';
                  } elseif ($status === 'Partial') {
                      $statusClass = 'bg-info';
                  }

                  $studentClass = trim((string) ($transaction['student_class'] ?? ''));
                  if ($studentClass === '') {
                      $studentClass = trim((string) ($transaction['mapped_class'] ?? ''));
                  }
                ?>
                <tr>
                  <td>#FEE<?php echo str_pad((string) ((int) $transaction['id']), 3, '0', STR_PAD_LEFT); ?></td>
                  <td><?php echo htmlspecialchars((string) ($transaction['student_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars($studentClass !== '' ? $studentClass : '-'); ?></td>
                  <td><?php echo htmlspecialchars(admin_format_currency($transaction['amount'] ?? 0)); ?></td>
                  <td><?php echo !empty($transaction['due_date']) ? htmlspecialchars(date('M d, Y', strtotime((string) $transaction['due_date']))) : '-'; ?></td>
                  <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <form method="POST" action="" style="display:inline-block;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="fee_id" value="<?php echo (int) $transaction['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this fee record?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No fee records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

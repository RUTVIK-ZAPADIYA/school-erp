<?php
// Admin page for listing and managing fee records.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';
require_once __DIR__ . '/../includes/razorpay-helper.php';

admin_ensure_column($connection, 'fees', 'payment_method', "VARCHAR(40) NULL");
admin_ensure_column($connection, 'fees', 'remarks', 'TEXT NULL');
admin_ensure_column($connection, 'fees', 'paid_date', 'DATE NULL');
admin_ensure_column($connection, 'fees', 'razorpay_order_id', 'VARCHAR(80) NULL');
admin_ensure_column($connection, 'fees', 'razorpay_payment_id', 'VARCHAR(80) NULL');
admin_ensure_column($connection, 'fees', 'razorpay_signature', 'VARCHAR(255) NULL');

if (empty($_SESSION['razorpay_csrf'])) {
  try {
    $_SESSION['razorpay_csrf'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    $_SESSION['razorpay_csrf'] = sha1(uniqid((string) mt_rand(), true));
  }
}

// Formatting helper used by summary cards and table output.
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

// Handle delete requests for fee records.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $feeId = (int) ($_POST['fee_id'] ?? 0);
  if ($feeId > 0) {
    $deleteStmt = $connection->prepare( 'DELETE FROM fees WHERE id = ?');
    if (!$deleteStmt) {
      admin_set_flash('danger', 'Unable to process fee delete request.');
    } else {
      $deleteStmt->bind_param( 'i', $feeId);
      if (!$deleteStmt->execute()) {
        admin_set_flash('danger', 'Unable to delete fee record right now.');
      } elseif ($deleteStmt->affected_rows < 1) {
        admin_set_flash('warning', 'Fee record was already removed or not found.');
      } else {
        admin_set_flash('success', 'Fee record deleted successfully.');
      }
      $deleteStmt->close();
    }
  }

  header('Location: fees.php');
  exit();
}

// Fetch recent fee transactions, optionally filtered by search text.
$baseSql = "SELECT f.id, f.amount, f.fee_type, f.due_date, f.status, s.name AS student_name, s.class AS student_class, s.class_id, {$classNameExpression} AS mapped_class
      FROM fees f
      LEFT JOIN students s ON s.id = f.student_id
      LEFT JOIN classes c ON c.id = s.class_id";

if ($search !== '') {
  $searchSql = $baseSql . ' WHERE s.name LIKE ? OR f.fee_type LIKE ? OR CAST(f.id AS CHAR) LIKE ? ORDER BY f.id DESC LIMIT 50';
  $searchStmt = $connection->prepare( $searchSql);
  if ($searchStmt) {
    $searchTerm = '%' . $search . '%';
    $searchStmt->bind_param( 'sss', $searchTerm, $searchTerm, $searchTerm);
    $searchStmt->execute();
    $searchResult = $searchStmt->get_result();
    if ($searchResult) {
      while ($feeRow = $searchResult->fetch_assoc()) {
        $transactions[] = $feeRow;
      }
    }
    $searchStmt->close();
  }
} else {
  $listStmt = $connection->prepare($baseSql . ' ORDER BY f.id DESC LIMIT 50');
  if ($listStmt) {
    $listStmt->execute();
    $result = $listStmt->get_result();
    if ($result) {
      while ($feeRow = $result->fetch_assoc()) {
        $transactions[] = $feeRow;
      }
    }
    $listStmt->close();
  }
}

// Calculate dashboard totals shown above the transaction table.
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

$razorpayConfigured = razorpay_is_configured();
$razorpayConfig = razorpay_load_config();
$razorpayCsrfToken = (string) ($_SESSION['razorpay_csrf'] ?? '');
$adminName = trim((string) ($_SESSION['admin_name'] ?? $_SESSION['name'] ?? 'Admin'));

$paymentNoticeType = '';
$paymentNoticeText = '';
$paymentState = strtolower(trim((string) ($_GET['payment'] ?? '')));
$paymentMessage = trim((string) ($_GET['message'] ?? ''));
$autoPayRequested = (string) ($_GET['autopay'] ?? '') === '1';
$autoPayFeeId = (int) ($_GET['pay_fee_id'] ?? 0);
if ($paymentState === 'success') {
  $paymentNoticeType = 'success';
  $paymentNoticeText = 'Payment completed and verified successfully.';
} elseif ($paymentState === 'failed') {
  $paymentNoticeType = 'danger';
  $paymentNoticeText = $paymentMessage !== ''
    ? $paymentMessage
    : 'Payment could not be verified. If amount was debited, verify the transaction in Razorpay dashboard.';
} elseif ($paymentState === 'cancelled') {
  $paymentNoticeType = 'warning';
  $paymentNoticeText = 'Payment checkout was cancelled.';
}

$flash = admin_pull_flash();
?>
<!-- Render fee analytics cards, search, and fee transaction rows. -->
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
  <?php include '../includes/error-modal.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-rupee-sign"></i> Fee Management</h2>
      <button class="btn-add" onclick="window.location.href='add-fee.php'" style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;"><i class="fas fa-plus"></i> Add Fee Record</button>
    </div>

    <?php if ($flash && $flash['type'] !== 'danger'): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($paymentNoticeText !== ''): ?>
      <div class="alert alert-<?php echo htmlspecialchars($paymentNoticeType); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($paymentNoticeText); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (!$razorpayConfigured): ?>
      <div class="alert alert-warning" role="alert">
        Razorpay is not configured. Add Razorpay keys in config/razorpay-config.php or environment variables to enable online fee collection.
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
        <form method="GET" action="" novalidate>
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
                  $statusRaw = strtolower(trim((string) ($transaction['status'] ?? 'pending')));
                  $isPaid = in_array($statusRaw, ['paid', 'completed'], true);
                  $status = $isPaid ? 'Paid' : admin_normalize_status($transaction['status'] ?? 'Pending', 'Pending');
                  $statusClass = 'bg-warning';
                  if ($isPaid) {
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
                    <a class="btn btn-sm btn-outline-primary" href="edit-fee.php?id=<?php echo (int) $transaction['id']; ?>" title="Edit Fee Record">
                      <i class="fas fa-edit"></i>
                    </a>
                    <?php if (!$isPaid && $razorpayConfigured): ?>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-success pay-razorpay-btn"
                        data-fee-id="<?php echo (int) $transaction['id']; ?>"
                        data-fee-type="<?php echo htmlspecialchars((string) ($transaction['fee_type'] ?? 'Fee')); ?>"
                        title="Pay with Razorpay">
                        <i class="fas fa-bolt"></i>
                      </button>
                    <?php endif; ?>
                    <form method="POST" action="" style="display:inline-block;" novalidate>
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($razorpayConfigured): ?>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script>
    (function () {
      var payButtons = document.querySelectorAll('.pay-razorpay-btn');
      if (!payButtons.length) {
        return;
      }

      var csrfToken = <?php echo json_encode($razorpayCsrfToken); ?>;
      var adminName = <?php echo json_encode($adminName); ?>;
      var companyName = <?php echo json_encode((string) ($razorpayConfig['company_name'] ?? 'School ERP')); ?>;
      var autoPayEnabled = <?php echo $autoPayRequested && $autoPayFeeId > 0 ? 'true' : 'false'; ?>;
      var autoPayFeeId = <?php echo (int) $autoPayFeeId; ?>;

      function toFormBody(payload) {
        var params = new URLSearchParams();
        Object.keys(payload).forEach(function (key) {
          params.append(key, payload[key]);
        });
        return params.toString();
      }

      function redirectWithState(state, message) {
        var nextUrl = 'fees.php?payment=' + encodeURIComponent(state);
        if (message) {
          nextUrl += '&message=' + encodeURIComponent(message);
        }
        window.location.href = nextUrl;
      }

      payButtons.forEach(function (button) {
        button.addEventListener('click', async function () {
          var feeId = button.getAttribute('data-fee-id') || '';
          var feeType = button.getAttribute('data-fee-type') || 'Fee Payment';
          var previousHtml = button.innerHTML;

          button.disabled = true;
          button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

          try {
            var createResponse = await fetch('create-razorpay-order.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
              },
              body: toFormBody({
                fee_id: feeId,
                csrf_token: csrfToken
              })
            });

            var createData = await createResponse.json();
            if (!createData.success) {
              throw new Error(createData.message || 'Unable to create payment order.');
            }

            if (typeof Razorpay === 'undefined') {
              throw new Error('Razorpay checkout SDK failed to load.');
            }

            var options = {
              key: createData.key_id,
              amount: createData.amount,
              currency: createData.currency,
              name: createData.name || companyName,
              description: createData.description || feeType,
              order_id: createData.order_id,
              prefill: {
                name: adminName
              },
              method: {
                upi: true,
                card: true,
                netbanking: true,
                wallet: true,
                emi: true,
                paylater: true
              },
              theme: {
                color: '#3498db'
              },
              modal: {
                ondismiss: function () {
                  redirectWithState('cancelled');
                }
              },
              handler: async function (paymentResponse) {
                try {
                  var verifyResponse = await fetch('verify-razorpay-payment.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: toFormBody({
                      fee_id: feeId,
                      csrf_token: csrfToken,
                      razorpay_order_id: paymentResponse.razorpay_order_id,
                      razorpay_payment_id: paymentResponse.razorpay_payment_id,
                      razorpay_signature: paymentResponse.razorpay_signature
                    })
                  });

                  var verifyData = await verifyResponse.json();
                  if (!verifyData.success) {
                    throw new Error(verifyData.message || 'Unable to verify payment.');
                  }

                  redirectWithState('success');
                } catch (verificationError) {
                  var verificationMessage = verificationError && verificationError.message
                    ? verificationError.message
                    : 'Payment verification failed.';
                  redirectWithState('failed', verificationMessage);
                }
              }
            };

            var checkout = new Razorpay(options);
            checkout.on('payment.failed', function (response) {
              var failedReason = response && response.error && response.error.description
                ? response.error.description
                : 'Payment failed at gateway.';
              redirectWithState('failed', failedReason);
            });

            checkout.open();
          } catch (error) {
            alert(error.message || 'Unable to start Razorpay payment.');
            button.disabled = false;
            button.innerHTML = previousHtml;
          }
        });
      });

      if (autoPayEnabled) {
        var autoPayButton = document.querySelector('.pay-razorpay-btn[data-fee-id="' + autoPayFeeId + '"]');
        if (autoPayButton) {
          autoPayButton.click();
        }
      }
    })();
  </script>
  <?php endif; ?>
  <?php if ($flash && $flash['type'] === 'danger'): ?>
  <script>document.addEventListener('DOMContentLoaded', function() { showErrorModal('Error', <?php echo json_encode($flash['message']); ?>); });</script>
  <?php endif; ?>
</body>
</html>

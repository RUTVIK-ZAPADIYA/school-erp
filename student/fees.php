<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/razorpay-helper.php';

if (function_exists('ensure_school_erp_column')) {
  ensure_school_erp_column($conn, 'fees', 'payment_method', 'VARCHAR(40) NULL');
  ensure_school_erp_column($conn, 'fees', 'remarks', 'TEXT NULL');
  ensure_school_erp_column($conn, 'fees', 'paid_date', 'DATE NULL');
  ensure_school_erp_column($conn, 'fees', 'razorpay_order_id', 'VARCHAR(80) NULL');
  ensure_school_erp_column($conn, 'fees', 'razorpay_payment_id', 'VARCHAR(80) NULL');
  ensure_school_erp_column($conn, 'fees', 'razorpay_signature', 'VARCHAR(255) NULL');
}

if (empty($_SESSION['razorpay_csrf'])) {
  try {
    $_SESSION['razorpay_csrf'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    $_SESSION['razorpay_csrf'] = sha1(uniqid((string) mt_rand(), true));
  }
}

$studentContext = student_auth_context();
$studentName = trim((string) ($studentContext['student_name'] ?? 'Student'));
$studentFilter = student_auth_student_id_filter_sql('f.student_id');

$fee_records = [];
$total_fees = 0.0;
$paid_amount = 0.0;

try {
  $sql = "SELECT
            f.id,
            f.fee_type,
            f.amount,
            f.status,
            f.due_date,
            f.created_at,
            f.payment_method,
            f.paid_date,
            f.razorpay_payment_id
          FROM fees f
          WHERE {$studentFilter['sql']}
          ORDER BY COALESCE(f.due_date, DATE(f.created_at)) DESC, f.id DESC
          LIMIT 100";

  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $filterParams = $studentFilter['params'];
    if (student_auth_bind_dynamic_params($stmt, $studentFilter['types'], $filterParams)) {
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result) {
        while ($row = $result->fetch_assoc()) {
          $amount = (float) ($row['amount'] ?? 0);
          $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
          $fee_records[] = $row;
          $total_fees += $amount;
          if (in_array($status, ['paid', 'completed'], true)) {
            $paid_amount += $amount;
          }
        }
      }
    }
    $stmt->close();
  }
} catch (Exception $e) {
  error_log('Fees query error: ' . $e->getMessage());
}

$pending_amount = max(0, $total_fees - $paid_amount);
$payment_percentage = $total_fees > 0 ? round(($paid_amount / $total_fees) * 100) : 0;
$razorpayConfigured = razorpay_is_configured();
$razorpayConfig = razorpay_load_config();
$razorpayCsrfToken = (string) ($_SESSION['razorpay_csrf'] ?? '');

$paymentNoticeType = '';
$paymentNoticeText = '';
$paymentState = strtolower(trim((string) ($_GET['payment'] ?? '')));
$paymentMessage = trim((string) ($_GET['message'] ?? ''));
if ($paymentState === 'success') {
  $paymentNoticeType = 'emerald';
  $paymentNoticeText = 'Payment completed and verified successfully.';
} elseif ($paymentState === 'failed') {
  $paymentNoticeType = 'red';
  $paymentNoticeText = $paymentMessage !== ''
    ? $paymentMessage
    : 'Payment could not be verified. If amount was debited, contact admin with transaction details.';
} elseif ($paymentState === 'cancelled') {
  $paymentNoticeType = 'amber';
  $paymentNoticeText = 'Payment checkout was cancelled.';
}
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
        <p class="text-sm text-stone-500">Track your fees and pay pending dues online</p>
      </div>
    </div>

    <?php if ($paymentNoticeText !== ''): ?>
      <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?php echo $paymentNoticeType === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($paymentNoticeType === 'red' ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700'); ?>">
        <?php echo htmlspecialchars($paymentNoticeText); ?>
      </div>
    <?php endif; ?>

    <?php if (!$razorpayConfigured): ?>
      <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
        Online payments are disabled. Ask admin to configure Razorpay keys in config/razorpay-config.php or environment variables.
      </div>
    <?php endif; ?>

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
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Fee Type</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Due Date</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Amount</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Status</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Method</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (count($fee_records) > 0): ?>
              <?php foreach ($fee_records as $record): ?>
                <?php
                  $feeId = (int) ($record['id'] ?? 0);
                  $amount = (float) ($record['amount'] ?? 0);
                  $statusRaw = strtolower(trim((string) ($record['status'] ?? 'pending')));
                  $isPaid = in_array($statusRaw, ['paid', 'completed'], true);
                  $receiptNumber = '#FEE' . str_pad((string) $feeId, 4, '0', STR_PAD_LEFT);
                  $dueDate = trim((string) ($record['due_date'] ?? ''));
                  $createdAt = trim((string) ($record['created_at'] ?? ''));
                  $displayDate = $dueDate !== '' ? $dueDate : $createdAt;
                  $paymentMethod = trim((string) ($record['payment_method'] ?? ''));
                  if ($paymentMethod === '') {
                    $paymentMethod = '-';
                  }
                  $feeType = trim((string) ($record['fee_type'] ?? 'Fee'));
                ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-stone-900"><?php echo htmlspecialchars($receiptNumber); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars($feeType); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo $displayDate !== '' ? htmlspecialchars(date('d M Y', strtotime($displayDate))) : '-'; ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700">₹<?php echo number_format($amount, 2); ?></td>
                  <td class="px-6 py-4">
                    <?php if ($isPaid): ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">done</span>
                        Paid
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        <?php echo htmlspecialchars(ucfirst($statusRaw)); ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo htmlspecialchars($paymentMethod); ?></td>
                  <td class="px-6 py-4">
                    <?php if ($isPaid): ?>
                      <span class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-emerald-700 bg-emerald-50 rounded transition">
                        <span class="material-symbols-outlined text-sm">task_alt</span>
                        <?php echo !empty($record['razorpay_payment_id']) ? htmlspecialchars((string) $record['razorpay_payment_id']) : 'Paid'; ?>
                      </span>
                    <?php elseif (!$razorpayConfigured): ?>
                      <span class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-stone-500 bg-stone-100 rounded">
                        <span class="material-symbols-outlined text-sm">settings</span>
                        Setup Required
                      </span>
                    <?php else: ?>
                      <button
                        type="button"
                        class="pay-now-btn inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-emerald-600 hover:bg-emerald-50 rounded transition"
                        data-fee-id="<?php echo $feeId; ?>"
                        data-fee-type="<?php echo htmlspecialchars($feeType); ?>">
                        <span class="material-symbols-outlined text-sm">credit_card</span>
                        Pay with Razorpay
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-8 text-center text-stone-500">No fee records found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php if ($razorpayConfigured): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
      (function () {
        var buttons = document.querySelectorAll('.pay-now-btn');
        if (!buttons.length) {
          return;
        }

        var csrfToken = <?php echo json_encode($razorpayCsrfToken); ?>;
        var studentName = <?php echo json_encode($studentName); ?>;
        var companyName = <?php echo json_encode((string) ($razorpayConfig['company_name'] ?? 'School ERP')); ?>;
        var upiFlow = <?php echo json_encode((string) ($razorpayConfig['upi_flow'] ?? 'collect')); ?>;
        var prefillContact = <?php echo json_encode((string) ($razorpayConfig['prefill_contact'] ?? '')); ?>;
        var prefillEmail = <?php echo json_encode((string) ($razorpayConfig['prefill_email'] ?? '')); ?>;

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

        buttons.forEach(function (button) {
          button.addEventListener('click', async function () {
            var feeId = button.getAttribute('data-fee-id') || '';
            var feeType = button.getAttribute('data-fee-type') || 'Fee Payment';
            var originalLabel = button.innerHTML;
            button.disabled = true;
            button.classList.add('opacity-60', 'cursor-not-allowed');
            button.innerHTML = '<span class="material-symbols-outlined text-sm">hourglass_top</span> Processing...';

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

              var prefillData = {
                name: studentName
              };

              if (prefillContact) {
                prefillData.contact = prefillContact;
              }

              if (prefillEmail) {
                prefillData.email = prefillEmail;
              }

              var options = {
                key: createData.key_id,
                amount: createData.amount,
                currency: createData.currency,
                name: createData.name || companyName,
                description: createData.description || feeType,
                order_id: createData.order_id,
                prefill: prefillData,
                method: {
                  upi: true,
                  card: true,
                  netbanking: true,
                  wallet: true,
                  emi: true,
                  paylater: true
                },
                upi: {
                  flow: upiFlow === 'intent' ? 'intent' : 'collect'
                },
                readonly: {
                  contact: false,
                  email: false
                },
                theme: {
                  color: '#059669'
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
                    console.error('Verification error details:', {
                      message: verificationMessage,
                      response: verifyResponse ? verifyResponse.status + ' ' + verifyResponse.statusText : 'No response',
                      full: verificationError
                    });
                    alert('Payment verification failed:\n' + verificationMessage);
                    redirectWithState('failed', verificationMessage);
                  }
                }
              };

              if (typeof Razorpay === 'undefined') {
                throw new Error('Razorpay checkout SDK failed to load.');
              }

              var razorpay = new Razorpay(options);
              
              // Capture payment.failed event with error details
              razorpay.on('payment.failed', function (response) {
                var errorCode = response && response.error && response.error.code ? response.error.code : 'UNKNOWN';
                var errorDesc = response && response.error && response.error.description ? response.error.description : 'Payment failed at gateway.';
                console.error('Razorpay payment failed:', {code: errorCode, description: errorDesc, full: response});
                var failMessage = 'Payment failed: ' + errorDesc + ' (Code: ' + errorCode + ')';
                redirectWithState('failed', failMessage);
              });
              
              razorpay.open();
            } catch (err) {
              console.error('Payment error:', err);
              alert(err.message || 'Unable to start online payment.');
              button.disabled = false;
              button.classList.remove('opacity-60', 'cursor-not-allowed');
              button.innerHTML = originalLabel;
            }
          });
        });
      })();
    </script>
  <?php endif; ?>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/razorpay-helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$csrfToken = trim((string) ($_POST['csrf_token'] ?? ''));
$sessionToken = trim((string) ($_SESSION['razorpay_csrf'] ?? ''));
if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request token. Please refresh and try again.']);
    exit();
}

if (!razorpay_is_configured()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Razorpay is not configured. Add key_id and key_secret in config/razorpay-config.php or environment variables.',
    ]);
    exit();
}

$feeId = (int) ($_POST['fee_id'] ?? 0);
if ($feeId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid fee record.']);
    exit();
}

$studentFilter = student_auth_student_id_filter_sql('f.student_id');
$feeSql = "SELECT f.id, f.amount, f.fee_type, f.status, f.razorpay_order_id FROM fees f WHERE f.id = ? AND {$studentFilter['sql']} LIMIT 1";
$feeStmt = $conn->prepare($feeSql);

if (!$feeStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process fee payment right now.']);
    exit();
}

$bindTypes = 'i' . $studentFilter['types'];
$bindParams = array_merge([$feeId], $studentFilter['params']);

if (!student_auth_bind_dynamic_params($feeStmt, $bindTypes, $bindParams)) {
    $feeStmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare payment request.']);
    exit();
}

$feeStmt->execute();
$feeResult = $feeStmt->get_result();
$feeRow = $feeResult ? $feeResult->fetch_assoc() : null;
$feeStmt->close();

if (!$feeRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Fee record not found.']);
    exit();
}

$status = strtolower(trim((string) ($feeRow['status'] ?? 'pending')));
if (in_array($status, ['paid', 'completed'], true)) {
    echo json_encode(['success' => false, 'message' => 'This fee is already paid.']);
    exit();
}

$amount = (float) ($feeRow['amount'] ?? 0);
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid fee amount.']);
    exit();
}

$amountInPaise = (int) round($amount * 100);
if ($amountInPaise < 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Amount must be at least Rs.1 for online payment.']);
    exit();
}

$studentContext = student_auth_context();
$receipt = 'fee_' . $feeId . '_' . time();

$orderResponse = razorpay_create_order(
    $amountInPaise,
    $receipt,
    [
        'fee_id' => (string) $feeId,
        'student_id' => (string) ((int) ($studentContext['student_id'] ?? 0)),
    ]
);

if (!(bool) ($orderResponse['success'] ?? false)) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => (string) ($orderResponse['error'] ?? 'Unable to create Razorpay order.'),
    ]);
    exit();
}

$order = (array) ($orderResponse['order'] ?? []);
$orderId = trim((string) ($order['id'] ?? ''));
if ($orderId === '') {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Razorpay order id missing in response.']);
    exit();
}

$updateParts = [];
$updateTypes = '';
$updateParams = [];

if (student_auth_column_exists($conn, 'fees', 'razorpay_order_id')) {
    $updateParts[] = 'razorpay_order_id = ?';
    $updateTypes .= 's';
    $updateParams[] = $orderId;
}
if (student_auth_column_exists($conn, 'fees', 'payment_method')) {
    $updateParts[] = 'payment_method = ?';
    $updateTypes .= 's';
    $updateParams[] = 'Razorpay';
}

if (!empty($updateParts)) {
    $updateSql = 'UPDATE fees SET ' . implode(', ', $updateParts) . ' WHERE id = ?';
    $updateTypes .= 'i';
    $updateParams[] = $feeId;

    $updateStmt = $conn->prepare($updateSql);
    if ($updateStmt) {
        if (student_auth_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
            $updateStmt->execute();
        }
        $updateStmt->close();
    }
}

$config = razorpay_load_config();
$descriptionPrefix = trim((string) ($config['payment_description_prefix'] ?? 'School Fee Payment'));
$feeType = trim((string) ($feeRow['fee_type'] ?? 'Fee'));

echo json_encode([
    'success' => true,
    'key_id' => (string) $config['key_id'],
    'order_id' => $orderId,
    'amount' => (int) ($order['amount'] ?? $amountInPaise),
    'currency' => (string) ($order['currency'] ?? $config['currency'] ?? 'INR'),
    'name' => (string) ($config['company_name'] ?? 'School ERP'),
    'description' => $descriptionPrefix . ' - ' . $feeType,
    'fee_id' => $feeId,
]);

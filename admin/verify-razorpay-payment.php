<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';
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

$feeId = (int) ($_POST['fee_id'] ?? 0);
$orderId = trim((string) ($_POST['razorpay_order_id'] ?? ''));
$paymentId = trim((string) ($_POST['razorpay_payment_id'] ?? ''));
$signature = trim((string) ($_POST['razorpay_signature'] ?? ''));

if ($signature === 'undefined' || $signature === 'null') {
  $signature = '';
}

if ($feeId <= 0 || $orderId === '' || $paymentId === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Incomplete payment details.']);
  exit();
}

$feeColumns = ['id', 'status'];
if (admin_column_exists($connection, 'fees', 'razorpay_order_id')) {
  $feeColumns[] = 'razorpay_order_id';
}

$feeSql = 'SELECT ' . implode(', ', $feeColumns) . ' FROM fees WHERE id = ? LIMIT 1';
$feeStmt = $connection->prepare($feeSql);
if (!$feeStmt) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unable to verify payment right now.']);
  exit();
}

$feeStmt->bind_param('i', $feeId);
$feeStmt->execute();
$feeResult = $feeStmt->get_result();
$feeRow = $feeResult ? $feeResult->fetch_assoc() : null;
$feeStmt->close();

if (!$feeRow) {
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'Fee record not found.']);
  exit();
}

$currentStatus = strtolower(trim((string) ($feeRow['status'] ?? 'pending')));
if (in_array($currentStatus, ['paid', 'completed'], true)) {
  echo json_encode(['success' => true, 'message' => 'Fee is already marked as paid.']);
  exit();
}

$storedOrderId = trim((string) ($feeRow['razorpay_order_id'] ?? ''));
if ($storedOrderId !== '' && $storedOrderId !== $orderId) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Order mismatch detected. Please retry payment.']);
  exit();
}

$signatureVerified = false;
if ($signature !== '') {
  $signatureVerified = razorpay_verify_payment_signature($orderId, $paymentId, $signature);
}

if (!$signatureVerified) {
  $apiVerification = razorpay_verify_payment_with_api($orderId, $paymentId);
  if (!(bool) ($apiVerification['success'] ?? false)) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => (string) ($apiVerification['error'] ?? 'Payment verification failed.'),
    ]);
    exit();
  }
}

$updateParts = [];
$updateTypes = '';
$updateParams = [];

if (admin_column_exists($connection, 'fees', 'status')) {
  $updateParts[] = 'status = ?';
  $updateTypes .= 's';
  $updateParams[] = 'Paid';
}
if (admin_column_exists($connection, 'fees', 'payment_method')) {
  $updateParts[] = 'payment_method = ?';
  $updateTypes .= 's';
  $updateParams[] = 'Razorpay';
}
if (admin_column_exists($connection, 'fees', 'paid_date')) {
  $updateParts[] = 'paid_date = ?';
  $updateTypes .= 's';
  $updateParams[] = date('Y-m-d');
}
if (admin_column_exists($connection, 'fees', 'razorpay_order_id')) {
  $updateParts[] = 'razorpay_order_id = ?';
  $updateTypes .= 's';
  $updateParams[] = $orderId;
}
if (admin_column_exists($connection, 'fees', 'razorpay_payment_id')) {
  $updateParts[] = 'razorpay_payment_id = ?';
  $updateTypes .= 's';
  $updateParams[] = $paymentId;
}
if (admin_column_exists($connection, 'fees', 'razorpay_signature')) {
  $updateParts[] = 'razorpay_signature = ?';
  $updateTypes .= 's';
  $updateParams[] = $signature;
}

if (empty($updateParts)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Fee schema is missing payment columns.']);
  exit();
}

$updateSql = 'UPDATE fees SET ' . implode(', ', $updateParts) . ' WHERE id = ?';
$updateTypes .= 'i';
$updateParams[] = $feeId;

$updateStmt = $connection->prepare($updateSql);
if (!$updateStmt) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unable to update payment status.']);
  exit();
}

if (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
  $updateStmt->close();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unable to bind payment update parameters.']);
  exit();
}

if (!$updateStmt->execute()) {
  $updateStmt->close();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unable to save payment right now.']);
  exit();
}

$updateStmt->close();

echo json_encode(['success' => true, 'message' => 'Payment verified successfully.']);
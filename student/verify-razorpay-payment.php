<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/razorpay-helper.php';

header('Content-Type: application/json; charset=utf-8');

// Debug logging
$debugLog = __DIR__ . '/../logs/razorpay-debug.log';
if (!is_dir(dirname($debugLog))) {
    @mkdir(dirname($debugLog), 0755, true);
}
function debug_log($message) {
    global $debugLog;
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] $message\n";
    @error_log($entry, 3, $debugLog);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$csrfToken = trim((string) ($_POST['csrf_token'] ?? ''));
$sessionToken = trim((string) ($_SESSION['razorpay_csrf'] ?? ''));
if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    debug_log('CSRF validation failed');
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

debug_log("Payment verification attempt - Fee ID: $feeId, Order: $orderId, Payment: $paymentId, Signature provided: " . ($signature !== '' ? 'YES' : 'NO'));

if ($feeId <= 0 || $orderId === '' || $paymentId === '') {
    debug_log('Incomplete payment details: feeId=' . $feeId . ', orderId=' . $orderId . ', paymentId=' . $paymentId);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Incomplete payment details.']);
    exit();
}

$studentFilter = student_auth_student_id_filter_sql('f.student_id');
$feeSql = "SELECT f.id, f.status, f.razorpay_order_id FROM fees f WHERE f.id = ? AND {$studentFilter['sql']} LIMIT 1";
$feeStmt = $conn->prepare($feeSql);

if (!$feeStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to verify payment right now.']);
    exit();
}

$bindTypes = 'i' . $studentFilter['types'];
$bindParams = array_merge([$feeId], $studentFilter['params']);

if (!student_auth_bind_dynamic_params($feeStmt, $bindTypes, $bindParams)) {
    $feeStmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to verify payment details.']);
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
    debug_log('Signature verification result: ' . ($signatureVerified ? 'SUCCESS' : 'FAILED'));
} else {
    debug_log('No signature provided, will use API verification');
}

if (!$signatureVerified) {
    debug_log('Attempting API-based payment verification for order: ' . $orderId . ', payment: ' . $paymentId);
    $apiVerification = razorpay_verify_payment_with_api($orderId, $paymentId);
    debug_log('API verification result: ' . json_encode($apiVerification));
    if (!(bool) ($apiVerification['success'] ?? false)) {
        $errorMsg = (string) ($apiVerification['error'] ?? 'Payment verification failed.');
        debug_log('VERIFICATION FAILED - Error: ' . $errorMsg);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
        ]);
        exit();
    }
    debug_log('API verification successful');
}

$updateParts = [];
$updateTypes = '';
$updateParams = [];

if (student_auth_column_exists($conn, 'fees', 'status')) {
    $updateParts[] = 'status = ?';
    $updateTypes .= 's';
    $updateParams[] = 'Paid';
}
if (student_auth_column_exists($conn, 'fees', 'payment_method')) {
    $updateParts[] = 'payment_method = ?';
    $updateTypes .= 's';
    $updateParams[] = 'Razorpay';
}
if (student_auth_column_exists($conn, 'fees', 'paid_date')) {
    $updateParts[] = 'paid_date = ?';
    $updateTypes .= 's';
    $updateParams[] = date('Y-m-d');
}
if (student_auth_column_exists($conn, 'fees', 'razorpay_order_id')) {
    $updateParts[] = 'razorpay_order_id = ?';
    $updateTypes .= 's';
    $updateParams[] = $orderId;
}
if (student_auth_column_exists($conn, 'fees', 'razorpay_payment_id')) {
    $updateParts[] = 'razorpay_payment_id = ?';
    $updateTypes .= 's';
    $updateParams[] = $paymentId;
}
if (student_auth_column_exists($conn, 'fees', 'razorpay_signature')) {
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

$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update payment status.']);
    exit();
}

if (!student_auth_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
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

debug_log('Payment successfully verified and database updated. Fee ID: ' . $feeId . ', Payment ID: ' . $paymentId);
echo json_encode(['success' => true, 'message' => 'Payment verified successfully.']);

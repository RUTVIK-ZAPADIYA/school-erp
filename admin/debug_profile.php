<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';

$adminUserId = (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);

echo '<pre>';
echo "Session admin_id: " . ($adminUserId) . "\n";
echo "Session keys: " . implode(', ', array_keys($_SESSION)) . "\n\n";

$row = null;
$stmt = $connection->prepare("SELECT id, username, name, email, phone, role, status, created_at FROM users WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $adminUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}

echo "DB row for id={$adminUserId}:\n";
var_dump($row);

echo "\nAll admin users:\n";
$all = $connection->query("SELECT id, username, name, email, role FROM users WHERE role='admin'");
while ($r = $all->fetch_assoc()) {
    var_dump($r);
}
echo '</pre>';

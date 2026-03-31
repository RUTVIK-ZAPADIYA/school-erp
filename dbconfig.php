<?php
require_once __DIR__ . '/includes/db_connect.php';

// Backward compatibility for legacy pages using $connection.
if (!isset($connection) && isset($conn)) {
    $connection = $conn;
}

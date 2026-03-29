<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ((!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name'])) && isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'admin')) {
    $_SESSION['admin_id'] = (int) $_SESSION['user_id'];
    $_SESSION['admin_name'] = $_SESSION['name'] ?? 'Admin';
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

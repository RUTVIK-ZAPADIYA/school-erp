<?php
// Shared admin authentication guard for protected pages.
// Start a session when this guard is included directly.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Bridge role-based session fields to admin-specific keys when needed.
if ((!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name'])) && isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'admin')) {
    $_SESSION['admin_id'] = (int) $_SESSION['user_id'];
    $_SESSION['admin_name'] = $_SESSION['name'] ?? 'Admin';
}

// Redirect unauthenticated users to the login page.
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

<?php
// Admin logout handler that clears session state.
require_once __DIR__ . '/../includes/session_bootstrap.php';

// Clear role-specific session keys for all portal personas.
unset(
	$_SESSION['admin_id'],
	$_SESSION['admin_name'],
	$_SESSION['teacher_id'],
	$_SESSION['teacher_name'],
	$_SESSION['student_id'],
	$_SESSION['student_name'],
	$_SESSION['user_id'],
	$_SESSION['username'],
	$_SESSION['role'],
	$_SESSION['name'],
	$_SESSION['email']
);
$_SESSION = [];

// Expire the active session cookie before destroying the session.
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Destroy session and redirect to login.
session_destroy();
header("Location: ../login.php");
exit();
?>

<?php
// Start active session
session_start();
// Clear role sessions
unset(
	$_SESSION['admin_id'],
	$_SESSION['admin_name'],
	$_SESSION['teacher_id'],
	$_SESSION['teacher_name'],
	$_SESSION['student_id'],
	$_SESSION['student_profile_id'],
	$_SESSION['student_user_id'],
	$_SESSION['student_roll_no'],
	$_SESSION['student_name'],
	$_SESSION['user_id'],
	$_SESSION['username'],
	$_SESSION['role'],
	$_SESSION['name']
);
// Reset session array
$_SESSION = [];

// Remove session cookie
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Destroy current session
session_destroy();
// Redirect to login
header("Location: ../login.php");
exit();
?>

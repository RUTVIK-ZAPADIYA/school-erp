<?php
require_once __DIR__ . '/includes/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo '<h2>School ERP Setup Completed</h2>';
echo '<p>Database <strong>school_erp</strong> is connected and required tables are ensured.</p>';
echo '<h3>Default Login Accounts</h3>';
echo '<ul>';
echo '<li>Admin: <code>admin</code> / <code>admin123</code></li>';
echo '<li>Teacher: <code>teacher1</code> / <code>teacher123</code></li>';
echo '<li>Student: <code>student1</code> / <code>student123</code></li>';
echo '</ul>';
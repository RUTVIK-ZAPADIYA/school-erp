<?php
require_once __DIR__ . '/includes/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

function setup_list_tables($conn)
{
		$tables = [];
		$result = mysqli_query($conn, 'SHOW TABLES');

		if ($result) {
				while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
						if (isset($row[0])) {
								$tables[] = (string) $row[0];
						}
				}
		}

		return $tables;
}

function setup_run_sql_batch($conn, $sqlBatch, &$errors)
{
		$errors = [];
		$executedStatements = 0;

		if (!mysqli_multi_query($conn, $sqlBatch)) {
				$errors[] = mysqli_error($conn);
				return 0;
		}

		do {
				$result = mysqli_store_result($conn);
				if ($result instanceof mysqli_result) {
						mysqli_free_result($result);
				}

				if (mysqli_errno($conn)) {
						$errors[] = mysqli_error($conn);
						break;
				}

				$executedStatements++;
		} while (mysqli_more_results($conn) && mysqli_next_result($conn));

		if (mysqli_errno($conn)) {
				$errors[] = mysqli_error($conn);
		}

		return $executedStatements;
}

$message = '';
$messageType = 'info';
$sqlBatch = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$sqlBatch = trim((string) ($_POST['sql_batch'] ?? ''));

		if ($sqlBatch === '') {
				$message = 'Please paste SQL before running setup.';
				$messageType = 'danger';
		} else {
				if (substr(rtrim($sqlBatch), -1) !== ';') {
						$sqlBatch .= ';';
				}

				$errors = [];
				$executed = setup_run_sql_batch($conn, $sqlBatch, $errors);

				if (empty($errors)) {
						$message = 'SQL executed successfully. Statements processed: ' . $executed . '.';
						$messageType = 'success';
						$sqlBatch = '';
				} else {
						$message = 'SQL execution finished with errors: ' . implode(' | ', $errors);
						$messageType = 'danger';
				}
		}
}

$tables = setup_list_tables($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>School ERP Setup</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body { background: #f5f7fb; }
		.setup-wrap { max-width: 1000px; margin: 30px auto; }
		.card { border: 0; border-radius: 14px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08); }
		textarea { min-height: 220px; font-family: Consolas, Monaco, 'Courier New', monospace; }
		.table-list { columns: 2; -webkit-columns: 2; -moz-columns: 2; }
		@media (max-width: 767px) { .table-list { columns: 1; -webkit-columns: 1; -moz-columns: 1; } }
	</style>
</head>
<body>
	<div class="setup-wrap">
		<div class="card mb-4">
			<div class="card-body p-4">
				<h2 class="mb-2">School ERP Setup Completed</h2>
				<p class="mb-3">Database <strong><?php echo htmlspecialchars($dbname); ?></strong> is connected and required tables are ensured.</p>
				<h5>Default Login Accounts</h5>
				<ul class="mb-0">
					<li>Admin: <code>admin</code> / <code>admin123</code></li>
					<li>Teacher: <code>teacher1</code> / <code>teacher123</code></li>
					<li>Student: <code>student1</code> / <code>student123</code></li>
				</ul>
			</div>
		</div>

		<?php if ($message !== ''): ?>
			<div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
		<?php endif; ?>

		<div class="card mb-4">
			<div class="card-body p-4">
				<h4 class="mb-2">Add New Tables Directly</h4>
				<p class="text-muted">Paste one or more SQL statements (for example, <code>CREATE TABLE IF NOT EXISTS ...</code>) and run them.</p>

				<form method="POST" action="">
					<div class="mb-3">
						<label for="sql_batch" class="form-label">SQL Statements</label>
						<textarea class="form-control" id="sql_batch" name="sql_batch" placeholder="CREATE TABLE IF NOT EXISTS my_new_table (
		id INT AUTO_INCREMENT PRIMARY KEY,
		title VARCHAR(150) NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);&#10;&#10;ALTER TABLE my_new_table ADD COLUMN status VARCHAR(20) DEFAULT 'Active';"><?php echo htmlspecialchars($sqlBatch); ?></textarea>
					</div>
					<button type="submit" class="btn btn-primary">Run SQL</button>
				</form>
			</div>
		</div>

		<div class="card">
			<div class="card-body p-4">
				<h4 class="mb-3">Current Tables (<?php echo count($tables); ?>)</h4>
				<?php if (!empty($tables)): ?>
					<ul class="table-list mb-0">
						<?php foreach ($tables as $tableName): ?>
							<li><?php echo htmlspecialchars($tableName); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<p class="text-muted mb-0">No tables found.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</body>
</html>
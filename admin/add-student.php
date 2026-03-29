<?php
require_once __DIR__ . '/auth.php';

include '../dbconfig.php';

$message = '';
$message_type = '';
$classes = [];

$classQuery = mysqli_query($connection, "SELECT COALESCE(NULLIF(name, ''), class_name) AS class_name FROM classes ORDER BY id ASC");
if ($classQuery) {
    while ($row = mysqli_fetch_assoc($classQuery)) {
        if (!empty($row['class_name'])) {
            $classes[] = $row['class_name'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = trim($_POST['roll_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

    if ($roll_no === '' || $name === '' || $class === '') {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } else {
        $checkStmt = mysqli_prepare($connection, 'SELECT id FROM students WHERE roll_no = ? LIMIT 1');
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 's', $roll_no);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $exists = $checkResult && mysqli_num_rows($checkResult) > 0;
            mysqli_stmt_close($checkStmt);
        } else {
            $exists = false;
        }

        if ($exists) {
            $message = 'Roll number already exists!';
            $message_type = 'danger';
        } else {
            $class_id = null;
            $classStmt = mysqli_prepare($connection, 'SELECT id FROM classes WHERE name = ? OR class_name = ? LIMIT 1');
            if ($classStmt) {
                mysqli_stmt_bind_param($classStmt, 'ss', $class, $class);
                mysqli_stmt_execute($classStmt);
                $classResult = mysqli_stmt_get_result($classStmt);
                if ($classResult && mysqli_num_rows($classResult) > 0) {
                    $classRow = mysqli_fetch_assoc($classResult);
                    $class_id = (int) $classRow['id'];
                }
                mysqli_stmt_close($classStmt);
            }

            $insertSql = 'INSERT INTO students (roll_no, name, class, class_id, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)';
            $insertStmt = mysqli_prepare($connection, $insertSql);

            if ($insertStmt) {
                mysqli_stmt_bind_param($insertStmt, 'sssisss', $roll_no, $name, $class, $class_id, $email, $phone, $status);
                if (mysqli_stmt_execute($insertStmt)) {
                    $message = 'Student added successfully!';
                    $message_type = 'success';
                    $_POST = [];
                } else {
                    $message = 'Error adding student: ' . mysqli_error($connection);
                    $message_type = 'danger';
                }
                mysqli_stmt_close($insertStmt);
            } else {
                $message = 'Failed to prepare insert query.';
                $message_type = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fcfbfb; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom: 3px solid #f7d794; }
    .header h2 { color: #192a56; margin: 0; font-weight: 700; }
    .form-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 3px solid #f7d794; }
    .form-label { color: #192a56; font-weight: 600; margin-bottom: 8px; }
    .form-control, .form-select { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .form-control:focus, .form-select:focus { border-color: #f7d794; box-shadow: 0 0 0 3px rgba(247,215,148,0.25); }
    .btn-submit { background: #f7d794; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-submit:hover { background: #e5c682; }
    .btn-cancel { background: #e2e8f0; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; margin-left: 10px; text-decoration: none; display: inline-block; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user-plus"></i> Add New Student</h2>
    </div>

    <div class="form-card">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Roll Number *</label>
            <input type="text" class="form-control" name="roll_no" required value="<?php echo htmlspecialchars($_POST['roll_no'] ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Student Name *</label>
            <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Class *</label>
            <input list="class-list" class="form-control" name="class" placeholder="e.g., Grade 10A" required value="<?php echo htmlspecialchars($_POST['class'] ?? ''); ?>">
            <datalist id="class-list">
              <?php foreach ($classes as $className): ?>
              <option value="<?php echo htmlspecialchars($className); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="Active" <?php echo (($_POST['status'] ?? 'Active') === 'Active') ? 'selected' : ''; ?>>Active</option>
              <option value="Inactive" <?php echo (($_POST['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Student</button>
          <a href="students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>
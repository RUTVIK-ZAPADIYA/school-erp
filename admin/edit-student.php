<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

$message = '';
$message_type = '';
$student = null;

$studentsHasClass = admin_column_exists($connection, 'students', 'class');
$studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
$studentsHasEmail = admin_column_exists($connection, 'students', 'email');
$studentsHasPhone = admin_column_exists($connection, 'students', 'phone');
$studentsHasStatus = admin_column_exists($connection, 'students', 'status');

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classOptions = [];

if ($classNameColumn !== null) {
  $classResult = mysqli_query($connection, "SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classResult) {
    while ($classRow = mysqli_fetch_assoc($classResult)) {
      $classOptions[] = $classRow;
    }
  }
}

function fetch_student_by_id($connection, $studentId)
{
  $stmt = mysqli_prepare($connection, 'SELECT * FROM students WHERE id = ? LIMIT 1');
  if (!$stmt) {
    return null;
  }

  mysqli_stmt_bind_param($stmt, 'i', $studentId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = $result ? mysqli_fetch_assoc($result) : null;
  mysqli_stmt_close($stmt);

  return $row ?: null;
}

if (isset($_GET['id'])) {
  $studentId = (int) $_GET['id'];
  if ($studentId > 0) {
    $student = fetch_student_by_id($connection, $studentId);
  }

  if (!$student) {
    $message = 'Student not found!';
    $message_type = 'danger';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $studentId = (int) ($_POST['student_id'] ?? 0);
  $student = fetch_student_by_id($connection, $studentId);

  $rollNo = trim((string) ($_POST['roll_no'] ?? ''));
  $name = trim((string) ($_POST['name'] ?? ''));
  $class = trim((string) ($_POST['class'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $status = admin_normalize_status($_POST['status'] ?? 'Active', 'Active');

  if (!$student) {
    $message = 'Student not found!';
    $message_type = 'danger';
  } elseif ($rollNo === '' || $name === '' || ($studentsHasClass && $class === '')) {
    $message = 'Please fill in all required fields!';
    $message_type = 'danger';
  } else {
    $duplicateStmt = mysqli_prepare($connection, 'SELECT id FROM students WHERE roll_no = ? AND id != ? LIMIT 1');
    if ($duplicateStmt) {
      mysqli_stmt_bind_param($duplicateStmt, 'si', $rollNo, $studentId);
      mysqli_stmt_execute($duplicateStmt);
      $duplicateResult = mysqli_stmt_get_result($duplicateStmt);
      $duplicateExists = $duplicateResult && mysqli_num_rows($duplicateResult) > 0;
      mysqli_stmt_close($duplicateStmt);
    } else {
      $duplicateExists = false;
    }

    if ($duplicateExists) {
      $message = 'Roll number already exists!';
      $message_type = 'danger';
    } else {
      $classId = null;

      if ($studentsHasClassId && $class !== '' && $classNameColumn !== null) {
        $classFindSql = "SELECT id FROM classes WHERE {$classNameColumn} = ? LIMIT 1";
        $classFindStmt = mysqli_prepare($connection, $classFindSql);
        if ($classFindStmt) {
          mysqli_stmt_bind_param($classFindStmt, 's', $class);
          mysqli_stmt_execute($classFindStmt);
          $classFindResult = mysqli_stmt_get_result($classFindStmt);
          $classFindRow = $classFindResult ? mysqli_fetch_assoc($classFindResult) : null;
          if ($classFindRow) {
            $classId = (int) $classFindRow['id'];
          }
          mysqli_stmt_close($classFindStmt);
        }

        if ($classId === null) {
          $classInsertColumns = [$classNameColumn];
          $classInsertValues = ['?'];
          $classInsertTypes = 's';
          $classInsertParams = [$class];

          if ($classNameColumn === 'name' && admin_column_exists($connection, 'classes', 'class_name')) {
            $classInsertColumns[] = 'class_name';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertParams[] = $class;
          }
          if ($classNameColumn === 'class_name' && admin_column_exists($connection, 'classes', 'name')) {
            $classInsertColumns[] = 'name';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertParams[] = $class;
          }
          if (admin_column_exists($connection, 'classes', 'status')) {
            $classInsertColumns[] = 'status';
            $classInsertValues[] = '?';
            $classInsertTypes .= 's';
            $classInsertParams[] = 'Active';
          }

          $classInsertSql = 'INSERT INTO classes (' . implode(', ', $classInsertColumns) . ') VALUES (' . implode(', ', $classInsertValues) . ')';
          $classInsertStmt = mysqli_prepare($connection, $classInsertSql);
          if ($classInsertStmt && admin_bind_dynamic_params($classInsertStmt, $classInsertTypes, $classInsertParams) && mysqli_stmt_execute($classInsertStmt)) {
            $insertedClassId = (int) mysqli_insert_id($connection);
            if ($insertedClassId > 0) {
              $classId = $insertedClassId;
            }
          }
          if ($classInsertStmt) {
            mysqli_stmt_close($classInsertStmt);
          }
        }
      }

      $updateFields = ['roll_no = ?', 'name = ?'];
      $updateTypes = 'ss';
      $updateParams = [$rollNo, $name];

      if ($studentsHasClass) {
        $updateFields[] = 'class = ?';
        $updateTypes .= 's';
        $updateParams[] = $class;
      }

      if ($studentsHasClassId) {
        if ($classId === null) {
          $updateFields[] = 'class_id = NULL';
        } else {
          $updateFields[] = 'class_id = ?';
          $updateTypes .= 'i';
          $updateParams[] = $classId;
        }
      }

      if ($studentsHasEmail) {
        $updateFields[] = 'email = ?';
        $updateTypes .= 's';
        $updateParams[] = $email;
      }

      if ($studentsHasPhone) {
        $updateFields[] = 'phone = ?';
        $updateTypes .= 's';
        $updateParams[] = $phone;
      }

      if ($studentsHasStatus) {
        $updateFields[] = 'status = ?';
        $updateTypes .= 's';
        $updateParams[] = $status;
      }

      $updateSql = 'UPDATE students SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
      $updateTypes .= 'i';
      $updateParams[] = $studentId;

      $updateStmt = mysqli_prepare($connection, $updateSql);

      if ($updateStmt && admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams) && mysqli_stmt_execute($updateStmt)) {
        $message = 'Student updated successfully!';
        $message_type = 'success';
        $student = fetch_student_by_id($connection, $studentId);
      } else {
        $message = 'Failed to update student. Please try again.';
        $message_type = 'danger';
      }

      if ($updateStmt) {
        mysqli_stmt_close($updateStmt);
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
  <title>Edit Student</title>
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
    .btn-cancel { background: #e2e8f0; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; margin-left: 10px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-edit"></i> Edit Student</h2>
    </div>
    
    <div class="form-card">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo $message; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <?php if ($student): ?>
        <form method="POST" action="">
          <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Roll Number *</label>
              <input type="text" class="form-control" name="roll_no" required value="<?php echo htmlspecialchars($student['roll_no']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Student Name *</label>
              <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($student['name']); ?>">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class *</label>
              <input list="class-list" type="text" class="form-control" name="class" placeholder="e.g., Grade 10A" required value="<?php echo htmlspecialchars((string) ($student['class'] ?? '')); ?>">
              <datalist id="class-list">
                <?php foreach ($classOptions as $classOption): ?>
                  <option value="<?php echo htmlspecialchars((string) $classOption['class_name']); ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars((string) ($student['email'] ?? '')); ?>">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars((string) ($student['phone'] ?? '')); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="Active" <?php echo (($student['status'] ?? 'Active') == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo (($student['status'] ?? 'Active') == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          
          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Student</button>
            <a href="students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Student not found. <a href="students.php">Go back to students list</a></div>
      <?php endif; ?>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

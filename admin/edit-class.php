<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'classes', 'room_number', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'classes', 'capacity', 'INT NULL');
admin_ensure_column($connection, 'classes', 'academic_year', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'classes', 'description', 'TEXT NULL');

$teachers = [];
if (admin_table_exists($connection, 'teachers')) {
  $teacherResult = $connection->query('SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherResult) {
    while ($teacherRow = $teacherResult->fetch_assoc()) {
      $teachers[] = $teacherRow;
    }
  }
}

$hasName = admin_column_exists($connection, 'classes', 'name');
$hasClassName = admin_column_exists($connection, 'classes', 'class_name');
$classNameExpression = "''";
if ($hasName && $hasClassName) {
  $classNameExpression = "COALESCE(NULLIF(name, ''), class_name)";
} elseif ($hasName) {
  $classNameExpression = 'name';
} elseif ($hasClassName) {
  $classNameExpression = 'class_name';
}

$classId = (int) ($_GET['id'] ?? $_POST['class_id'] ?? 0);
$errorMessage = '';

$classRow = null;
if ($classId > 0) {
  $classStmt = $connection->prepare("SELECT *, {$classNameExpression} AS resolved_class_name FROM classes WHERE id = ? LIMIT 1");
  if ($classStmt) {
    $classStmt->bind_param('i', $classId);
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    $classRow = $classResult ? $classResult->fetch_assoc() : null;
    $classStmt->close();
  }
}

if (!$classRow) {
  $errorMessage = 'Class not found.';
}

$formData = [
  'class_name' => trim((string) ($classRow['resolved_class_name'] ?? '')),
  'section' => trim((string) ($classRow['section'] ?? '')),
  'class_teacher' => (string) ((int) ($classRow['teacher_id'] ?? 0)),
  'room_number' => trim((string) ($classRow['room_number'] ?? '')),
  'capacity' => (string) ($classRow['capacity'] ?? ''),
  'academic_year' => trim((string) ($classRow['academic_year'] ?? '')),
  'description' => trim((string) ($classRow['description'] ?? '')),
  'status' => admin_normalize_status($classRow['status'] ?? 'Active', 'Active'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $classRow) {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $formData['status'] = admin_normalize_status($formData['status'], 'Active');

  if (
    $formData['class_name'] === '' ||
    $formData['section'] === '' ||
    $formData['class_teacher'] === '' ||
    $formData['room_number'] === '' ||
    $formData['capacity'] === '' ||
    $formData['academic_year'] === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['capacity']) || (int) $formData['capacity'] <= 0) {
    $errorMessage = 'Capacity must be a positive number.';
  }

  if ($errorMessage === '') {
    if ($hasName && $hasClassName) {
      $duplicateStmt = $connection->prepare('SELECT id FROM classes WHERE (name = ? OR class_name = ?) AND section = ? AND id != ? LIMIT 1');
      if ($duplicateStmt) {
        $duplicateStmt->bind_param('sssi', $formData['class_name'], $formData['class_name'], $formData['section'], $classId);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
          $errorMessage = 'This class and section already exists.';
        }
        $duplicateStmt->close();
      }
    } elseif ($hasName || $hasClassName) {
      $column = $hasName ? 'name' : 'class_name';
      $duplicateStmt = $connection->prepare("SELECT id FROM classes WHERE {$column} = ? AND section = ? AND id != ? LIMIT 1");
      if ($duplicateStmt) {
        $duplicateStmt->bind_param('ssi', $formData['class_name'], $formData['section'], $classId);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
          $errorMessage = 'This class and section already exists.';
        }
        $duplicateStmt->close();
      }
    }
  }

  if ($errorMessage === '') {
    $updateFields = [];
    $updateTypes = '';
    $updateParams = [];

    if ($hasName) {
      $updateFields[] = 'name = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['class_name'];
    }
    if ($hasClassName) {
      $updateFields[] = 'class_name = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['class_name'];
    }
    if (admin_column_exists($connection, 'classes', 'section')) {
      $updateFields[] = 'section = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['section'];
    }
    if (admin_column_exists($connection, 'classes', 'teacher_id')) {
      $updateFields[] = 'teacher_id = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['class_teacher'];
    }
    if (admin_column_exists($connection, 'classes', 'room_number')) {
      $updateFields[] = 'room_number = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['room_number'];
    }
    if (admin_column_exists($connection, 'classes', 'capacity')) {
      $updateFields[] = 'capacity = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['capacity'];
    }
    if (admin_column_exists($connection, 'classes', 'academic_year')) {
      $updateFields[] = 'academic_year = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['academic_year'];
    }
    if (admin_column_exists($connection, 'classes', 'description')) {
      $updateFields[] = 'description = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['description'];
    }
    if (admin_column_exists($connection, 'classes', 'status')) {
      $updateFields[] = 'status = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['status'];
    }

    if (empty($updateFields)) {
      $errorMessage = 'No editable class fields were found.';
    } else {
      $updateSql = 'UPDATE classes SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
      $updateTypes .= 'i';
      $updateParams[] = $classId;

      $updateStmt = $connection->prepare($updateSql);
      if (!$updateStmt) {
        $errorMessage = 'Unable to update class right now.';
      } elseif (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
        $errorMessage = 'Unable to bind class parameters.';
      } elseif (!$updateStmt->execute()) {
        $errorMessage = 'Failed to update class. Please try again.';
      } else {
        admin_set_flash('success', 'Class updated successfully.');
        $updateStmt->close();
        header('Location: classes.php');
        exit();
      }

      if ($updateStmt) {
        $updateStmt->close();
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
  <title>Edit Class</title>
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
      <h2><i class="fas fa-edit"></i> Edit Class</h2>
    </div>

    <div class="form-card">
      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($classRow): ?>
        <form method="POST" action="">
          <input type="hidden" name="class_id" value="<?php echo (int) $classId; ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class Name *</label>
              <input type="text" class="form-control" name="class_name" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['class_name']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Section *</label>
              <input type="text" class="form-control" name="section" data-validation="required,alphabetic" data-min="1" value="<?php echo htmlspecialchars($formData['section']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class Teacher *</label>
              <select class="form-select" name="class_teacher" data-validation="required,select">
                <option value="">Select Teacher</option>
                <?php foreach ($teachers as $teacher): ?>
                  <option value="<?php echo (int) $teacher['id']; ?>" <?php echo ((string) $teacher['id'] === $formData['class_teacher']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $teacher['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Room Number *</label>
              <input type="text" class="form-control" name="room_number" data-validation="required,min,number" data-min="1" value="<?php echo htmlspecialchars($formData['room_number']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Capacity *</label>
              <input type="text" class="form-control" name="capacity" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['capacity']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Academic Year *</label>
              <input type="text" class="form-control" name="academic_year" data-validation="required,min" data-min="4" value="<?php echo htmlspecialchars($formData['academic_year']); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3" data-validation="max" data-max="500"><?php echo htmlspecialchars($formData['description']); ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Status *</label>
              <select class="form-select" name="status" data-validation="required,select">
                <option value="Active" <?php echo $formData['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $formData['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Class</button>
            <a href="classes.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Class not found. <a href="classes.php">Back to classes</a></div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

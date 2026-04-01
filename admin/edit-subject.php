<?php
// Admin page for editing subject details.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'subjects', 'class_id', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'teacher_id', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'credits', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'type', "VARCHAR(40) NULL");
admin_ensure_column($connection, 'subjects', 'description', 'TEXT NULL');

// Load class and teacher options for selection fields.
$classOptions = [];
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
if ($classNameColumn !== null) {
  $classResult = $connection->query("SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classResult) {
    while ($classRow = $classResult->fetch_assoc()) {
      $classOptions[] = $classRow;
    }
  }
}

$teacherOptions = [];
if (admin_table_exists($connection, 'teachers')) {
  $teacherResult = $connection->query('SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherResult) {
    while ($teacherRow = $teacherResult->fetch_assoc()) {
      $teacherOptions[] = $teacherRow;
    }
  }
}

$hasName = admin_column_exists($connection, 'subjects', 'name');
$hasSubjectName = admin_column_exists($connection, 'subjects', 'subject_name');
$subjectNameExpression = "''";
if ($hasName && $hasSubjectName) {
  $subjectNameExpression = "COALESCE(NULLIF(name, ''), subject_name)";
} elseif ($hasName) {
  $subjectNameExpression = 'name';
} elseif ($hasSubjectName) {
  $subjectNameExpression = 'subject_name';
}

$subjectId = (int) ($_GET['id'] ?? $_POST['subject_id'] ?? 0);
$errorMessage = '';

// Fetch the subject row that will be edited.
$subjectRow = null;
if ($subjectId > 0) {
  $subjectStmt = $connection->prepare("SELECT *, {$subjectNameExpression} AS resolved_subject_name FROM subjects WHERE id = ? LIMIT 1");
  if ($subjectStmt) {
    $subjectStmt->bind_param('i', $subjectId);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    $subjectRow = $subjectResult ? $subjectResult->fetch_assoc() : null;
    $subjectStmt->close();
  }
}

if (!$subjectRow) {
  $errorMessage = 'Subject not found.';
}

$formData = [
  'subject_name' => trim((string) ($subjectRow['resolved_subject_name'] ?? '')),
  'subject_code' => trim((string) ($subjectRow['code'] ?? '')),
  'class' => (string) ((int) ($subjectRow['class_id'] ?? 0)),
  'teacher' => (string) ((int) ($subjectRow['teacher_id'] ?? 0)),
  'credits' => (string) ($subjectRow['credits'] ?? ''),
  'type' => trim((string) ($subjectRow['type'] ?? '')),
  'description' => trim((string) ($subjectRow['description'] ?? '')),
  'status' => admin_normalize_status($subjectRow['status'] ?? 'Active', 'Active'),
];

// Handle submitted subject updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $subjectRow) {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $formData['status'] = admin_normalize_status($formData['status'], 'Active');

  // Validate required fields and numeric credits.
  if (
    $formData['subject_name'] === '' ||
    $formData['subject_code'] === '' ||
    $formData['class'] === '' ||
    $formData['teacher'] === '' ||
    $formData['credits'] === '' ||
    $formData['type'] === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['credits']) || (int) $formData['credits'] <= 0) {
    $errorMessage = 'Credits must be a positive number.';
  }

  // Enforce unique subject codes across rows.
  if ($errorMessage === '' && admin_column_exists($connection, 'subjects', 'code')) {
    $codeCheckStmt = $connection->prepare('SELECT id FROM subjects WHERE code = ? AND id != ? LIMIT 1');
    if ($codeCheckStmt) {
      $codeCheckStmt->bind_param('si', $formData['subject_code'], $subjectId);
      $codeCheckStmt->execute();
      $codeCheckResult = $codeCheckStmt->get_result();
      if ($codeCheckResult && $codeCheckResult->num_rows > 0) {
        $errorMessage = 'Subject code already exists.';
      }
      $codeCheckStmt->close();
    }
  }

  // Build and run the subject update query.
  if ($errorMessage === '') {
    $updateFields = [];
    $updateTypes = '';
    $updateParams = [];

    if ($hasName) {
      $updateFields[] = 'name = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['subject_name'];
    }
    if ($hasSubjectName) {
      $updateFields[] = 'subject_name = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['subject_name'];
    }
    if (admin_column_exists($connection, 'subjects', 'code')) {
      $updateFields[] = 'code = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['subject_code'];
    }
    if (admin_column_exists($connection, 'subjects', 'class_id')) {
      $updateFields[] = 'class_id = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['class'];
    }
    if (admin_column_exists($connection, 'subjects', 'teacher_id')) {
      $updateFields[] = 'teacher_id = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['teacher'];
    }
    if (admin_column_exists($connection, 'subjects', 'credits')) {
      $updateFields[] = 'credits = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['credits'];
    }
    if (admin_column_exists($connection, 'subjects', 'type')) {
      $updateFields[] = 'type = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['type'];
    }
    if (admin_column_exists($connection, 'subjects', 'description')) {
      $updateFields[] = 'description = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['description'];
    }
    if (admin_column_exists($connection, 'subjects', 'status')) {
      $updateFields[] = 'status = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['status'];
    }

    if (empty($updateFields)) {
      $errorMessage = 'No editable subject fields were found.';
    } else {
      $updateSql = 'UPDATE subjects SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
      $updateTypes .= 'i';
      $updateParams[] = $subjectId;

      $updateStmt = $connection->prepare($updateSql);
      if (!$updateStmt) {
        $errorMessage = 'Unable to update subject right now.';
      } elseif (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
        $errorMessage = 'Unable to bind subject parameters.';
      } elseif (!$updateStmt->execute()) {
        $errorMessage = 'Failed to update subject. Please try again.';
      } else {
        admin_set_flash('success', 'Subject updated successfully.');
        $updateStmt->close();
        header('Location: subjects.php');
        exit();
      }

      if ($updateStmt) {
        $updateStmt->close();
      }
    }
  }
}
?>
<!-- Render the subject edit form and validation feedback. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Subject</title>
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
      <h2><i class="fas fa-edit"></i> Edit Subject</h2>
    </div>

    <div class="form-card">
      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($subjectRow): ?>
        <form method="POST" action="" novalidate>
          <input type="hidden" name="subject_id" value="<?php echo (int) $subjectId; ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Subject Name *</label>
              <input type="text" class="form-control" name="subject_name" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['subject_name']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Subject Code *</label>
              <input type="text" class="form-control" name="subject_code" data-validation="required,min,max" data-min="2" data-max="20" value="<?php echo htmlspecialchars($formData['subject_code']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class *</label>
              <select class="form-select" name="class" data-validation="required,select">
                <option value="">Select Class</option>
                <?php foreach ($classOptions as $classOption): ?>
                  <option value="<?php echo (int) $classOption['id']; ?>" <?php echo ((string) $classOption['id'] === $formData['class']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $classOption['class_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Teacher *</label>
              <select class="form-select" name="teacher" data-validation="required,select">
                <option value="">Select Teacher</option>
                <?php foreach ($teacherOptions as $teacherOption): ?>
                  <option value="<?php echo (int) $teacherOption['id']; ?>" <?php echo ((string) $teacherOption['id'] === $formData['teacher']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $teacherOption['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Credits *</label>
              <input type="text" class="form-control" name="credits" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['credits']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Type *</label>
              <select class="form-select" name="type" data-validation="required,select">
                <option value="">Select Type</option>
                <option value="Core" <?php echo $formData['type'] === 'Core' ? 'selected' : ''; ?>>Core</option>
                <option value="Elective" <?php echo $formData['type'] === 'Elective' ? 'selected' : ''; ?>>Elective</option>
                <option value="Optional" <?php echo $formData['type'] === 'Optional' ? 'selected' : ''; ?>>Optional</option>
              </select>
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
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Subject</button>
            <a href="subjects.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Subject not found. <a href="subjects.php">Back to subjects</a></div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

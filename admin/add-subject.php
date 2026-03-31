<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'subjects', 'class_id', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'teacher_id', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'credits', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'type', "VARCHAR(40) NULL");
admin_ensure_column($connection, 'subjects', 'description', 'TEXT NULL');

$classOptions = [];
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
if ($classNameColumn !== null) {
  $classResult = mysqli_query($connection, "SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classResult) {
    while ($classRow = mysqli_fetch_assoc($classResult)) {
      $classOptions[] = $classRow;
    }
  }
}

$teacherOptions = [];
if (admin_table_exists($connection, 'teachers')) {
  $teacherResult = mysqli_query($connection, 'SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherResult) {
    while ($teacherRow = mysqli_fetch_assoc($teacherResult)) {
      $teacherOptions[] = $teacherRow;
    }
  }
}

$formData = [
  'subject_name' => '',
  'subject_code' => '',
  'class' => '',
  'teacher' => '',
  'credits' => '',
  'type' => '',
  'description' => '',
];

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

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

  if ($errorMessage === '' && admin_column_exists($connection, 'subjects', 'code')) {
    $codeCheckStmt = mysqli_prepare($connection, 'SELECT id FROM subjects WHERE code = ? LIMIT 1');
    if ($codeCheckStmt) {
      mysqli_stmt_bind_param($codeCheckStmt, 's', $formData['subject_code']);
      mysqli_stmt_execute($codeCheckStmt);
      $codeCheckResult = mysqli_stmt_get_result($codeCheckStmt);
      if ($codeCheckResult && mysqli_num_rows($codeCheckResult) > 0) {
        $errorMessage = 'Subject code already exists.';
      }
      mysqli_stmt_close($codeCheckStmt);
    }
  }

  if ($errorMessage === '') {
    $insertColumns = [];
    $insertValues = [];
    $insertTypes = '';
    $insertParams = [];

    if (admin_column_exists($connection, 'subjects', 'name')) {
      $insertColumns[] = 'name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['subject_name'];
    }
    if (admin_column_exists($connection, 'subjects', 'subject_name')) {
      $insertColumns[] = 'subject_name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['subject_name'];
    }
    if (admin_column_exists($connection, 'subjects', 'code')) {
      $insertColumns[] = 'code';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['subject_code'];
    }
    if (admin_column_exists($connection, 'subjects', 'class_id')) {
      $insertColumns[] = 'class_id';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['class'];
    }
    if (admin_column_exists($connection, 'subjects', 'teacher_id')) {
      $insertColumns[] = 'teacher_id';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['teacher'];
    }
    if (admin_column_exists($connection, 'subjects', 'credits')) {
      $insertColumns[] = 'credits';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['credits'];
    }
    if (admin_column_exists($connection, 'subjects', 'type')) {
      $insertColumns[] = 'type';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['type'];
    }
    if (admin_column_exists($connection, 'subjects', 'description')) {
      $insertColumns[] = 'description';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['description'];
    }
    if (admin_column_exists($connection, 'subjects', 'status')) {
      $insertColumns[] = 'status';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = 'Active';
    }

    $insertSql = 'INSERT INTO subjects (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $insertStmt = mysqli_prepare($connection, $insertSql);

    if (!$insertStmt) {
      $errorMessage = 'Unable to save subject right now.';
    } else {
      if (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
        $errorMessage = 'Unable to bind subject parameters.';
      } elseif (!mysqli_stmt_execute($insertStmt)) {
        $errorMessage = 'Failed to add subject. Please try again.';
      }
      mysqli_stmt_close($insertStmt);
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Subject added successfully.');
    header('Location: add-subject.php');
    exit();
  }
}

$flash = admin_pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Subject</title>
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
      <h2><i class="fas fa-book"></i> Add New Subject</h2>
    </div>
    
    <div class="form-card">
      <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($flash['message']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Subject Name *</label>
            <input type="text" class="form-control" name="subject_name" placeholder="e.g., Mathematics" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['subject_name']); ?>">
            <div id="subject_name_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Subject Code *</label>
            <input type="text" class="form-control" name="subject_code" placeholder="e.g., MATH101" data-validation="required,min,max" data-min="2" data-max="20" value="<?php echo htmlspecialchars($formData['subject_code']); ?>">
            <div id="subject_code_error" class="invalid-feedback"></div>
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
            <div id="class_error" class="invalid-feedback"></div>
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
            <div id="teacher_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Credits *</label>
            <input type="text" class="form-control" name="credits" placeholder="e.g., 4" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['credits']); ?>">
            <div id="credits_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Type *</label>
            <select class="form-select" name="type" data-validation="required,select">
              <option value="">Select Type</option>
              <option value="Core" <?php echo $formData['type'] === 'Core' ? 'selected' : ''; ?>>Core</option>
              <option value="Elective" <?php echo $formData['type'] === 'Elective' ? 'selected' : ''; ?>>Elective</option>
              <option value="Optional" <?php echo $formData['type'] === 'Optional' ? 'selected' : ''; ?>>Optional</option>
            </select>
            <div id="type_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Subject description" data-validation="max" data-max="500"><?php echo htmlspecialchars($formData['description']); ?></textarea>
          <div id="description_error" class="invalid-feedback"></div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Subject</button>
          <a href="subjects.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

<?php
// Admin page for creating class records.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'classes', 'room_number', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'classes', 'capacity', 'INT NULL');
admin_ensure_column($connection, 'classes', 'academic_year', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'classes', 'description', 'TEXT NULL');

// Load teacher choices used by the class teacher dropdown.
$teachers = [];
if (admin_table_exists($connection, 'teachers')) {
  $teacherResult = $connection->query( 'SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherResult) {
    while ($teacherRow = $teacherResult->fetch_assoc()) {
      $teachers[] = $teacherRow;
    }
  }
}

$formData = [
  'class_name' => '',
  'section' => '',
  'class_teacher' => '',
  'room_number' => '',
  'capacity' => '',
  'academic_year' => '',
  'description' => '',
];

$errorMessage = '';

// Handle submitted form data for class creation.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  // Validate required fields and numeric constraints before writes.
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

  // Check for duplicate class and section combinations.
  if ($errorMessage === '') {
    $classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
    if ($classNameColumn !== null) {
      $duplicateCheckSql = "SELECT id FROM classes WHERE {$classNameColumn} = ? AND section = ? LIMIT 1";
      $duplicateStmt = $connection->prepare( $duplicateCheckSql);
      if ($duplicateStmt) {
        $duplicateStmt->bind_param( 'ss', $formData['class_name'], $formData['section']);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        if ($duplicateResult && $duplicateResult->num_rows > 0) {
          $errorMessage = 'This class and section already exists.';
        }
        $duplicateStmt->close();
      }
    }
  }

  // Build and execute a schema-aware insert statement.
  if ($errorMessage === '') {
    $insertColumns = [];
    $insertValues = [];
    $insertTypes = '';
    $insertParams = [];

    if (admin_column_exists($connection, 'classes', 'name')) {
      $insertColumns[] = 'name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['class_name'];
    }
    if (admin_column_exists($connection, 'classes', 'class_name')) {
      $insertColumns[] = 'class_name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['class_name'];
    }
    if (admin_column_exists($connection, 'classes', 'section')) {
      $insertColumns[] = 'section';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['section'];
    }
    if (admin_column_exists($connection, 'classes', 'teacher_id')) {
      $insertColumns[] = 'teacher_id';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['class_teacher'];
    }
    if (admin_column_exists($connection, 'classes', 'room_number')) {
      $insertColumns[] = 'room_number';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['room_number'];
    }
    if (admin_column_exists($connection, 'classes', 'capacity')) {
      $insertColumns[] = 'capacity';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['capacity'];
    }
    if (admin_column_exists($connection, 'classes', 'academic_year')) {
      $insertColumns[] = 'academic_year';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['academic_year'];
    }
    if (admin_column_exists($connection, 'classes', 'description')) {
      $insertColumns[] = 'description';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['description'];
    }
    if (admin_column_exists($connection, 'classes', 'status')) {
      $insertColumns[] = 'status';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = 'Active';
    }

    $insertSql = 'INSERT INTO classes (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $insertStmt = $connection->prepare( $insertSql);

    if (!$insertStmt) {
      $errorMessage = 'Unable to save class right now.';
    } else {
      if (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
        $errorMessage = 'Unable to bind class parameters.';
      } elseif (!$insertStmt->execute()) {
        $errorMessage = 'Failed to add class. Please try again.';
      }
      $insertStmt->close();
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Class added successfully.');
    header('Location: add-class.php');
    exit();
  }
}

$flash = admin_pull_flash();
?>
<!-- Render the add class form and server-side feedback messages. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Class</title>
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
      <h2><i class="fas fa-school"></i> Add New Class</h2>
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

      <form method="POST" action="" novalidate>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Class Name *</label>
            <input type="text" class="form-control" name="class_name" placeholder="e.g., Grade 10A" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['class_name']); ?>">
            <div id="class_name_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Section *</label>
            <input type="text" class="form-control" name="section" placeholder="e.g., A, B, C" data-validation="required,alphabetic" data-min="1" value="<?php echo htmlspecialchars($formData['section']); ?>">
            <div id="section_error" class="invalid-feedback"></div>
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
            <div id="class_teacher_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Room Number *</label>
            <input type="text" class="form-control" name="room_number" placeholder="e.g., 101" data-validation="required,min,number" data-min="1" value="<?php echo htmlspecialchars($formData['room_number']); ?>">
            <div id="room_number_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Capacity *</label>
            <input type="text" class="form-control" name="capacity" placeholder="Maximum students" data-validation="required,number" data-min="1" value="<?php echo htmlspecialchars($formData['capacity']); ?>">
            <div id="capacity_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Academic Year *</label>
            <input type="text" class="form-control" name="academic_year" placeholder="e.g., 2024-2025" data-validation="required,min" data-min="4" value="<?php echo htmlspecialchars($formData['academic_year']); ?>">
            <div id="academic_year_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Optional class description" data-validation="max" data-max="500"><?php echo htmlspecialchars($formData['description']); ?></textarea>
          <div id="description_error" class="invalid-feedback"></div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Class</button>
          <a href="classes.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

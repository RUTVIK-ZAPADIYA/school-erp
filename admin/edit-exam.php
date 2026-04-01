<?php
// Admin page for editing exam details.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'exams', 'exam_type', "VARCHAR(60) NULL");
admin_ensure_column($connection, 'exams', 'start_time', 'TIME NULL');
admin_ensure_column($connection, 'exams', 'duration', 'INT NULL');
admin_ensure_column($connection, 'exams', 'total_marks', 'INT NULL');
admin_ensure_column($connection, 'exams', 'room_number', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'exams', 'invigilator', 'INT NULL');
admin_ensure_column($connection, 'exams', 'instructions', 'TEXT NULL');

// Load option lists for class, subject, and invigilator fields.
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

$subjectOptions = [];
$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
if ($subjectNameColumn !== null) {
  $subjectResult = $connection->query("SELECT id, {$subjectNameColumn} AS subject_name FROM subjects ORDER BY {$subjectNameColumn} ASC");
  if ($subjectResult) {
    while ($subjectRow = $subjectResult->fetch_assoc()) {
      $subjectOptions[] = $subjectRow;
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

$examId = (int) ($_GET['id'] ?? $_POST['exam_id'] ?? 0);
$errorMessage = '';

// Fetch the exam row currently being edited.
$examRow = null;
if ($examId > 0) {
  $examStmt = $connection->prepare('SELECT * FROM exams WHERE id = ? LIMIT 1');
  if ($examStmt) {
    $examStmt->bind_param('i', $examId);
    $examStmt->execute();
    $examResult = $examStmt->get_result();
    $examRow = $examResult ? $examResult->fetch_assoc() : null;
    $examStmt->close();
  }
}

if (!$examRow) {
  $errorMessage = 'Exam not found.';
}

$formData = [
  'exam_name' => trim((string) ($examRow['exam_name'] ?? '')),
  'exam_type' => trim((string) ($examRow['exam_type'] ?? '')),
  'class' => (string) ((int) ($examRow['class_id'] ?? 0)),
  'subject' => (string) ((int) ($examRow['subject_id'] ?? 0)),
  'exam_date' => trim((string) ($examRow['exam_date'] ?? '')),
  'start_time' => trim((string) ($examRow['start_time'] ?? '')),
  'duration' => (string) ($examRow['duration'] ?? ''),
  'total_marks' => (string) ($examRow['total_marks'] ?? ''),
  'room_number' => trim((string) ($examRow['room_number'] ?? '')),
  'invigilator' => (string) ((int) ($examRow['invigilator'] ?? 0)),
  'instructions' => trim((string) ($examRow['instructions'] ?? '')),
];

// Handle exam update form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $examRow) {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  // Validate required scheduling fields and numeric values.
  if (
    $formData['exam_name'] === '' ||
    $formData['exam_type'] === '' ||
    $formData['class'] === '' ||
    $formData['subject'] === '' ||
    $formData['exam_date'] === '' ||
    $formData['start_time'] === '' ||
    $formData['duration'] === '' ||
    $formData['total_marks'] === '' ||
    $formData['room_number'] === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['duration']) || (int) $formData['duration'] <= 0) {
    $errorMessage = 'Duration must be a positive number.';
  } elseif (!is_numeric($formData['total_marks']) || (int) $formData['total_marks'] <= 0) {
    $errorMessage = 'Total marks must be a positive number.';
  }

  // Build and execute a dynamic update across available exam columns.
  if ($errorMessage === '') {
    $status = strtotime($formData['exam_date']) < strtotime(date('Y-m-d')) ? 'Completed' : 'Scheduled';

    $updateFields = [];
    $updateTypes = '';
    $updateParams = [];

    if (admin_column_exists($connection, 'exams', 'exam_name')) {
      $updateFields[] = 'exam_name = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['exam_name'];
    }
    if (admin_column_exists($connection, 'exams', 'exam_type')) {
      $updateFields[] = 'exam_type = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['exam_type'];
    }
    if (admin_column_exists($connection, 'exams', 'class_id')) {
      $updateFields[] = 'class_id = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['class'];
    }
    if (admin_column_exists($connection, 'exams', 'subject_id')) {
      $updateFields[] = 'subject_id = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['subject'];
    }
    if (admin_column_exists($connection, 'exams', 'exam_date')) {
      $updateFields[] = 'exam_date = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['exam_date'];
    }
    if (admin_column_exists($connection, 'exams', 'start_time')) {
      $updateFields[] = 'start_time = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['start_time'];
    }
    if (admin_column_exists($connection, 'exams', 'duration')) {
      $updateFields[] = 'duration = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['duration'];
    }
    if (admin_column_exists($connection, 'exams', 'total_marks')) {
      $updateFields[] = 'total_marks = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['total_marks'];
    }
    if (admin_column_exists($connection, 'exams', 'room_number')) {
      $updateFields[] = 'room_number = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['room_number'];
    }
    if (admin_column_exists($connection, 'exams', 'invigilator')) {
      if ($formData['invigilator'] === '') {
        $updateFields[] = 'invigilator = NULL';
      } else {
        $updateFields[] = 'invigilator = ?';
        $updateTypes .= 'i';
        $updateParams[] = (int) $formData['invigilator'];
      }
    }
    if (admin_column_exists($connection, 'exams', 'instructions')) {
      $updateFields[] = 'instructions = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['instructions'];
    }
    if (admin_column_exists($connection, 'exams', 'status')) {
      $updateFields[] = 'status = ?';
      $updateTypes .= 's';
      $updateParams[] = $status;
    }

    if (empty($updateFields)) {
      $errorMessage = 'No editable exam fields were found.';
    } else {
      $updateSql = 'UPDATE exams SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
      $updateTypes .= 'i';
      $updateParams[] = $examId;

      $updateStmt = $connection->prepare($updateSql);
      if (!$updateStmt) {
        $errorMessage = 'Unable to update exam right now.';
      } elseif (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
        $errorMessage = 'Unable to bind exam parameters.';
      } elseif (!$updateStmt->execute()) {
        $errorMessage = 'Failed to update exam. Please try again.';
      } else {
        admin_set_flash('success', 'Exam updated successfully.');
        $updateStmt->close();
        header('Location: exams.php');
        exit();
      }

      if ($updateStmt) {
        $updateStmt->close();
      }
    }
  }
}
?>
<!-- Render the exam edit form and server-side errors. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Exam</title>
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
      <h2><i class="fas fa-edit"></i> Edit Exam</h2>
    </div>

    <div class="form-card">
      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($examRow): ?>
        <form method="POST" action="" novalidate>
          <input type="hidden" name="exam_id" value="<?php echo (int) $examId; ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Exam Name *</label>
              <input type="text" class="form-control" name="exam_name" data-validation="required,min" data-min="3" value="<?php echo htmlspecialchars($formData['exam_name']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Exam Type *</label>
              <select class="form-select" name="exam_type" data-validation="required,select">
                <option value="">Select Type</option>
                <option value="Mid-term" <?php echo $formData['exam_type'] === 'Mid-term' ? 'selected' : ''; ?>>Mid-term</option>
                <option value="Final" <?php echo $formData['exam_type'] === 'Final' ? 'selected' : ''; ?>>Final</option>
                <option value="Unit Test" <?php echo $formData['exam_type'] === 'Unit Test' ? 'selected' : ''; ?>>Unit Test</option>
                <option value="Quiz" <?php echo $formData['exam_type'] === 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
              </select>
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
              <label class="form-label">Subject *</label>
              <select class="form-select" name="subject" data-validation="required,select">
                <option value="">Select Subject</option>
                <?php foreach ($subjectOptions as $subjectOption): ?>
                  <option value="<?php echo (int) $subjectOption['id']; ?>" <?php echo ((string) $subjectOption['id'] === $formData['subject']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $subjectOption['subject_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Exam Date *</label>
              <input type="date" class="form-control" name="exam_date" data-validation="required" value="<?php echo htmlspecialchars($formData['exam_date']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Time *</label>
              <input type="time" class="form-control" name="start_time" data-validation="required" value="<?php echo htmlspecialchars($formData['start_time']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Duration (Minutes) *</label>
              <input type="text" class="form-control" name="duration" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['duration']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Total Marks *</label>
              <input type="text" class="form-control" name="total_marks" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['total_marks']); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Room Number *</label>
              <input type="text" class="form-control" name="room_number" data-validation="required,min,number" data-min="1" value="<?php echo htmlspecialchars($formData['room_number']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Invigilator</label>
              <select class="form-select" name="invigilator" data-validation="select">
                <option value="">Select Teacher</option>
                <?php foreach ($teacherOptions as $teacherOption): ?>
                  <option value="<?php echo (int) $teacherOption['id']; ?>" <?php echo ((string) $teacherOption['id'] === $formData['invigilator']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $teacherOption['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Instructions</label>
            <textarea class="form-control" name="instructions" rows="3" data-validation="max" data-max="1000"><?php echo htmlspecialchars($formData['instructions']); ?></textarea>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Exam</button>
            <a href="exams.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Exam not found. <a href="exams.php">Back to exams</a></div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

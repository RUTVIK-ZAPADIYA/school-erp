<?php
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

$classOptions = [];
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
if ($classNameColumn !== null) {
  $classResult = $connection->query( "SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classResult) {
    while ($classRow = $classResult->fetch_assoc()) {
      $classOptions[] = $classRow;
    }
  }
}

$subjectOptions = [];
$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
if ($subjectNameColumn !== null) {
  $subjectResult = $connection->query( "SELECT id, {$subjectNameColumn} AS subject_name FROM subjects ORDER BY {$subjectNameColumn} ASC");
  if ($subjectResult) {
    while ($subjectRow = $subjectResult->fetch_assoc()) {
      $subjectOptions[] = $subjectRow;
    }
  }
}

$teacherOptions = [];
if (admin_table_exists($connection, 'teachers')) {
  $teacherResult = $connection->query( 'SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherResult) {
    while ($teacherRow = $teacherResult->fetch_assoc()) {
      $teacherOptions[] = $teacherRow;
    }
  }
}

$formData = [
  'exam_name' => '',
  'exam_type' => '',
  'class' => '',
  'subject' => '',
  'exam_date' => '',
  'start_time' => '',
  'duration' => '',
  'total_marks' => '',
  'room_number' => '',
  'invigilator' => '',
  'instructions' => '',
];

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

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

  if ($errorMessage === '') {
    $status = strtotime($formData['exam_date']) < strtotime(date('Y-m-d')) ? 'Completed' : 'Scheduled';

    $insertColumns = ['exam_name'];
    $insertValues = ['?'];
    $insertTypes = 's';
    $insertParams = [$formData['exam_name']];

    if (admin_column_exists($connection, 'exams', 'exam_type')) {
      $insertColumns[] = 'exam_type';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['exam_type'];
    }
    if (admin_column_exists($connection, 'exams', 'class_id')) {
      $insertColumns[] = 'class_id';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['class'];
    }
    if (admin_column_exists($connection, 'exams', 'subject_id')) {
      $insertColumns[] = 'subject_id';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['subject'];
    }
    if (admin_column_exists($connection, 'exams', 'exam_date')) {
      $insertColumns[] = 'exam_date';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['exam_date'];
    }
    if (admin_column_exists($connection, 'exams', 'start_time')) {
      $insertColumns[] = 'start_time';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['start_time'];
    }
    if (admin_column_exists($connection, 'exams', 'duration')) {
      $insertColumns[] = 'duration';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['duration'];
    }
    if (admin_column_exists($connection, 'exams', 'total_marks')) {
      $insertColumns[] = 'total_marks';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['total_marks'];
    }
    if (admin_column_exists($connection, 'exams', 'room_number')) {
      $insertColumns[] = 'room_number';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['room_number'];
    }
    if (admin_column_exists($connection, 'exams', 'invigilator')) {
      $insertColumns[] = 'invigilator';
      if ($formData['invigilator'] === '') {
        $insertValues[] = 'NULL';
      } else {
        $insertValues[] = '?';
        $insertTypes .= 'i';
        $insertParams[] = (int) $formData['invigilator'];
      }
    }
    if (admin_column_exists($connection, 'exams', 'instructions')) {
      $insertColumns[] = 'instructions';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['instructions'];
    }
    if (admin_column_exists($connection, 'exams', 'status')) {
      $insertColumns[] = 'status';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $status;
    }

    $insertSql = 'INSERT INTO exams (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $insertStmt = $connection->prepare( $insertSql);

    if (!$insertStmt) {
      $errorMessage = 'Unable to schedule exam right now.';
    } else {
      if (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
        $errorMessage = 'Unable to bind exam parameters.';
      } elseif (!$insertStmt->execute()) {
        $errorMessage = 'Failed to schedule exam. Please try again.';
      }
      $insertStmt->close();
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Exam scheduled successfully.');
    header('Location: add-exam.php');
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
  <title>Schedule New Exam</title>
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
      <h2><i class="fas fa-file-alt"></i> Schedule New Exam</h2>
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
            <label class="form-label">Exam Name *</label>
            <input type="text" class="form-control" name="exam_name" placeholder="e.g., Mid-term Exam" data-validation="required,min" data-min="3" value="<?php echo htmlspecialchars($formData['exam_name']); ?>">
            <div id="exam_name_error" class="invalid-feedback"></div>
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
            <div id="exam_type_error" class="invalid-feedback"></div>
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
            <label class="form-label">Subject *</label>
            <select class="form-select" name="subject" data-validation="required,select">
              <option value="">Select Subject</option>
              <?php foreach ($subjectOptions as $subjectOption): ?>
                <option value="<?php echo (int) $subjectOption['id']; ?>" <?php echo ((string) $subjectOption['id'] === $formData['subject']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $subjectOption['subject_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="subject_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Exam Date *</label>
            <input type="date" class="form-control" name="exam_date" data-validation="required" value="<?php echo htmlspecialchars($formData['exam_date']); ?>">
            <div id="exam_date_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Start Time *</label>
            <input type="time" class="form-control" name="start_time" data-validation="required" value="<?php echo htmlspecialchars($formData['start_time']); ?>">
            <div id="start_time_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Duration (Minutes) *</label>
            <input type="text" class="form-control" name="duration" placeholder="e.g., 120" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['duration']); ?>">
            <div id="duration_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Total Marks *</label>
            <input type="text" class="form-control" name="total_marks" placeholder="e.g., 100" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['total_marks']); ?>">
            <div id="total_marks_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Room Number *</label>
            <input type="text" class="form-control" name="room_number" placeholder="e.g., 101" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['room_number']); ?>">
            <div id="room_number_error" class="invalid-feedback"></div>
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
            <div id="invigilator_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Instructions</label>
          <textarea class="form-control" name="instructions" rows="3" placeholder="Exam instructions for students" data-validation="max" data-max="1000"><?php echo htmlspecialchars($formData['instructions']); ?></textarea>
          <div id="instructions_error" class="invalid-feedback"></div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Schedule Exam</button>
          <a href="exams.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

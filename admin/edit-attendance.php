<?php
// Admin page for editing attendance entries.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

$attendanceDateColumn = admin_first_existing_column($connection, 'attendance', ['attendance_date', 'date']);
$attendanceHasClassId = admin_column_exists($connection, 'attendance', 'class_id');
$attendanceHasSubjectId = admin_column_exists($connection, 'attendance', 'subject_id');
$attendanceHasTeacherId = admin_column_exists($connection, 'attendance', 'teacher_id');
$studentsHasClassId = admin_column_exists($connection, 'students', 'class_id');
$studentsHasClass = admin_column_exists($connection, 'students', 'class');
$studentRollColumn = admin_first_existing_column($connection, 'students', ['roll_no', 'roll_number']);
$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);

// Load dropdown data required by the attendance edit form.
$classOptions = [];
if ($classNameColumn !== null) {
  $classResult = $connection->query("SELECT id, {$classNameColumn} AS class_name FROM classes ORDER BY {$classNameColumn} ASC");
  if ($classResult) {
    while ($classRow = $classResult->fetch_assoc()) {
      $classOptions[] = $classRow;
    }
  }
}

$subjectOptions = [];
if ($attendanceHasSubjectId && $subjectNameColumn !== null) {
  $subjectResult = $connection->query("SELECT id, {$subjectNameColumn} AS subject_name FROM subjects ORDER BY {$subjectNameColumn} ASC");
  if ($subjectResult) {
    while ($subjectRow = $subjectResult->fetch_assoc()) {
      $subjectOptions[] = $subjectRow;
    }
  }
}

$teacherOptions = [];
if ($attendanceHasTeacherId && admin_table_exists($connection, 'teachers')) {
  $teacherResult = $connection->query('SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherResult) {
    while ($teacherRow = $teacherResult->fetch_assoc()) {
      $teacherOptions[] = $teacherRow;
    }
  }
}

$rollExpr = $studentRollColumn !== null ? "s.{$studentRollColumn}" : "''";
$studentSql = "SELECT s.id, s.name, {$rollExpr} AS roll_no";
if ($studentsHasClassId && $classNameColumn !== null) {
  $studentSql .= ", c.{$classNameColumn} AS mapped_class";
} else {
  $studentSql .= ", '' AS mapped_class";
}
if ($studentsHasClass) {
  $studentSql .= ', s.class AS legacy_class';
} else {
  $studentSql .= ", '' AS legacy_class";
}
$studentSql .= ' FROM students s';
if ($studentsHasClassId && $classNameColumn !== null) {
  $studentSql .= ' LEFT JOIN classes c ON c.id = s.class_id';
}
$studentSql .= ' ORDER BY s.name ASC';

$studentOptions = [];
$studentResult = $connection->query($studentSql);
if ($studentResult) {
  while ($studentRow = $studentResult->fetch_assoc()) {
    $displayClass = trim((string) ($studentRow['mapped_class'] ?? ''));
    if ($displayClass === '') {
      $displayClass = trim((string) ($studentRow['legacy_class'] ?? ''));
    }
    $studentRow['display_class'] = $displayClass;
    $studentOptions[] = $studentRow;
  }
}

$attendanceId = (int) ($_GET['id'] ?? $_POST['attendance_id'] ?? 0);
$errorMessage = '';

// Fetch the attendance row being edited.
$attendanceRow = null;
if ($attendanceId > 0) {
  $attendanceStmt = $connection->prepare('SELECT * FROM attendance WHERE id = ? LIMIT 1');
  if ($attendanceStmt) {
    $attendanceStmt->bind_param('i', $attendanceId);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    $attendanceRow = $attendanceResult ? $attendanceResult->fetch_assoc() : null;
    $attendanceStmt->close();
  }
}

if (!$attendanceRow) {
  $errorMessage = 'Attendance record not found.';
} elseif ($attendanceDateColumn === null) {
  $errorMessage = 'Attendance date column is not available.';
}

$formData = [
  'student_id' => (string) ((int) ($attendanceRow['student_id'] ?? 0)),
  'class_id' => (string) ((int) ($attendanceRow['class_id'] ?? 0)),
  'subject_id' => (string) ((int) ($attendanceRow['subject_id'] ?? 0)),
  'date' => trim((string) ($attendanceDateColumn !== null ? ($attendanceRow[$attendanceDateColumn] ?? '') : '')),
  'status' => strtolower(trim((string) ($attendanceRow['status'] ?? ''))),
  'teacher_id' => (string) ((int) ($attendanceRow['teacher_id'] ?? 0)),
];

if ($formData['status'] === '') {
  $formData['status'] = 'absent';
}

// Handle submitted updates for the attendance record.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $attendanceRow && $attendanceDateColumn !== null) {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $formData['status'] = strtolower($formData['status']);
  if (!in_array($formData['status'], ['present', 'absent', 'late', 'leave'], true)) {
    $formData['status'] = 'absent';
  }

  // Validate required selections and date format.
  if ((int) $formData['student_id'] <= 0) {
    $errorMessage = 'Please select a student.';
  } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData['date'])) {
    $errorMessage = 'Please enter a valid attendance date.';
  }

  if ($errorMessage === '' && $attendanceHasClassId && (int) $formData['class_id'] <= 0 && $studentsHasClassId) {
    $studentClassStmt = $connection->prepare('SELECT class_id FROM students WHERE id = ? LIMIT 1');
    if ($studentClassStmt) {
      $selectedStudentId = (int) $formData['student_id'];
      $studentClassStmt->bind_param('i', $selectedStudentId);
      $studentClassStmt->execute();
      $studentClassResult = $studentClassStmt->get_result();
      $studentClassRow = $studentClassResult ? $studentClassResult->fetch_assoc() : null;
      $derivedClassId = (int) ($studentClassRow['class_id'] ?? 0);
      if ($derivedClassId > 0) {
        $formData['class_id'] = (string) $derivedClassId;
      }
      $studentClassStmt->close();
    }
  }

  if ($errorMessage === '' && $attendanceHasClassId && (int) $formData['class_id'] <= 0) {
    $errorMessage = 'Please select a class.';
  }

  // Build and execute a schema-aware update statement.
  if ($errorMessage === '') {
    $updateFields = [];
    $updateTypes = '';
    $updateParams = [];

    if (admin_column_exists($connection, 'attendance', 'student_id')) {
      $updateFields[] = 'student_id = ?';
      $updateTypes .= 'i';
      $updateParams[] = (int) $formData['student_id'];
    }

    if ($attendanceHasClassId) {
      if ((int) $formData['class_id'] > 0) {
        $updateFields[] = 'class_id = ?';
        $updateTypes .= 'i';
        $updateParams[] = (int) $formData['class_id'];
      } else {
        $updateFields[] = 'class_id = NULL';
      }
    }

    if ($attendanceHasSubjectId) {
      if ((int) $formData['subject_id'] > 0) {
        $updateFields[] = 'subject_id = ?';
        $updateTypes .= 'i';
        $updateParams[] = (int) $formData['subject_id'];
      } else {
        $updateFields[] = 'subject_id = NULL';
      }
    }

    $updateFields[] = "`{$attendanceDateColumn}` = ?";
    $updateTypes .= 's';
    $updateParams[] = $formData['date'];

    if (admin_column_exists($connection, 'attendance', 'status')) {
      $updateFields[] = 'status = ?';
      $updateTypes .= 's';
      $updateParams[] = $formData['status'];
    }

    if ($attendanceHasTeacherId) {
      if ((int) $formData['teacher_id'] > 0) {
        $updateFields[] = 'teacher_id = ?';
        $updateTypes .= 'i';
        $updateParams[] = (int) $formData['teacher_id'];
      } else {
        $updateFields[] = 'teacher_id = NULL';
      }
    }

    if (empty($updateFields)) {
      $errorMessage = 'No editable attendance fields were found.';
    } else {
      $updateSql = 'UPDATE attendance SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
      $updateTypes .= 'i';
      $updateParams[] = $attendanceId;

      $updateStmt = $connection->prepare($updateSql);
      if (!$updateStmt) {
        $errorMessage = 'Unable to update attendance right now.';
      } elseif (!admin_bind_dynamic_params($updateStmt, $updateTypes, $updateParams)) {
        $errorMessage = 'Unable to bind attendance parameters.';
      } elseif (!$updateStmt->execute()) {
        $errorMessage = 'Failed to update attendance. Please try again.';
      } else {
        admin_set_flash('success', 'Attendance updated successfully.');
        $updateStmt->close();
        header('Location: attendance.php');
        exit();
      }

      if ($updateStmt) {
        $updateStmt->close();
      }
    }
  }
}
?>
<!-- Render the edit attendance form and validation feedback. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Attendance</title>
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
      <h2><i class="fas fa-edit"></i> Edit Attendance</h2>
    </div>

    <div class="form-card">
      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($attendanceRow && $attendanceDateColumn !== null): ?>
        <form method="POST" action="">
          <input type="hidden" name="attendance_id" value="<?php echo (int) $attendanceId; ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Student *</label>
              <select class="form-select" name="student_id" data-validation="required,select">
                <option value="">Select Student</option>
                <?php foreach ($studentOptions as $student): ?>
                  <option value="<?php echo (int) $student['id']; ?>" <?php echo ((string) $student['id'] === $formData['student_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $student['name']); ?><?php echo trim((string) ($student['roll_no'] ?? '')) !== '' ? ' (' . htmlspecialchars((string) $student['roll_no']) . ')' : ''; ?><?php echo $student['display_class'] !== '' ? ' - ' . htmlspecialchars((string) $student['display_class']) : ''; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if ($attendanceHasClassId): ?>
              <div class="col-md-6 mb-3">
                <label class="form-label">Class *</label>
                <select class="form-select" name="class_id" data-validation="required,select">
                  <option value="">Select Class</option>
                  <?php foreach ($classOptions as $classOption): ?>
                    <option value="<?php echo (int) $classOption['id']; ?>" <?php echo ((string) $classOption['id'] === $formData['class_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) $classOption['class_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Attendance Date *</label>
              <input type="date" class="form-control" name="date" data-validation="required" value="<?php echo htmlspecialchars($formData['date']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status *</label>
              <select class="form-select" name="status" data-validation="required,select">
                <option value="present" <?php echo $formData['status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                <option value="absent" <?php echo $formData['status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                <option value="late" <?php echo $formData['status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                <option value="leave" <?php echo $formData['status'] === 'leave' ? 'selected' : ''; ?>>Leave</option>
              </select>
            </div>
          </div>

          <?php if ($attendanceHasSubjectId || $attendanceHasTeacherId): ?>
            <div class="row">
              <?php if ($attendanceHasSubjectId): ?>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Subject</label>
                  <select class="form-select" name="subject_id" data-validation="select">
                    <option value="">Select Subject</option>
                    <?php foreach ($subjectOptions as $subjectOption): ?>
                      <option value="<?php echo (int) $subjectOption['id']; ?>" <?php echo ((string) $subjectOption['id'] === $formData['subject_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $subjectOption['subject_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endif; ?>

              <?php if ($attendanceHasTeacherId): ?>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Teacher</label>
                  <select class="form-select" name="teacher_id" data-validation="select">
                    <option value="">Select Teacher</option>
                    <?php foreach ($teacherOptions as $teacherOption): ?>
                      <option value="<?php echo (int) $teacherOption['id']; ?>" <?php echo ((string) $teacherOption['id'] === $formData['teacher_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $teacherOption['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Attendance</button>
            <a href="attendance.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Attendance record not found. <a href="attendance.php">Back to attendance</a></div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

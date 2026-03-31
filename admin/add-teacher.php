<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'teachers', 'first_name', "VARCHAR(100) NULL");
admin_ensure_column($connection, 'teachers', 'last_name', "VARCHAR(100) NULL");
admin_ensure_column($connection, 'teachers', 'qualification', "VARCHAR(150) NULL");
admin_ensure_column($connection, 'teachers', 'experience', 'INT NULL');
admin_ensure_column($connection, 'teachers', 'joining_date', 'DATE NULL');
admin_ensure_column($connection, 'teachers', 'address', 'TEXT NULL');
admin_ensure_column($connection, 'teachers', 'salary', 'DECIMAL(10,2) NULL');

$subjects = [];
$subjectColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
if ($subjectColumn !== null) {
  $subjectResult = mysqli_query(
    $connection,
    "SELECT DISTINCT {$subjectColumn} AS subject_name FROM subjects WHERE {$subjectColumn} IS NOT NULL AND {$subjectColumn} != '' ORDER BY {$subjectColumn} ASC"
  );
  if ($subjectResult) {
    while ($subjectRow = mysqli_fetch_assoc($subjectResult)) {
      $subjects[] = $subjectRow['subject_name'];
    }
  }
}

if (empty($subjects)) {
  $subjects = ['Mathematics', 'Physics', 'Chemistry', 'English', 'History'];
}

$formData = [
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'phone' => '',
  'subject' => '',
  'qualification' => '',
  'experience' => '',
  'joining_date' => '',
  'address' => '',
  'salary' => '',
  'status' => 'Active',
];

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $formData['status'] = admin_normalize_status($formData['status'], 'Active');

  if (
    $formData['first_name'] === '' ||
    $formData['last_name'] === '' ||
    $formData['email'] === '' ||
    $formData['phone'] === '' ||
    $formData['subject'] === '' ||
    $formData['qualification'] === '' ||
    $formData['experience'] === '' ||
    $formData['joining_date'] === '' ||
    $formData['address'] === '' ||
    $formData['salary'] === ''
  ) {
    $errorMessage = 'Please fill in all required fields.';
  } elseif (!is_numeric($formData['experience']) || (int) $formData['experience'] < 0) {
    $errorMessage = 'Experience must be a valid non-negative number.';
  } elseif (!is_numeric($formData['salary']) || (float) $formData['salary'] <= 0) {
    $errorMessage = 'Salary must be a valid amount greater than zero.';
  }

  if ($errorMessage === '') {
    $teachersHasEmail = admin_column_exists($connection, 'teachers', 'email');
    if ($teachersHasEmail) {
      $emailCheckStmt = mysqli_prepare($connection, 'SELECT id FROM teachers WHERE email = ? LIMIT 1');
      if ($emailCheckStmt) {
        mysqli_stmt_bind_param($emailCheckStmt, 's', $formData['email']);
        mysqli_stmt_execute($emailCheckStmt);
        $emailCheckResult = mysqli_stmt_get_result($emailCheckStmt);
        if ($emailCheckResult && mysqli_num_rows($emailCheckResult) > 0) {
          $errorMessage = 'A teacher with this email already exists.';
        }
        mysqli_stmt_close($emailCheckStmt);
      }
    }
  }

  if ($errorMessage === '') {
    $teacherName = trim($formData['first_name'] . ' ' . $formData['last_name']);

    $insertColumns = ['name'];
    $insertValues = ['?'];
    $insertTypes = 's';
    $insertParams = [$teacherName];

    if (admin_column_exists($connection, 'teachers', 'first_name')) {
      $insertColumns[] = 'first_name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['first_name'];
    }
    if (admin_column_exists($connection, 'teachers', 'last_name')) {
      $insertColumns[] = 'last_name';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['last_name'];
    }
    if (admin_column_exists($connection, 'teachers', 'email')) {
      $insertColumns[] = 'email';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['email'];
    }
    if (admin_column_exists($connection, 'teachers', 'phone')) {
      $insertColumns[] = 'phone';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['phone'];
    }
    if (admin_column_exists($connection, 'teachers', 'subject')) {
      $insertColumns[] = 'subject';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['subject'];
    }
    if (admin_column_exists($connection, 'teachers', 'qualification')) {
      $insertColumns[] = 'qualification';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['qualification'];
    }
    if (admin_column_exists($connection, 'teachers', 'experience')) {
      $insertColumns[] = 'experience';
      $insertValues[] = '?';
      $insertTypes .= 'i';
      $insertParams[] = (int) $formData['experience'];
    }
    if (admin_column_exists($connection, 'teachers', 'joining_date')) {
      $insertColumns[] = 'joining_date';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['joining_date'];
    }
    if (admin_column_exists($connection, 'teachers', 'address')) {
      $insertColumns[] = 'address';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['address'];
    }
    if (admin_column_exists($connection, 'teachers', 'salary')) {
      $insertColumns[] = 'salary';
      $insertValues[] = '?';
      $insertTypes .= 'd';
      $insertParams[] = (float) $formData['salary'];
    }
    if (admin_column_exists($connection, 'teachers', 'status')) {
      $insertColumns[] = 'status';
      $insertValues[] = '?';
      $insertTypes .= 's';
      $insertParams[] = $formData['status'];
    }

    $insertSql = 'INSERT INTO teachers (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $insertStmt = mysqli_prepare($connection, $insertSql);

    if (!$insertStmt) {
      $errorMessage = 'Unable to save teacher right now.';
    } else {
      if (!admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams)) {
        $errorMessage = 'Unable to bind insert parameters.';
      } elseif (!mysqli_stmt_execute($insertStmt)) {
        $errorMessage = 'Failed to add teacher. Please try again.';
      }
      mysqli_stmt_close($insertStmt);
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Teacher added successfully.');
    header('Location: add-teacher.php');
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
  <title>Add New Teacher</title>
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
      <h2><i class="fas fa-chalkboard-teacher"></i> Add New Teacher</h2>
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
            <label class="form-label">First Name *</label>
            <input type="text" class="form-control" name="first_name" data-validation="required,alphabetic,min" data-min="2" value="<?php echo htmlspecialchars($formData['first_name']); ?>">
            <div id="first_name_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Last Name *</label>
            <input type="text" class="form-control" name="last_name" data-validation="required,alphabetic,min" data-min="2" value="<?php echo htmlspecialchars($formData['last_name']); ?>">
            <div id="last_name_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Email *</label>
            <input type="text" class="form-control" name="email" data-validation="required,email" value="<?php echo htmlspecialchars($formData['email']); ?>">
            <div id="email_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number *</label>
            <input type="text" class="form-control" name="phone" data-validation="required,number,min" data-min="10" value="<?php echo htmlspecialchars($formData['phone']); ?>">
            <div id="phone_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Subject *</label>
            <select class="form-select" name="subject" data-validation="required,select">
              <option value="">Select Subject</option>
              <?php foreach ($subjects as $subjectName): ?>
                <option value="<?php echo htmlspecialchars($subjectName); ?>" <?php echo $formData['subject'] === $subjectName ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($subjectName); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="subject_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Qualification *</label>
            <input type="text" class="form-control" name="qualification" placeholder="e.g., M.Sc, B.Ed" data-validation="required,min" data-min="2" value="<?php echo htmlspecialchars($formData['qualification']); ?>">
            <div id="qualification_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Experience (Years) *</label>
            <input type="text" class="form-control" name="experience" data-validation="required,number,min" data-min="0" value="<?php echo htmlspecialchars($formData['experience']); ?>">
            <div id="experience_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Date of Joining *</label>
            <input type="date" class="form-control" name="joining_date" data-validation="required" value="<?php echo htmlspecialchars($formData['joining_date']); ?>">
            <div id="joining_date_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Address *</label>
          <textarea class="form-control" name="address" rows="3" data-validation="required,min" data-min="5"><?php echo htmlspecialchars($formData['address']); ?></textarea>
          <div id="address_error" class="invalid-feedback"></div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Salary *</label>
            <input type="text" class="form-control" name="salary" data-validation="required,number,min" data-min="1" value="<?php echo htmlspecialchars($formData['salary']); ?>">
            <div id="salary_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" data-validation="required,select">
              <option value="">Select Status</option>
              <option value="Active" <?php echo $formData['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
              <option value="Inactive" <?php echo $formData['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <div id="status_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Teacher</button>
          <a href="teachers.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

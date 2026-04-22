<?php
// Admin page for editing notice board entries.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

function admin_notice_ensure_schema($connection)
{
  $connection->query(
    "CREATE TABLE IF NOT EXISTS notices (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      message TEXT NOT NULL,
      target_audience VARCHAR(30) DEFAULT 'all',
      class_id INT NULL,
      publish_date DATE NULL,
      expiry_date DATE NULL,
      status VARCHAR(20) DEFAULT 'Active',
      created_by INT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  );

  admin_ensure_column($connection, 'notices', 'title', 'VARCHAR(255) NOT NULL');
  admin_ensure_column($connection, 'notices', 'message', 'TEXT NOT NULL');
  admin_ensure_column($connection, 'notices', 'target_audience', "VARCHAR(30) DEFAULT 'all'");
  admin_ensure_column($connection, 'notices', 'class_id', 'INT NULL');
  admin_ensure_column($connection, 'notices', 'publish_date', 'DATE NULL');
  admin_ensure_column($connection, 'notices', 'expiry_date', 'DATE NULL');
  admin_ensure_column($connection, 'notices', 'status', "VARCHAR(20) DEFAULT 'Active'");
  admin_ensure_column($connection, 'notices', 'created_by', 'INT NULL');
  admin_ensure_column($connection, 'notices', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
}

function admin_notice_normalize_audience($audience)
{
  $normalized = strtolower(trim((string) $audience));
  if (in_array($normalized, ['all', 'students', 'teachers'], true)) {
    return $normalized;
  }

  return 'all';
}

function admin_notice_valid_date($value)
{
  if ($value === '') {
    return true;
  }

  $dt = DateTime::createFromFormat('Y-m-d', $value);
  return $dt instanceof DateTime && $dt->format('Y-m-d') === $value;
}

admin_notice_ensure_schema($connection);

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classOptions = [];
if ($classNameColumn !== null) {
  $classStmt = $connection->prepare("SELECT id, COALESCE(NULLIF({$classNameColumn}, ''), CONCAT('Class ', id)) AS class_name FROM classes ORDER BY id ASC");
  if ($classStmt) {
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    if ($classResult) {
      while ($classRow = $classResult->fetch_assoc()) {
        $classOptions[] = $classRow;
      }
    }
    $classStmt->close();
  }
}

$noticeId = (int) ($_GET['id'] ?? $_POST['notice_id'] ?? 0);
$errorMessage = '';
$noticeRecord = null;
if ($noticeId > 0) {
  $noticeStmt = $connection->prepare('SELECT * FROM notices WHERE id = ? LIMIT 1');
  if ($noticeStmt) {
    $noticeStmt->bind_param('i', $noticeId);
    $noticeStmt->execute();
    $noticeResult = $noticeStmt->get_result();
    $noticeRecord = $noticeResult ? $noticeResult->fetch_assoc() : null;
    $noticeStmt->close();
  }
}

if (!$noticeRecord) {
  $errorMessage = 'Notice record not found.';
}

$formData = [
  'title' => trim((string) ($noticeRecord['title'] ?? '')),
  'message' => trim((string) ($noticeRecord['message'] ?? '')),
  'target_audience' => admin_notice_normalize_audience($noticeRecord['target_audience'] ?? 'all'),
  'class_id' => (string) ((int) ($noticeRecord['class_id'] ?? 0)),
  'publish_date' => trim((string) ($noticeRecord['publish_date'] ?? date('Y-m-d'))),
  'expiry_date' => trim((string) ($noticeRecord['expiry_date'] ?? '')),
  'status' => admin_normalize_status($noticeRecord['status'] ?? 'Active', 'Active'),
];
if ($formData['class_id'] === '0') {
  $formData['class_id'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $noticeRecord) {
  foreach ($formData as $key => $value) {
    if ($key === 'class_id') {
      $formData[$key] = trim((string) ($_POST[$key] ?? ''));
      continue;
    }
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  $formData['target_audience'] = admin_notice_normalize_audience($formData['target_audience']);
  $formData['status'] = admin_normalize_status($formData['status'], 'Active');

  if ($formData['title'] === '' || $formData['message'] === '') {
    $errorMessage = 'Title and message are required.';
  } elseif (!admin_notice_valid_date($formData['publish_date']) || !admin_notice_valid_date($formData['expiry_date'])) {
    $errorMessage = 'Please enter valid publish and expiry dates.';
  } elseif ($formData['expiry_date'] !== '' && $formData['publish_date'] !== '' && $formData['expiry_date'] < $formData['publish_date']) {
    $errorMessage = 'Expiry date cannot be earlier than publish date.';
  }

  $classId = null;
  if ($formData['target_audience'] === 'students' && $formData['class_id'] !== '') {
    $classId = (int) $formData['class_id'];
    if ($classId <= 0) {
      $classId = null;
    }
  }

  if ($errorMessage === '') {
    $publishDate = $formData['publish_date'] !== '' ? $formData['publish_date'] : date('Y-m-d');
    $expiryDate = $formData['expiry_date'] !== '' ? $formData['expiry_date'] : null;

    $updateSql = 'UPDATE notices SET title = ?, message = ?, target_audience = ?, class_id = ?, publish_date = ?, expiry_date = ?, status = ? WHERE id = ?';
    $updateStmt = $connection->prepare($updateSql);

    if (!$updateStmt) {
      $errorMessage = 'Unable to update notice right now.';
    } else {
      $updateStmt->bind_param(
        'sssisssi',
        $formData['title'],
        $formData['message'],
        $formData['target_audience'],
        $classId,
        $publishDate,
        $expiryDate,
        $formData['status'],
        $noticeId
      );

      if (!$updateStmt->execute()) {
        $errorMessage = 'Failed to update notice. Please try again.';
      }

      $updateStmt->close();
    }
  }

  if ($errorMessage === '') {
    admin_set_flash('success', 'Notice updated successfully.');
    header('Location: notices.php');
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Notice</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    body { background: #fcfbfb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: #fff; padding: 20px 30px; border-radius: 10px; margin-bottom: 22px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); border-bottom: 3px solid #9ec5ff; }
    .header h2 { margin: 0; color: #192a56; font-weight: 700; }
    .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); border-top: 3px solid #9ec5ff; }
    .form-label { color: #192a56; font-weight: 600; margin-bottom: 8px; }
    .form-control, .form-select { padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .form-control:focus, .form-select:focus { border-color: #7aa9ff; box-shadow: 0 0 0 3px rgba(122, 169, 255, 0.2); }
    .btn-submit { background: #7aa9ff; color: #fff; border: 0; padding: 12px 28px; border-radius: 8px; font-weight: 600; }
    .btn-submit:hover { background: #5f95f4; color: #fff; }
    .btn-cancel { background: #e2e8f0; color: #192a56; border: 0; padding: 12px 28px; border-radius: 8px; font-weight: 600; margin-left: 10px; text-decoration: none; display: inline-block; }
    @media (max-width: 991px) { .main-content { margin-left: 0; padding: 80px 14px 20px; } }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-edit"></i> Edit Notice</h2>
    </div>

    <div class="form-card">
      <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($errorMessage); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($noticeRecord): ?>
        <form method="POST" action="" novalidate>
          <input type="hidden" name="notice_id" value="<?php echo (int) $noticeId; ?>">

          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Notice Title *</label>
              <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" maxlength="255" data-validation="required,min,max" data-min="3" data-max="255">
              <div id="title_error" class="invalid-feedback"></div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Status *</label>
              <select class="form-select" name="status" data-validation="required,select">
                <option value="">Select Status</option>
                <option value="Active" <?php echo $formData['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $formData['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
              <div id="status_error" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Message *</label>
            <textarea class="form-control" name="message" rows="5" data-validation="required,min" data-min="5"><?php echo htmlspecialchars($formData['message']); ?></textarea>
            <div id="message_error" class="invalid-feedback"></div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Audience *</label>
              <select class="form-select" name="target_audience" id="target_audience" data-validation="required,select">
                <option value="all" <?php echo $formData['target_audience'] === 'all' ? 'selected' : ''; ?>>All Users</option>
                <option value="students" <?php echo $formData['target_audience'] === 'students' ? 'selected' : ''; ?>>Students</option>
                <option value="teachers" <?php echo $formData['target_audience'] === 'teachers' ? 'selected' : ''; ?>>Teachers</option>
              </select>
              <div id="target_audience_error" class="invalid-feedback"></div>
            </div>

            <div class="col-md-4 mb-3" id="class_scope_group">
              <label class="form-label">Class Scope (Students)</label>
              <select class="form-select" name="class_id">
                <option value="">All Classes</option>
                <?php foreach ($classOptions as $classOption): ?>
                  <option value="<?php echo (int) $classOption['id']; ?>" <?php echo (string) $classOption['id'] === $formData['class_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) ($classOption['class_name'] ?? ('Class ' . (int) $classOption['id']))); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Publish Date *</label>
              <input type="date" class="form-control" name="publish_date" value="<?php echo htmlspecialchars($formData['publish_date']); ?>" data-validation="required">
              <div id="publish_date_error" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Expiry Date</label>
              <input type="date" class="form-control" name="expiry_date" value="<?php echo htmlspecialchars($formData['expiry_date']); ?>">
            </div>
          </div>

          <div class="mt-2">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
            <a href="notices.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script>
    (function () {
      const audience = document.getElementById('target_audience');
      const classGroup = document.getElementById('class_scope_group');
      if (!audience || !classGroup) {
        return;
      }

      const toggleClassScope = function () {
        classGroup.style.display = audience.value === 'students' ? '' : 'none';
      };

      audience.addEventListener('change', toggleClassScope);
      toggleClassScope();
    })();
  </script>
</body>
</html>

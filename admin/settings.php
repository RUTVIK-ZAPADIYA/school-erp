<?php
// Admin settings page for application preferences.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

// Ensure the settings storage table exists before reads/writes.
$connection->query(
  "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$defaultSettings = [
  'school_name' => 'ABC School',
  'school_email' => 'info@school.com',
  'school_phone' => '+1 234 567 8900',
  'school_address' => '123 School St',
];

// Handle settings form submission and validation.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedSettings = [
    'school_name' => trim((string) ($_POST['school_name'] ?? '')),
    'school_email' => trim((string) ($_POST['school_email'] ?? '')),
    'school_phone' => trim((string) ($_POST['school_phone'] ?? '')),
    'school_address' => trim((string) ($_POST['school_address'] ?? '')),
  ];

  $errorMessage = '';
  foreach ($submittedSettings as $label => $value) {
    if ($value === '') {
      $errorMessage = 'All settings fields are required.';
      break;
    }
  }

  if ($errorMessage === '') {
    $upsertSql = 'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
    $upsertStmt = $connection->prepare( $upsertSql);

    if ($upsertStmt) {
      foreach ($submittedSettings as $key => $value) {
        $upsertStmt->bind_param( 'ss', $key, $value);
        $upsertStmt->execute();
      }
      $upsertStmt->close();
      admin_set_flash('success', 'Settings updated successfully.');
    } else {
      admin_set_flash('danger', 'Unable to save settings right now.');
    }

    header('Location: settings.php');
    exit();
  }

  admin_set_flash('danger', $errorMessage);
  header('Location: settings.php');
  exit();
}

// Load persisted settings and merge them with defaults.
$settings = $defaultSettings;
$settingsResult = $connection->query( 'SELECT setting_key, setting_value FROM system_settings');
if ($settingsResult) {
  while ($settingRow = $settingsResult->fetch_assoc()) {
    $settings[$settingRow['setting_key']] = (string) ($settingRow['setting_value'] ?? '');
  }
}

$flash = admin_pull_flash();
?>
<!-- Render editable system settings with flash feedback. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .form-label { color: #2d3748; font-weight: 500; margin-bottom: 8px; }
    .form-control { padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .btn-save { background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 8px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-cog"></i> System Settings</h2>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="content-card">
      <h5 class="mb-4">School Information</h5>
      <form method="POST" action="" novalidate>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">School Name</label>
            <input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars((string) ($settings['school_name'] ?? '')); ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="school_email" value="<?php echo htmlspecialchars((string) ($settings['school_email'] ?? '')); ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-control" name="school_phone" value="<?php echo htmlspecialchars((string) ($settings['school_phone'] ?? '')); ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="school_address" value="<?php echo htmlspecialchars((string) ($settings['school_address'] ?? '')); ?>" required>
          </div>
        </div>
        <button class="btn-save" type="submit"><i class="fas fa-save"></i> Save Changes</button>
      </form>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

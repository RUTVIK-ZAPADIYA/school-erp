<?php
// Admin profile page for viewing and updating account details.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

$adminUserId = (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);
$profile = [
  'id' => $adminUserId,
  'username' => 'admin',
  'name' => (string) ($_SESSION['admin_name'] ?? 'Admin User'),
  'email' => '',
  'phone' => '',
  'status' => 'Active',
  'created_at' => date('Y-m-d'),
];

// Load the persisted admin profile from users table when available.
if ($adminUserId > 0 && admin_table_exists($connection, 'users')) {
  $fetchStmt = $connection->prepare( "SELECT id, username, name, email, phone, status, created_at FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
  if ($fetchStmt) {
    $fetchStmt->bind_param( 'i', $adminUserId);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    $fetchRow = $fetchResult ? $fetchResult->fetch_assoc() : null;
    if ($fetchRow) {
      $profile = array_merge($profile, $fetchRow);
    }
    $fetchStmt->close();
  }
}

// Handle profile updates and optional password changes.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminUserId > 0) {
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $status = admin_normalize_status($_POST['status'] ?? 'Active', 'Active');
  $newPassword = trim((string) ($_POST['new_password'] ?? ''));

  if ($name === '' || $email === '') {
    admin_set_flash('danger', 'Name and email are required.');
  } else {
    $updateSql = 'UPDATE users SET name = ?, email = ?, phone = ?, status = ?';
    $types = 'ssss';
    $params = [$name, $email, $phone, $status];

    if ($newPassword !== '') {
      $updateSql .= ', password = ?';
      $types .= 's';
      $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $updateSql .= ' WHERE id = ?';
    $types .= 'i';
    $params[] = $adminUserId;

    $updateStmt = $connection->prepare( $updateSql);
    if ($updateStmt && admin_bind_dynamic_params($updateStmt, $types, $params) && $updateStmt->execute()) {
      $_SESSION['admin_name'] = $name;
      $_SESSION['name'] = $name;
      admin_set_flash('success', 'Profile updated successfully.');
    } else {
      admin_set_flash('danger', 'Unable to update profile right now.');
    }

    if ($updateStmt) {
      $updateStmt->close();
    }
  }

  header('Location: profile.php');
  exit();
}

$flash = admin_pull_flash();

$createdDate = !empty($profile['created_at']) ? date('F j, Y', strtotime((string) $profile['created_at'])) : '-';
$adminCode = 'ADM' . str_pad((string) ((int) $profile['id']), 3, '0', STR_PAD_LEFT);
?>
<!-- Render admin profile details and editable account form. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Profile</title>
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
    .profile-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 3px solid #f7d794; }
    .profile-header { text-align: center; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0; margin-bottom: 30px; }
    .profile-avatar { width: 100px; height: 100px; background: #f7d794; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .profile-avatar i { font-size: 50px; color: #192a56; }
    .profile-name { color: #192a56; font-size: 24px; font-weight: 700; margin-bottom: 5px; }
    .profile-role { color: #7f8c8d; font-size: 14px; }
    .info-section { margin-bottom: 30px; }
    .info-section h5 { color: #192a56; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f7d794; }
    .info-row { display: flex; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #7f8c8d; width: 150px; font-size: 14px; }
    .info-value { color: #192a56; font-weight: 600; }
    .btn-edit { background: #f7d794; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-edit:hover { background: #e5c682; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user"></i> My Profile</h2>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-avatar">
          <i class="fas fa-user-shield"></i>
        </div>
        <div class="profile-name"><?php echo htmlspecialchars((string) $profile['name']); ?></div>
        <div class="profile-role">System Administrator</div>
      </div>
      
      <div class="info-section">
        <h5>Personal Information</h5>
        <div class="info-row">
          <div class="info-label">Full Name</div>
          <div class="info-value"><?php echo htmlspecialchars((string) $profile['name']); ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Email</div>
          <div class="info-value"><?php echo htmlspecialchars((string) ($profile['email'] ?? '-')); ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Phone</div>
          <div class="info-value"><?php echo htmlspecialchars((string) ($profile['phone'] ?? '-')); ?></div>
        </div>
      </div>
      
      <div class="info-section">
        <h5>Account Details</h5>
        <div class="info-row">
          <div class="info-label">Admin ID</div>
          <div class="info-value"><?php echo htmlspecialchars($adminCode); ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Role</div>
          <div class="info-value">System Administrator</div>
        </div>
        <div class="info-row">
          <div class="info-label">Joined Date</div>
          <div class="info-value"><?php echo htmlspecialchars($createdDate); ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Status</div>
          <div class="info-value"><span class="badge <?php echo (admin_normalize_status($profile['status'] ?? 'Active', 'Active') === 'Active') ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars(admin_normalize_status($profile['status'] ?? 'Active', 'Active')); ?></span></div>
        </div>
      </div>
      
      <div class="info-section">
        <h5>Edit Profile</h5>
        <form method="POST" action="">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars((string) $profile['name']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars((string) ($profile['email'] ?? '')); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars((string) ($profile['phone'] ?? '')); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="Active" <?php echo admin_normalize_status($profile['status'] ?? 'Active', 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo admin_normalize_status($profile['status'] ?? 'Active', 'Active') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current password">
          </div>
          <div class="text-center">
            <button type="submit" class="btn-edit"><i class="fas fa-save"></i> Update Profile</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

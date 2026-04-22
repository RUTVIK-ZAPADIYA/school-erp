<?php
// Admin profile page for viewing and updating account details.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

$adminUserId = (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);
$dbError = null;
$profileExists = false;

// Default profile structure — only used as fallback when DB row is unavailable
$profile = [
  'id' => $adminUserId,
  'username' => (string) ($_SESSION['username'] ?? ''),
  'name' => (string) ($_SESSION['admin_name'] ?? $_SESSION['name'] ?? ''),
  'email' => (string) ($_SESSION['email'] ?? ''),
  'phone' => '',
  'status' => 'Active',
  'created_at' => null,
  'role' => 'admin'
];

// Load the persisted admin profile from users table when available (READ Operation)
if ($adminUserId > 0) {
  if (!admin_table_exists($connection, 'users')) {
    $dbError = 'Users table not found. Please run setup script.';
  } else {
    try {
      // Build dynamic SELECT to handle schema variations
      $selectFields = ['id', 'username', 'name', 'email', 'phone', 'status', 'role', 'created_at'];
      $selectList = [];
      foreach ($selectFields as $field) {
        if (admin_column_exists($connection, 'users', $field)) {
          $selectList[] = $field;
        }
      }
      
      if (empty($selectList)) {
        $selectList = ['id', 'name'];
      }
      
      $fetchSql = "SELECT " . implode(', ', $selectList) . " FROM users WHERE id = ? LIMIT 1";
      $fetchStmt = $connection->prepare($fetchSql);
      
      if ($fetchStmt) {
        $fetchStmt->bind_param('i', $adminUserId);
        if ($fetchStmt->execute()) {
          $fetchResult = $fetchStmt->get_result();
          $fetchRow = $fetchResult ? $fetchResult->fetch_assoc() : null;
          
          if ($fetchRow) {
            // Merge: DB values win, but keep defaults for any null DB fields
            foreach ($fetchRow as $k => $v) {
              if ($v !== null) {
                $profile[$k] = $v;
              }
            }
            $profileExists = true;

            // Keep session identity aligned with the latest database values.
            if (isset($fetchRow['name'])) {
              $_SESSION['admin_name'] = (string) $fetchRow['name'];
              $_SESSION['name'] = (string) $fetchRow['name'];
            }
            if (isset($fetchRow['username'])) {
              $_SESSION['username'] = (string) $fetchRow['username'];
            }
            if (array_key_exists('email', $fetchRow)) {
              $_SESSION['email'] = (string) ($fetchRow['email'] ?? '');
            }
          }
        } else {
          $dbError = 'Error fetching profile: ' . $fetchStmt->error;
        }
        $fetchStmt->close();
      } else {
        $dbError = 'Error preparing statement: ' . $connection->error;
      }
    } catch (Exception $e) {
      $dbError = 'Database error: ' . $e->getMessage();
    }
  }
}

// Handle profile updates and optional password changes (UPDATE Operation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminUserId > 0) {
  // Validate CSRF token.
  $submittedCsrf = (string) ($_POST['csrf_token'] ?? '');
  $expectedCsrf = (string) ($_SESSION['admin_flash_csrf'] ?? '');
  if ($expectedCsrf === '' || !hash_equals($expectedCsrf, $submittedCsrf)) {
    admin_set_flash('danger', 'Invalid request. Please try again.');
    header('Location: profile.php');
    exit();
  }
  $formAction = trim((string) ($_POST['form_action'] ?? 'save_profile'));
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $status = admin_normalize_status($_POST['status'] ?? 'Active', 'Active');

  // Prevent admin from deactivating their own account.
  if ($status === 'Inactive') {
    $status = 'Active';
  }
  $newPassword = trim((string) ($_POST['new_password'] ?? ''));
  $deleteConfirmText = trim((string) ($_POST['delete_confirm_text'] ?? ''));

  // Handle profile deletion (DELETE Operation)
  if ($formAction === 'delete_profile') {
    if (!admin_table_exists($connection, 'users')) {
      admin_set_flash('danger', 'Users table not found.');
      header('Location: profile.php');
      exit();
    }

    if (strtoupper($deleteConfirmText) !== 'DELETE') {
      admin_set_flash('danger', 'Type DELETE to confirm account deletion.');
      header('Location: profile.php');
      exit();
    }

    $adminCount = 0;
    $countStmt = $connection->prepare("SELECT COUNT(*) AS total_admins FROM users WHERE role = 'admin'");
    if ($countStmt && $countStmt->execute()) {
      $countResult = $countStmt->get_result();
      $countRow = $countResult ? $countResult->fetch_assoc() : null;
      $adminCount = (int) ($countRow['total_admins'] ?? 0);
      $countStmt->close();
    } else {
      if ($countStmt) {
        $countStmt->close();
      }
      admin_set_flash('danger', 'Unable to verify admin accounts. Try again.');
      header('Location: profile.php');
      exit();
    }

    if ($adminCount <= 1) {
      admin_set_flash('danger', 'Cannot delete the only admin account. Create another admin first.');
      header('Location: profile.php');
      exit();
    }

    try {
      $connection->begin_transaction();
      $deleteStmt = $connection->prepare("DELETE FROM users WHERE id = ? LIMIT 1");
      if ($deleteStmt) {
        $deleteStmt->bind_param('i', $adminUserId);
        $deleteExecuted = $deleteStmt->execute();
        $deletedRows = (int) $deleteStmt->affected_rows;
        $deleteStmt->close();

        if ($deleteExecuted && $deletedRows > 0) {
          $connection->commit();
          $_SESSION = [];
          if (ini_get('session.use_cookies')) {
            $sessionParams = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $sessionParams['path'], $sessionParams['domain'], $sessionParams['secure'], $sessionParams['httponly']);
          }
          session_destroy();
          header('Location: ../login.php?account_deleted=1');
          exit();
        }

        $connection->rollback();
        admin_set_flash('danger', 'Account deletion failed. Please try again.');
      } else {
        $connection->rollback();
        admin_set_flash('danger', 'Unable to prepare account deletion query.');
      }
    } catch (Exception $e) {
      $connection->rollback();
      admin_set_flash('danger', 'Database error: ' . $e->getMessage());
    }

    header('Location: profile.php');
    exit();
  }

  // Input validation
  $validationErrors = [];
  
  if ($name === '') {
    $validationErrors[] = 'Name is required.';
  } elseif (strlen($name) < 2) {
    $validationErrors[] = 'Name must be at least 2 characters long.';
  }
  
  if ($email === '') {
    $validationErrors[] = 'Email is required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $validationErrors[] = 'Invalid email format.';
  }
  
  if (strlen($phone) > 0 && strlen($phone) < 10) {
    $validationErrors[] = 'Phone number must be at least 10 digits.';
  }
  
  if ($newPassword !== '' && strlen($newPassword) < 8) {
    $validationErrors[] = 'New password must be at least 8 characters long.';
  }

  if (!$profileExists && admin_column_exists($connection, 'users', 'password') && $newPassword === '') {
    $validationErrors[] = 'Set a password to create a missing admin profile record.';
  }
  
  if (!empty($validationErrors)) {
    admin_set_flash('danger', implode(' ', $validationErrors));
  } else if (admin_table_exists($connection, 'users')) {
    // Check if email is unique (excluding current user)
    $emailCheckStmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    if ($emailCheckStmt) {
      $emailCheckStmt->bind_param('si', $email, $adminUserId);
      $emailCheckStmt->execute();
      $emailCheckResult = $emailCheckStmt->get_result();
      
      if ($emailCheckResult && $emailCheckResult->num_rows > 0) {
        admin_set_flash('danger', 'Email is already in use by another account.');
      } else {
        $generatedUsername = trim((string) ($profile['username'] ?? ''));
        if ($generatedUsername === '') {
          $usernameFromEmail = strstr($email, '@', true);
          $generatedUsername = $usernameFromEmail !== false ? $usernameFromEmail : preg_replace('/\s+/', '', strtolower($name));
          $generatedUsername = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $generatedUsername);
          if ($generatedUsername === '') {
            $generatedUsername = 'admin' . $adminUserId;
          }
        }

        // Ensure username is unique when profile needs to be inserted.
        if (!$profileExists) {
          $usernameCandidate = $generatedUsername;
          $suffix = 1;
          while (true) {
            $usernameCheckStmt = $connection->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            if (!$usernameCheckStmt) {
              break;
            }

            $usernameCheckStmt->bind_param('s', $usernameCandidate);
            $usernameCheckStmt->execute();
            $usernameCheckResult = $usernameCheckStmt->get_result();
            $usernameExists = $usernameCheckResult && $usernameCheckResult->num_rows > 0;
            $usernameCheckStmt->close();

            if (!$usernameExists) {
              break;
            }

            $usernameCandidate = $generatedUsername . $suffix;
            $suffix++;
            if ($suffix > 5000) {
              break;
            }
          }
          $generatedUsername = $usernameCandidate;
        }

        try {
          if ($profileExists) {
            // UPDATE operation
            $updateSql = 'UPDATE users SET name = ?, email = ?, phone = ?, status = ?';
            $types = 'ssss';
            $params = [$name, $email, $phone, $status];

            if ($newPassword !== '' && admin_column_exists($connection, 'users', 'password')) {
              $updateSql .= ', password = ?';
              $types .= 's';
              $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $updateSql .= ' WHERE id = ? LIMIT 1';
            $types .= 'i';
            $params[] = $adminUserId;

            $updateStmt = $connection->prepare($updateSql);
            if ($updateStmt && admin_bind_dynamic_params($updateStmt, $types, $params) && $updateStmt->execute()) {
              admin_set_flash('success', 'Profile updated successfully!');
            } else {
              admin_set_flash('danger', 'Unable to update profile. Please try again.');
            }

            if ($updateStmt) {
              $updateStmt->close();
            }
          } else {
            // CREATE operation for missing admin row
            $insertColumns = ['id', 'username', 'role', 'name', 'email', 'phone', 'status'];
            $insertTypes = 'issssss';
            $insertParams = [$adminUserId, $generatedUsername, 'admin', $name, $email, $phone, $status];

            if (admin_column_exists($connection, 'users', 'password')) {
              $insertColumns[] = 'password';
              $insertTypes .= 's';
              $insertParams[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $insertSql = 'INSERT INTO users (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', array_fill(0, count($insertColumns), '?')) . ')';
            $insertStmt = $connection->prepare($insertSql);

            if ($insertStmt && admin_bind_dynamic_params($insertStmt, $insertTypes, $insertParams) && $insertStmt->execute()) {
              $profileExists = true;
              admin_set_flash('success', 'Admin profile record created and updated successfully!');
            } else {
              admin_set_flash('danger', 'Unable to create missing admin profile record.');
            }

            if ($insertStmt) {
              $insertStmt->close();
            }
          }

          $_SESSION['admin_name'] = $name;
          $_SESSION['name'] = $name;
          $_SESSION['email'] = $email;
          $profile['name'] = $name;
          $profile['email'] = $email;
          $profile['phone'] = $phone;
          $profile['status'] = $status;
          $profile['username'] = $generatedUsername;
        } catch (Exception $e) {
          admin_set_flash('danger', 'Database error: ' . $e->getMessage());
        }
      }
      $emailCheckStmt->close();
    } else {
      admin_set_flash('danger', 'Database error. Please try again.');
    }
  } else {
    admin_set_flash('danger', 'Users table not found.');
  }

  header('Location: profile.php');
  exit();
}

$flash = admin_pull_flash();

$createdDate = !empty($profile['created_at']) ? date('F j, Y', strtotime((string) $profile['created_at'])) : 'N/A';
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
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --ink-900: #17243d;
      --ink-700: #364766;
      --ink-500: #60708e;
      --bg-soft: #f2f6fb;
      --surface: #ffffff;
      --brand: #0d6efd;
      --brand-strong: #0a58ca;
      --brand-tint: #cfe2ff;
      --accent: #f1b24a;
      --danger: #d64545;
      --success: #2d9b66;
      --warning: #c98515;
      --radius-md: 12px;
      --radius-lg: 18px;
      --shadow-sm: 0 6px 18px rgba(23, 36, 61, 0.08);
      --shadow-md: 0 14px 34px rgba(23, 36, 61, 0.1);
    }
    body {
      font-family: 'Manrope', 'Segoe UI', sans-serif !important;
      background: radial-gradient(circle at top right, #e4f9f2, transparent 32%), var(--bg-soft) !important;
      color: var(--ink-900);
    }
    .dashboard-shell { margin-left: 280px; padding: 28px; min-height: 100vh; }
    .dashboard-inner { max-width: 1200px; margin: 0 auto; }
    .headline-panel {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
      border-radius: var(--radius-lg);
      padding: 32px;
      margin-bottom: 28px;
      box-shadow: var(--shadow-md);
      color: white;
      position: relative;
      overflow: hidden;
    }
    .headline-panel h1 { color: white !important; margin: 0; font-size: 2rem; font-weight: 800; position: relative; z-index: 1; }
    .headline-panel p { color: rgba(255,255,255,0.85); margin: 8px 0 0; font-weight: 500; position: relative; z-index: 1; }
    .headline-panel::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
    }
    .profile-section {
      background: var(--surface);
      border: 1px solid #dce7f3;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      margin-bottom: 24px;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .profile-section:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
    .profile-header {
      background: linear-gradient(135deg, #f6fbff 0%, rgba(241, 178, 74, 0.08) 100%);
      padding: 40px;
      text-align: center;
      border-bottom: 1px solid #dce7f3;
    }
    .profile-avatar {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, var(--brand), var(--brand-strong));
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      box-shadow: 0 10px 20px rgba(13, 110, 253, 0.32);
    }
    .profile-avatar i { font-size: 60px; color: #19253f; }
    .profile-name { color: var(--ink-900); font-size: 28px; font-weight: 800; margin: 0; }
    .profile-role { color: var(--ink-500); font-size: 15px; margin: 6px 0 0; font-weight: 600; }
    .profile-id { color: var(--ink-700); font-size: 13px; margin: 4px 0 0; font-weight: 500; }
    .info-section {
      padding: 32px;
      border-bottom: 1px solid #dce7f3;
    }
    .info-section:last-child { border-bottom: none; }
    .info-section h5 {
      color: var(--ink-900);
      font-weight: 800;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--brand);
      font-size: 16px;
    }
    .info-section h5 i { color: var(--brand); font-size: 18px; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
    .info-item {
      padding: 16px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .info-item:last-child { border-bottom: none; }
    .info-label { color: var(--ink-500); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .info-value { color: var(--ink-900); font-size: 16px; font-weight: 600; }
    .form-label { color: var(--ink-900); font-weight: 700; margin-bottom: 8px; font-size: 14px; }
    .form-control, .form-select {
      border: 1px solid #cedbf0 !important;
      border-radius: var(--radius-md) !important;
      min-height: 44px;
      font-family: 'Manrope', sans-serif;
      color: var(--ink-900);
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--brand) !important;
      box-shadow: 0 0 0 3px var(--brand-tint);
    }
    .btn-submit {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
      color: white !important;
      border: none !important;
      font-weight: 700 !important;
      border-radius: var(--radius-md) !important;
      box-shadow: 0 8px 18px rgba(13, 110, 253, 0.2);
      padding: 12px 32px;
      min-height: 44px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      font-size: 15px;
    }
    .btn-submit:hover {
      background: linear-gradient(135deg, var(--brand-strong) 0%, #0947a1 100%);
      transform: translateY(-2px);
      box-shadow: 0 12px 22px rgba(13, 110, 253, 0.28);
      color: white !important;
    }
    .btn-submit i { margin-right: 8px; }
    .badge {
      border-radius: 999px !important;
      font-weight: 700 !important;
      letter-spacing: 0.2px;
      padding: 6px 12px !important;
      font-size: 12px;
    }
    .badge.bg-success { background: var(--success) !important; }
    .badge.bg-secondary { background: var(--ink-500) !important; }
    .alert { border: none; border-radius: var(--radius-md); margin-bottom: 24px; }
    .alert-success { background: #d1fce6; color: #155e4e; }
    .alert-danger { background: #fdd8d8; color: #842c24; }
    .form-section {
      background: var(--surface);
      border: 1px solid #dce7f3;
      border-radius: var(--radius-md);
      padding: 32px;
      box-shadow: var(--shadow-sm);
    }
    .btn-group-submit { display: flex; justify-content: center; gap: 12px; margin-top: 32px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="dashboard-shell">
    <div class="dashboard-inner">
      <!-- Headline Panel -->
      <div class="headline-panel">
        <h1><i class="fas fa-user-shield" style="margin-right: 12px;"></i>My Profile</h1>
        <p>Manage your admin account and security settings</p>
      </div>

      <!-- Flash Messages -->
      <?php if ($dbError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
          <strong>Database Error:</strong> <?php echo htmlspecialchars($dbError); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
          <i class="fas fa-<?php echo htmlspecialchars($flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'); ?>" style="margin-right: 8px;"></i>
          <?php echo htmlspecialchars($flash['message']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <!-- Profile Card -->
      <div class="profile-section">
        <div class="profile-header">
          <div class="profile-avatar">
            <i class="fas fa-user-shield"></i>
          </div>
          <h2 class="profile-name"><?php echo htmlspecialchars((string) $profile['name']); ?></h2>
          <p class="profile-role"><?php echo htmlspecialchars(ucfirst((string) ($profile['role'] ?? 'admin'))); ?></p>
          <p class="profile-id"><?php echo htmlspecialchars($adminCode); ?> &nbsp;|&nbsp; <?php echo htmlspecialchars((string) ($profile['username'] ?? '')); ?></p>
        </div>
        
        <!-- Personal Information Section -->
        <div class="info-section">
          <h5><i class="fas fa-id-card"></i> Personal Information</h5>
          <div class="info-grid">
            <div>
              <div class="info-item">
                <div class="info-label">Full Name</div>
                <div class="info-value"><?php echo htmlspecialchars((string) $profile['name']); ?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Username</div>
                <div class="info-value"><?php echo htmlspecialchars((string) ($profile['username'] ?: '-')); ?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars((string) ($profile['email'] ?: '-')); ?></div>
              </div>
            </div>
            <div>
              <div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value"><?php echo htmlspecialchars((string) ($profile['phone'] ?: '-')); ?></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Account Details Section -->
        <div class="info-section">
          <h5><i class="fas fa-shield-alt"></i> Account Details</h5>
          <div class="info-grid">
            <div>
              <div class="info-item">
                <div class="info-label">Admin ID</div>
                <div class="info-value"><?php echo htmlspecialchars($adminCode); ?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Role</div>
                <div class="info-value"><?php echo htmlspecialchars(ucfirst((string) ($profile['role'] ?? 'admin'))); ?></div>
              </div>
            </div>
            <div>
              <div class="info-item">
                <div class="info-label">Joined Date</div>
                <div class="info-value"><?php echo htmlspecialchars($createdDate); ?></div>
              </div>
              <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                  <span class="badge <?php echo (admin_normalize_status($profile['status'] ?? 'Active', 'Active') === 'Active') ? 'bg-success' : 'bg-secondary'; ?>">
                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 6px;"></i>
                    <?php echo htmlspecialchars(admin_normalize_status($profile['status'] ?? 'Active', 'Active')); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Edit Profile Form -->
      <div class="form-section">
        <h5 style="color: var(--ink-900); font-weight: 800; margin-bottom: 28px; display: flex; align-items: center; gap: 10px; font-size: 18px;">
          <i class="fas fa-edit" style="color: var(--brand);"></i> Edit Profile
        </h5>
        <form method="POST" action="" novalidate id="profileForm">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_flash_csrf'] ?? ($_SESSION['admin_flash_csrf'] = bin2hex(random_bytes(32)))); ?>">
          <input type="hidden" name="form_action" id="formActionInput" value="save_profile">
          <input type="hidden" name="delete_confirm_text" id="deleteConfirmInput" value="">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
              <input type="text" class="form-control" name="name" id="nameInput" data-validation="required,min,max" minlength="2" maxlength="100" value="<?php echo htmlspecialchars((string) $profile['name']); ?>" placeholder="Enter your full name">
              <small style="color: var(--ink-500); margin-top: 4px; display: block;">Minimum 2 characters required.</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email Address <span style="color: var(--danger);">*</span></label>
              <input type="email" class="form-control" name="email" id="emailInput" data-validation="required,email" value="<?php echo htmlspecialchars((string) ($profile['email'] ?? '')); ?>" placeholder="Enter your email">
              <small style="color: var(--ink-500); margin-top: 4px; display: block;">Valid email format required.</small>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" class="form-control" name="phone" id="phoneInput" minlength="10" maxlength="15" value="<?php echo htmlspecialchars((string) ($profile['phone'] ?? '')); ?>" placeholder="Enter your phone number">
              <small style="color: var(--ink-500); margin-top: 4px; display: block;">At least 10 digits if provided.</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Account Status</label>
              <input type="text" class="form-control" value="Active" disabled style="background:#f8f9fa; color: var(--ink-500);">
              <small style="color: var(--ink-500); margin-top: 4px; display: block;">Status cannot be changed from your own profile.</small>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">New Password <span style="color: var(--ink-500); font-weight: 500;">(Optional)</span></label>
            <input type="password" class="form-control" name="new_password" id="passwordInput" minlength="8" maxlength="128" placeholder="Leave blank to keep current password">
            <small style="color: var(--ink-500); margin-top: 4px; display: block;">Password must be at least 8 characters long if provided.</small>
          </div>
          <div class="btn-group-submit">
            <button type="submit" class="btn-submit" id="submitBtn"><i class="fas fa-save"></i> Update Profile</button>
            <button type="button" class="btn btn-outline-danger" id="deleteBtn" style="border-radius: var(--radius-md); min-height: 44px; padding: 12px 24px; font-weight: 700;">
              <i class="fas fa-trash-alt" style="margin-right: 8px;"></i>Delete Account
            </button>
          </div>
        </form>
        
        <script>
          document.getElementById('deleteBtn').addEventListener('click', function() {
            const finalCheck = prompt('This action is permanent. Type DELETE to confirm account deletion.');
            if (finalCheck === null) return;
            if (finalCheck.trim().toUpperCase() !== 'DELETE') {
              alert('Deletion cancelled. You must type DELETE exactly.');
              return;
            }
            document.getElementById('formActionInput').value = 'delete_profile';
            document.getElementById('deleteConfirmInput').value = finalCheck.trim();
            document.getElementById('profileForm').submit();
          });
        </script>
      </div>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

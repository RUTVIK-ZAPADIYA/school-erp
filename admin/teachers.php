<?php
// Admin page for listing and managing teachers.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/db_helpers.php';

$connection = $conn ?? null;

if (!($connection instanceof mysqli) && !($connection instanceof SchoolErpDemoConnection)) {
  die('Database connection is not available.');
}

// Ensure optional teacher fields exist before querying and rendering.
admin_ensure_column($connection, 'teachers', 'experience', 'INT NULL');
admin_ensure_column($connection, 'teachers', 'qualification', "VARCHAR(150) NULL");
admin_ensure_column($connection, 'teachers', 'username', "VARCHAR(100) NULL");

// Handle teacher deletion and cleanup of dependent records/accounts.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $teacherId = (int) ($_POST['teacher_id'] ?? 0);

  if ($teacherId > 0) {
    $deleteError = '';
    $teacherProfile = null;
    $linkedUserId = 0;
    $referenceTeacherIds = [];
    $transactionStarted = false;

    $teacherStmt = $connection->prepare( 'SELECT * FROM teachers WHERE id = ? LIMIT 1');
    if ($teacherStmt) {
      $teacherStmt->bind_param( 'i', $teacherId);
      $teacherStmt->execute();
      $teacherResult = $teacherStmt->get_result();
      $teacherProfile = $teacherResult ? $teacherResult->fetch_assoc() : null;
      $teacherStmt->close();
    }

    if (!$teacherProfile) {
      $deleteError = 'Teacher record was not found.';
    } else {
      if (admin_column_exists($connection, 'teachers', 'user_id')) {
        $linkedUserId = (int) ($teacherProfile['user_id'] ?? 0);
      }

      if ($linkedUserId <= 0 && admin_table_exists($connection, 'users')) {
        $lookupUsername = trim((string) ($teacherProfile['username'] ?? ''));
        $lookupEmail = trim((string) ($teacherProfile['email'] ?? ''));

        if ($lookupUsername !== '' || $lookupEmail !== '') {
          $lookupStmt = $connection->prepare(
            "SELECT id FROM users WHERE role = 'teacher' AND (username = ? OR email = ? OR username = ? OR email = ?) LIMIT 1"
          );
          if ($lookupStmt) {
            $lookupStmt->bind_param( 'ssss', $lookupUsername, $lookupUsername, $lookupEmail, $lookupEmail);
            $lookupStmt->execute();
            $lookupResult = $lookupStmt->get_result();
            $lookupRow = $lookupResult ? $lookupResult->fetch_assoc() : null;
            if ($lookupRow) {
              $linkedUserId = (int) ($lookupRow['id'] ?? 0);
            }
            $lookupStmt->close();
          }
        }
      }

      if ($linkedUserId <= 0 && admin_table_exists($connection, 'users')) {
        $legacyUserStmt = $connection->prepare( "SELECT id FROM users WHERE id = ? AND role = 'teacher' LIMIT 1");
        if ($legacyUserStmt) {
          $legacyUserStmt->bind_param( 'i', $teacherId);
          $legacyUserStmt->execute();
          $legacyUserResult = $legacyUserStmt->get_result();
          $legacyUserRow = $legacyUserResult ? $legacyUserResult->fetch_assoc() : null;
          if ($legacyUserRow) {
            $linkedUserId = (int) ($legacyUserRow['id'] ?? 0);
          }
          $legacyUserStmt->close();
        }
      }

      $referenceTeacherIds[] = $teacherId;
      if ($linkedUserId > 0 && $linkedUserId !== $teacherId) {
        $referenceTeacherIds[] = $linkedUserId;
      }
    }

    if ($deleteError === '' && $connection->begin_transaction()) {
      $transactionStarted = true;
    }

    $nullifyTargets = [
      ['classes', 'teacher_id', true],
      ['subjects', 'teacher_id', true],
      ['schedule', 'teacher_id', true],
      ['attendance', 'teacher_id', true],
      ['assignments', 'teacher_id', true],
      ['grades', 'teacher_id', true],
      ['marks', 'teacher_id', true],
      ['exams', 'invigilator', true],
    ];

    foreach ($referenceTeacherIds as $referenceTeacherId) {
      foreach ($nullifyTargets as $target) {
        [$tableName, $columnName, $allowDeleteFallback] = $target;
        $referenceError = '';
        if (!admin_clear_reference($connection, $tableName, $columnName, $referenceTeacherId, $allowDeleteFallback, $referenceError)) {
          $deleteError = 'Unable to clear related teacher data in ' . $tableName . '.';
          if ($referenceError !== '') {
            $deleteError .= ' ' . $referenceError;
          }
        }

        if ($deleteError !== '') {
          break;
        }
      }

      if ($deleteError !== '') {
        break;
      }
    }

    if ($deleteError === '') {
      $deleteStmt = $connection->prepare( 'DELETE FROM teachers WHERE id = ?');
      if (!$deleteStmt) {
        $deleteError = 'Unable to process delete request.';
      } else {
        $deleteStmt->bind_param( 'i', $teacherId);
        if (!$deleteStmt->execute()) {
          $deleteError = 'Unable to delete teacher right now.';
        } elseif ($deleteStmt->affected_rows < 1) {
          $deleteError = 'Teacher record was not found.';
        }
        $deleteStmt->close();
      }
    }

    if ($deleteError === '' && $linkedUserId > 0 && admin_table_exists($connection, 'users')) {
      $deleteUserStmt = $connection->prepare( "DELETE FROM users WHERE id = ? AND role = 'teacher' LIMIT 1");
      if ($deleteUserStmt) {
        $deleteUserStmt->bind_param( 'i', $linkedUserId);
        if (!$deleteUserStmt->execute()) {
          $deleteUserError = trim((string) $deleteUserStmt->error);
          $deleteError = 'Unable to remove linked teacher login account right now.';
          if ($deleteUserError !== '') {
            $deleteError .= ' ' . $deleteUserError;
          }
        }
        $deleteUserStmt->close();
      }
    }

    if ($deleteError === '') {
      if ($transactionStarted && !$connection->commit()) {
        $deleteError = 'Unable to finalize teacher deletion. Please try again.';
      }
    }

    if ($deleteError !== '') {
      if ($transactionStarted) {
        $connection->rollback();
      }
      admin_set_flash('danger', $deleteError);
    } else {
      admin_set_flash('success', 'Teacher deleted successfully.');
    }
  }

  header('Location: teachers.php');
  exit();
}

$search = trim((string) ($_GET['search'] ?? ''));
$teachers = [];

// Fetch teachers list with optional search filtering.
if (admin_table_exists($connection, 'teachers')) {
  if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $searchStmt = $connection->prepare(
      'SELECT id, name, username, subject, email, phone, experience, status FROM teachers WHERE name LIKE ? OR username LIKE ? OR subject LIKE ? OR email LIKE ? ORDER BY id DESC'
    );
    if ($searchStmt) {
      $searchStmt->bind_param( 'ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
      $searchStmt->execute();
      $searchResult = $searchStmt->get_result();
      if ($searchResult) {
        while ($teacherRow = $searchResult->fetch_assoc()) {
          $teachers[] = $teacherRow;
        }
      }
      $searchStmt->close();
    }
  } else {
    $listStmt = $connection->prepare('SELECT id, name, username, subject, email, phone, experience, status FROM teachers ORDER BY id DESC');
    if ($listStmt) {
      $listStmt->execute();
      $teacherResult = $listStmt->get_result();
      if ($teacherResult) {
        while ($teacherRow = $teacherResult->fetch_assoc()) {
          $teachers[] = $teacherRow;
        }
      }
      $listStmt->close();
    }
  }
}

$flash = admin_pull_flash();
?>
<!-- Render teacher list, search form, and row actions. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Teachers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .btn-add { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; }
    .btn-add:hover { background: #2980b9; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .search-box { margin-bottom: 20px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <?php include '../includes/error-modal.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h2>
      <button class="btn-add" onclick="window.location.href='add-teacher.php'"><i class="fas fa-plus"></i> Add New Teacher</button>
    </div>

    <?php if ($flash && $flash['type'] !== 'danger'): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <div class="content-card">
      <div class="search-box">
        <form method="GET" action="" novalidate>
          <input
            type="text"
            class="form-control"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search by teacher name, username, subject, or email">
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Teacher ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Subject</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Experience</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($teachers)): ?>
              <?php foreach ($teachers as $teacher): ?>
                <?php
                  $status = admin_normalize_status($teacher['status'] ?? 'Active', 'Active');
                  $badgeClass = $status === 'Active' ? 'bg-success' : 'bg-secondary';
                  $teacherCode = 'TCH' . str_pad((string) ((int) $teacher['id']), 3, '0', STR_PAD_LEFT);
                  $experience = isset($teacher['experience']) && $teacher['experience'] !== null && $teacher['experience'] !== ''
                      ? ((int) $teacher['experience']) . ' Years'
                      : '-';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($teacherCode); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['username'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['subject'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['email'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['phone'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars($experience); ?></td>
                  <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="edit-teacher.php?id=<?php echo (int) $teacher['id']; ?>" title="Edit Teacher">
                      <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" action="" style="display:inline-block;" novalidate>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="teacher_id" value="<?php echo (int) $teacher['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this teacher?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted">No teachers found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($flash && $flash['type'] === 'danger'): ?>
  <script>document.addEventListener('DOMContentLoaded', function() { showErrorModal('Error', <?php echo json_encode($flash['message']); ?>); });</script>
  <?php endif; ?>
</body>
</html>

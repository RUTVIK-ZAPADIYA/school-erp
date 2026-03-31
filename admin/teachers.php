<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'teachers', 'experience', 'INT NULL');
admin_ensure_column($connection, 'teachers', 'qualification', "VARCHAR(150) NULL");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $teacherId = (int) ($_POST['teacher_id'] ?? 0);

  if ($teacherId > 0) {
    $deleteStmt = mysqli_prepare($connection, 'DELETE FROM teachers WHERE id = ?');
    if ($deleteStmt) {
      mysqli_stmt_bind_param($deleteStmt, 'i', $teacherId);
      if (mysqli_stmt_execute($deleteStmt)) {
        admin_set_flash('success', 'Teacher deleted successfully.');
      } else {
        admin_set_flash('danger', 'Unable to delete teacher right now.');
      }
      mysqli_stmt_close($deleteStmt);
    } else {
      admin_set_flash('danger', 'Unable to process delete request.');
    }
  }

  header('Location: teachers.php');
  exit();
}

$search = trim((string) ($_GET['search'] ?? ''));
$teachers = [];

if (admin_table_exists($connection, 'teachers')) {
  if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $searchStmt = mysqli_prepare(
      $connection,
      'SELECT id, name, subject, email, phone, experience, status FROM teachers WHERE name LIKE ? OR subject LIKE ? OR email LIKE ? ORDER BY id DESC'
    );
    if ($searchStmt) {
      mysqli_stmt_bind_param($searchStmt, 'sss', $searchTerm, $searchTerm, $searchTerm);
      mysqli_stmt_execute($searchStmt);
      $searchResult = mysqli_stmt_get_result($searchStmt);
      if ($searchResult) {
        while ($teacherRow = mysqli_fetch_assoc($searchResult)) {
          $teachers[] = $teacherRow;
        }
      }
      mysqli_stmt_close($searchStmt);
    }
  } else {
    $teacherResult = mysqli_query($connection, 'SELECT id, name, subject, email, phone, experience, status FROM teachers ORDER BY id DESC');
    if ($teacherResult) {
      while ($teacherRow = mysqli_fetch_assoc($teacherResult)) {
        $teachers[] = $teacherRow;
      }
    }
  }
}

$flash = admin_pull_flash();
?>
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
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h2>
      <button class="btn-add" onclick="window.location.href='add-teacher.php'"><i class="fas fa-plus"></i> Add New Teacher</button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <div class="content-card">
      <div class="search-box">
        <form method="GET" action="">
          <input
            type="text"
            class="form-control"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search by teacher name, subject, or email">
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Teacher ID</th>
              <th>Name</th>
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
                  <td><?php echo htmlspecialchars((string) ($teacher['subject'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['email'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($teacher['phone'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars($experience); ?></td>
                  <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="add-teacher.php"><i class="fas fa-plus"></i></a>
                    <form method="POST" action="" style="display:inline-block;">
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
                <td colspan="8" class="text-center text-muted">No teachers found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

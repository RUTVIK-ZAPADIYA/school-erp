<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'subjects', 'class_id', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'teacher_id', 'INT NULL');
admin_ensure_column($connection, 'subjects', 'credits', 'INT NULL');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $subjectId = (int) ($_POST['subject_id'] ?? 0);
  if ($subjectId > 0) {
    $deleteStmt = mysqli_prepare($connection, 'DELETE FROM subjects WHERE id = ?');
    if ($deleteStmt) {
      mysqli_stmt_bind_param($deleteStmt, 'i', $subjectId);
      if (mysqli_stmt_execute($deleteStmt)) {
        admin_set_flash('success', 'Subject deleted successfully.');
      } else {
        admin_set_flash('danger', 'Unable to delete subject right now.');
      }
      mysqli_stmt_close($deleteStmt);
    }
  }

  header('Location: subjects.php');
  exit();
}

$search = trim((string) ($_GET['search'] ?? ''));
$subjects = [];

$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
$subjectNameExpression = $subjectNameColumn !== null ? "s.{$subjectNameColumn}" : "''";

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classNameExpression = $classNameColumn !== null ? "c.{$classNameColumn}" : "''";

if (admin_table_exists($connection, 'subjects')) {
  $baseSql = "SELECT s.id, {$subjectNameExpression} AS subject_name, s.code, s.credits, s.status, t.name AS teacher_name, {$classNameExpression} AS class_name
        FROM subjects s
        LEFT JOIN teachers t ON t.id = s.teacher_id
        LEFT JOIN classes c ON c.id = s.class_id";

  if ($search !== '') {
    $searchSql = $baseSql . " WHERE {$subjectNameExpression} LIKE ? OR s.code LIKE ? OR t.name LIKE ? OR {$classNameExpression} LIKE ? ORDER BY s.id DESC";
    $searchStmt = mysqli_prepare($connection, $searchSql);
    if ($searchStmt) {
      $searchTerm = '%' . $search . '%';
      mysqli_stmt_bind_param($searchStmt, 'ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
      mysqli_stmt_execute($searchStmt);
      $result = mysqli_stmt_get_result($searchStmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $subjects[] = $row;
        }
      }
      mysqli_stmt_close($searchStmt);
    }
  } else {
    $result = mysqli_query($connection, $baseSql . ' ORDER BY s.id DESC');
    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $subjects[] = $row;
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
  <title>Manage Subjects</title>
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
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .search-box { margin-bottom: 20px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-book"></i> Manage Subjects</h2>
      <button class="btn-add" onclick="window.location.href='add-subject.php'"><i class="fas fa-plus"></i> Add New Subject</button>
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
            placeholder="Search by subject name, code, class, or teacher">
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Subject Code</th><th>Subject Name</th><th>Teacher</th><th>Class</th><th>Credits</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($subjects)): ?>
              <?php foreach ($subjects as $subject): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) ($subject['code'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($subject['subject_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($subject['teacher_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($subject['class_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($subject['credits'] ?? '-')); ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="add-subject.php"><i class="fas fa-plus"></i></a>
                    <form method="POST" action="" style="display:inline-block;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="subject_id" value="<?php echo (int) $subject['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this subject?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No subjects found.</td>
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

<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'classes', 'room_number', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'classes', 'capacity', 'INT NULL');
admin_ensure_column($connection, 'classes', 'academic_year', "VARCHAR(30) NULL");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $classId = (int) ($_POST['class_id'] ?? 0);
  if ($classId > 0) {
    if (admin_column_exists($connection, 'students', 'class_id')) {
      $resetStmt = mysqli_prepare($connection, 'UPDATE students SET class_id = NULL WHERE class_id = ?');
      if ($resetStmt) {
        mysqli_stmt_bind_param($resetStmt, 'i', $classId);
        mysqli_stmt_execute($resetStmt);
        mysqli_stmt_close($resetStmt);
      }
    }

    $deleteStmt = mysqli_prepare($connection, 'DELETE FROM classes WHERE id = ?');
    if ($deleteStmt) {
      mysqli_stmt_bind_param($deleteStmt, 'i', $classId);
      if (mysqli_stmt_execute($deleteStmt)) {
        admin_set_flash('success', 'Class deleted successfully.');
      } else {
        admin_set_flash('danger', 'Unable to delete class right now.');
      }
      mysqli_stmt_close($deleteStmt);
    }
  }

  header('Location: classes.php');
  exit();
}

$search = trim((string) ($_GET['search'] ?? ''));
$classes = [];

$hasName = admin_column_exists($connection, 'classes', 'name');
$hasClassName = admin_column_exists($connection, 'classes', 'class_name');
$classNameExpression = "''";
if ($hasName && $hasClassName) {
  $classNameExpression = "COALESCE(NULLIF(c.name, ''), c.class_name)";
} elseif ($hasName) {
  $classNameExpression = 'c.name';
} elseif ($hasClassName) {
  $classNameExpression = 'c.class_name';
}

if (admin_table_exists($connection, 'classes')) {
  $baseSql = "SELECT c.id, {$classNameExpression} AS class_name, c.section, c.teacher_id, c.room_number, c.status, t.name AS teacher_name
        FROM classes c
        LEFT JOIN teachers t ON t.id = c.teacher_id";

  if ($search !== '') {
    $searchSql = $baseSql . " WHERE {$classNameExpression} LIKE ? OR c.section LIKE ? OR t.name LIKE ? ORDER BY c.id DESC";
    $searchStmt = mysqli_prepare($connection, $searchSql);
    if ($searchStmt) {
      $searchTerm = '%' . $search . '%';
      mysqli_stmt_bind_param($searchStmt, 'sss', $searchTerm, $searchTerm, $searchTerm);
      mysqli_stmt_execute($searchStmt);
      $result = mysqli_stmt_get_result($searchStmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $classes[] = $row;
        }
      }
      mysqli_stmt_close($searchStmt);
    }
  } else {
    $result = mysqli_query($connection, $baseSql . ' ORDER BY c.id DESC');
    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
      }
    }
  }
}

$hasStudentClassId = admin_column_exists($connection, 'students', 'class_id');
$hasStudentClass = admin_column_exists($connection, 'students', 'class');

foreach ($classes as $index => $classRow) {
  $totalStudents = 0;

  if ($hasStudentClassId) {
    $countStmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM students WHERE class_id = ?');
    if ($countStmt) {
      $classId = (int) $classRow['id'];
      mysqli_stmt_bind_param($countStmt, 'i', $classId);
      mysqli_stmt_execute($countStmt);
      $countResult = mysqli_stmt_get_result($countStmt);
      $countRow = $countResult ? mysqli_fetch_assoc($countResult) : null;
      $totalStudents = (int) ($countRow['total'] ?? 0);
      mysqli_stmt_close($countStmt);
    }
  }

  if ($totalStudents === 0 && $hasStudentClass) {
    $className = (string) ($classRow['class_name'] ?? '');
    if ($className !== '') {
      $nameCountStmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM students WHERE class = ?');
      if ($nameCountStmt) {
        mysqli_stmt_bind_param($nameCountStmt, 's', $className);
        mysqli_stmt_execute($nameCountStmt);
        $nameCountResult = mysqli_stmt_get_result($nameCountStmt);
        $nameCountRow = $nameCountResult ? mysqli_fetch_assoc($nameCountResult) : null;
        $totalStudents = (int) ($nameCountRow['total'] ?? 0);
        mysqli_stmt_close($nameCountStmt);
      }
    }
  }

  $classes[$index]['total_students'] = $totalStudents;
}

$flash = admin_pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Classes</title>
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
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .search-box { margin-bottom: 20px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-school"></i> Manage Classes</h2>
      <button class="btn-add" onclick="window.location.href='add-class.php'"><i class="fas fa-plus"></i> Add New Class</button>
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
            placeholder="Search classes by name, section, or teacher">
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Class Name</th><th>Class Teacher</th><th>Total Students</th><th>Room No</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($classes)): ?>
              <?php foreach ($classes as $classRow): ?>
                <?php
                  $status = admin_normalize_status($classRow['status'] ?? 'Active', 'Active');
                  $badgeClass = $status === 'Active' ? 'bg-success' : 'bg-secondary';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) ($classRow['class_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($classRow['teacher_name'] ?? '-')); ?></td>
                  <td><?php echo (int) ($classRow['total_students'] ?? 0); ?></td>
                  <td><?php echo htmlspecialchars((string) ($classRow['room_number'] ?? '-')); ?></td>
                  <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="add-class.php"><i class="fas fa-plus"></i></a>
                    <form method="POST" action="" style="display:inline-block;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="class_id" value="<?php echo (int) $classRow['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this class?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No classes found.</td>
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

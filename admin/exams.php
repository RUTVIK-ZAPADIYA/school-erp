<?php
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

admin_ensure_column($connection, 'exams', 'exam_type', "VARCHAR(60) NULL");
admin_ensure_column($connection, 'exams', 'start_time', 'TIME NULL');
admin_ensure_column($connection, 'exams', 'duration', 'INT NULL');
admin_ensure_column($connection, 'exams', 'total_marks', 'INT NULL');
admin_ensure_column($connection, 'exams', 'room_number', "VARCHAR(30) NULL");
admin_ensure_column($connection, 'exams', 'invigilator', 'INT NULL');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $examId = (int) ($_POST['exam_id'] ?? 0);
  if ($examId > 0) {
    $deleteStmt = mysqli_prepare($connection, 'DELETE FROM exams WHERE id = ?');
    if ($deleteStmt) {
      mysqli_stmt_bind_param($deleteStmt, 'i', $examId);
      if (mysqli_stmt_execute($deleteStmt)) {
        admin_set_flash('success', 'Exam deleted successfully.');
      } else {
        admin_set_flash('danger', 'Unable to delete exam right now.');
      }
      mysqli_stmt_close($deleteStmt);
    }
  }

  header('Location: exams.php');
  exit();
}

$search = trim((string) ($_GET['search'] ?? ''));
$exams = [];

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classNameExpression = $classNameColumn !== null ? "c.{$classNameColumn}" : "''";

$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);
$subjectNameExpression = $subjectNameColumn !== null ? "s.{$subjectNameColumn}" : "''";

if (admin_table_exists($connection, 'exams')) {
  $baseSql = "SELECT e.id, e.exam_name, e.exam_type, e.exam_date, e.duration, e.status, {$classNameExpression} AS class_name, {$subjectNameExpression} AS subject_name
        FROM exams e
        LEFT JOIN classes c ON c.id = e.class_id
        LEFT JOIN subjects s ON s.id = e.subject_id";

  if ($search !== '') {
    $searchSql = $baseSql . " WHERE e.exam_name LIKE ? OR e.exam_type LIKE ? OR {$classNameExpression} LIKE ? OR {$subjectNameExpression} LIKE ? ORDER BY e.exam_date DESC";
    $searchStmt = mysqli_prepare($connection, $searchSql);
    if ($searchStmt) {
      $searchTerm = '%' . $search . '%';
      mysqli_stmt_bind_param($searchStmt, 'ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
      mysqli_stmt_execute($searchStmt);
      $result = mysqli_stmt_get_result($searchStmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $exams[] = $row;
        }
      }
      mysqli_stmt_close($searchStmt);
    }
  } else {
    $result = mysqli_query($connection, $baseSql . ' ORDER BY e.exam_date DESC');
    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $exams[] = $row;
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
  <title>Manage Exams</title>
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
      <h2><i class="fas fa-file-alt"></i> Manage Exams</h2>
      <button class="btn-add" onclick="window.location.href='add-exam.php'"><i class="fas fa-plus"></i> Schedule New Exam</button>
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
            placeholder="Search by exam, class, subject, or type">
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Exam Name</th><th>Class</th><th>Subject</th><th>Date</th><th>Duration</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (!empty($exams)): ?>
              <?php foreach ($exams as $exam): ?>
                <?php
                  $status = trim((string) ($exam['status'] ?? ''));
                  if ($status === '' && !empty($exam['exam_date'])) {
                      $status = strtotime((string) $exam['exam_date']) < strtotime(date('Y-m-d')) ? 'Completed' : 'Scheduled';
                  }
                  if ($status === '') {
                      $status = 'Scheduled';
                  }

                  $statusClass = 'bg-primary';
                  if (strcasecmp($status, 'Completed') === 0) {
                      $statusClass = 'bg-success';
                  } elseif (strcasecmp($status, 'Upcoming') === 0) {
                      $statusClass = 'bg-warning';
                  }

                  $durationText = !empty($exam['duration']) ? ((int) $exam['duration']) . ' Min' : '-';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) ($exam['exam_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($exam['class_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($exam['subject_name'] ?? '-')); ?></td>
                  <td><?php echo !empty($exam['exam_date']) ? htmlspecialchars(date('M d, Y', strtotime((string) $exam['exam_date']))) : '-'; ?></td>
                  <td><?php echo htmlspecialchars($durationText); ?></td>
                  <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="add-exam.php"><i class="fas fa-plus"></i></a>
                    <form method="POST" action="" style="display:inline-block;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="exam_id" value="<?php echo (int) $exam['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this exam?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No exams found.</td>
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

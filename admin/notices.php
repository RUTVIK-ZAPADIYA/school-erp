<?php
// Admin page for listing and managing notice board entries.
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

admin_notice_ensure_schema($connection);

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$classLabelSql = $classNameColumn !== null
  ? "COALESCE(NULLIF(c.{$classNameColumn}, ''), CONCAT('Class ', c.id))"
  : "CONCAT('Class ', c.id)";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = trim((string) ($_POST['action'] ?? ''));
  $noticeId = (int) ($_POST['notice_id'] ?? 0);

  if ($noticeId > 0 && $action === 'delete') {
    $deleteStmt = $connection->prepare('DELETE FROM notices WHERE id = ?');
    if (!$deleteStmt) {
      admin_set_flash('danger', 'Unable to process notice deletion right now.');
    } else {
      $deleteStmt->bind_param('i', $noticeId);
      if (!$deleteStmt->execute()) {
        admin_set_flash('danger', 'Failed to delete the notice.');
      } elseif ($deleteStmt->affected_rows < 1) {
        admin_set_flash('warning', 'Notice not found or already deleted.');
      } else {
        admin_set_flash('success', 'Notice deleted successfully.');
      }
      $deleteStmt->close();
    }
  }

  if ($noticeId > 0 && $action === 'toggle_status') {
    $targetStatus = admin_normalize_status($_POST['target_status'] ?? 'Active', 'Active');
    $toggleStmt = $connection->prepare('UPDATE notices SET status = ? WHERE id = ?');
    if (!$toggleStmt) {
      admin_set_flash('danger', 'Unable to update notice status right now.');
    } else {
      $toggleStmt->bind_param('si', $targetStatus, $noticeId);
      if (!$toggleStmt->execute()) {
        admin_set_flash('danger', 'Failed to update notice status.');
      } elseif ($toggleStmt->affected_rows < 1) {
        admin_set_flash('warning', 'Notice status was not changed.');
      } else {
        admin_set_flash('success', 'Notice status updated successfully.');
      }
      $toggleStmt->close();
    }
  }

  header('Location: notices.php');
  exit();
}

$search = trim((string) ($_GET['search'] ?? ''));
$rows = [];

$baseSql = "SELECT n.id, n.title, n.message, n.target_audience, n.class_id, n.publish_date, n.expiry_date, n.status,
                   {$classLabelSql} AS class_label
            FROM notices n
            LEFT JOIN classes c ON c.id = n.class_id";

if ($search !== '') {
  $listSql = $baseSql . ' WHERE n.title LIKE ? OR n.message LIKE ? ORDER BY COALESCE(n.publish_date, DATE(n.created_at)) DESC, n.id DESC';
  $listStmt = $connection->prepare($listSql);
  if ($listStmt) {
    $searchLike = '%' . $search . '%';
    $listStmt->bind_param('ss', $searchLike, $searchLike);
    $listStmt->execute();
    $result = $listStmt->get_result();
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
    }
    $listStmt->close();
  }
} else {
  $listStmt = $connection->prepare($baseSql . ' ORDER BY COALESCE(n.publish_date, DATE(n.created_at)) DESC, n.id DESC');
  if ($listStmt) {
    $listStmt->execute();
    $result = $listStmt->get_result();
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
    }
    $listStmt->close();
  }
}

$totalNotices = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM notices', 0);
$activeNotices = (int) admin_scalar_value($connection, "SELECT COUNT(*) FROM notices WHERE LOWER(COALESCE(status, '')) = 'active'", 0);
$publishedToday = (int) admin_scalar_value($connection, 'SELECT COUNT(*) FROM notices WHERE publish_date = CURDATE()', 0);
$expiredNotices = (int) admin_scalar_value(
  $connection,
  "SELECT COUNT(*) FROM notices WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE()",
  0
);

$flash = admin_pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notice Board Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: #fff; border-radius: 10px; padding: 20px 30px; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); display: flex; justify-content: space-between; align-items: center; }
    .header h2 { margin: 0; color: #2c3e50; font-weight: 700; }
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
    .summary-value { font-size: 1.8rem; font-weight: 800; color: #1f4ea3; line-height: 1; }
    .summary-label { color: #6b7b8f; margin-top: 8px; font-size: 0.9rem; }
    .content-card { background: #fff; border-radius: 10px; padding: 22px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
    .message-preview { max-width: 420px; color: #4a5b72; font-size: 0.9rem; }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 700; }
    .badge { padding: 6px 10px; border-radius: 999px; }
    @media (max-width: 991px) {
      .main-content { margin-left: 0; padding: 80px 14px 20px; }
      .header { flex-direction: column; align-items: flex-start; gap: 10px; }
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <?php include '../includes/error-modal.php'; ?>

  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-bullhorn"></i> Notice Board</h2>
      <a href="add-notice.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Notice</a>
    </div>

    <?php if ($flash && $flash['type'] !== 'danger'): ?>
      <div class="alert alert-<?php echo htmlspecialchars((string) $flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars((string) $flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="summary-grid">
      <div class="summary-card"><div class="summary-value"><?php echo $totalNotices; ?></div><div class="summary-label">Total Notices</div></div>
      <div class="summary-card"><div class="summary-value"><?php echo $activeNotices; ?></div><div class="summary-label">Active Notices</div></div>
      <div class="summary-card"><div class="summary-value"><?php echo $publishedToday; ?></div><div class="summary-label">Published Today</div></div>
      <div class="summary-card"><div class="summary-value"><?php echo $expiredNotices; ?></div><div class="summary-label">Expired</div></div>
    </div>

    <div class="content-card">
      <form method="GET" action="" class="mb-3" novalidate>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-search"></i></span>
          <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title or content">
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Title</th>
              <th>Audience</th>
              <th>Class</th>
              <th>Publish Date</th>
              <th>Expiry Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): ?>
              <?php foreach ($rows as $row): ?>
                <?php
                  $status = admin_normalize_status($row['status'] ?? 'Active', 'Active');
                  $statusClass = $status === 'Active' ? 'bg-success' : 'bg-secondary';
                  $audience = admin_notice_normalize_audience($row['target_audience'] ?? 'all');
                  $audienceLabel = ucfirst($audience);
                  $fullMessage = trim((string) ($row['message'] ?? ''));
                  $messagePreview = strlen($fullMessage) > 90 ? substr($fullMessage, 0, 90) . '...' : $fullMessage;
                  $classLabel = trim((string) ($row['class_label'] ?? ''));
                  if ($audience !== 'students') {
                    $classLabel = '-';
                  } elseif ($classLabel === '') {
                    $classLabel = 'All Classes';
                  }
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars((string) ($row['title'] ?? 'Untitled')); ?></div>
                    <div class="message-preview"><?php echo htmlspecialchars($messagePreview); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($audienceLabel); ?></td>
                  <td><?php echo htmlspecialchars($classLabel); ?></td>
                  <td><?php echo !empty($row['publish_date']) ? htmlspecialchars(date('d M Y', strtotime((string) $row['publish_date']))) : '-'; ?></td>
                  <td><?php echo !empty($row['expiry_date']) ? htmlspecialchars(date('d M Y', strtotime((string) $row['expiry_date']))) : '-'; ?></td>
                  <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <a href="edit-notice.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Notice"><i class="fas fa-edit"></i></a>
                    <form method="POST" action="" class="d-inline" novalidate>
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="notice_id" value="<?php echo (int) $row['id']; ?>">
                      <input type="hidden" name="target_status" value="<?php echo $status === 'Active' ? 'Inactive' : 'Active'; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                        <i class="fas <?php echo $status === 'Active' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                      </button>
                    </form>
                    <form method="POST" action="" class="d-inline" novalidate>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="notice_id" value="<?php echo (int) $row['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this notice?');" title="Delete Notice">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center text-muted py-4">No notices found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($flash && $flash['type'] === 'danger'): ?>
  <script>document.addEventListener('DOMContentLoaded', function() { showErrorModal('Error', <?php echo json_encode((string) $flash['message']); ?>); });</script>
  <?php endif; ?>
</body>
</html>

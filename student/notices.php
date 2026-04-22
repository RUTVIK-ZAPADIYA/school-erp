<?php
// Student notice board page.
require_once __DIR__ . '/auth.php';

$studentContext = student_auth_context();
$studentId = (int) ($studentContext['student_id'] ?? 0);
$studentUserId = (int) ($studentContext['user_id'] ?? 0);
$studentName = (string) ($studentContext['student_name'] ?? 'Student');

$studentClassId = 0;
$studentClassLabel = '';
if (student_auth_table_exists($conn, 'students')) {
  $classStmt = $conn->prepare('SELECT class_id, class FROM students WHERE id = ? LIMIT 1');
  if ($classStmt) {
    $classStmt->bind_param('i', $studentId);
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    $classRow = $classResult ? $classResult->fetch_assoc() : null;
    if ($classRow) {
      $studentClassId = (int) ($classRow['class_id'] ?? 0);
      $studentClassLabel = trim((string) ($classRow['class'] ?? ''));
    }
    $classStmt->close();
  }

  if ($studentClassId <= 0 && $studentUserId > 0 && $studentUserId !== $studentId) {
    if (student_auth_column_exists($conn, 'students', 'user_id')) {
      $classByUserStmt = $conn->prepare('SELECT class_id, class FROM students WHERE user_id = ? LIMIT 1');
    } else {
      $classByUserStmt = $conn->prepare('SELECT class_id, class FROM students WHERE id = ? LIMIT 1');
    }

    if ($classByUserStmt) {
      $classByUserStmt->bind_param('i', $studentUserId);
      $classByUserStmt->execute();
      $classByUserResult = $classByUserStmt->get_result();
      $classByUserRow = $classByUserResult ? $classByUserResult->fetch_assoc() : null;
      if ($classByUserRow) {
        $studentClassId = (int) ($classByUserRow['class_id'] ?? 0);
        $classLabelCandidate = trim((string) ($classByUserRow['class'] ?? ''));
        if ($classLabelCandidate !== '') {
          $studentClassLabel = $classLabelCandidate;
        }
      }
      $classByUserStmt->close();
    }
  }
}

$notices = [];
$classSpecificCount = 0;
$expiringSoonCount = 0;
$hasNoticeTable = student_auth_table_exists($conn, 'notices');

if ($hasNoticeTable) {
  $classNameColumn = student_auth_column_exists($conn, 'classes', 'name') ? 'name' : (student_auth_column_exists($conn, 'classes', 'class_name') ? 'class_name' : null);
  $classLabelExpr = $classNameColumn !== null
    ? "COALESCE(NULLIF(c.{$classNameColumn}, ''), CONCAT('Class ', c.id))"
    : "CONCAT('Class ', c.id)";

  $sql = "SELECT n.id, n.title, n.message, n.target_audience, n.class_id, n.publish_date, n.expiry_date, n.created_at,
                 {$classLabelExpr} AS class_label
          FROM notices n
          LEFT JOIN classes c ON c.id = n.class_id
          WHERE LOWER(COALESCE(n.status, '')) = 'active'
            AND (n.publish_date IS NULL OR n.publish_date <= CURDATE())
            AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())
            AND LOWER(COALESCE(n.target_audience, 'all')) IN ('all', 'students')";

  if ($studentClassId > 0) {
    $sql .= ' AND (n.class_id IS NULL OR n.class_id = 0 OR n.class_id = ?)';
    $sql .= ' ORDER BY COALESCE(n.publish_date, DATE(n.created_at)) DESC, n.id DESC';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param('i', $studentClassId);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($result && ($row = $result->fetch_assoc())) {
        if ((int) ($row['class_id'] ?? 0) > 0) {
          $classSpecificCount++;
        }
        if (!empty($row['expiry_date']) && strtotime((string) $row['expiry_date']) <= strtotime('+3 days')) {
          $expiringSoonCount++;
        }
        $notices[] = $row;
      }
      $stmt->close();
    }
  } else {
    $sql .= ' AND (n.class_id IS NULL OR n.class_id = 0)';
    $sql .= ' ORDER BY COALESCE(n.publish_date, DATE(n.created_at)) DESC, n.id DESC';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->execute();
      $result = $stmt->get_result();
      while ($result && ($row = $result->fetch_assoc())) {
        if (!empty($row['expiry_date']) && strtotime((string) $row['expiry_date']) <= strtotime('+3 days')) {
          $expiringSoonCount++;
        }
        $notices[] = $row;
      }
      $stmt->close();
    }
  }
}

$totalNotices = count($notices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notice Board - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-8 lg:pt-8">
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-amber-500" style="font-variation-settings: 'FILL' 1;">campaign</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Notice Board</h1>
        <p class="text-sm text-stone-500">Latest announcements for <?php echo htmlspecialchars($studentName); ?></p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Visible Notices</p>
        <p class="text-3xl font-bold text-amber-600"><?php echo $totalNotices; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Class Specific</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo $classSpecificCount; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Expiring in 3 Days</p>
        <p class="text-3xl font-bold text-red-600"><?php echo $expiringSoonCount; ?></p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200 flex items-center justify-between">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">notifications</span>
          Announcement Feed
        </h2>
        <span class="text-xs text-stone-500">
          <?php echo $studentClassLabel !== '' ? htmlspecialchars('Class: ' . $studentClassLabel) : 'General Notices'; ?>
        </span>
      </div>

      <div class="p-6 space-y-4">
        <?php if (!$hasNoticeTable): ?>
          <div class="p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm">
            Notice board is not configured yet. Please contact admin.
          </div>
        <?php elseif (!empty($notices)): ?>
          <?php foreach ($notices as $notice): ?>
            <?php
              $isClassScoped = (int) ($notice['class_id'] ?? 0) > 0;
              $audience = strtolower(trim((string) ($notice['target_audience'] ?? 'all')));
              $badgeText = $isClassScoped ? 'Class Notice' : ($audience === 'students' ? 'Students' : 'General');
              $badgeClass = $isClassScoped ? 'bg-blue-100 text-blue-700' : ($audience === 'students' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700');
            ?>
            <article class="rounded-lg border border-stone-200 p-5 hover:bg-stone-50 transition">
              <div class="flex items-center justify-between gap-3 mb-2">
                <h3 class="text-base font-semibold text-stone-900"><?php echo htmlspecialchars((string) ($notice['title'] ?? 'Untitled')); ?></h3>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeText); ?></span>
              </div>
              <p class="text-sm text-stone-700 leading-6 mb-3"><?php echo nl2br(htmlspecialchars((string) ($notice['message'] ?? ''))); ?></p>
              <div class="text-xs text-stone-500 flex flex-wrap gap-4">
                <span>Published: <?php echo !empty($notice['publish_date']) ? htmlspecialchars(date('d M Y', strtotime((string) $notice['publish_date']))) : htmlspecialchars(date('d M Y', strtotime((string) ($notice['created_at'] ?? 'now')))); ?></span>
                <span>Expiry: <?php echo !empty($notice['expiry_date']) ? htmlspecialchars(date('d M Y', strtotime((string) $notice['expiry_date']))) : 'No expiry'; ?></span>
                <?php if ($isClassScoped): ?>
                  <span>Class: <?php echo htmlspecialchars((string) ($notice['class_label'] ?? 'Assigned Class')); ?></span>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="p-6 rounded-lg border border-stone-200 bg-stone-50 text-center text-stone-500 text-sm">
            No active notices are available right now.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>

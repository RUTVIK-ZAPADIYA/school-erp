<?php
// Teacher notice board page.
require_once __DIR__ . '/auth.php';
include '../includes/db_connect.php';

function teacher_notice_valid_date($value)
{
  if ($value === '') {
    return true;
  }

  $dt = DateTime::createFromFormat('Y-m-d', $value);
  return $dt instanceof DateTime && $dt->format('Y-m-d') === $value;
}

$teacherContext = teacher_auth_resolve_context($conn);
$teacherName = (string) ($teacherContext['teacher_name'] ?? ($_SESSION['teacher_name'] ?? 'Teacher'));
$teacherUserId = (int) ($teacherContext['user_id'] ?? 0);
$teacherIds = teacher_auth_sanitize_ids((array) ($teacherContext['teacher_ids'] ?? [$teacherUserId]));
$teacherIdsSql = implode(',', $teacherIds);

$classOptions = [];
if (
  teacher_auth_table_exists($conn, 'classes')
  && teacher_auth_column_exists($conn, 'classes', 'teacher_id')
) {
  $classNameColumn = teacher_auth_first_existing_column($conn, 'classes', ['name', 'class_name']);
  $classLabelExpr = $classNameColumn !== null
    ? "COALESCE(NULLIF({$classNameColumn}, ''), CONCAT('Class ', id))"
    : "CONCAT('Class ', id)";

  $classSql = "SELECT id, {$classLabelExpr} AS class_label
               FROM classes
               WHERE teacher_id IN ({$teacherIdsSql})
               ORDER BY class_label ASC, id ASC";
  $classStmt = $conn->prepare($classSql);
  if ($classStmt) {
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    while ($classResult && ($classRow = $classResult->fetch_assoc())) {
      $classOptions[] = [
        'id' => (int) ($classRow['id'] ?? 0),
        'class_label' => (string) ($classRow['class_label'] ?? ''),
      ];
    }
    $classStmt->close();
  }
}

$formData = [
  'title' => '',
  'message' => '',
  'class_id' => '',
  'publish_date' => date('Y-m-d'),
  'expiry_date' => '',
];
$successMessage = '';
$errorMessage = '';

$hasNoticeTable = teacher_auth_table_exists($conn, 'notices');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'publish_student_notice') {
  foreach ($formData as $key => $value) {
    $formData[$key] = trim((string) ($_POST[$key] ?? ''));
  }

  if (!$hasNoticeTable) {
    $errorMessage = 'Notice board is not configured yet. Please contact admin.';
  } elseif ($teacherUserId <= 0) {
    $errorMessage = 'Unable to verify teacher account. Please login again.';
  } elseif ($formData['title'] === '' || $formData['message'] === '') {
    $errorMessage = 'Title and message are required.';
  } elseif (!teacher_notice_valid_date($formData['publish_date']) || !teacher_notice_valid_date($formData['expiry_date'])) {
    $errorMessage = 'Please provide valid publish and expiry dates.';
  } elseif ($formData['expiry_date'] !== '' && $formData['publish_date'] !== '' && $formData['expiry_date'] < $formData['publish_date']) {
    $errorMessage = 'Expiry date cannot be earlier than publish date.';
  }

  $classId = (int) $formData['class_id'];
  if ($errorMessage === '' && $classId <= 0) {
    $errorMessage = 'Please select a class for this student notice.';
  }

  if ($errorMessage === '' && teacher_auth_class_owner_id($conn, $classId, $teacherIds) <= 0) {
    $errorMessage = 'You can only publish notices to your own classes.';
  }

  if ($errorMessage === '') {
    $publishDate = $formData['publish_date'] !== '' ? $formData['publish_date'] : date('Y-m-d');
    $insertSql = "INSERT INTO notices
      (title, message, target_audience, class_id, publish_date, expiry_date, status, created_by)
      VALUES (?, ?, 'students', ?, ?, NULLIF(?, ''), 'Active', ?)";
    $insertStmt = $conn->prepare($insertSql);

    if (!$insertStmt) {
      $errorMessage = 'Unable to publish notice right now. Please try again.';
    } else {
      $insertStmt->bind_param(
        'ssissi',
        $formData['title'],
        $formData['message'],
        $classId,
        $publishDate,
        $formData['expiry_date'],
        $teacherUserId
      );

      if ($insertStmt->execute()) {
        $successMessage = 'Notice published for students successfully.';
        $formData = [
          'title' => '',
          'message' => '',
          'class_id' => '',
          'publish_date' => date('Y-m-d'),
          'expiry_date' => '',
        ];
      } else {
        $errorMessage = 'Failed to publish notice. Please try again.';
      }

      $insertStmt->close();
    }
  }
}

$notices = [];
$expiringSoonCount = 0;
$teachersOnlyCount = 0;
$publishedToStudentsCount = 0;

if ($hasNoticeTable) {
  $sql = "SELECT id, title, message, target_audience, publish_date, expiry_date, created_at
          FROM notices
          WHERE LOWER(COALESCE(status, '')) = 'active'
            AND (publish_date IS NULL OR publish_date <= CURDATE())
            AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            AND LOWER(COALESCE(target_audience, 'all')) IN ('all', 'teachers')
          ORDER BY COALESCE(publish_date, DATE(created_at)) DESC, id DESC";

  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) {
      if (strtolower(trim((string) ($row['target_audience'] ?? 'all'))) === 'teachers') {
        $teachersOnlyCount++;
      }
      if (!empty($row['expiry_date']) && strtotime((string) $row['expiry_date']) <= strtotime('+3 days')) {
        $expiringSoonCount++;
      }
      $notices[] = $row;
    }
    $stmt->close();
  }

  if (
    teacher_auth_table_exists($conn, 'classes')
    && teacher_auth_column_exists($conn, 'classes', 'teacher_id')
  ) {
    $studentNoticeSql = "SELECT COUNT(*) AS total
                         FROM notices
                         WHERE LOWER(COALESCE(status, '')) = 'active'
                           AND (publish_date IS NULL OR publish_date <= CURDATE())
                           AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                           AND LOWER(COALESCE(target_audience, 'all')) = 'students'
                           AND class_id IN (SELECT id FROM classes WHERE teacher_id IN ({$teacherIdsSql}))";
    $studentNoticeStmt = $conn->prepare($studentNoticeSql);
    if ($studentNoticeStmt) {
      $studentNoticeStmt->execute();
      $studentNoticeResult = $studentNoticeStmt->get_result();
      $studentNoticeRow = $studentNoticeResult ? $studentNoticeResult->fetch_assoc() : null;
      $publishedToStudentsCount = (int) ($studentNoticeRow['total'] ?? 0);
      $studentNoticeStmt->close();
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
  <title>Notice Board - Teacher Portal</title>
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
        <p class="text-sm text-stone-500">Manage announcements and publish to students, <?php echo htmlspecialchars($teacherName); ?></p>
      </div>
    </div>

    <?php if ($successMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
        <?php echo htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Visible Notices</p>
        <p class="text-3xl font-bold text-amber-600"><?php echo $totalNotices; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Your Classes</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo count($classOptions); ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Teachers Only</p>
        <p class="text-3xl font-bold text-indigo-600"><?php echo $teachersOnlyCount; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-1">Student Notices Live</p>
        <p class="text-3xl font-bold text-emerald-600"><?php echo $publishedToStudentsCount; ?></p>
      </div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200 mb-8">
      <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-amber-500">outgoing_mail</span>
        <h2 class="text-lg font-bold text-stone-900">Publish Notice To Students</h2>
      </div>

      <?php if (empty($classOptions)): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
          No classes are assigned to you yet. Ask admin to assign classes before publishing student notices.
        </div>
      <?php else: ?>
        <form method="POST" action="" class="space-y-4" novalidate>
          <input type="hidden" name="action" value="publish_student_notice">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-stone-700 mb-1">Notice Title *</label>
              <input
                type="text"
                name="title"
                maxlength="255"
                value="<?php echo htmlspecialchars($formData['title']); ?>"
                class="w-full rounded-lg border-stone-300 focus:border-amber-400 focus:ring-amber-200"
                data-validation="required,min,max" data-min="3" data-max="255"
              >
              <p id="title_error" class="text-xs text-red-600 hidden mt-1"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-stone-700 mb-1">Class *</label>
              <select
                name="class_id"
                class="w-full rounded-lg border-stone-300 focus:border-amber-400 focus:ring-amber-200"
                data-validation="required,select"
              >
                <option value="">Select class</option>
                <?php foreach ($classOptions as $classOption): ?>
                  <option value="<?php echo (int) $classOption['id']; ?>" <?php echo (string) $classOption['id'] === $formData['class_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) ($classOption['class_label'] !== '' ? $classOption['class_label'] : ('Class ' . (int) $classOption['id']))); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p id="class_id_error" class="text-xs text-red-600 hidden mt-1"></p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-stone-700 mb-1">Message *</label>
            <textarea
              name="message"
              rows="4"
              class="w-full rounded-lg border-stone-300 focus:border-amber-400 focus:ring-amber-200"
              data-validation="required,min" data-min="5"
            ><?php echo htmlspecialchars($formData['message']); ?></textarea>
            <p id="message_error" class="text-xs text-red-600 hidden mt-1"></p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-stone-700 mb-1">Publish Date *</label>
              <input
                type="date"
                name="publish_date"
                value="<?php echo htmlspecialchars($formData['publish_date']); ?>"
                class="w-full rounded-lg border-stone-300 focus:border-amber-400 focus:ring-amber-200"
                data-validation="required"
              >
              <p id="publish_date_error" class="text-xs text-red-600 hidden mt-1"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-stone-700 mb-1">Expiry Date (optional)</label>
              <input
                type="date"
                name="expiry_date"
                value="<?php echo htmlspecialchars($formData['expiry_date']); ?>"
                class="w-full rounded-lg border-stone-300 focus:border-amber-400 focus:ring-amber-200"
              >
            </div>
          </div>

          <div class="flex items-center justify-between gap-3">
            <p class="text-xs text-stone-500">This notice is published to students of the selected class.</p>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 transition">
              <span class="material-symbols-outlined text-base">campaign</span>
              Publish Notice
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">notifications</span>
          Announcement Feed
        </h2>
      </div>

      <div class="p-6 space-y-4">
        <?php if (!$hasNoticeTable): ?>
          <div class="p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm">
            Notice board is not configured yet. Please contact admin.
          </div>
        <?php elseif (!empty($notices)): ?>
          <?php foreach ($notices as $notice): ?>
            <?php
              $audience = strtolower(trim((string) ($notice['target_audience'] ?? 'all')));
              $isTeacherOnly = $audience === 'teachers';
              $badgeText = $isTeacherOnly ? 'Faculty' : 'General';
              $badgeClass = $isTeacherOnly ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700';
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
  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>

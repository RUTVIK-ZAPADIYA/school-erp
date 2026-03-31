<?php
require_once __DIR__ . '/auth.php';

// Include database connection
include '../includes/db_connect.php';

// Resolve teacher identity across users.id and teachers.id.
$teacherContext = teacher_auth_resolve_context($conn);
$teacher_id = (int) ($teacherContext['user_id'] ?? 0);
$teacher_owner_ids = (array) ($teacherContext['teacher_ids'] ?? [$teacher_id]);
$teacher_ids_sql = (string) ($teacherContext['teacher_ids_sql'] ?? '0');
$teacher_name = (string) ($teacherContext['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher');

function teacher_table_exists($conn, $tableName)
{
  $safeTable = $conn->real_escape_string( $tableName);
  $result = $conn->query( "SHOW TABLES LIKE '{$safeTable}'");

  return $result && $result->num_rows > 0;
}

function teacher_column_exists($conn, $tableName, $columnName)
{
  if (!teacher_table_exists($conn, $tableName)) {
    return false;
  }

  $safeTable = $conn->real_escape_string( $tableName);
  $safeColumn = $conn->real_escape_string( $columnName);
  $result = $conn->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

  return $result && $result->num_rows > 0;
}

function teacher_first_existing_column($conn, $tableName, array $candidates)
{
  foreach ($candidates as $candidate) {
    if (teacher_column_exists($conn, $tableName, $candidate)) {
      return $candidate;
    }
  }

  return null;
}

$classNameColumn = teacher_first_existing_column($conn, 'classes', ['name', 'class_name']);
$subjectNameColumn = teacher_first_existing_column($conn, 'subjects', ['name', 'subject_name']);
$assignmentPointsColumn = teacher_first_existing_column($conn, 'assignments', ['total_points', 'total_marks']) ?? 'total_points';
$assignmentOrderColumn = teacher_column_exists($conn, 'assignments', 'created_at') ? 'a.created_at' : 'a.id';

function teacher_is_valid_date($value)
{
  return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
}

function teacher_class_owner_id($conn, $classId, array $teacherIds)
{
  if ($classId <= 0 || empty($teacherIds) || !teacher_column_exists($conn, 'classes', 'teacher_id')) {
    return 0;
  }

  if (function_exists('teacher_auth_class_owner_id')) {
    return teacher_auth_class_owner_id($conn, $classId, $teacherIds);
  }

  $safeTeacherIds = array_values(array_unique(array_filter(array_map('intval', $teacherIds), function ($id) {
    return $id > 0;
  })));
  if (empty($safeTeacherIds)) {
    return 0;
  }
  $teacherIdSql = implode(',', $safeTeacherIds);

  $stmt = $conn->prepare( "SELECT teacher_id FROM classes WHERE id = ? AND teacher_id IN ({$teacherIdSql}) LIMIT 1");
  if (!$stmt) {
    return 0;
  }

  $stmt->bind_param( 'i', $classId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();

  return (int) ($row['teacher_id'] ?? 0);
}

function teacher_subject_exists($conn, $subjectId)
{
  if ($subjectId <= 0) {
    return false;
  }

  $stmt = $conn->prepare( 'SELECT id FROM subjects WHERE id = ? LIMIT 1');
  if (!$stmt) {
    return false;
  }

  $stmt->bind_param( 'i', $subjectId);
  $stmt->execute();
  $result = $stmt->get_result();
  $exists = $result && $result->num_rows > 0;
  $stmt->close();

  return $exists;
}

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
  $title = trim((string) ($_POST['title'] ?? ''));
  $description = trim((string) ($_POST['description'] ?? ''));
  $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
  $subject_id = isset($_POST['subject_id']) ? (int) $_POST['subject_id'] : 0;
  $due_date = trim((string) ($_POST['due_date'] ?? ''));
  $points = isset($_POST['points']) ? (int) $_POST['points'] : 100;
  $class_owner_id = 0;

  if ($title === '' || $class_id <= 0 || $subject_id <= 0 || !teacher_is_valid_date($due_date)) {
    $error = 'Please fill in all required fields with valid values.';
  } elseif (($class_owner_id = teacher_class_owner_id($conn, $class_id, $teacher_owner_ids)) <= 0) {
    $error = 'Selected class is not assigned to your account.';
  } elseif (!teacher_subject_exists($conn, $subject_id)) {
    $error = 'Selected subject was not found.';
  } else {
    $safePoints = max(1, $points);
    $insertSql = "INSERT INTO assignments (title, description, teacher_id, class_id, subject_id, due_date, {$assignmentPointsColumn}, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare( $insertSql);

    if (!$stmt) {
      $error = 'Unable to create assignment right now.';
    } else {
      $stmt->bind_param( 'ssiiisi', $title, $description, $class_owner_id, $class_id, $subject_id, $due_date, $safePoints);
      if ($stmt->execute()) {
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
      }
      $error = 'Error creating assignment: ' . $conn->error;
      $stmt->close();
    }
  }
}

// Handle assignment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
  $assignment_id = isset($_POST['assignment_id']) ? (int) $_POST['assignment_id'] : 0;

  if ($assignment_id > 0) {
    $ownsStmt = $conn->prepare( "SELECT id FROM assignments WHERE id = ? AND teacher_id IN ({$teacher_ids_sql}) LIMIT 1");
    if (!$ownsStmt) {
      $error = 'Unable to validate assignment ownership.';
    } else {
      $ownsStmt->bind_param( 'i', $assignment_id);
      $ownsStmt->execute();
      $ownsResult = $ownsStmt->get_result();
      $isOwner = $ownsResult && $ownsResult->num_rows > 0;
      $ownsStmt->close();

      if (!$isOwner) {
        $error = 'Assignment not found or not assigned to your account.';
      } else {
        $deleteSubStmt = $conn->prepare( 'DELETE FROM assignment_submissions WHERE assignment_id = ?');
        if ($deleteSubStmt) {
          $deleteSubStmt->bind_param( 'i', $assignment_id);
          $deleteSubStmt->execute();
          $deleteSubStmt->close();
        }

        $deleteStmt = $conn->prepare( "DELETE FROM assignments WHERE id = ? AND teacher_id IN ({$teacher_ids_sql})");
        if ($deleteStmt) {
          $deleteStmt->bind_param( 'i', $assignment_id);
          if ($deleteStmt->execute()) {
            $deleteStmt->close();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
          }
          $error = 'Error deleting assignment: ' . $conn->error;
          $deleteStmt->close();
        } else {
          $error = 'Unable to delete assignment right now.';
        }
      }
    }
  } else {
    $error = 'Invalid assignment selected for deletion.';
  }
}

// Handle assignment editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assignment'])) {
  $assignment_id = isset($_POST['assignment_id']) ? (int) $_POST['assignment_id'] : 0;
  $title = trim((string) ($_POST['edit_title'] ?? ''));
  $description = trim((string) ($_POST['edit_description'] ?? ''));
  $class_id = isset($_POST['edit_class_id']) ? (int) $_POST['edit_class_id'] : 0;
  $subject_id = isset($_POST['edit_subject_id']) ? (int) $_POST['edit_subject_id'] : 0;
  $due_date = trim((string) ($_POST['edit_due_date'] ?? ''));
  $points = isset($_POST['edit_points']) ? (int) $_POST['edit_points'] : 100;
  $class_owner_id = 0;

  if ($assignment_id <= 0 || $title === '' || $class_id <= 0 || $subject_id <= 0 || !teacher_is_valid_date($due_date)) {
    $error = 'Please fill in all required fields with valid values.';
  } elseif (($class_owner_id = teacher_class_owner_id($conn, $class_id, $teacher_owner_ids)) <= 0) {
    $error = 'Selected class is not assigned to your account.';
  } elseif (!teacher_subject_exists($conn, $subject_id)) {
    $error = 'Selected subject was not found.';
  } else {
    $safePoints = max(1, $points);
    $updateSql = "UPDATE assignments
            SET title = ?, description = ?, class_id = ?, subject_id = ?, due_date = ?, {$assignmentPointsColumn} = ?, teacher_id = ?
            WHERE id = ? AND teacher_id IN ({$teacher_ids_sql})";
    $stmt = $conn->prepare( $updateSql);

    if (!$stmt) {
      $error = 'Unable to update assignment right now.';
    } else {
      $stmt->bind_param( 'ssiisiii', $title, $description, $class_id, $subject_id, $due_date, $safePoints, $class_owner_id, $assignment_id);
      if ($stmt->execute()) {
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
      }
      $error = 'Error updating assignment: ' . $conn->error;
      $stmt->close();
    }
  }
}

// Get assignments for the teacher
$assignments = [];
if (teacher_table_exists($conn, 'assignments') && teacher_table_exists($conn, 'classes') && teacher_table_exists($conn, 'subjects')) {
  $classNameExpr = $classNameColumn !== null ? "c.`{$classNameColumn}`" : "''";
  $subjectNameExpr = $subjectNameColumn !== null ? "s.`{$subjectNameColumn}`" : "''";

  $sql_assignments = "SELECT a.id, a.title, a.description, a.due_date, a.{$assignmentPointsColumn} AS total_points,
                 a.created_at, a.class_id, a.subject_id,
                 {$classNameExpr} AS class_name, {$subjectNameExpr} AS subject_name,
              (SELECT COUNT(*) FROM students st
                WHERE st.class_id = a.class_id
                  OR LOWER(REPLACE(REPLACE(TRIM(COALESCE(st.`class`, '')), ' ', ''), '-', '')) =
                    LOWER(REPLACE(REPLACE(TRIM(COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))), ' ', ''), '-', ''))
              ) AS total_students,
                 (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id) AS submissions
            FROM assignments a
            INNER JOIN classes c ON a.class_id = c.id
            INNER JOIN subjects s ON a.subject_id = s.id
            WHERE a.teacher_id IN ({$teacher_ids_sql})
            ORDER BY {$assignmentOrderColumn} DESC";
  $result_assignments = $conn->query( $sql_assignments);
  if ($result_assignments) {
    while ($row = $result_assignments->fetch_assoc()) {
      $assignments[] = $row;
    }
  }
}

// Get classes and subjects for form
$classes = [];
if ($classNameColumn !== null && teacher_table_exists($conn, 'classes') && teacher_column_exists($conn, 'classes', 'teacher_id')) {
  $sql_classes = "SELECT id, `{$classNameColumn}` AS name FROM classes WHERE teacher_id IN ({$teacher_ids_sql}) ORDER BY `{$classNameColumn}` ASC";
  $result_classes = $conn->query( $sql_classes);
  if ($result_classes) {
    while ($row = $result_classes->fetch_assoc()) {
      $classes[] = $row;
    }
  }
}

$subjects = [];
if ($subjectNameColumn !== null && teacher_table_exists($conn, 'subjects')) {
  $sql_subjects = "SELECT id, `{$subjectNameColumn}` AS name FROM subjects ORDER BY `{$subjectNameColumn}` ASC";
  $result_subjects = $conn->query( $sql_subjects);
  if ($result_subjects) {
    while ($row = $result_subjects->fetch_assoc()) {
      $subjects[] = $row;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignments - The Academic Editorial</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-container-low": "#f6f3f2",
            "error-container": "#ffdad6",
            "on-surface": "#1b1c1c",
            "secondary-fixed": "#e4e2e1",
            "surface-container-high": "#eae8e7",
            "on-tertiary-fixed-variant": "#920600",
            "surface-variant": "#e4e2e1",
            "on-error-container": "#93000a",
            "surface-container-lowest": "#ffffff",
            "surface-container-highest": "#e4e2e1",
            "on-primary": "#ffffff",
            "tertiary-fixed": "#ffdad4",
            "surface-bright": "#fbf9f8",
            "on-secondary-container": "#656464",
            "secondary": "#5f5e5e",
            "on-primary-fixed": "#001947",
            "on-background": "#1b1c1c",
            "surface-tint": "#1357c9",
            "on-primary-container": "#beceff",
            "primary-container": "#0051c3",
            "on-tertiary-fixed": "#400100",
            "tertiary": "#870500",
            "on-tertiary-container": "#ffc0b6",
            "error": "#ba1a1a",
            "outline-variant": "#c3c6d6",
            "outline": "#737785",
            "on-tertiary": "#ffffff",
            "on-secondary-fixed-variant": "#474747",
            "surface-dim": "#dcd9d9",
            "secondary-fixed-dim": "#c8c6c6",
            "on-secondary-fixed": "#1b1c1c",
            "secondary-container": "#e4e2e1",
            "background": "#fbf9f8",
            "primary-fixed-dim": "#b1c5ff",
            "on-secondary": "#ffffff",
            "surface": "#fbf9f8",
            "on-surface-variant": "#434653",
            "on-primary-fixed-variant": "#00419f",
            "surface-container": "#f0eded",
            "on-error": "#ffffff",
            "inverse-surface": "#303030",
            "inverse-primary": "#b1c5ff",
            "tertiary-fixed-dim": "#ffb4a7",
            "primary": "#003b93",
            "tertiary-container": "#b20f03",
            "primary-fixed": "#dae2ff",
            "inverse-on-surface": "#f3f0f0"
          },
          fontFamily: {
            "headline": ["Inter", "sans-serif"],
            "body": ["Inter", "sans-serif"],
            "label": ["Inter", "sans-serif"],
            "mono": ["JetBrains Mono", "monospace"]
          },
          borderRadius: {
            "DEFAULT": "0.125rem",
            "lg": "0.25rem",
            "xl": "0.5rem",
            "full": "0.75rem"
          }
        }
      }
    }
  </script>
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .glass-panel {
      background: rgba(251, 249, 248, 0.7);
      backdrop-filter: blur(12px);
    }
    .pro-shadow {
      box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
    }
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
  </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-10 space-y-10">
    <!-- Header Section -->
    <section class="space-y-6">
      <div class="flex justify-between items-end">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Assignment Management</h1>
          <p class="text-on-surface-variant font-medium">Create, edit, and track assignments for your classes</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="glass-panel rounded-xl p-4 pro-shadow">
            <div class="text-sm text-on-surface-variant">Total Assignments</div>
            <div class="text-2xl font-bold text-on-surface"><?php echo count($assignments); ?></div>
          </div>
          <button onclick="openCreateModal()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow hover:bg-primary/90">
            <span class="material-symbols-outlined">add</span>
            New Assignment
          </button>
        </div>
      </div>
    </section>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
    <div class="glass-panel p-4 rounded-xl pro-shadow border-l-4 border-green-500 animate-fade-in">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-green-600">check_circle</span>
        <p class="text-sm font-semibold text-green-800"><?php echo $success; ?></p>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="glass-panel p-4 rounded-xl pro-shadow border-l-4 border-red-500 animate-fade-in">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-red-600">error</span>
        <p class="text-sm font-semibold text-red-800"><?php echo $error; ?></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Assignments Table -->
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <h3 class="text-sm font-bold text-on-surface">All Assignments</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
            </span>
            <input type="text" id="searchInput" placeholder="Search assignments..." class="pl-10 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-sm w-64 focus:ring-2 focus:ring-primary/20 focus:border-primary">
          </div>
          <select id="filterClass" class="px-4 py-2 bg-white border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            <option value="">All Classes</option>
            <?php foreach ($classes as $class): ?>
            <option value="<?php echo $class['name']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-stone-50/50">
            <tr>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Assignment</th>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Class</th>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Subject</th>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Due Date</th>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Progress</th>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10" id="assignmentsTable">
            <?php if (count($assignments) > 0): ?>
              <?php foreach ($assignments as $assignment): ?>
              <tr class="assignment-row" data-class="<?php echo htmlspecialchars($assignment['class_name']); ?>">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                      <span class="material-symbols-outlined text-blue-600">assignment</span>
                    </div>
                    <div>
                      <span class="text-sm font-bold text-on-surface"><?php echo htmlspecialchars($assignment['title']); ?></span>
                      <p class="text-xs text-on-surface-variant"><?php echo $assignment['total_points']; ?> points • Created <?php echo date('M d', strtotime($assignment['created_at'])); ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full"><?php echo htmlspecialchars($assignment['class_name']); ?></span>
                </td>
                <td class="px-6 py-4 font-mono text-xs text-on-surface-variant"><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-on-surface-variant">schedule</span>
                    <span class="font-mono text-xs text-on-surface-variant"><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                    <?php
                    $days_diff = (strtotime($assignment['due_date']) - time()) / (60 * 60 * 24);
                    if ($days_diff < 0) {
                      echo '<span class="px-2 py-1 bg-red-50 text-red-700 text-xs font-bold rounded">Overdue</span>';
                    } elseif ($days_diff <= 3) {
                      echo '<span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs font-bold rounded">Due Soon</span>';
                    }
                    ?>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-20 h-2 bg-surface-container-low rounded-full overflow-hidden">
                      <?php
                      $progress = $assignment['total_students'] > 0 ? ($assignment['submissions'] / $assignment['total_students']) * 100 : 0;
                      ?>
                      <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <span class="text-xs font-bold text-on-surface-variant"><?php echo $assignment['submissions']; ?>/<?php echo $assignment['total_students']; ?></span>
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button onclick="viewAssignment(<?php echo $assignment['id']; ?>)" class="p-2 text-on-surface-variant hover:text-blue-600 rounded-lg hover:bg-blue-50" title="View Details">
                      <span class="material-symbols-outlined text-sm">visibility</span>
                    </button>
                    <button onclick="editAssignment(<?php echo $assignment['id']; ?>, '<?php echo addslashes($assignment['title']); ?>', '<?php echo addslashes($assignment['description']); ?>', <?php echo $assignment['class_id']; ?>, <?php echo $assignment['subject_id']; ?>, '<?php echo $assignment['due_date']; ?>', <?php echo $assignment['total_points']; ?>)" class="p-2 text-on-surface-variant hover:text-green-600 rounded-lg hover:bg-green-50" title="Edit Assignment">
                      <span class="material-symbols-outlined text-sm">edit</span>
                    </button>
                    <button onclick="confirmDelete(<?php echo $assignment['id']; ?>, '<?php echo addslashes($assignment['title']); ?>')" class="p-2 text-on-surface-variant hover:text-red-600 rounded-lg hover:bg-red-50" title="Delete Assignment">
                      <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
            <tr>
              <td colspan="6" class="px-6 py-16 text-center">
                <div class="w-16 h-16 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
                  <span class="material-symbols-outlined text-on-surface-variant text-2xl">assignment</span>
                </div>
                <h3 class="text-lg font-semibold text-on-surface mb-2">No assignments yet</h3>
                <p class="text-on-surface-variant mb-6">Create your first assignment to get started with managing student work.</p>
                <button onclick="openCreateModal()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 mx-auto pro-shadow hover:bg-primary/90">
                  <span class="material-symbols-outlined">add</span>
                  Create Assignment
                </button>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <!-- Edit Assignment Modal -->
  <!-- Edit Assignment Modal -->
  <!-- Edit Assignment Modal -->
  <!-- Edit Assignment Modal -->
  <!-- Edit Assignment Modal -->
  </div>

  <!-- Edit Assignment Modal -->
  <div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="glass-panel rounded-2xl p-8 w-full max-w-2xl mx-4 pro-shadow">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold text-on-surface">Edit Assignment</h2>
          <button onclick="closeEditModal()" class="p-2 hover:bg-surface-variant rounded-lg">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <form method="POST" class="space-y-6" novalidate>
          <input type="hidden" name="assignment_id" id="edit_assignment_id">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Assignment Title</label>
              <input type="text" name="edit_title" id="edit_title" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" data-validation="required,min,max" data-min="3" data-max="120">
              <p id="edit_title_error" class="text-sm text-red-600 hidden"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Total Points</label>
              <input type="number" name="edit_points" id="edit_points" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" data-validation="required,number">
              <p id="edit_points_error" class="text-sm text-red-600 hidden"></p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Description</label>
            <textarea name="edit_description" id="edit_description" rows="4" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Class</label>
              <select name="edit_class_id" id="edit_class_id" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" data-validation="required,select">
                <option value="">Select Class</option>
                <?php foreach ($classes as $class): ?>
                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <p id="edit_class_id_error" class="text-sm text-red-600 hidden"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
              <select name="edit_subject_id" id="edit_subject_id" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" data-validation="required,select">
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <p id="edit_subject_id_error" class="text-sm text-red-600 hidden"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Due Date</label>
              <input type="date" name="edit_due_date" id="edit_due_date" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary" data-validation="required">
              <p id="edit_due_date_error" class="text-sm text-red-600 hidden"></p>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button type="button" onclick="closeEditModal()" class="px-6 py-3 text-on-surface-variant font-semibold rounded-xl hover:bg-surface-variant">Cancel</button>
            <button type="submit" name="edit_assignment" class="bg-green-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-green-700">Update Assignment</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="glass-panel rounded-2xl p-8 w-full max-w-md mx-4 pro-shadow">
        <div class="text-center">
          <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-red-600 text-2xl">delete_forever</span>
          </div>
          <h2 class="text-xl font-bold text-on-surface mb-2">Delete Assignment</h2>
          <p class="text-on-surface-variant mb-6" id="deleteMessage">Are you sure you want to delete this assignment? This action cannot be undone.</p>

          <form method="POST" class="flex justify-center gap-3" novalidate>
            <input type="hidden" name="assignment_id" id="delete_assignment_id">
            <button type="button" onclick="closeDeleteModal()" class="px-6 py-3 text-on-surface-variant font-semibold rounded-xl hover:bg-surface-variant">Cancel</button>
            <button type="submit" name="delete_assignment" class="bg-red-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-700">Delete Assignment</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
  <script>
    // Modal functions
    function openCreateModal() {
      document.getElementById('createModal').classList.remove('hidden');
    }

    function closeCreateModal() {
      document.getElementById('createModal').classList.add('hidden');
    }

    function editAssignment(id, title, description, classId, subjectId, dueDate, points) {
      document.getElementById('edit_assignment_id').value = id;
      document.getElementById('edit_title').value = title;
      document.getElementById('edit_description').value = description;
      document.getElementById('edit_class_id').value = classId;
      document.getElementById('edit_subject_id').value = subjectId;
      document.getElementById('edit_due_date').value = dueDate;
      document.getElementById('edit_points').value = points;
      document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(id, title) {
      document.getElementById('delete_assignment_id').value = id;
      document.getElementById('deleteMessage').innerHTML = `Are you sure you want to delete "<strong>${title}</strong>"? This will also delete all associated submissions and cannot be undone.`;
      document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.add('hidden');
    }

    function viewAssignment(id) {
      window.location.href = `view-assignment.php?id=${id}`;
    }

    // Search and filter functionality
    document.getElementById('searchInput').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('.assignment-row');

      rows.forEach(row => {
        const title = row.querySelector('.font-bold').textContent.toLowerCase();
        const subject = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const visible = title.includes(searchTerm) || subject.includes(searchTerm);
        row.style.display = visible ? '' : 'none';
      });
    });

    document.getElementById('filterClass').addEventListener('change', function() {
      const selectedClass = this.value;
      const rows = document.querySelectorAll('.assignment-row');

      rows.forEach(row => {
        const rowClass = row.dataset.class;
        const visible = !selectedClass || rowClass === selectedClass;
        row.style.display = visible ? '' : 'none';
      });
    });

    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
      const createModal = document.getElementById('createModal');
      const editModal = document.getElementById('editModal');
      const deleteModal = document.getElementById('deleteModal');

      if (event.target === createModal) closeCreateModal();
      if (event.target === editModal) closeEditModal();
      if (event.target === deleteModal) closeDeleteModal();
    });

    // Auto-hide success/error messages
    setTimeout(() => {
      const messages = document.querySelectorAll('.animate-fade-in');
      messages.forEach(msg => {
        msg.remove();
      });
    }, 5000);
  </script>

  <!-- Create Assignment Modal -->
  <div id="createModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="glass-panel rounded-2xl p-8 w-full max-w-2xl mx-4 pro-shadow">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-on-surface">Create New Assignment</h3>
        <button onclick="document.getElementById('createModal').classList.add('hidden')" class="p-2 text-on-surface-variant hover:text-on-surface rounded-lg hover:bg-surface-variant">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <form method="POST" class="space-y-6" novalidate>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-on-surface mb-2">Assignment Title</label>
            <input type="text" name="title" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" placeholder="Enter assignment title" data-validation="required,min,max" data-min="3" data-max="120">
            <p id="title_error" class="text-sm text-red-600 hidden"></p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-on-surface mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm resize-none" placeholder="Enter assignment description"></textarea>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Class</label>
            <select name="class_id" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" data-validation="required,select">
              <?php foreach ($classes as $class): ?>
              <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <p id="class_id_error" class="text-sm text-red-600 hidden"></p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
            <select name="subject_id" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" data-validation="required,select">
              <?php foreach ($subjects as $subject): ?>
              <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <p id="subject_id_error" class="text-sm text-red-600 hidden"></p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Due Date</label>
            <input type="date" name="due_date" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus-border-primary text-sm" data-validation="required">
            <p id="due_date_error" class="text-sm text-red-600 hidden"></p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Total Points</label>
            <input type="number" name="points" value="100" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus-border-primary text-sm" data-validation="required,number">
            <p id="points_error" class="text-sm text-red-600 hidden"></p>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-outline-variant">
          <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-6 py-2.5 border border-outline-variant rounded-xl text-sm font-medium text-on-surface hover:bg-surface-variant">
            Cancel
          </button>
          <button type="submit" name="create_assignment" class="px-6 py-2.5 bg-primary text-white rounded-xl text-sm font-medium pro-shadow">
            Create Assignment
          </button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
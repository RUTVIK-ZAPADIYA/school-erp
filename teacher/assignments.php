<?php
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
  header("Location: ../login.php");
  exit();
}

// Include database connection
include '../includes/db_connect.php';

// Get teacher info
$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'];

// Define assignment query
$sql_assignments = "SELECT a.id, a.title, a.description, a.due_date, a.total_points, a.created_at, a.class_id, a.subject_id,
                    c.name as class_name, s.name as subject_name,
                    (SELECT COUNT(*) FROM students WHERE class_id = a.class_id) as total_students,
                    (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submissions
                    FROM assignments a
                    JOIN classes c ON a.class_id = c.id
                    JOIN subjects s ON a.subject_id = s.id
                    WHERE a.teacher_id = $teacher_id
                    ORDER BY a.created_at DESC";

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, $_POST['title']) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 100;

    if (!empty($title) && !empty($class_id) && !empty($subject_id) && !empty($due_date)) {
        $sql = "INSERT INTO assignments (title, description, teacher_id, class_id, subject_id, due_date, total_points, created_at)
                VALUES ('$title', '$description', $teacher_id, $class_id, $subject_id, '$due_date', $points, NOW())";

        if (mysqli_query($conn, $sql)) {
            $success = "Assignment created successfully!";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Error creating assignment: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle assignment deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_assignment'])) {
    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;

    if ($assignment_id > 0) {
        // First delete related submissions
        $sql_delete_submissions = "DELETE FROM assignment_submissions WHERE assignment_id = $assignment_id";
        mysqli_query($conn, $sql_delete_submissions);

        // Then delete the assignment
        $sql_delete = "DELETE FROM assignments WHERE id = $assignment_id AND teacher_id = $teacher_id";
        if (mysqli_query($conn, $sql_delete)) {
            $success = "Assignment deleted successfully!";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Error deleting assignment: " . mysqli_error($conn);
        }
    }
}

// Handle assignment editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_assignment'])) {
    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    $title = isset($_POST['edit_title']) ? mysqli_real_escape_string($conn, $_POST['edit_title']) : '';
    $description = isset($_POST['edit_description']) ? mysqli_real_escape_string($conn, $_POST['edit_description']) : '';
    $class_id = isset($_POST['edit_class_id']) ? (int)$_POST['edit_class_id'] : 0;
    $subject_id = isset($_POST['edit_subject_id']) ? (int)$_POST['edit_subject_id'] : 0;
    $due_date = isset($_POST['edit_due_date']) ? $_POST['edit_due_date'] : '';
    $points = isset($_POST['edit_points']) ? (int)$_POST['edit_points'] : 100;

    if (!empty($title) && !empty($class_id) && !empty($subject_id) && !empty($due_date) && $assignment_id > 0) {
        $sql = "UPDATE assignments SET
                title = '$title',
                description = '$description',
                class_id = $class_id,
                subject_id = $subject_id,
                due_date = '$due_date',
                total_points = $points
                WHERE id = $assignment_id AND teacher_id = $teacher_id";

        if (mysqli_query($conn, $sql)) {
            $success = "Assignment updated successfully!";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Error updating assignment: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Get assignments for the teacher
$result_assignments = mysqli_query($conn, $sql_assignments);
$assignments = [];
while ($row = mysqli_fetch_assoc($result_assignments)) {
    $assignments[] = $row;
}

// Get classes and subjects for form
$sql_classes = "SELECT id, name FROM classes WHERE teacher_id = $teacher_id";
$result_classes = mysqli_query($conn, $sql_classes);
$classes = [];
while ($row = mysqli_fetch_assoc($result_classes)) {
    $classes[] = $row;
}

$sql_subjects = "SELECT id, name FROM subjects";
$result_subjects = mysqli_query($conn, $sql_subjects);
$subjects = [];
while ($row = mysqli_fetch_assoc($result_subjects)) {
    $subjects[] = $row;
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

        <form method="POST" class="space-y-6">
          <input type="hidden" name="assignment_id" id="edit_assignment_id">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Assignment Title</label>
              <input type="text" name="edit_title" id="edit_title" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Total Points</label>
              <input type="number" name="edit_points" id="edit_points" min="1" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Description</label>
            <textarea name="edit_description" id="edit_description" rows="4" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Class</label>
              <select name="edit_class_id" id="edit_class_id" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Select Class</option>
                <?php foreach ($classes as $class): ?>
                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
              <select name="edit_subject_id" id="edit_subject_id" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">Due Date</label>
              <input type="date" name="edit_due_date" id="edit_due_date" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
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

          <form method="POST" class="flex justify-center gap-3">
            <input type="hidden" name="assignment_id" id="delete_assignment_id">
            <button type="button" onclick="closeDeleteModal()" class="px-6 py-3 text-on-surface-variant font-semibold rounded-xl hover:bg-surface-variant">Cancel</button>
            <button type="submit" name="delete_assignment" class="bg-red-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-700">Delete Assignment</button>
          </form>
        </div>
      </div>
    </div>
  </div>

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

      <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-on-surface mb-2">Assignment Title</label>
            <input type="text" name="title" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" placeholder="Enter assignment title">
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-on-surface mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm resize-none" placeholder="Enter assignment description"></textarea>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Class</label>
            <select name="class_id" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
              <?php foreach ($classes as $class): ?>
              <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
            <select name="subject_id" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
              <?php foreach ($subjects as $subject): ?>
              <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Due Date</label>
            <input type="date" name="due_date" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Total Points</label>
            <input type="number" name="points" value="100" min="1" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
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
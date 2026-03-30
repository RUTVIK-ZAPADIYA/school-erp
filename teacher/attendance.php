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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

    if ($class_id > 0 && $subject_id > 0) {
        // Get students in the class
        $sql_students = "SELECT id, roll_no, name FROM students WHERE class_id = $class_id";
        $result_students = mysqli_query($conn, $sql_students);

        if ($result_students) {
            while ($student = mysqli_fetch_assoc($result_students)) {
                $student_id = $student['id'];
                $status = isset($_POST['attendance'][$student_id]) ? $_POST['attendance'][$student_id] : 'absent';

                // Insert or update attendance
                $sql_check = "SELECT id FROM attendance WHERE student_id = $student_id AND date = '$date' AND subject_id = $subject_id";
                $result_check = mysqli_query($conn, $sql_check);

                if ($result_check && mysqli_num_rows($result_check) > 0) {
                    // Update existing
                    $sql = "UPDATE attendance SET status = '$status', teacher_id = $teacher_id
                            WHERE student_id = $student_id AND date = '$date' AND subject_id = $subject_id";
                } else {
                    // Insert new
                    $sql = "INSERT INTO attendance (student_id, date, status, subject_id, teacher_id)
                            VALUES ($student_id, '$date', '$status', $subject_id, $teacher_id)";
                }
                mysqli_query($conn, $sql);
            }
            $success = "Attendance saved successfully!";
        } else {
            $error = "Error retrieving students.";
        }
    } else {
        $error = "Please select class and subject.";
    }
}

// Get classes for the teacher
$sql_classes = "SELECT id, name FROM classes WHERE teacher_id = $teacher_id";
$result_classes = mysqli_query($conn, $sql_classes);
$classes = [];
while ($row = mysqli_fetch_assoc($result_classes)) {
    $classes[] = $row;
}

// Get subjects
$sql_subjects = "SELECT id, name FROM subjects";
$result_subjects = mysqli_query($conn, $sql_subjects);
$subjects = [];
while ($row = mysqli_fetch_assoc($result_subjects)) {
    $subjects[] = $row;
}

// Default class and subject for display
$selected_class = $_POST['class_id'] ?? ($classes[0]['id'] ?? 1);
$selected_date = $_POST['date'] ?? date('Y-m-d');
$selected_subject = $_POST['subject_id'] ?? ($subjects[0]['id'] ?? 1);

// Get students for selected class with attendance data
$sql_students = "SELECT s.id, s.roll_no, s.name,
                        COALESCE(a.status, 'unmarked') as attendance_status
                 FROM students s
                 LEFT JOIN attendance a ON s.id = a.student_id
                     AND a.date = '$selected_date'
                     AND a.subject_id = $selected_subject
                 WHERE s.class_id = $selected_class
                 ORDER BY s.roll_no";
$result_students = mysqli_query($conn, $sql_students);
$students = [];
while ($row = mysqli_fetch_assoc($result_students)) {
    $students[] = $row;
}

// Get attendance statistics
$sql_stats = "SELECT
    COUNT(*) as total_students,
    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN attendance_status = 'unmarked' THEN 1 ELSE 0 END) as unmarked_count
    FROM ($sql_students) as stats";
$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Management - The Academic Editorial</title>
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
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Attendance Management</h1>
          <p class="text-on-surface-variant font-medium">Track and manage student attendance</p>
        </div>
        <div class="flex items-center gap-4">
          <button onclick="document.getElementById('attendanceForm').scrollIntoView()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">edit</span>
            Mark Attendance
          </button>
        </div>
      </div>
    </section>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
    <div class="glass-panel p-4 rounded-xl pro-shadow border-l-4 border-green-500">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-green-600">check_circle</span>
        <p class="text-sm font-semibold text-green-800"><?php echo $success; ?></p>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="glass-panel p-4 rounded-xl pro-shadow border-l-4 border-red-500">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-red-600">error</span>
        <p class="text-sm font-semibold text-red-800"><?php echo $error; ?></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Attendance Management Form -->
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <h3 class="text-sm font-bold text-on-surface">Mark Attendance</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
            </span>
            <input type="text" id="searchInput" placeholder="Search students..." class="pl-10 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-sm w-64 focus:ring-2 focus:ring-primary/20">
          </div>
        </div>
      </div>

      <form method="POST" class="p-6 space-y-6" id="attendanceForm" novalidate>
        <!-- Form Controls -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Select Class</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="class_id" data-validation="required,select" onchange="this.form.submit()">
              <?php foreach ($classes as $class): ?>
              <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <p id="class_id_error" class="text-sm text-red-600 hidden"></p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Date</label>
            <input type="date" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="date" value="<?php echo $selected_date; ?>" data-validation="required" onchange="this.form.submit()">
            <p id="date_error" class="text-sm text-red-600 hidden"></p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="subject_id" data-validation="required,select" onchange="this.form.submit()">
              <?php foreach ($subjects as $subject): ?>
              <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject == $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <p id="subject_id_error" class="text-sm text-red-600 hidden"></p>
          </div>
        </div>

        <!-- Students Attendance Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-stone-50/50">
              <tr>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Student</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Present</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Absent</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Late</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10">
              <?php foreach ($students as $student): ?>
              <tr>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded flex items-center justify-center">
                      <span class="material-symbols-outlined text-blue-600 text-sm">person</span>
                    </div>
                    <div>
                      <span class="text-xs font-bold text-on-surface"><?php echo htmlspecialchars($student['name']); ?></span>
                      <p class="text-xs text-on-surface-variant">Roll: <?php echo htmlspecialchars($student['roll_no']); ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="present"
                         <?php echo $student['attendance_status'] == 'present' ? 'checked' : ''; ?>
                         class="w-4 h-4 text-green-600 bg-white border-outline-variant focus:ring-green-500 focus:ring-2">
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="absent"
                         <?php echo $student['attendance_status'] == 'absent' ? 'checked' : ''; ?>
                         class="w-4 h-4 text-red-600 bg-white border-outline-variant focus:ring-red-500 focus:ring-2">
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="late"
                         <?php echo $student['attendance_status'] == 'late' ? 'checked' : ''; ?>
                         class="w-4 h-4 text-yellow-600 bg-white border-outline-variant focus:ring-yellow-500 focus:ring-2">
                </td>
                <td class="px-6 py-4">
                  <?php
                  $status = $student['attendance_status'];
                  $statusClass = '';
                  $statusIcon = '';
                  $statusText = ucfirst($status);

                  switch ($status) {
                    case 'present':
                      $statusClass = 'bg-green-100 text-green-800';
                      $statusIcon = 'check_circle';
                      break;
                    case 'absent':
                      $statusClass = 'bg-red-100 text-red-800';
                      $statusIcon = 'cancel';
                      break;
                    case 'late':
                      $statusClass = 'bg-yellow-100 text-yellow-800';
                      $statusIcon = 'schedule';
                      break;
                    default:
                      $statusClass = 'bg-gray-100 text-gray-800';
                      $statusIcon = 'help';
                      $statusText = 'Unmarked';
                      break;
                  }
                  ?>
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                    <span class="material-symbols-outlined text-xs"><?php echo $statusIcon; ?></span>
                    <?php echo $statusText; ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (empty($students)): ?>
        <div class="px-6 py-12 text-center">
          <div class="w-12 h-12 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-on-surface-variant text-xl">group</span>
          </div>
          <h3 class="text-sm font-semibold text-on-surface mb-2">No students found</h3>
          <p class="text-on-surface-variant">No students are enrolled in the selected class.</p>
        </div>
        <?php endif; ?>

        <!-- Submit Button -->
        <?php if (!empty($students)): ?>
        <div class="pt-6 border-t border-outline-variant flex justify-between items-center">
          <div class="text-sm text-on-surface-variant">
            Changes will be saved for all students in the selected class, date, and subject.
          </div>
          <button type="submit" name="save_attendance" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold pro-shadow flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Save Attendance
          </button>
        </div>
        <?php endif; ?>
      </form>
    </section>
  </main>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
  <script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('tbody tr');

    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();

      tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
      });
    });

    // Update status badge when radio button changes
    document.addEventListener('change', function(e) {
      if (e.target.type === 'radio' && e.target.name.startsWith('attendance[')) {
        const row = e.target.closest('tr');
        const statusCell = row.querySelector('td:last-child span');
        const status = e.target.value;

        const statusClasses = {
          present: 'bg-green-100 text-green-800',
          absent: 'bg-red-100 text-red-800',
          late: 'bg-yellow-100 text-yellow-800',
          unmarked: 'bg-gray-100 text-gray-800'
        };

        const statusIcons = {
          present: 'check_circle',
          absent: 'cancel',
          late: 'schedule',
          unmarked: 'help'
        };

        const statusTexts = {
          present: 'Present',
          absent: 'Absent',
          late: 'Late',
          unmarked: 'Unmarked'
        };

        statusCell.className = `inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${statusClasses[status]}`;
        statusCell.innerHTML = `<span class="material-symbols-outlined text-xs">${statusIcons[status]}</span>${statusTexts[status]}`;
      }
    });
  </script>
</body>
</html>

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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grades'])) {
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $exam_type = isset($_POST['exam_type']) ? mysqli_real_escape_string($conn, $_POST['exam_type']) : '';
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

    if ($class_id > 0 && !empty($exam_type) && $subject_id > 0) {
        // Get students in the class
        $sql_students = "SELECT id FROM students WHERE class_id = $class_id";
        $result_students = mysqli_query($conn, $sql_students);

        if ($result_students) {
            while ($student = mysqli_fetch_assoc($result_students)) {
                $student_id = $student['id'];
                $obtained_marks = (int)($_POST['marks_' . $student_id] ?? 0);
                $remarks = mysqli_real_escape_string($conn, $_POST['remarks_' . $student_id] ?? '');

                // Calculate grade based on marks (simple grading)
                $total_marks = 100; // Assuming 100
                $percentage = ($obtained_marks / $total_marks) * 100;
                if ($percentage >= 90) $grade = 'A+';
                elseif ($percentage >= 80) $grade = 'A';
                elseif ($percentage >= 70) $grade = 'B+';
                elseif ($percentage >= 60) $grade = 'B';
                elseif ($percentage >= 50) $grade = 'C+';
                elseif ($percentage >= 40) $grade = 'C';
                else $grade = 'F';

                // Insert or update grade
                $sql_check = "SELECT id FROM grades WHERE student_id = $student_id AND subject_id = $subject_id AND exam_type = '$exam_type'";
                $result_check = mysqli_query($conn, $sql_check);

                if (mysqli_num_rows($result_check) > 0) {
                    // Update existing
                    $sql = "UPDATE grades SET obtained_marks = $obtained_marks, grade = '$grade', remarks = '$remarks', teacher_id = $teacher_id
                            WHERE student_id = $student_id AND subject_id = $subject_id AND exam_type = '$exam_type'";
                } else {
                    // Insert new
                    $sql = "INSERT INTO grades (student_id, subject_id, exam_type, total_marks, obtained_marks, grade, remarks, teacher_id)
                            VALUES ($student_id, $subject_id, '$exam_type', $total_marks, $obtained_marks, '$grade', '$remarks', $teacher_id)";
                }
                mysqli_query($conn, $sql);
            }
            $success = "Grades saved successfully!";
        } else {
            $error = "Error retrieving students.";
        }
    } else {
        $error = "Please select class, exam type, and subject.";
    }
}

// Get classes, subjects
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

// Default selections
$selected_class = $_POST['class_id'] ?? ($classes[0]['id'] ?? 1);
$selected_exam = $_POST['exam_type'] ?? 'Mid-term';
$selected_subject = $_POST['subject_id'] ?? 1;

// Get students and their grades
$sql_students = "SELECT s.id, s.roll_no, s.name, g.obtained_marks, g.grade, g.remarks
                 FROM students s
                 LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = $selected_subject AND g.exam_type = '$selected_exam'
                 WHERE s.class_id = $selected_class";
$result_students = mysqli_query($conn, $sql_students);
$students = [];
while ($row = mysqli_fetch_assoc($result_students)) {
    $students[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grade Management - The Academic Editorial</title>
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
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Grade Management</h1>
          <p class="text-on-surface-variant font-medium">Assign and track student performance</p>
        </div>
        <div class="flex items-center gap-4">
          <button onclick="document.getElementById('gradeForm').scrollIntoView()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">edit</span>
            Manage Grades
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

    <!-- Grades Management Form -->
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <h3 class="text-sm font-bold text-on-surface">Grade Entry & Management</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
            </span>
            <input type="text" id="searchInput" placeholder="Search students..." class="pl-10 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-sm w-64 focus:ring-2 focus:ring-primary/20">
          </div>
        </div>
      </div>

      <form method="POST" class="p-6 space-y-6" id="gradeForm">
        <!-- Form Controls -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Select Class</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="class_id" required onchange="this.form.submit()">
              <?php foreach ($classes as $class): ?>
              <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Exam Type</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="exam_type" required onchange="this.form.submit()">
              <option value="Mid-term" <?php echo ($selected_exam == 'Mid-term') ? 'selected' : ''; ?>>Mid-term Examination</option>
              <option value="Final" <?php echo ($selected_exam == 'Final') ? 'selected' : ''; ?>>Final Examination</option>
              <option value="Quiz" <?php echo ($selected_exam == 'Quiz') ? 'selected' : ''; ?>>Quiz Assessment</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Subject</label>
            <select class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="subject_id" required onchange="this.form.submit()">
              <?php foreach ($subjects as $subject): ?>
              <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject == $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Students Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-stone-50/50">
              <tr>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Roll No</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Student Name</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Marks</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Grade</th>
                <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Remarks</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10">
              <?php foreach ($students as $student): ?>
              <tr>
                <td class="px-6 py-4 font-mono text-xs text-on-surface-variant"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                <td class="px-6 py-4">
                  <span class="text-xs font-bold text-on-surface"><?php echo htmlspecialchars($student['name']); ?></span>
                </td>
                <td class="px-6 py-4">
                  <input type="number" class="w-20 px-3 py-2 bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-center" name="marks_<?php echo $student['id']; ?>" value="<?php echo $student['obtained_marks'] ?? ''; ?>" max="100" min="0" placeholder="0">
                </td>
                <td class="px-6 py-4">
                  <?php if (!empty($student['grade'])): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium
                      <?php
                      $grade = $student['grade'];
                      if (in_array($grade, ['A+', 'A'])) echo 'bg-green-100 text-green-800';
                      elseif (in_array($grade, ['B+', 'B'])) echo 'bg-blue-100 text-blue-800';
                      elseif (in_array($grade, ['C+', 'C'])) echo 'bg-yellow-100 text-yellow-800';
                      else echo 'bg-red-100 text-red-800';
                      ?>">
                      <?php echo htmlspecialchars($student['grade']); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-on-surface-variant text-xs">Not graded</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                  <input type="text" class="w-full px-3 py-2 bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" name="remarks_<?php echo $student['id']; ?>" value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>" placeholder="Add remarks...">
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (empty($students)): ?>
        <div class="px-6 py-12 text-center">
          <div class="w-12 h-12 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-on-surface-variant text-xl">school</span>
          </div>
          <h3 class="text-sm font-semibold text-on-surface mb-2">No students found</h3>
          <p class="text-on-surface-variant">No students are enrolled in the selected class.</p>
        </div>
        <?php endif; ?>

        <!-- Submit Button -->
        <?php if (!empty($students)): ?>
        <div class="pt-6 border-t border-outline-variant flex justify-between items-center">
          <div class="text-sm text-on-surface-variant">
            Changes will be saved for all students in the selected class, exam, and subject.
          </div>
          <button type="submit" name="save_grades" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold pro-shadow flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Save All Grades
          </button>
        </div>
        <?php endif; ?>
      </form>
    </section>
  </main>

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

    // Auto-calculate grade based on marks
    document.querySelectorAll('input[type="number"]').forEach(input => {
      input.addEventListener('input', function() {
        const row = this.closest('tr');
        const gradeCell = row.querySelector('td:nth-child(4)');

        let grade = '';
        const marks = parseInt(this.value) || 0;
        const percentage = (marks / 100) * 100;
        if (percentage >= 90) grade = 'A+';
        else if (percentage >= 80) grade = 'A';
        else if (percentage >= 70) grade = 'B+';
        else if (percentage >= 60) grade = 'B';
        else if (percentage >= 50) grade = 'C+';
        else if (percentage >= 40) grade = 'C';
        else grade = 'F';

        if (grade) {
          const gradeClass = grade.includes('A') ? 'bg-green-100 text-green-800' :
                           grade.includes('B') ? 'bg-blue-100 text-blue-800' :
                           grade.includes('C') ? 'bg-yellow-100 text-yellow-800' :
                           'bg-red-100 text-red-800';

          gradeCell.innerHTML = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${gradeClass}">${grade}</span>`;
        }
      });
    });
  </script>
</body>
</html>

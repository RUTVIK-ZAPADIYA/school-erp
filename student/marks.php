<?php
require_once __DIR__ . '/auth.php';

$studentContext = student_auth_context();
$student_id = (int) ($studentContext['student_id'] ?? 0);
$studentFilter = student_auth_student_id_filter_sql('student_id');

// Get marks records
$marks_records = [];
$total_marks = 0;
$obtained_marks = 0;
$average = 0;
$highest = 0;
$lowest = 100;

try {
  $sql = "SELECT subject, total_marks, obtained_marks FROM marks WHERE {$studentFilter['sql']} ORDER BY subject ASC";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
    $filterParams = $studentFilter['params'];
    if (student_auth_bind_dynamic_params($stmt, $studentFilter['types'], $filterParams)) {
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $marks_records[] = $row;
        $total_marks += $row['total_marks'];
        $obtained_marks += $row['obtained_marks'];

        $recordTotalMarks = (float) ($row['total_marks'] ?? 0);
        $recordObtainedMarks = (float) ($row['obtained_marks'] ?? 0);
        $percentage = $recordTotalMarks > 0 ? ($recordObtainedMarks / $recordTotalMarks) * 100 : 0;
        if ($percentage > $highest) $highest = $percentage;
        if ($percentage < $lowest) $lowest = $percentage;
      }
        }
        $stmt->close();

        // Calculate average
        if (count($marks_records) > 0 && $total_marks > 0) {
            $average = round(($obtained_marks / $total_marks) * 100, 2);
    } else {
          $average = 0;
      $lowest = 0;
        }
    }
} catch (Exception $e) {
    error_log("Marks query error: " . $e->getMessage());
}

// Determine overall grade
if ($average >= 90) $grade = 'A+';
elseif ($average >= 80) $grade = 'A';
elseif ($average >= 70) $grade = 'B+';
elseif ($average >= 60) $grade = 'B';
else $grade = 'C';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marks - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-violet-500" style="font-variation-settings: 'FILL' 1;">grade</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Marks</h1>
        <p class="text-sm text-stone-500">View your academic performance</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Average Marks</p>
        <p class="text-3xl font-bold text-violet-600"><?php echo number_format($average, 2); ?>%</p>
        <p class="text-xs text-stone-400 mt-2">Overall performance</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Highest Score</p>
        <p class="text-3xl font-bold text-emerald-600"><?php echo number_format($highest, 2); ?>%</p>
        <p class="text-xs text-stone-400 mt-2">Best subject</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Lowest Score</p>
        <p class="text-3xl font-bold text-amber-600"><?php echo number_format($lowest, 2); ?>%</p>
        <p class="text-xs text-stone-400 mt-2">Area to improve</p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Overall Grade</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo $grade; ?></p>
        <p class="text-xs text-stone-400 mt-2">Current grade</p>
      </div>
    </div>

    <!-- Marks Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200">
      <div class="p-6 border-b border-stone-200">
        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
          <span class="material-symbols-outlined">table_chart</span>
          Subject-wise Performance
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Subject</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Total Marks</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Obtained Marks</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Percentage</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Grade</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-stone-900">Progress</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <?php if (count($marks_records) > 0): ?>
              <?php foreach ($marks_records as $record): ?>
                <?php
                  $recordTotalMarks = (float) ($record['total_marks'] ?? 0);
                  $recordObtainedMarks = (float) ($record['obtained_marks'] ?? 0);
                  $percentage = $recordTotalMarks > 0 ? ($recordObtainedMarks / $recordTotalMarks) * 100 : 0;
                  if ($percentage >= 90) $subject_grade = 'A+';
                  elseif ($percentage >= 80) $subject_grade = 'A';
                  elseif ($percentage >= 70) $subject_grade = 'B+';
                  elseif ($percentage >= 60) $subject_grade = 'B';
                  else $subject_grade = 'C';

                  if ($percentage >= 80) $color = 'emerald';
                  elseif ($percentage >= 70) $color = 'blue';
                  else $color = 'amber';
                ?>
                <tr class="hover:bg-stone-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-stone-900"><?php echo htmlspecialchars($record['subject']); ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo $record['total_marks']; ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo $record['obtained_marks']; ?></td>
                  <td class="px-6 py-4 text-sm text-stone-700"><?php echo number_format($percentage, 2); ?>%</td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-stone-100 text-stone-700 text-sm font-medium">
                      <?php echo $subject_grade; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="w-24 bg-stone-200 rounded-full h-2">
                      <div class="bg-<?php echo $color; ?>-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-stone-500">No marks records found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>

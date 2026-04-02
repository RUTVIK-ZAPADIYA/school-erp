<?php
// Student curriculum view showing weekly timetable.
require_once __DIR__ . '/auth.php';

$studentContext = student_auth_context();
$studentId = (int) ($studentContext['student_id'] ?? 0);
$studentUserId = (int) ($studentContext['user_id'] ?? 0);
$studentName = (string) ($studentContext['student_name'] ?? 'Student');

function student_curriculum_time_label($timeValue)
{
  $timestamp = strtotime((string) $timeValue);
  if ($timestamp === false) {
    return (string) $timeValue;
  }

  return date('H:i', $timestamp);
}

$studentClassId = 0;
$studentClassName = '';
$studentClassSection = '';
$noticeMessage = '';

if (student_auth_table_exists($conn, 'students')) {
  $studentRow = null;

  if ($studentId > 0 && student_auth_column_exists($conn, 'students', 'id')) {
    $studentByIdStmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    if ($studentByIdStmt) {
      $studentByIdStmt->bind_param('i', $studentId);
      $studentByIdStmt->execute();
      $studentByIdResult = $studentByIdStmt->get_result();
      $studentRow = $studentByIdResult ? $studentByIdResult->fetch_assoc() : null;
      $studentByIdStmt->close();
    }
  }

  if (!$studentRow && $studentUserId > 0 && student_auth_column_exists($conn, 'students', 'user_id')) {
    $studentByUserStmt = $conn->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
    if ($studentByUserStmt) {
      $studentByUserStmt->bind_param('i', $studentUserId);
      $studentByUserStmt->execute();
      $studentByUserResult = $studentByUserStmt->get_result();
      $studentRow = $studentByUserResult ? $studentByUserResult->fetch_assoc() : null;
      $studentByUserStmt->close();
    }
  }

  if ($studentRow) {
    $studentClassId = (int) ($studentRow['class_id'] ?? 0);
    $studentClassName = trim((string) ($studentRow['class'] ?? ''));
  }
}

if (student_auth_table_exists($conn, 'classes')) {
  $classNameColumn = student_auth_column_exists($conn, 'classes', 'name') ? 'name' : (student_auth_column_exists($conn, 'classes', 'class_name') ? 'class_name' : null);

  if ($studentClassId <= 0 && $studentClassName !== '' && $classNameColumn !== null) {
    $normalizedClassName = strtolower(str_replace([' ', '-'], '', $studentClassName));
    $classByNameSql = "SELECT id, {$classNameColumn} AS class_name, COALESCE(section, '') AS section FROM classes ORDER BY id DESC";
    $classByNameStmt = $conn->prepare($classByNameSql);
    if ($classByNameStmt && $classByNameStmt->execute()) {
      $classByNameResult = $classByNameStmt->get_result();
      while ($classByNameResult && ($classByNameRow = $classByNameResult->fetch_assoc())) {
        $candidate = strtolower(str_replace([' ', '-'], '', (string) ($classByNameRow['class_name'] ?? '')));
        if ($candidate !== '' && $candidate === $normalizedClassName) {
          $studentClassId = (int) ($classByNameRow['id'] ?? 0);
          $studentClassName = (string) ($classByNameRow['class_name'] ?? $studentClassName);
          $studentClassSection = trim((string) ($classByNameRow['section'] ?? ''));
          break;
        }
      }
      $classByNameStmt->close();
    }
  }

  if ($studentClassId > 0 && $classNameColumn !== null) {
    $classStmt = $conn->prepare("SELECT {$classNameColumn} AS class_name, COALESCE(section, '') AS section FROM classes WHERE id = ? LIMIT 1");
    if ($classStmt) {
      $classStmt->bind_param('i', $studentClassId);
      $classStmt->execute();
      $classResult = $classStmt->get_result();
      $classRow = $classResult ? $classResult->fetch_assoc() : null;
      if ($classRow) {
        $resolvedName = trim((string) ($classRow['class_name'] ?? ''));
        if ($resolvedName !== '') {
          $studentClassName = $resolvedName;
        }
        $studentClassSection = trim((string) ($classRow['section'] ?? ''));
      }
      $classStmt->close();
    }
  }
}

$scheduleEntries = [];

if (!student_auth_table_exists($conn, 'schedule')) {
  $noticeMessage = 'Curriculum is not available yet.';
} elseif ($studentClassId <= 0) {
  $noticeMessage = 'Your class is not assigned yet. Please contact admin.';
} else {
  $subjectExpr = "CONCAT('Subject ', COALESCE(s.subject_id, 0))";
  $teacherExpr = "CONCAT('Teacher ', COALESCE(s.teacher_id, 0))";
  $joinSql = '';

  if (student_auth_table_exists($conn, 'subjects')) {
    $joinSql .= ' LEFT JOIN subjects sub ON s.subject_id = sub.id';
    $hasSubjectName = student_auth_column_exists($conn, 'subjects', 'name');
    $hasLegacySubjectName = student_auth_column_exists($conn, 'subjects', 'subject_name');
    if ($hasSubjectName && $hasLegacySubjectName) {
      $subjectExpr = "COALESCE(NULLIF(sub.name, ''), sub.subject_name, CONCAT('Subject ', COALESCE(s.subject_id, 0)))";
    } elseif ($hasSubjectName) {
      $subjectExpr = "COALESCE(NULLIF(sub.name, ''), CONCAT('Subject ', COALESCE(s.subject_id, 0)))";
    } elseif ($hasLegacySubjectName) {
      $subjectExpr = "COALESCE(NULLIF(sub.subject_name, ''), CONCAT('Subject ', COALESCE(s.subject_id, 0)))";
    }
  }

  if (student_auth_table_exists($conn, 'teachers') && student_auth_column_exists($conn, 'teachers', 'name')) {
    $joinSql .= ' LEFT JOIN teachers t ON s.teacher_id = t.id';
    $teacherExpr = "COALESCE(NULLIF(t.name, ''), CONCAT('Teacher ', COALESCE(s.teacher_id, 0)))";
  }

  $scheduleSql = 'SELECT s.day_of_week, s.start_time, s.end_time, s.room, '
    . $subjectExpr . ' AS subject_name, '
    . $teacherExpr . ' AS teacher_name '
    . 'FROM schedule s '
    . $joinSql . ' '
    . 'WHERE s.class_id = ? '
    . "ORDER BY FIELD(LOWER(COALESCE(s.day_of_week, '')), 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), s.start_time ASC";

  $scheduleStmt = $conn->prepare($scheduleSql);
  if ($scheduleStmt) {
    $scheduleStmt->bind_param('i', $studentClassId);
    $scheduleStmt->execute();
    $scheduleResult = $scheduleStmt->get_result();
    while ($scheduleResult && ($scheduleRow = $scheduleResult->fetch_assoc())) {
      $scheduleRow['start_label'] = student_curriculum_time_label($scheduleRow['start_time'] ?? '');
      $scheduleRow['end_label'] = student_curriculum_time_label($scheduleRow['end_time'] ?? '');
      $scheduleRow['day_label'] = ucfirst(strtolower(trim((string) ($scheduleRow['day_of_week'] ?? ''))));
      $scheduleEntries[] = $scheduleRow;
    }
    $scheduleStmt->close();
  }
}

$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$matrix = [];
$timeSlots = [];

foreach ($scheduleEntries as $entry) {
  $dayLabel = (string) ($entry['day_label'] ?? '');
  if ($dayLabel === '') {
    continue;
  }

  if (!in_array($dayLabel, $weekDays, true)) {
    $weekDays[] = $dayLabel;
  }

  $timeSlot = (string) ($entry['start_label'] ?? '--:--') . ' - ' . (string) ($entry['end_label'] ?? '--:--');
  if (!in_array($timeSlot, $timeSlots, true)) {
    $timeSlots[] = $timeSlot;
  }

  if (!isset($matrix[$timeSlot])) {
    $matrix[$timeSlot] = [];
  }

  if (!isset($matrix[$timeSlot][$dayLabel])) {
    $matrix[$timeSlot][$dayLabel] = [];
  }

  $matrix[$timeSlot][$dayLabel][] = $entry;
}

$dayOrderMap = [
  'Monday' => 1,
  'Tuesday' => 2,
  'Wednesday' => 3,
  'Thursday' => 4,
  'Friday' => 5,
  'Saturday' => 6,
  'Sunday' => 7,
];

usort($weekDays, function ($firstDay, $secondDay) use ($dayOrderMap) {
  $firstRank = $dayOrderMap[$firstDay] ?? 99;
  $secondRank = $dayOrderMap[$secondDay] ?? 99;
  return $firstRank <=> $secondRank;
});

usort($timeSlots, function ($firstSlot, $secondSlot) {
  $firstStart = trim((string) explode('-', $firstSlot)[0]);
  $secondStart = trim((string) explode('-', $secondSlot)[0]);

  return strcmp($firstStart, $secondStart);
});

$totalPeriods = count($scheduleEntries);
$totalHours = 0.0;
$activeDayMap = [];
foreach ($scheduleEntries as $entry) {
  $startTs = strtotime((string) ($entry['start_time'] ?? ''));
  $endTs = strtotime((string) ($entry['end_time'] ?? ''));
  if ($startTs !== false && $endTs !== false && $endTs > $startTs) {
    $totalHours += ($endTs - $startTs) / 3600;
  }

  $dayKey = (string) ($entry['day_label'] ?? '');
  if ($dayKey !== '') {
    $activeDayMap[$dayKey] = true;
  }
}
$activeDays = count($activeDayMap);
$todayName = date('l');
$todayPeriods = isset($matrix) && !empty($timeSlots)
  ? array_reduce($timeSlots, function ($carry, $slot) use ($matrix, $todayName) {
      return $carry + (isset($matrix[$slot][$todayName]) ? count($matrix[$slot][$todayName]) : 0);
    }, 0)
  : 0;

$classLabel = $studentClassName !== '' ? $studentClassName : 'Unassigned';
if ($studentClassSection !== '') {
  $classLabel .= ' - ' . $studentClassSection;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Curriculum - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-8 lg:pt-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-3xl text-indigo-600" style="font-variation-settings: 'FILL' 1;">calendar_view_week</span>
        <div>
          <h1 class="text-3xl font-bold text-stone-900">Curriculum</h1>
          <p class="text-sm text-stone-500">Weekly timetable for your class</p>
        </div>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-700">
        Student: <?php echo htmlspecialchars($studentName); ?>
      </div>
    </div>

    <?php if ($noticeMessage !== ''): ?>
      <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
        <?php echo htmlspecialchars($noticeMessage); ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">Class</p>
        <p class="mt-1 text-lg font-bold text-stone-900"><?php echo htmlspecialchars($classLabel); ?></p>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">Weekly Periods</p>
        <p class="mt-1 text-lg font-bold text-blue-700"><?php echo (int) $totalPeriods; ?></p>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">Weekly Hours</p>
        <p class="mt-1 text-lg font-bold text-emerald-700"><?php echo number_format($totalHours, 1); ?>h</p>
      </div>
      <div class="rounded-lg border border-stone-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-stone-500">Today</p>
        <p class="mt-1 text-lg font-bold text-violet-700"><?php echo (int) $todayPeriods; ?> period(s)</p>
      </div>
    </div>

    <div class="rounded-lg border border-stone-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-stone-200 px-6 py-4">
        <h2 class="text-lg font-bold text-stone-900">Weekly Timetable</h2>
        <p class="text-xs text-stone-500 mt-1"><?php echo (int) $activeDays; ?> active day(s) this week</p>
      </div>

      <?php if (!empty($timeSlots)): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-stone-50 border-b border-stone-200">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700">Time</th>
                <?php foreach ($weekDays as $dayLabel): ?>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-700 <?php echo $dayLabel === $todayName ? 'bg-indigo-50' : ''; ?>">
                    <?php echo htmlspecialchars($dayLabel); ?>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-200">
              <?php foreach ($timeSlots as $timeSlot): ?>
                <tr>
                  <td class="px-4 py-3 text-sm font-medium text-stone-800 whitespace-nowrap"><?php echo htmlspecialchars($timeSlot); ?></td>
                  <?php foreach ($weekDays as $dayLabel): ?>
                    <td class="px-4 py-3 align-top min-w-[180px] <?php echo $dayLabel === $todayName ? 'bg-indigo-50/40' : ''; ?>">
                      <?php if (!empty($matrix[$timeSlot][$dayLabel])): ?>
                        <div class="space-y-2">
                          <?php foreach ($matrix[$timeSlot][$dayLabel] as $slotEntry): ?>
                            <div class="rounded-lg border border-indigo-100 bg-indigo-50/80 p-2">
                              <p class="text-sm font-semibold text-indigo-900"><?php echo htmlspecialchars((string) ($slotEntry['subject_name'] ?? 'Subject')); ?></p>
                              <p class="text-xs text-indigo-700">Teacher: <?php echo htmlspecialchars((string) ($slotEntry['teacher_name'] ?? 'Teacher')); ?></p>
                              <p class="text-xs text-indigo-700">Room: <?php echo htmlspecialchars((string) (($slotEntry['room'] ?? '') !== '' ? $slotEntry['room'] : '-')); ?></p>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <span class="text-xs text-stone-400">-</span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="px-6 py-10 text-center text-stone-500">
          No timetable entries published for your class yet.
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>

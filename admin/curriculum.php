<?php
// Curriculum and timetable management for administrators.
require_once __DIR__ . '/auth.php';
include '../dbconfig.php';
require_once __DIR__ . '/db_helpers.php';

if (!admin_table_exists($connection, 'schedule')) {
  $connection->query(
    "CREATE TABLE IF NOT EXISTS schedule (
      id INT AUTO_INCREMENT PRIMARY KEY,
      teacher_id INT NULL,
      class_id INT NULL,
      subject_id INT NULL,
      day_of_week VARCHAR(20) NULL,
      start_time TIME NULL,
      end_time TIME NULL,
      room VARCHAR(60) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  );
}

admin_ensure_column($connection, 'schedule', 'teacher_id', 'INT NULL');
admin_ensure_column($connection, 'schedule', 'class_id', 'INT NULL');
admin_ensure_column($connection, 'schedule', 'subject_id', 'INT NULL');
admin_ensure_column($connection, 'schedule', 'day_of_week', 'VARCHAR(20) NULL');
admin_ensure_column($connection, 'schedule', 'start_time', 'TIME NULL');
admin_ensure_column($connection, 'schedule', 'end_time', 'TIME NULL');
admin_ensure_column($connection, 'schedule', 'room', 'VARCHAR(60) NULL');

function curriculum_time_label($timeValue)
{
  $timestamp = strtotime((string) $timeValue);
  if ($timestamp === false) {
    return (string) $timeValue;
  }

  return date('H:i', $timestamp);
}

$dayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$errorMessage = '';

$formData = [
  'id' => 0,
  'class_id' => '',
  'subject_id' => '',
  'teacher_id' => '',
  'day_of_week' => 'Monday',
  'start_time' => '',
  'end_time' => '',
  'room' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? '');

  if ($action === 'save') {
    $formData['id'] = (int) ($_POST['schedule_id'] ?? 0);
    $formData['class_id'] = trim((string) ($_POST['class_id'] ?? ''));
    $formData['subject_id'] = trim((string) ($_POST['subject_id'] ?? ''));
    $formData['teacher_id'] = trim((string) ($_POST['teacher_id'] ?? ''));
    $formData['day_of_week'] = trim((string) ($_POST['day_of_week'] ?? ''));
    $formData['start_time'] = trim((string) ($_POST['start_time'] ?? ''));
    $formData['end_time'] = trim((string) ($_POST['end_time'] ?? ''));
    $formData['room'] = trim((string) ($_POST['room'] ?? ''));

    $classId = (int) $formData['class_id'];
    $subjectId = (int) $formData['subject_id'];
    $teacherId = (int) $formData['teacher_id'];
    $scheduleId = (int) $formData['id'];

    if ($classId <= 0) {
      $errorMessage = 'Please select a class.';
    } elseif ($subjectId <= 0) {
      $errorMessage = 'Please select a subject.';
    } elseif ($teacherId <= 0) {
      $errorMessage = 'Please select a teacher.';
    } elseif (!in_array($formData['day_of_week'], $dayOptions, true)) {
      $errorMessage = 'Please select a valid day of week.';
    } elseif ($formData['start_time'] === '' || $formData['end_time'] === '') {
      $errorMessage = 'Please provide both start and end times.';
    } elseif (strtotime($formData['end_time']) <= strtotime($formData['start_time'])) {
      $errorMessage = 'End time must be later than start time.';
    }

    if ($errorMessage === '') {
      $overlapSql = 'SELECT id FROM schedule WHERE class_id = ? AND LOWER(COALESCE(day_of_week, \"\")) = LOWER(?) AND id != ? AND start_time < ? AND end_time > ? LIMIT 1';
      $overlapStmt = $connection->prepare($overlapSql);
      if ($overlapStmt) {
        $overlapStmt->bind_param('isiss', $classId, $formData['day_of_week'], $scheduleId, $formData['end_time'], $formData['start_time']);
        $overlapStmt->execute();
        $overlapResult = $overlapStmt->get_result();
        $overlapRow = $overlapResult ? $overlapResult->fetch_assoc() : null;
        $overlapStmt->close();

        if ($overlapRow) {
          $errorMessage = 'This class already has a timetable entry during that time range.';
        }
      }
    }

    if ($errorMessage === '') {
      $roomValue = $formData['room'];
      if ($scheduleId > 0) {
        $updateSql = 'UPDATE schedule SET teacher_id = ?, class_id = ?, subject_id = ?, day_of_week = ?, start_time = ?, end_time = ?, room = ? WHERE id = ?';
        $updateStmt = $connection->prepare($updateSql);
        if (!$updateStmt) {
          $errorMessage = 'Unable to update timetable entry right now.';
        } else {
          $updateStmt->bind_param('iiissssi', $teacherId, $classId, $subjectId, $formData['day_of_week'], $formData['start_time'], $formData['end_time'], $roomValue, $scheduleId);
          if ($updateStmt->execute()) {
            $updateStmt->close();
            admin_set_flash('success', 'Curriculum entry updated successfully.');
            header('Location: curriculum.php');
            exit();
          }

          $errorMessage = 'Unable to update timetable entry right now.';
          $updateStmt->close();
        }
      } else {
        $insertSql = 'INSERT INTO schedule (teacher_id, class_id, subject_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $insertStmt = $connection->prepare($insertSql);
        if (!$insertStmt) {
          $errorMessage = 'Unable to create timetable entry right now.';
        } else {
          $insertStmt->bind_param('iiissss', $teacherId, $classId, $subjectId, $formData['day_of_week'], $formData['start_time'], $formData['end_time'], $roomValue);
          if ($insertStmt->execute()) {
            $insertStmt->close();
            admin_set_flash('success', 'Curriculum entry created successfully.');
            header('Location: curriculum.php');
            exit();
          }

          $errorMessage = 'Unable to create timetable entry right now.';
          $insertStmt->close();
        }
      }
    }
  }

  if ($action === 'delete') {
    $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
    if ($scheduleId > 0) {
      $deleteStmt = $connection->prepare('DELETE FROM schedule WHERE id = ?');
      if ($deleteStmt) {
        $deleteStmt->bind_param('i', $scheduleId);
        if ($deleteStmt->execute()) {
          admin_set_flash('success', 'Curriculum entry deleted successfully.');
        } else {
          admin_set_flash('danger', 'Unable to delete timetable entry right now.');
        }
        $deleteStmt->close();
      } else {
        admin_set_flash('danger', 'Unable to delete timetable entry right now.');
      }
    }

    header('Location: curriculum.php');
    exit();
  }
}

$classOptions = [];
$subjectOptions = [];
$teacherOptions = [];

$classNameColumn = admin_first_existing_column($connection, 'classes', ['name', 'class_name']);
$subjectNameColumn = admin_first_existing_column($connection, 'subjects', ['name', 'subject_name']);

if (admin_table_exists($connection, 'classes')) {
  if ($classNameColumn !== null) {
    $classSql = "SELECT id, {$classNameColumn} AS class_name, COALESCE(section, '') AS section FROM classes ORDER BY {$classNameColumn} ASC";
  } else {
    $classSql = "SELECT id, CONCAT('Class ', id) AS class_name, COALESCE(section, '') AS section FROM classes ORDER BY id ASC";
  }

  $classStmt = $connection->prepare($classSql);
  if ($classStmt && $classStmt->execute()) {
    $classResult = $classStmt->get_result();
    while ($classResult && ($classRow = $classResult->fetch_assoc())) {
      $classOptions[] = $classRow;
    }
    $classStmt->close();
  }
}

if (admin_table_exists($connection, 'subjects')) {
  if ($subjectNameColumn !== null) {
    $subjectSql = "SELECT id, {$subjectNameColumn} AS subject_name FROM subjects ORDER BY {$subjectNameColumn} ASC";
  } else {
    $subjectSql = "SELECT id, CONCAT('Subject ', id) AS subject_name FROM subjects ORDER BY id ASC";
  }

  $subjectStmt = $connection->prepare($subjectSql);
  if ($subjectStmt && $subjectStmt->execute()) {
    $subjectResult = $subjectStmt->get_result();
    while ($subjectResult && ($subjectRow = $subjectResult->fetch_assoc())) {
      $subjectOptions[] = $subjectRow;
    }
    $subjectStmt->close();
  }
}

if (admin_table_exists($connection, 'teachers')) {
  $teacherStmt = $connection->prepare('SELECT id, name FROM teachers ORDER BY name ASC');
  if ($teacherStmt && $teacherStmt->execute()) {
    $teacherResult = $teacherStmt->get_result();
    while ($teacherResult && ($teacherRow = $teacherResult->fetch_assoc())) {
      $teacherOptions[] = $teacherRow;
    }
    $teacherStmt->close();
  }
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $editStmt = $connection->prepare('SELECT id, class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room FROM schedule WHERE id = ? LIMIT 1');
  if ($editStmt) {
    $editStmt->bind_param('i', $editId);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    $editRow = $editResult ? $editResult->fetch_assoc() : null;
    $editStmt->close();

    if ($editRow) {
      $formData['id'] = (int) ($editRow['id'] ?? 0);
      $formData['class_id'] = (string) ((int) ($editRow['class_id'] ?? 0));
      $formData['subject_id'] = (string) ((int) ($editRow['subject_id'] ?? 0));
      $formData['teacher_id'] = (string) ((int) ($editRow['teacher_id'] ?? 0));
      $formData['day_of_week'] = (string) ($editRow['day_of_week'] ?? 'Monday');
      $formData['start_time'] = (string) ($editRow['start_time'] ?? '');
      $formData['end_time'] = (string) ($editRow['end_time'] ?? '');
      $formData['room'] = (string) ($editRow['room'] ?? '');
    }
  }
}

$scheduleEntries = [];

$hasClassTable = admin_table_exists($connection, 'classes');
$hasSubjectTable = admin_table_exists($connection, 'subjects');
$hasTeacherTable = admin_table_exists($connection, 'teachers');

$classExpr = "CONCAT('Class ', COALESCE(s.class_id, 0))";
$subjectExpr = "CONCAT('Subject ', COALESCE(s.subject_id, 0))";
$teacherExpr = "CONCAT('Teacher ', COALESCE(s.teacher_id, 0))";
$joins = [];

if ($hasClassTable) {
  $joins[] = 'LEFT JOIN classes c ON s.class_id = c.id';
  $hasClassName = admin_column_exists($connection, 'classes', 'name');
  $hasClassLegacyName = admin_column_exists($connection, 'classes', 'class_name');
  if ($hasClassName && $hasClassLegacyName) {
    $classExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', COALESCE(s.class_id, 0)))";
  } elseif ($hasClassName) {
    $classExpr = "COALESCE(NULLIF(c.name, ''), CONCAT('Class ', COALESCE(s.class_id, 0)))";
  } elseif ($hasClassLegacyName) {
    $classExpr = "COALESCE(NULLIF(c.class_name, ''), CONCAT('Class ', COALESCE(s.class_id, 0)))";
  }
}

if ($hasSubjectTable) {
  $joins[] = 'LEFT JOIN subjects sub ON s.subject_id = sub.id';
  $hasSubjectName = admin_column_exists($connection, 'subjects', 'name');
  $hasSubjectLegacyName = admin_column_exists($connection, 'subjects', 'subject_name');
  if ($hasSubjectName && $hasSubjectLegacyName) {
    $subjectExpr = "COALESCE(NULLIF(sub.name, ''), sub.subject_name, CONCAT('Subject ', COALESCE(s.subject_id, 0)))";
  } elseif ($hasSubjectName) {
    $subjectExpr = "COALESCE(NULLIF(sub.name, ''), CONCAT('Subject ', COALESCE(s.subject_id, 0)))";
  } elseif ($hasSubjectLegacyName) {
    $subjectExpr = "COALESCE(NULLIF(sub.subject_name, ''), CONCAT('Subject ', COALESCE(s.subject_id, 0)))";
  }
}

if ($hasTeacherTable) {
  $joins[] = 'LEFT JOIN teachers t ON s.teacher_id = t.id';
  if (admin_column_exists($connection, 'teachers', 'name')) {
    $teacherExpr = "COALESCE(NULLIF(t.name, ''), CONCAT('Teacher ', COALESCE(s.teacher_id, 0)))";
  }
}

$listSql = 'SELECT s.id, s.teacher_id, s.class_id, s.subject_id, s.day_of_week, s.start_time, s.end_time, s.room, '
  . $classExpr . ' AS class_name, '
  . $subjectExpr . ' AS subject_name, '
  . $teacherExpr . ' AS teacher_name '
  . 'FROM schedule s '
  . (!empty($joins) ? implode(' ', $joins) . ' ' : '')
  . "ORDER BY FIELD(LOWER(COALESCE(s.day_of_week, '')), 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), s.start_time ASC";

$listStmt = $connection->prepare($listSql);
if ($listStmt && $listStmt->execute()) {
  $listResult = $listStmt->get_result();
  while ($listResult && ($row = $listResult->fetch_assoc())) {
    $row['start_label'] = curriculum_time_label($row['start_time'] ?? '');
    $row['end_label'] = curriculum_time_label($row['end_time'] ?? '');
    $row['day_label'] = ucfirst(strtolower(trim((string) ($row['day_of_week'] ?? ''))));
    $scheduleEntries[] = $row;
  }
  $listStmt->close();
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

$flash = admin_pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Curriculum Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
    }
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      padding: 24px;
    }
    .page-title {
      font-size: 1.7rem;
      font-weight: 700;
      color: #0f172a;
    }
    .page-subtitle {
      font-size: 0.95rem;
      color: #475569;
    }
    .card-shell {
      border: 1px solid #e2e8f0;
      border-radius: 0.9rem;
      background: #ffffff;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    .form-label {
      font-weight: 600;
      color: #1e293b;
    }
    .badge-soft {
      background: #e2e8f0;
      color: #334155;
      border-radius: 999px;
      font-size: 0.75rem;
      padding: 0.2rem 0.6rem;
    }
    .timetable-cell {
      min-width: 180px;
      vertical-align: top;
    }
    .timetable-item {
      border: 1px solid #dbeafe;
      background: #eff6ff;
      border-radius: 0.7rem;
      padding: 0.5rem;
      margin-bottom: 0.45rem;
    }
    .timetable-item:last-child {
      margin-bottom: 0;
    }
    @media (max-width: 991px) {
      .main-content {
        margin-left: 0;
        padding: 84px 16px 24px;
      }
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
      <div>
        <h1 class="page-title mb-1">Curriculum And Weekly Timetable</h1>
        <p class="page-subtitle mb-0">Create, edit, and publish class-wise timetable entries for students.</p>
      </div>
      <span class="badge-soft align-self-center"><?php echo (int) count($scheduleEntries); ?> entries</span>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'info')); ?>" role="alert">
        <?php echo htmlspecialchars((string) ($flash['message'] ?? '')); ?>
      </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-4">
        <div class="card-shell p-4 h-100">
          <h2 class="h5 mb-3"><?php echo ((int) $formData['id'] > 0) ? 'Edit Entry' : 'Add Entry'; ?></h2>
          <form method="POST" novalidate>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="schedule_id" value="<?php echo (int) ($formData['id'] ?? 0); ?>">

            <div class="mb-3">
              <label class="form-label">Class</label>
              <select class="form-select" name="class_id" required>
                <option value="">Select class</option>
                <?php foreach ($classOptions as $classOption): ?>
                  <?php
                    $classIdValue = (int) ($classOption['id'] ?? 0);
                    $classNameValue = (string) ($classOption['class_name'] ?? 'Class');
                    $classSectionValue = trim((string) ($classOption['section'] ?? ''));
                    $classLabel = $classNameValue . ($classSectionValue !== '' ? ' - ' . $classSectionValue : '');
                  ?>
                  <option value="<?php echo $classIdValue; ?>" <?php echo ((string) $classIdValue === (string) ($formData['class_id'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($classLabel); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Subject</label>
              <select class="form-select" name="subject_id" required>
                <option value="">Select subject</option>
                <?php foreach ($subjectOptions as $subjectOption): ?>
                  <?php $subjectIdValue = (int) ($subjectOption['id'] ?? 0); ?>
                  <option value="<?php echo $subjectIdValue; ?>" <?php echo ((string) $subjectIdValue === (string) ($formData['subject_id'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) ($subjectOption['subject_name'] ?? 'Subject')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Teacher</label>
              <select class="form-select" name="teacher_id" required>
                <option value="">Select teacher</option>
                <?php foreach ($teacherOptions as $teacherOption): ?>
                  <?php $teacherIdValue = (int) ($teacherOption['id'] ?? 0); ?>
                  <option value="<?php echo $teacherIdValue; ?>" <?php echo ((string) $teacherIdValue === (string) ($formData['teacher_id'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) ($teacherOption['name'] ?? 'Teacher')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Day</label>
              <select class="form-select" name="day_of_week" required>
                <?php foreach ($dayOptions as $dayOption): ?>
                  <option value="<?php echo htmlspecialchars($dayOption); ?>" <?php echo ($dayOption === (string) ($formData['day_of_week'] ?? 'Monday')) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dayOption); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label">Start Time</label>
                <input type="time" class="form-control" name="start_time" value="<?php echo htmlspecialchars((string) ($formData['start_time'] ?? '')); ?>" required>
              </div>
              <div class="col-6">
                <label class="form-label">End Time</label>
                <input type="time" class="form-control" name="end_time" value="<?php echo htmlspecialchars((string) ($formData['end_time'] ?? '')); ?>" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Room</label>
              <input type="text" class="form-control" name="room" value="<?php echo htmlspecialchars((string) ($formData['room'] ?? '')); ?>" maxlength="60" placeholder="Optional room number">
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary"><?php echo ((int) $formData['id'] > 0) ? 'Update Entry' : 'Add Entry'; ?></button>
              <?php if ((int) ($formData['id'] ?? 0) > 0): ?>
                <a href="curriculum.php" class="btn btn-outline-secondary">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <div class="col-12 col-xl-8">
        <div class="card-shell p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Weekly Timetable Grid</h2>
            <span class="text-muted small">Class-wise curriculum visibility</span>
          </div>

          <?php if (!empty($timeSlots)): ?>
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="min-width: 110px;">Time</th>
                    <?php foreach ($weekDays as $dayLabel): ?>
                      <th><?php echo htmlspecialchars($dayLabel); ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($timeSlots as $timeSlot): ?>
                    <tr>
                      <td class="fw-semibold"><?php echo htmlspecialchars($timeSlot); ?></td>
                      <?php foreach ($weekDays as $dayLabel): ?>
                        <td class="timetable-cell">
                          <?php if (!empty($matrix[$timeSlot][$dayLabel])): ?>
                            <?php foreach ($matrix[$timeSlot][$dayLabel] as $slotEntry): ?>
                              <div class="timetable-item">
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($slotEntry['subject_name'] ?? 'Subject')); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars((string) ($slotEntry['class_name'] ?? 'Class')); ?></div>
                                <div class="small text-muted">Teacher: <?php echo htmlspecialchars((string) ($slotEntry['teacher_name'] ?? 'Teacher')); ?></div>
                                <div class="small text-muted">Room: <?php echo htmlspecialchars((string) (($slotEntry['room'] ?? '') !== '' ? $slotEntry['room'] : '-')); ?></div>
                              </div>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <span class="text-muted small">-</span>
                          <?php endif; ?>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center text-muted py-5">No timetable entries yet. Add one from the form.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card-shell p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">All Curriculum Entries</h2>
        <span class="text-muted small">Use Edit to modify, Delete to remove</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Day</th>
              <th>Time</th>
              <th>Class</th>
              <th>Subject</th>
              <th>Teacher</th>
              <th>Room</th>
              <th style="width: 170px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($scheduleEntries)): ?>
              <?php foreach ($scheduleEntries as $entry): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) ($entry['day_label'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars((string) (($entry['start_label'] ?? '--:--') . ' - ' . ($entry['end_label'] ?? '--:--'))); ?></td>
                  <td><?php echo htmlspecialchars((string) ($entry['class_name'] ?? 'Class')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($entry['subject_name'] ?? 'Subject')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($entry['teacher_name'] ?? 'Teacher')); ?></td>
                  <td><?php echo htmlspecialchars((string) (($entry['room'] ?? '') !== '' ? $entry['room'] : '-')); ?></td>
                  <td>
                    <a href="curriculum.php?edit=<?php echo (int) ($entry['id'] ?? 0); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="POST" style="display:inline-block;" novalidate>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="schedule_id" value="<?php echo (int) ($entry['id'] ?? 0); ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this timetable entry?');">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No curriculum entries found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>

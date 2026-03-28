<?php
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    // For testing, set session manually
    $_SESSION['user_id'] = 2; // Assuming teacher ID is 2
    $_SESSION['role'] = 'teacher';
    $_SESSION['name'] = 'Prof. Priya Patel';
    // header("Location: ../login.php");
    // exit();
}

// Include database connection
include '../includes/db_connect.php';

// Get teacher info
$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'];

// Get schedule for the teacher
$sql_schedule = "SELECT s.day_of_week, s.start_time, s.end_time, s.room, c.name as class_name, sub.name as subject_name
                 FROM schedule s
                 JOIN classes c ON s.class_id = c.id
                 JOIN subjects sub ON s.subject_id = sub.id
                 WHERE s.teacher_id = $teacher_id
                 ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.start_time";
$result_schedule = mysqli_query($conn, $sql_schedule);

// Organize schedule by day and time
$schedule_data = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

while ($row = mysqli_fetch_assoc($result_schedule)) {
    $day = $row['day_of_week'];
    $time_slot = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));

    if (!isset($schedule_data[$day])) {
        $schedule_data[$day] = [];
    }

    $schedule_data[$day][$time_slot] = [
        'subject' => $row['subject_name'],
        'class' => $row['class_name'],
        'room' => $row['room']
    ];
}

// Get today's schedule
$current_day = date('l'); // Get current day name
$today_schedule = isset($schedule_data[$current_day]) ? $schedule_data[$current_day] : [];

// Get next class
$next_class = null;
$current_time = date('H:i');
foreach ($today_schedule as $time_slot => $class_info) {
    $start_time = explode(' - ', $time_slot)[0];
    if ($start_time > $current_time) {
        $next_class = array_merge($class_info, ['time' => $time_slot]);
        break;
    }
}

// Calculate weekly statistics
$total_classes = 0;
$total_hours = 0;
$classes_per_day = [];

foreach ($schedule_data as $day => $slots) {
    $day_count = count($slots);
    $classes_per_day[$day] = $day_count;
    $total_classes += $day_count;

    foreach ($slots as $time_slot => $class_info) {
        $times = explode(' - ', $time_slot);
        $start = strtotime($times[0]);
        $end = strtotime($times[1]);
        $total_hours += ($end - $start) / 3600;
    }
}

// Get current class (if any)
$current_class = null;
foreach ($today_schedule as $time_slot => $class_info) {
    $start_time = explode(' - ', $time_slot)[0];
    $end_time = explode(' - ', $time_slot)[1];
    if ($current_time >= $start_time && $current_time <= $end_time) {
        $current_class = array_merge($class_info, ['time' => $time_slot]);
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Class Schedule - The Academic Editorial</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
          <h1 class="text-3xl font-bold tracking-tight text-on-surface">Class Schedule</h1>
          <p class="text-on-surface-variant font-medium">Manage your weekly teaching schedule and class timings</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="glass-panel rounded-xl p-4 pro-shadow">
            <div class="text-sm text-on-surface-variant">Today</div>
            <div class="text-lg font-semibold text-on-surface"><?php echo date('l, F j, Y'); ?></div>
          </div>
          <button onclick="document.getElementById('weeklySchedule').scrollIntoView()" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 pro-shadow">
            <span class="material-symbols-outlined">calendar_view_week</span>
            View Weekly
          </button>
        </div>
      </div>
    </section>

    <!-- Analytics Cards -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">school</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $total_classes; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Total Classes</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-primary h-2 rounded-full" style="width: 100%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600">access_time</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo round($total_hours, 1); ?>h</p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Weekly Hours</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $total_hours > 0 ? min(($total_hours / 40) * 100, 100) : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600">today</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo count($today_schedule); ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Today's Classes</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo count($today_schedule) > 0 ? (count($today_schedule) / 8) * 100 : 0; ?>%"></div>
        </div>
      </div>

      <div class="glass-panel rounded-xl p-6 pro-shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-purple-600">schedule</span>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-on-surface"><?php echo $next_class ? 'Next' : 'Done'; ?></p>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Next Class</p>
          </div>
        </div>
        <div class="w-full bg-surface-variant rounded-full h-2">
          <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $next_class ? 75 : 100; ?>%"></div>
        </div>
      </div>
    </section>

    <!-- Current/Next Class Highlight -->
    <?php if ($current_class || $next_class): ?>
    <section class="glass-panel rounded-xl p-6 pro-shadow">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 <?php echo $current_class ? 'bg-green-100' : 'bg-blue-100'; ?> rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-<?php echo $current_class ? 'green' : 'blue'; ?>-600 text-2xl"><?php echo $current_class ? 'play_circle' : 'schedule'; ?></span>
          </div>
          <div>
            <h3 class="text-xl font-bold text-on-surface">
              <?php echo $current_class ? 'Current Class' : 'Next Class'; ?>
            </h3>
            <p class="text-on-surface-variant">
              <?php echo $current_class ? $current_class['subject'] : $next_class['subject']; ?> •
              <?php echo $current_class ? $current_class['class'] : $next_class['class']; ?> •
              Room <?php echo $current_class ? $current_class['room'] : $next_class['room']; ?>
            </p>
          </div>
        </div>
        <div class="text-right">
          <div class="text-2xl font-bold text-on-surface">
            <?php echo $current_class ? $current_class['time'] : $next_class['time']; ?>
          </div>
          <div class="text-sm text-on-surface-variant">
            <?php echo $current_class ? 'In Progress' : 'Upcoming'; ?>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Weekly Schedule Table -->
    <section id="weeklySchedule" class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex justify-between items-center bg-stone-50/30">
        <div>
          <h2 class="text-xl font-bold text-on-surface">Weekly Schedule</h2>
          <p class="text-on-surface-variant">Complete overview of your teaching schedule</p>
        </div>
        <div class="flex items-center gap-3">
          <select id="weekFilter" class="px-4 py-2 bg-white border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            <option value="current">This Week</option>
            <option value="next">Next Week</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-stone-50/50">
            <tr>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Time</th>
              <?php foreach ($days as $day): ?>
              <th class="px-6 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-widest <?php echo $day === $current_day ? 'bg-primary/10' : ''; ?>">
                <?php echo $day; ?>
                <?php if ($day === $current_day): ?>
                  <span class="ml-1 text-primary text-xs">(Today)</span>
                <?php endif; ?>
              </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            <?php
            // Get all unique time slots
            $time_slots = [];
            foreach ($schedule_data as $day => $slots) {
                foreach ($slots as $time => $data) {
                    if (!in_array($time, $time_slots)) {
                        $time_slots[] = $time;
                    }
                }
            }
            sort($time_slots);

            if (empty($time_slots)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center">
                <div class="w-12 h-12 bg-surface-variant rounded-full flex items-center justify-center mx-auto mb-4">
                  <span class="material-symbols-outlined text-on-surface-variant text-xl">schedule</span>
                </div>
                <h3 class="text-sm font-semibold text-on-surface mb-2">No schedule found</h3>
                <p class="text-on-surface-variant">Your class schedule will appear here once it's set up.</p>
              </td>
            </tr>
            <?php else:
            foreach ($time_slots as $time_slot): ?>
            <tr>
              <td class="px-6 py-4 text-sm font-semibold text-on-surface"><?php echo $time_slot; ?></td>
              <?php foreach ($days as $day): ?>
              <td class="px-6 py-4">
                <?php if (isset($schedule_data[$day][$time_slot])): ?>
                <div class="glass-panel rounded-xl p-4 pro-shadow">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                      <span class="material-symbols-outlined text-primary text-sm">school</span>
                    </div>
                    <div class="flex-1">
                      <div class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($schedule_data[$day][$time_slot]['subject']); ?></div>
                      <div class="text-xs text-on-surface-variant">
                        <?php echo htmlspecialchars($schedule_data[$day][$time_slot]['class']); ?> • Room <?php echo htmlspecialchars($schedule_data[$day][$time_slot]['room']); ?>
                      </div>
                    </div>
                    <?php if ($day === $current_day): ?>
                      <?php
                      $start_time = explode(' - ', $time_slot)[0];
                      $end_time = explode(' - ', $time_slot)[1];
                      $is_current = ($current_time >= $start_time && $current_time <= $end_time);
                      $is_upcoming = ($start_time > $current_time);
                      $is_completed = ($end_time < $current_time);
                      ?>
                      <div class="flex flex-col items-end gap-1">
                        <?php if ($is_current): ?>
                          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="material-symbols-outlined text-xs">play_circle</span>
                            Live
                          </span>
                        <?php elseif ($is_upcoming): ?>
                          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <span class="material-symbols-outlined text-xs">schedule</span>
                            Upcoming
                          </span>
                        <?php elseif ($is_completed): ?>
                          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <span class="material-symbols-outlined text-xs">check_circle</span>
                            Done
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Today's Schedule (if any) -->
    <?php if (!empty($today_schedule)): ?>
    <section class="glass-panel rounded-xl pro-shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center gap-3 bg-stone-50/30">
        <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white">
          <span class="material-symbols-outlined">today</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-on-surface">Today's Classes</h2>
          <p class="text-on-surface-variant"><?php echo date('l, F j, Y'); ?> • <?php echo count($today_schedule); ?> classes scheduled</p>
        </div>
      </div>

      <div class="divide-y divide-outline-variant/10">
        <?php foreach ($today_schedule as $time_slot => $class_info): ?>
        <div class="px-6 py-5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">school</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-on-surface"><?php echo htmlspecialchars($class_info['subject']); ?></h3>
                <p class="text-on-surface-variant">
                  <?php echo htmlspecialchars($class_info['class']); ?> • Room <?php echo htmlspecialchars($class_info['room']); ?>
                </p>
              </div>
            </div>
            <div class="text-right">
              <div class="text-xl font-bold text-on-surface mb-1"><?php echo $time_slot; ?></div>
              <?php
              $start_time = explode(' - ', $time_slot)[0];
              $end_time = explode(' - ', $time_slot)[1];
              $is_current = ($current_time >= $start_time && $current_time <= $end_time);
              $is_upcoming = ($start_time > $current_time);
              $is_completed = ($end_time < $current_time);
              ?>
              <?php if ($is_current): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <span class="material-symbols-outlined text-xs">play_circle</span>
                  In Progress
                </span>
              <?php elseif ($is_upcoming): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  <span class="material-symbols-outlined text-xs">schedule</span>
                  Upcoming
                </span>
              <?php elseif ($is_completed): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  <span class="material-symbols-outlined text-xs">check_circle</span>
                  Completed
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </main>
</body>
</html>
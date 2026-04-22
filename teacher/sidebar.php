<?php
require_once __DIR__ . '/auth.php';

$teacherDisplayName = (string) ($_SESSION['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher');
$teacherNameParts = preg_split('/\s+/', trim($teacherDisplayName));
$teacherLastName = !empty($teacherNameParts)
  ? (string) $teacherNameParts[count($teacherNameParts) - 1]
  : 'Teacher';

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SideNavBar Shell -->
<button
  type="button"
  id="teacher-sidebar-toggle"
  aria-label="Toggle menu"
  class="lg:hidden fixed top-4 left-4 z-40 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-700 shadow-sm"
>
  <span class="material-symbols-outlined">menu</span>
</button>

<div id="teacher-sidebar-overlay" class="fixed inset-0 z-40 hidden bg-stone-900/40 lg:hidden"></div>

<aside id="teacher-sidebar" class="h-screen w-64 fixed left-0 top-0 bg-white border-r border-outline-variant/20 flex flex-col py-6 px-4 z-50 transform -translate-x-full transition-transform duration-200 ease-out lg:translate-x-0">
<div class="mb-10 px-2 flex items-center gap-3">
<div class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold">T</div>
<div>
<h1 class="text-sm font-bold tracking-tight text-stone-900 leading-tight">Teacher Portal</h1>
<p class="text-[10px] text-on-surface-variant font-medium tracking-wide uppercase opacity-70">Academic Management</p>
</div>
</div>
<nav class="flex-1 space-y-0.5">
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'dashboard.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="dashboard.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">grid_view</span>
<span class="text-[14px]">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'assignments.php' || $current_page == 'view-assignment.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="assignments.php">
<span class="material-symbols-outlined">assignment</span>
<span class="text-[14px]">Assignments</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'attendance.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="attendance.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="text-[14px]">Attendance</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'grades.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="grades.php">
<span class="material-symbols-outlined">analytics</span>
<span class="text-[14px]">Grades</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'schedule.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="schedule.php">
<span class="material-symbols-outlined">calendar_month</span>
<span class="text-[14px]">Curriculum</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'notices.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="notices.php">
<span class="material-symbols-outlined">campaign</span>
<span class="text-[14px]">Notice Board</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'students.php' || $current_page == 'view-student.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="students.php">
<span class="material-symbols-outlined">group</span>
<span class="text-[14px]">Students</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'leave-requests.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="leave-requests.php">
<span class="material-symbols-outlined">approval_delegation</span>
<span class="text-[14px]">Leave Requests</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'profile.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="profile.php">
<span class="material-symbols-outlined">person_outline</span>
<span class="text-[14px]">Profile</span>
</a>
</nav>
<div class="space-y-1 pt-4 border-t border-stone-100">
<a class="flex items-center gap-3 px-3 py-2 <?php echo $current_page == 'settings.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-400 hover:text-stone-900'; ?>" href="settings.php">
<span class="material-symbols-outlined text-sm">settings</span>
<span class="text-[13px]">System Settings</span>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-stone-400 hover:text-stone-900" href="logout.php">
<span class="material-symbols-outlined text-sm">logout</span>
<span class="text-[13px]">Logout</span>
</a>
</div>
</aside>
<style>
  .material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  }
  .pro-shadow {
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
  }
</style>
<script>
  (function () {
    const sidebar = document.getElementById('teacher-sidebar');
    const overlay = document.getElementById('teacher-sidebar-overlay');
    const toggle = document.getElementById('teacher-sidebar-toggle');
    if (!sidebar || !overlay || !toggle) return;
    const open = function () { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); };
    const close = function () { sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); };
    toggle.addEventListener('click', function () { sidebar.classList.contains('-translate-x-full') ? open() : close(); });
    overlay.addEventListener('click', close);
    window.addEventListener('resize', function () { if (window.innerWidth >= 1024) close(); });
  })();
</script>
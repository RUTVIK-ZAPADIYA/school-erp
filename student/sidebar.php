<?php
// Include auth guard
require_once __DIR__ . '/auth.php';

// Resolve sidebar user
$studentContext = student_auth_context();
$studentDisplayName = (string) ($studentContext['student_name'] ?? $_SESSION['student_name'] ?? 'Student');
$studentRollNo = (string) ($studentContext['student_roll_no'] ?? '-');

// Get current page filename
// Detect active page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SideNavBar Shell -->
<button
  type="button"
  data-sidebar-toggle
  aria-label="Toggle menu"
  class="lg:hidden fixed top-4 left-4 z-40 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-700 shadow-sm"
>
  <span class="material-symbols-outlined">menu</span>
</button>

<div id="student-sidebar-overlay" class="fixed inset-0 z-40 hidden bg-stone-900/40 lg:hidden"></div>

<aside id="student-sidebar" class="h-screen w-64 fixed left-0 top-0 bg-white border-r border-stone-200 flex flex-col py-6 px-4 z-50 transform -translate-x-full transition-transform duration-200 ease-out lg:translate-x-0">
<div class="mb-10 px-2 flex items-center gap-3">
<div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center text-white font-bold">S</div>
<div>
<h1 class="text-sm font-bold tracking-tight text-stone-900 leading-tight">Student Portal</h1>
<p class="text-[10px] text-stone-500 font-medium tracking-wide uppercase opacity-70">Academic Performance</p>
</div>
</div>
<nav class="flex-1 space-y-0.5">
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'dashboard.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="dashboard.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">grid_view</span>
<span class="text-[14px]">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'attendance.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="attendance.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="text-[14px]">Attendance</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'marks.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="marks.php">
<span class="material-symbols-outlined">analytics</span>
<span class="text-[14px]">Marks</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'assignments.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="assignments.php">
<span class="material-symbols-outlined">assignment</span>
<span class="text-[14px]">Assignments</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'curriculum.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="curriculum.php">
<span class="material-symbols-outlined">calendar_view_week</span>
<span class="text-[14px]">Curriculum</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'notices.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="notices.php">
<span class="material-symbols-outlined">campaign</span>
<span class="text-[14px]">Notice Board</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'fees.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="fees.php">
<span class="material-symbols-outlined">account_balance_wallet</span>
<span class="text-[14px]">Fees</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'leave.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="leave.php">
<span class="material-symbols-outlined">assignment_ind</span>
<span class="text-[14px]">Leave Application</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'profile.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="profile.php">
<span class="material-symbols-outlined">person_outline</span>
<span class="text-[14px]">Profile</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'contact-admin.php' ? 'text-blue-700 font-semibold bg-blue-50 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="contact-admin.php">
<span class="material-symbols-outlined">support_agent</span>
<span class="text-[14px]">Contact Admin</span>
</a>
</nav>
<div class="space-y-1 pt-4 border-t border-stone-100">
<a class="flex items-center gap-3 px-3 py-2 text-stone-400 hover:text-stone-900" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
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
    const sidebar = document.getElementById('student-sidebar');
    const overlay = document.getElementById('student-sidebar-overlay');
    const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');

    if (!sidebar || !overlay) {
      return;
    }

    const openSidebar = function () {
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
    };

    const closeSidebar = function () {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    };

    toggleButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        if (sidebar.classList.contains('-translate-x-full')) {
          openSidebar();
        } else {
          closeSidebar();
        }
      });
    });

    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024) {
        closeSidebar();
      }
    });
  })();
</script>

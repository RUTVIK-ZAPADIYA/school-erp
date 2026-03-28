<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SideNavBar Shell -->
<aside class="h-screen w-64 fixed left-0 top-0 bg-white border-r border-outline-variant/20 flex flex-col py-6 px-4 z-50">
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
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'students.php' || $current_page == 'view-student.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="students.php">
<span class="material-symbols-outlined">group</span>
<span class="text-[14px]">Students</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'profile.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="profile.php">
<span class="material-symbols-outlined">person_outline</span>
<span class="text-[14px]">Profile</span>
</a>
</nav>
<!-- Teacher Recognition Widget (Pro Feature 5) -->
<div class="mt-auto mb-6 p-4 bg-gradient-to-br from-stone-50 to-white border border-outline-variant/30 rounded-xl pro-shadow">
<div class="flex items-center gap-2 mb-2">
<span class="material-symbols-outlined text-amber-500 text-sm" style="font-variation-settings: 'FILL' 1;">stars</span>
<span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Faculty Recognition</span>
</div>
<div class="flex items-center gap-3">
<img class="w-8 h-8 rounded-full border border-white pro-shadow" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBxCF91fzs4syUOEI8L1TUsGleLRbNUMIwGSFUecqRsERsOYGwgFl7y350ienCTSOf5Rr3PG1_OL4qIoE4FYgA7iyPm4VW9YaZDsKsHsITgkx7vQ0A5L_vHI7OQMp2_PxdBu3s5uWA7HEryDOub9rCkTqxzjxeieF73fRbuPNHsmJkhZS8szK-OGH1QL6K_6A6ZATs2Tnn10r4YVI8SObS8qpWXPCP8-z5Yxfuf1O5AUzyIQlamKn0oz9DQfIoStOSqgr9KiTjZssI"/>
<div>
<p class="text-[11px] font-bold text-on-surface">Prof. <?php echo explode(' ', $_SESSION['name'])[1] ?? 'Patel'; ?></p>
<p class="text-[10px] text-on-surface-variant">Top Innovator '23</p>
</div>
</div>
</div>
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
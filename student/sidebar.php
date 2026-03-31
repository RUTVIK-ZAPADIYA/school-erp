<?php
require_once __DIR__ . '/auth.php';

$studentContext = student_auth_context();
$studentDisplayName = (string) ($studentContext['student_name'] ?? $_SESSION['student_name'] ?? 'Student');
$studentRollNo = (string) ($studentContext['student_roll_no'] ?? '-');

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SideNavBar Shell -->
<aside class="h-screen w-64 fixed left-0 top-0 bg-white border-r border-outline-variant/20 flex flex-col py-6 px-4 z-50">
<div class="mb-10 px-2 flex items-center gap-3">
<div class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold">S</div>
<div>
<h1 class="text-sm font-bold tracking-tight text-stone-900 leading-tight">Student Portal</h1>
<p class="text-[10px] text-on-surface-variant font-medium tracking-wide uppercase opacity-70">Academic Performance</p>
</div>
</div>
<nav class="flex-1 space-y-0.5">
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'dashboard.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="dashboard.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">grid_view</span>
<span class="text-[14px]">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'attendance.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="attendance.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="text-[14px]">Attendance</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'marks.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="marks.php">
<span class="material-symbols-outlined">analytics</span>
<span class="text-[14px]">Marks</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'assignments.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="assignments.php">
<span class="material-symbols-outlined">assignment</span>
<span class="text-[14px]">Assignments</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'fees.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="fees.php">
<span class="material-symbols-outlined">account_balance_wallet</span>
<span class="text-[14px]">Fees</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'leave.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="leave.php">
<span class="material-symbols-outlined">assignment_ind</span>
<span class="text-[14px]">Leave Application</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'profile.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="profile.php">
<span class="material-symbols-outlined">person_outline</span>
<span class="text-[14px]">Profile</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 <?php echo $current_page == 'contact-admin.php' ? 'text-primary font-semibold bg-primary/5 rounded-lg' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-50 rounded-lg'; ?>" href="contact-admin.php">
<span class="material-symbols-outlined">support_agent</span>
<span class="text-[14px]">Contact Admin</span>
</a>
</nav>
<!-- Student Info Widget -->
<div class="mt-auto mb-6 p-4 bg-gradient-to-br from-stone-50 to-white border border-outline-variant/30 rounded-xl pro-shadow">
<div class="flex items-center gap-2 mb-2">
<span class="material-symbols-outlined text-blue-500 text-sm" style="font-variation-settings: 'FILL' 1;">school</span>
<span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Student Info</span>
</div>
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full border-2 border-blue-500 flex items-center justify-center text-white bg-blue-500 font-bold text-xs">
<?php echo strtoupper(substr($studentDisplayName, 0, 1)); ?>
</div>
<div>
<p class="text-[11px] font-bold text-on-surface"><?php echo htmlspecialchars($studentDisplayName); ?></p>
<p class="text-[10px] text-on-surface-variant">Roll No: <?php echo htmlspecialchars($studentRollNo !== '' ? $studentRollNo : '-'); ?></p>
</div>
</div>
</div>
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

<?php
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<div class="sidebar" id="sidebar">
  <div class="logo-section">
    <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
    <h4>Student Portal</h4>
  </div>
  <div class="user-info">
    <i class="fas fa-user-circle"></i>
    <div>
      <p class="user-name"><?php echo $_SESSION['student_name'] ?? 'Student'; ?></p>
      <p class="user-role">Student</p>
    </div>
  </div>
  <nav class="nav-menu">
    <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
    <a href="attendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> View Attendance</a>
    <a href="marks.php" class="nav-item"><i class="fas fa-chart-bar"></i> View Marks</a>
    <a href="fees.php" class="nav-item"><i class="fas fa-rupee-sign"></i> Fees</a>
    <a href="leave.php" class="nav-item"><i class="fas fa-file-alt"></i> Leave Application</a>
    <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> Profile</a>
    <a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>
<div class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<style>
  .sidebar { width: 280px; background: #2c3e50; height: 100vh; position: fixed; left: 0; top: 0; color: white; overflow-y: auto; z-index: 1000; transition: transform 0.3s; }
  .logo-section { text-align: center; padding: 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
  .logo-icon { width: 60px; height: 60px; background: #3498db; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
  .logo-icon i { font-size: 1.8rem; }
  .logo-section h4 { margin: 0; font-size: 1.3rem; font-weight: 600; }
  .user-info { display: flex; align-items: center; padding: 20px; background: rgba(0,0,0,0.2); margin: 20px; border-radius: 10px; }
  .user-info i { font-size: 2.5rem; color: #3498db; margin-right: 15px; }
  .user-name { margin: 0; font-weight: 600; font-size: 1rem; }
  .user-role { margin: 0; font-size: 0.85rem; color: #bdc3c7; }
  .nav-menu { padding: 10px 0; }
  .nav-item { display: flex; align-items: center; padding: 15px 25px; color: #ecf0f1; text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
  .nav-item i { margin-right: 12px; width: 20px; }
  .nav-item:hover, .nav-item.active { background: rgba(52,152,219,0.1); border-left-color: #3498db; color: #3498db; }
  .nav-item.logout { color: #e74c3c; margin-top: 20px; }
  .nav-item.logout:hover { background: rgba(231,76,60,0.1); border-left-color: #e74c3c; }
  .mobile-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #3498db; color: white; width: 45px; height: 45px; border-radius: 8px; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
  .mobile-toggle i { font-size: 1.2rem; }
  .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.active { transform: translateX(0); }
    .mobile-toggle { display: flex; }
    .sidebar-overlay.active { display: block; }
  }
</style>
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('active');
  document.getElementById('sidebarOverlay').classList.toggle('active');
}
</script>

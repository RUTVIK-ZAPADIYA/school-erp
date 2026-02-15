<?php
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<div class="sidebar">
  <div class="logo-section">
    <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
    <h4>Admin Panel</h4>
  </div>
  <div class="user-info">
    <i class="fas fa-user-shield"></i>
    <div>
      <p class="user-name"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></p>
      <p class="user-role">Administrator</p>
    </div>
  </div>
  <nav class="nav-menu">
    <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
    <a href="students.php" class="nav-item"><i class="fas fa-user-graduate"></i> Students</a>
    <a href="teachers.php" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
    <a href="classes.php" class="nav-item"><i class="fas fa-school"></i> Classes</a>
    <a href="subjects.php" class="nav-item"><i class="fas fa-book"></i> Subjects</a>
    <a href="fees.php" class="nav-item"><i class="fas fa-dollar-sign"></i> Fee Management</a>
    <a href="attendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="exams.php" class="nav-item"><i class="fas fa-file-alt"></i> Exams</a>
    <a href="reports.php" class="nav-item"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>
<style>
  .sidebar { width: 280px; background: #2c3e50; height: 100vh; position: fixed; left: 0; top: 0; color: white; overflow-y: auto; }
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
</style>

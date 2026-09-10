<?php
// Reusable admin sidebar used across admin pages.
// Align role-based session values to the admin namespace.
if ((!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name'])) && isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
  $_SESSION['admin_id'] = (int) $_SESSION['user_id'];
  $_SESSION['admin_name'] = $_SESSION['name'] ?? 'Admin';
}
// Auth redirect is handled by auth.php — no duplicate redirect needed here.

// Resolve active route state for sidebar highlighting.
$currentPage = basename($_SERVER['PHP_SELF']);

function isAdminActive(array $pages, $currentPage)
{
    return in_array($currentPage, $pages, true) ? 'admin-nav-link active' : 'admin-nav-link';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<!-- Render global sidebar navigation for all admin views. -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-brand">
    <div class="admin-brand-mark">A</div>
    <div>
      <h1>Admin Portal</h1>
      <p>School Operations</p>
    </div>
  </div>

  <div class="admin-user-card">
    <div class="admin-user-avatar">
      <span class="material-symbols-outlined">shield_person</span>
    </div>
    <div>
      <p class="admin-user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></p>
      <p class="admin-user-role">Administrator</p>
    </div>
    <a href="logout.php" class="admin-user-logout" aria-label="Logout" title="Logout">
      <span class="material-symbols-outlined">logout</span>
    </a>
  </div>

  <nav class="admin-nav">
    <a href="dashboard.php" class="<?php echo isAdminActive(['dashboard.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">grid_view</span><span>Dashboard</span>
    </a>
    <a href="manage-users.php" class="<?php echo isAdminActive(['manage-users.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">manage_accounts</span><span>Manage Users</span>
    </a>
    <a href="students.php" class="<?php echo isAdminActive(['students.php', 'add-student.php', 'edit-student.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">school</span><span>Students</span>
    </a>
    <a href="teachers.php" class="<?php echo isAdminActive(['teachers.php', 'add-teacher.php', 'edit-teacher.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">groups</span><span>Teachers</span>
    </a>
    <a href="classes.php" class="<?php echo isAdminActive(['classes.php', 'add-class.php', 'edit-class.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">meeting_room</span><span>Classes</span>
    </a>
    <a href="curriculum.php" class="<?php echo isAdminActive(['curriculum.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">calendar_view_week</span><span>Curriculum</span>
    </a>
    <a href="subjects.php" class="<?php echo isAdminActive(['subjects.php', 'add-subject.php', 'edit-subject.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">menu_book</span><span>Subjects</span>
    </a>
    <a href="fees.php" class="<?php echo isAdminActive(['fees.php', 'add-fee.php', 'edit-fee.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">payments</span><span>Fees</span>
    </a>
    <a href="attendance.php" class="<?php echo isAdminActive(['attendance.php', 'edit-attendance.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">fact_check</span><span>Attendance</span>
    </a>
    <a href="leave-history.php" class="<?php echo isAdminActive(['leave-history.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">event_note</span><span>Leave History</span>
    </a>
    <a href="exams.php" class="<?php echo isAdminActive(['exams.php', 'add-exam.php', 'edit-exam.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">assignment</span><span>Exams</span>
    </a>
    <a href="support-tickets.php" class="<?php echo isAdminActive(['support-tickets.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">support_agent</span><span>Support Tickets</span>
    </a>
    <a href="notices.php" class="<?php echo isAdminActive(['notices.php', 'add-notice.php', 'edit-notice.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">campaign</span><span>Notice Board</span>
    </a>
    <a href="reports.php" class="<?php echo isAdminActive(['reports.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">analytics</span><span>Reports</span>
    </a>
    <a href="profile.php" class="<?php echo isAdminActive(['profile.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">person</span><span>Profile</span>
    </a>
    <a href="settings.php" class="<?php echo isAdminActive(['settings.php'], $currentPage); ?>">
      <span class="material-symbols-outlined">settings</span><span>Settings</span>
    </a>
  </nav>

  <div class="admin-sidebar-footer">
    <a href="logout.php" class="admin-logout-link">
      <span class="material-symbols-outlined">logout</span><span>Logout</span>
    </a>
  </div>
</aside>

<button class="admin-mobile-toggle" id="adminMobileToggle" aria-label="Toggle navigation" onclick="toggleAdminSidebar()">
  <span class="material-symbols-outlined">menu</span>
</button>
<div class="admin-sidebar-overlay" id="adminSidebarOverlay" onclick="toggleAdminSidebar()"></div>

<style>
  :root {
    --admin-primary: #1d4ed8;
    --admin-primary-soft: #dbeafe;
    --admin-bg: #f8fafc;
    --admin-text: #000000;
    --admin-text-soft: #000000;
    --admin-muted: #000000;
    --admin-link: #000000;
    --admin-sidebar-link: #000000;
    --admin-border: #e2e8f0;
    --admin-shadow: 0 8px 26px rgba(15, 23, 42, 0.06);
    --admin-shadow-soft: 0 4px 14px rgba(15, 23, 42, 0.05);
  }

  .material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  }

  body {
    background: var(--admin-bg) !important;
    color: var(--admin-text) !important;
    font-family: 'Inter', sans-serif !important;
  }

  .admin-sidebar {
    width: 16rem;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    background: #ffffff;
    border-right: 1px solid var(--admin-border);
    padding: 1.25rem 0.9rem;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
  }

  .admin-sidebar,
  .admin-sidebar p,
  .admin-sidebar h1,
  .admin-sidebar h2,
  .admin-sidebar h3,
  .admin-sidebar h4,
  .admin-sidebar h5,
  .admin-sidebar h6,
  .admin-sidebar span,
  .admin-sidebar .material-symbols-outlined {
    color: #000000 !important;
  }

  .admin-sidebar a,
  .admin-sidebar a:link,
  .admin-sidebar a:visited,
  .admin-sidebar a:hover,
  .admin-sidebar a:active,
  .admin-sidebar a:focus {
    color: #000000 !important;
  }

  .admin-brand {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.3rem 0.45rem;
    margin-bottom: 1.2rem;
  }

  .admin-brand-mark {
    width: 2rem;
    height: 2rem;
    border-radius: 0.35rem;
    background: var(--admin-primary);
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff !important;
  }

  .admin-brand h1 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: -0.015em;
    color: var(--admin-text);
    line-height: 1.15;
  }

  .admin-brand p {
    margin: 0.12rem 0 0;
    font-size: 0.64rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--admin-muted);
    font-weight: 700;
  }

  .admin-user-card {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.75rem;
    margin-bottom: 0.9rem;
    border: 1px solid var(--admin-border);
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border-radius: 0.75rem;
    box-shadow: var(--admin-shadow-soft);
  }

  .admin-user-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 0.65rem;
    background: var(--admin-primary-soft);
    color: var(--admin-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .admin-user-avatar .material-symbols-outlined {
    font-size: 1.1rem;
  }

  .admin-user-name {
    margin: 0;
    color: var(--admin-text);
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.2;
  }

  .admin-user-role {
    margin: 0.12rem 0 0;
    color: var(--admin-muted);
    font-size: 0.66rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .admin-user-logout {
    margin-left: auto;
    color: #64748b !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
  }

  .admin-user-logout:hover {
    background: #fff1f2;
    color: #dc2626 !important;
  }

  .admin-nav {
    display: flex;
    flex-direction: column;
    gap: 0.14rem;
    margin-top: 0.3rem;
  }

  .admin-nav-link {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    text-decoration: none;
    color: var(--admin-sidebar-link);
    padding: 0.52rem 0.62rem;
    border-radius: 0.5rem;
    font-size: 0.79rem;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .admin-nav-link .material-symbols-outlined {
    font-size: 1.05rem;
  }

  .admin-nav-link:hover {
    background: #f8fafc;
    color: #000000 !important;
  }

  .admin-nav-link.active {
    background: #2563eb !important;
    color: #ffffff !important;
    font-weight: 700;
  }

  .admin-nav-link.active .material-symbols-outlined {
    color: #ffffff !important;
  }

  .admin-nav-link.active span {
    color: #ffffff !important;
  }

  .admin-sidebar-footer {
    margin-top: auto;
    padding-top: 0.9rem;
    border-top: 1px solid #f1f5f9;
  }

  .admin-side-widget {
    margin: 0.95rem 0 0.6rem;
    border: 1px solid var(--admin-border);
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border-radius: 0.75rem;
    box-shadow: var(--admin-shadow-soft);
    padding: 0.7rem;
  }

  .admin-side-widget-title {
    display: flex;
    align-items: center;
    gap: 0.36rem;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--admin-muted);
    margin-bottom: 0.45rem;
  }

  .admin-side-widget-title .material-symbols-outlined {
    font-size: 0.85rem;
    color: #0ea5e9;
  }

  .admin-side-widget-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.72rem;
    color: var(--admin-muted);
    padding: 0.2rem 0;
  }

  .admin-side-widget-row strong {
    color: var(--admin-text);
    font-size: 0.7rem;
    font-weight: 700;
  }

  .admin-logout-link {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    color: var(--admin-sidebar-link);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.5rem 0.62rem;
    border-radius: 0.5rem;
  }

  .admin-logout-link:hover {
    background: #fff1f2;
    color: #dc2626;
  }

  .admin-logout-link:hover .material-symbols-outlined,
  .admin-logout-link:hover span {
    color: #dc2626 !important;
  }

  .admin-mobile-toggle {
    display: none;
    position: fixed;
    top: 0.95rem;
    left: 0.95rem;
    width: 2.65rem;
    height: 2.65rem;
    border: 0;
    border-radius: 0.7rem;
    background: #ffffff;
    color: var(--admin-text);
    z-index: 1002;
    box-shadow: var(--admin-shadow-soft);
    align-items: center;
    justify-content: center;
  }

  .admin-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 998;
    background: rgba(15, 23, 42, 0.4);
  }

  .main-content,
  .dashboard-shell {
    margin-left: 16rem !important;
    padding: 1.6rem !important;
  }

  .header,
  .content-card,
  .form-card,
  .profile-card,
  .summary-item,
  .stat-card,
  .report-card {
    border: 1px solid var(--admin-border) !important;
    border-radius: 0.9rem !important;
    box-shadow: var(--admin-shadow-soft) !important;
  }

  .header {
    border-bottom: 1px solid var(--admin-border) !important;
    border-left: 1px solid var(--admin-border) !important;
    background: #ffffff !important;
  }

  .header h1,
  .header h2,
  .header h5,
  .main-content h1,
  .main-content h2,
  .main-content h3,
  .main-content h4,
  .main-content h5,
  .main-content h6,
  .dashboard-shell h1,
  .dashboard-shell h2,
  .dashboard-shell h3,
  .dashboard-shell h4,
  .dashboard-shell h5,
  .dashboard-shell h6,
  .content-card h1,
  .content-card h2,
  .content-card h3,
  .content-card h4,
  .content-card h5 {
    color: var(--admin-text) !important;
  }

  .main-content,
  .dashboard-shell,
  .main-content p,
  .main-content span,
  .main-content li,
  .main-content label,
  .main-content small,
  .main-content .form-label,
  .main-content .info-value,
  .main-content .profile-name,
  .main-content .profile-id,
  .main-content .report-title,
  .main-content .summary-label,
  .main-content .stat-label,
  .dashboard-shell p,
  .dashboard-shell span,
  .dashboard-shell li,
  .dashboard-shell label,
  .dashboard-shell small,
  .dashboard-shell .summary-label,
  .dashboard-shell .stat-label {
    color: var(--admin-text-soft) !important;
  }

  .main-content .text-muted,
  .dashboard-shell .text-muted,
  .main-content .info-label,
  .main-content .report-desc,
  .main-content .profile-role,
  .main-content .user-role,
  .dashboard-shell .profile-role {
    color: var(--admin-muted) !important;
  }

  .main-content a:not(.btn):not(.badge):not(.btn-add):not(.btn-submit):not(.btn-save):not(.btn-create),
  .dashboard-shell a:not(.btn):not(.badge):not(.btn-add):not(.btn-submit):not(.btn-save):not(.btn-create) {
    color: var(--admin-link) !important;
  }

  .btn-add,
  .btn-submit,
  .btn-save,
  .btn-create {
    background: var(--admin-primary) !important;
    border-color: var(--admin-primary) !important;
    color: #ffffff !important;
    border-radius: 0.62rem !important;
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.18);
  }

  .btn-add:hover,
  .btn-submit:hover,
  .btn-save:hover,
  .btn-create:hover {
    background: #1e40af !important;
    border-color: #1e40af !important;
  }

  .btn-cancel {
    border-radius: 0.62rem !important;
    border: 1px solid var(--admin-border) !important;
    background: #ffffff !important;
    color: #334155 !important;
  }

  .form-control,
  .form-select,
  .grade-input {
    border-radius: 0.62rem !important;
    border: 1px solid #cbd5e1 !important;
    box-shadow: none !important;
  }

  .form-control:focus,
  .form-select:focus,
  .grade-input:focus {
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14) !important;
  }

  .table {
    margin-bottom: 0 !important;
  }

  .table thead th {
    background: #f8fafc !important;
    color: var(--admin-text-soft) !important;
    border-bottom: 1px solid var(--admin-border) !important;
    font-weight: 700 !important;
  }

  .table td {
    color: var(--admin-text-soft) !important;
    border-color: #e2e8f0 !important;
    vertical-align: middle;
  }

  .badge {
    border-radius: 999px !important;
    font-weight: 700 !important;
    letter-spacing: 0.01em;
  }

  .summary-value,
  .stat-value {
    color: #000000 !important;
  }

  .main-content [class*="text-green"],
  .main-content [class*="text-emerald"],
  .main-content [class*="text-success"],
  .dashboard-shell [class*="text-green"],
  .dashboard-shell [class*="text-emerald"],
  .dashboard-shell [class*="text-success"],
  .main-content .text-success,
  .main-content .text-warning,
  .main-content .text-danger,
  .main-content .text-info,
  .main-content .text-primary,
  .dashboard-shell .text-success,
  .dashboard-shell .text-warning,
  .dashboard-shell .text-danger,
  .dashboard-shell .text-info,
  .dashboard-shell .text-primary {
    color: #000000 !important;
  }

  .main-content .badge.bg-success,
  .main-content .badge.bg-warning,
  .main-content .badge.bg-danger,
  .main-content .badge.bg-secondary,
  .main-content .badge.bg-primary,
  .main-content .badge.active,
  .main-content .badge.inactive,
  .dashboard-shell .badge.bg-success,
  .dashboard-shell .badge.bg-warning,
  .dashboard-shell .badge.bg-danger,
  .dashboard-shell .badge.bg-secondary,
  .dashboard-shell .badge.bg-primary,
  .dashboard-shell .badge.active,
  .dashboard-shell .badge.inactive,
  .main-content .status-active,
  .main-content .status-inactive,
  .dashboard-shell .status-active,
  .dashboard-shell .status-inactive {
    background: #2563eb !important;
    border-color: #1d4ed8 !important;
    color: #ffffff !important;
  }

  @media (max-width: 991px) {
    .admin-sidebar {
      transform: translateX(-100%);
      transition: transform 0.25s ease;
    }

    .admin-sidebar.active {
      transform: translateX(0);
    }

    .admin-mobile-toggle {
      display: inline-flex;
    }

    .admin-sidebar-overlay.active {
      display: block;
    }

    .main-content,
    .dashboard-shell {
      margin-left: 0 !important;
      padding: 4.8rem 1rem 1rem !important;
    }
  }
</style>
<script>
function toggleAdminSidebar() {
  var sidebar = document.getElementById('adminSidebar');
  var overlay = document.getElementById('adminSidebarOverlay');
  if (!sidebar || !overlay) {
    return;
  }
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
}
</script>

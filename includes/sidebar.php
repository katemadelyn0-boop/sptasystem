<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role      = $_SESSION['role'] ?? '';
$user_name = $_SESSION['name'] ?? 'User';
$current   = basename($_SERVER['PHP_SELF']);
$dir       = basename(dirname($_SERVER['PHP_SELF']));
$BASE      = '/spta-system';

function navLink($href, $label, $icon, $current, $dir) {
    $file   = basename($href);
    $folder = basename(dirname($href));
    $active = ($current === $file && $dir === $folder) ? 'active' : '';
    echo "<a href='$href' class='nav-link $active'>$icon<span>$label</span></a>";
}

$ico_dash  = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>';
$ico_users = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>';
$ico_stud  = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>';
$ico_pay   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>';
$ico_rep   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>';
$ico_set   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>';
$ico_notif = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
    <div class="brand-text"><strong>SPTA System</strong><span>Pawing Central School</span></div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
    <div class="user-info">
      <strong><?= htmlspecialchars($user_name) ?></strong>
      <?php
        $roleLabels = ['admin'=>'Admin','staff'=>'Staff','spta_officer'=>'SPTA Officer','parent'=>'Parent'];
        $roleDisplay = $roleLabels[$role] ?? ucfirst(str_replace('_',' ',$role));
      ?>
      <span class="role-pill <?= $role ?>"><?= $roleDisplay ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <?php if ($role === 'admin'): ?>
      <div class="nav-section-label">Main</div>
      <?php navLink("$BASE/admin/dashboard.php", 'Dashboard', $ico_dash, $current, $dir); ?>
      <div class="nav-section-label">Management</div>
      <?php navLink("$BASE/admin/users.php",    'User Accounts', $ico_users, $current, $dir); ?>
      <?php navLink("$BASE/admin/students.php", 'Students',      $ico_stud,  $current, $dir); ?>
      <?php navLink("$BASE/admin/reports.php",  'Reports',       $ico_rep,   $current, $dir); ?>
      <?php navLink("$BASE/admin/settings.php", 'Settings',      $ico_set,   $current, $dir); ?>

    <?php elseif ($role === 'staff'): ?>
      <div class="nav-section-label">Main</div>
      <?php navLink("$BASE/staff/dashboard.php", 'Dashboard', $ico_dash, $current, $dir); ?>
      <div class="nav-section-label">Records</div>
      <?php navLink("$BASE/staff/students.php", 'Students', $ico_stud, $current, $dir); ?>
      <?php navLink("$BASE/staff/payments.php", 'Payments', $ico_pay,  $current, $dir); ?>
      <?php navLink("$BASE/staff/reports.php",  'Reports',  $ico_rep,  $current, $dir); ?>

    <?php elseif ($role === 'spta_officer'): ?>
      <div class="nav-section-label">Main</div>
      <?php navLink("$BASE/officer/dashboard.php", 'Dashboard', $ico_dash, $current, $dir); ?>
      <div class="nav-section-label">SPTA</div>
      <?php navLink("$BASE/officer/students.php", 'Students & Parents', $ico_stud, $current, $dir); ?>
      <?php navLink("$BASE/officer/payments.php", 'Payments', $ico_pay, $current, $dir); ?>
      <?php navLink("$BASE/officer/reports.php",  'Reports',  $ico_rep, $current, $dir); ?>

    <?php elseif ($role === 'parent'): ?>
      <div class="nav-section-label">My Account</div>
      <?php navLink("$BASE/parent/dashboard.php",      'Dashboard',     $ico_dash,  $current, $dir); ?>
      <?php navLink("$BASE/parent/payments.php",       'My Payments',   $ico_pay,   $current, $dir); ?>
      <?php navLink("$BASE/parent/notifications.php",  'Notifications', $ico_notif, $current, $dir); ?>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= $BASE ?>/logout.php" class="btn-logout">
      <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
      Sign Out
    </a>
  </div>
</aside>
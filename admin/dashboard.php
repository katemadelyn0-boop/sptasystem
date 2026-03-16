<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('admin');
$db = getDB();

$users    = $db->query("SELECT COUNT(*) c FROM users WHERE role!='admin' AND is_active=1")->fetch_assoc()['c'];
$students = $db->query("SELECT COUNT(*) c FROM students WHERE is_active=1")->fetch_assoc()['c'];
$paid     = $db->query("SELECT COUNT(*) c FROM payments WHERE status IN('paid','partial')")->fetch_assoc()['c'];
$unpaid   = $db->query("SELECT COUNT(*) c FROM payments WHERE status IN('unpaid','overdue')")->fetch_assoc()['c'];
$total    = $db->query("SELECT COALESCE(SUM(amount_paid),0) t FROM payments WHERE status IN('paid','partial')")->fetch_assoc()['t'];
$pending  = $db->query("SELECT COUNT(*) c FROM users WHERE is_verified=0")->fetch_assoc()['c'];

$recent = $db->query("
    SELECT p.payment_date, p.amount_paid, p.status, p.payment_method,
           CONCAT(s.first_name,' ',s.last_name) AS student_name, pc.category_name,
           CONCAT(u.first_name,' ',u.last_name) AS recorded_by
    FROM payments p
    JOIN students s ON p.student_id=s.student_id
    JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id
    JOIN payment_categories pc ON pr.category_id=pc.category_id
    JOIN users u ON p.recorded_by=u.user_id
    ORDER BY p.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin Dashboard — SPTA System</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/header.php'; ?>
    <div class="page-body">

      <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>! Here's an overview of the system.</p>
      </div>

      <?php if ($pending > 0): ?>
      <div class="alert alert-warning">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <?= $pending ?> user(s) pending email verification.
        <a href="/spta-system/admin/users.php" style="color:inherit;font-weight:700;margin-left:6px;">View Users →</a>
      </div>
      <?php endif; ?>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
          <div class="stat-info"><div class="num"><?= number_format($users) ?></div><div class="label">Active Users</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon navy"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
          <div class="stat-info"><div class="num"><?= number_format($students) ?></div><div class="label">Students</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
          <div class="stat-info"><div class="num">&#8369;<?= number_format($total,2) ?></div><div class="label">Total Collected</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
          <div class="stat-info"><div class="num"><?= number_format($paid) ?></div><div class="label">Paid / Partial</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>
          <div class="stat-info"><div class="num"><?= number_format($unpaid) ?></div><div class="label">Unpaid / Overdue</div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Recent Payments <a href="/spta-system/admin/reports.php" class="btn btn-outline btn-sm">View All</a></div>
        <?php if (empty($recent)): ?>
        <div class="empty-state"><p>No payments recorded yet.</p></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Student</th><th>Category</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Recorded By</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['category_name']) ?></td>
                <td>&#8369;<?= number_format($r['amount_paid'],2) ?></td>
                <td><?= ucfirst(str_replace('_',' ',$r['payment_method'])) ?></td>
                <td><?= date('M d, Y',strtotime($r['payment_date'])) ?></td>
                <td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td style="font-size:13px;color:#6b7280;"><?= htmlspecialchars($r['recorded_by']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div>
    <?php include '../includes/footer.php'; ?>
  </div>
</div>
</body>
</html>
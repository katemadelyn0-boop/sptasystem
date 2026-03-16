<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('staff');
$db = getDB();

$students = $db->query("SELECT COUNT(*) c FROM students WHERE is_active=1")->fetch_assoc()['c'];

$paid = $db->query("SELECT COUNT(*) c FROM payments p 
    JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id 
    JOIN payment_categories pc ON pr.category_id=pc.category_id 
    WHERE p.status IN('paid','partial') AND pc.managed_by='staff'")->fetch_assoc()['c'];

$total = $db->query("SELECT COALESCE(SUM(p.amount_paid),0) t FROM payments p 
    JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id 
    JOIN payment_categories pc ON pr.category_id=pc.category_id 
    WHERE p.status IN('paid','partial') AND pc.managed_by='staff'")->fetch_assoc()['t'];

// Correct unpaid: count students who have NOT fully paid each staff-managed requirement
$unpaid = $db->query("
    SELECT COUNT(*) c FROM (
        SELECT s.student_id, pr.requirement_id,
               COALESCE(SUM(p.amount_paid),0) AS paid_so_far,
               pr.amount AS required
        FROM students s
        JOIN school_years sy ON s.sy_id = sy.sy_id AND sy.is_active = 1
        JOIN payment_requirements pr ON pr.sy_id = sy.sy_id
        JOIN payment_categories pc ON pr.category_id = pc.category_id AND pc.managed_by = 'staff'
        LEFT JOIN payments p ON p.student_id = s.student_id AND p.requirement_id = pr.requirement_id
        WHERE s.is_active = 1
        GROUP BY s.student_id, pr.requirement_id, pr.amount
        HAVING paid_so_far < required
    ) x
")->fetch_assoc()['c'];

$recent = $db->query("SELECT p.payment_date,p.amount_paid,p.status,
    CONCAT(s.first_name,' ',s.last_name) sname,pc.category_name 
    FROM payments p 
    JOIN students s ON p.student_id=s.student_id 
    JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id 
    JOIN payment_categories pc ON pr.category_id=pc.category_id 
    WHERE pc.managed_by='staff' 
    ORDER BY p.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Staff Dashboard — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/header.php'; ?>
<div class="page-body">
<div class="page-header"><h1>Staff Dashboard</h1><p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p></div>
<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon navy"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div><div class="stat-info"><div class="num"><?= $students ?></div><div class="label">Students</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div><div class="stat-info"><div class="num">&#8369;<?= number_format($total,2) ?></div><div class="label">Collected</div></div></div>
  <div class="stat-card"><div class="stat-icon gold"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div><div class="stat-info"><div class="num"><?= $paid ?></div><div class="label">Paid</div></div></div>
  <div class="stat-card"><div class="stat-icon red"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div><div class="stat-info"><div class="num"><?= $unpaid ?></div><div class="label">Unpaid/Overdue</div></div></div>
</div>
<div class="card">
  <div class="card-title">Recent Payments <a href="/spta-system/staff/payments.php" class="btn btn-outline btn-sm">View All</a></div>
  <div class="table-wrap"><table><thead><tr><th>Student</th><th>Category</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
  <tbody>
    <?php if(empty($recent)):?><tr><td colspan="5"><div class="empty-state"><p>No payments yet.</p></div></td></tr>
    <?php else: foreach($recent as $r): ?>
    <tr><td><strong><?= htmlspecialchars($r['sname']) ?></strong></td><td><?= htmlspecialchars($r['category_name']) ?></td><td>&#8369;<?= number_format($r['amount_paid'],2) ?></td><td><?= date('M d, Y',strtotime($r['payment_date'])) ?></td><td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td></tr>
    <?php endforeach; endif; ?>
  </tbody></table></div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
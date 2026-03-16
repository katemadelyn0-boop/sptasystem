<?php
  require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('parent');
$db  = getDB();
$uid = $_SESSION['user_id'];

// Get children
$s = $db->prepare("SELECT s.*,g.grade_name,sy.sy_label,sy.sy_id FROM parent_student ps JOIN students s ON ps.student_id=s.student_id JOIN grade_levels g ON s.grade_id=g.grade_id JOIN school_years sy ON s.sy_id=sy.sy_id WHERE ps.parent_id=? AND s.is_active=1");
$s->bind_param('i',$uid);$s->execute();$children=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();

// Unread notifications
$n=$db->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
$n->bind_param('i',$uid);$n->execute();$notifs=$n->get_result()->fetch_assoc()['c'];$n->close();

// Recent payments
$child_ids=array_column($children,'student_id');
$payments=[];
if(!empty($child_ids)){
    $in=implode(',',array_fill(0,count($child_ids),'?'));
    $s=$db->prepare("SELECT p.*,CONCAT(s.first_name,' ',s.last_name) sname,pc.category_name,r.receipt_no FROM payments p JOIN students s ON p.student_id=s.student_id JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id LEFT JOIN receipts r ON p.payment_id=r.payment_id WHERE p.student_id IN($in) ORDER BY p.payment_date DESC LIMIT 8");
    $s->bind_param(str_repeat('i',count($child_ids)),...$child_ids);$s->execute();
    $payments=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
}

// Get outstanding balances per child per requirement
// Logic: for each active requirement matching the child's sy_id,
//        sum up what's been paid, compare to required amount
$outstanding = []; // keyed by student_id
foreach($children as $c){
    $sid = $c['student_id'];
    $sy_id = $c['sy_id'];

    // All requirements for this school year
    $s=$db->prepare("
        SELECT pr.requirement_id, pr.amount AS amount_required,
               pc.category_name, pc.managed_by,
               COALESCE(SUM(p.amount_paid),0) AS total_paid
        FROM payment_requirements pr
        JOIN payment_categories pc ON pr.category_id=pc.category_id
        LEFT JOIN payments p ON p.requirement_id=pr.requirement_id AND p.student_id=?
        WHERE pr.sy_id=?
        GROUP BY pr.requirement_id, pr.amount, pc.category_name, pc.managed_by
    ");
    if(!$s){ error_log("DB prepare failed: ".$db->error); $reqs=[]; }
    else { $s->bind_param('ii',$sid,$sy_id);$s->execute(); $reqs=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close(); }

    $unpaid_list = [];
    $paid_count = 0;
    $unpaid_count = 0;
    foreach($reqs as $r){
        $balance = $r['amount_required'] - $r['total_paid'];
        if($r['total_paid'] >= $r['amount_required']){
            $paid_count++;
        } else {
            $unpaid_count++;
            $unpaid_list[] = [
                'category'  => $r['category_name'],
                'required'  => $r['amount_required'],
                'paid'      => $r['total_paid'],
                'balance'   => $balance,
                'managed_by'=> $r['managed_by'],
            ];
        }
    }
    $outstanding[$sid] = [
        'paid_count'   => $paid_count,
        'unpaid_count' => $unpaid_count,
        'unpaid_list'  => $unpaid_list,
    ];
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Dashboard — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/header.php'; ?>
<div class="page-body">
<div class="page-header"><h1>My Dashboard</h1><p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p></div>

<?php if($notifs>0):?>
<div class="alert alert-warning">
  <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
  You have <strong><?= $notifs ?></strong> unread notification(s).
  <a href="/spta-system/parent/notifications.php" style="color:inherit;font-weight:700;margin-left:6px;">View →</a>
</div>
<?php endif;?>

<?php if(empty($children)):?>
<div class="card" style="text-align:center;padding:48px;">
  <div style="font-size:48px;margin-bottom:12px;">👨‍👩‍👧</div>
  <h3 style="color:#0f2342;margin-bottom:8px;">No students linked yet</h3>
  <p style="color:#6b7280;font-size:14px;">Please contact your school administrator to link your child's record.</p>
</div>
<?php else:?>

<?php foreach($children as $c):
  $sid = $c['student_id'];
  $ov  = $outstanding[$sid];
?>
<div class="card" style="border-top:4px solid #0f2342;margin-bottom:20px;">
  <!-- Child header -->
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
    <div style="width:44px;height:44px;border-radius:50%;background:#0f2342;display:flex;align-items:center;justify-content:center;color:#e8a020;font-weight:800;font-size:18px;"><?= strtoupper(substr($c['first_name'],0,1)) ?></div>
    <div>
      <div style="font-weight:700;color:#0f2342;"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></div>
      <div style="font-size:13px;color:#6b7280;"><?= htmlspecialchars($c['grade_name'].' — '.$c['sy_label']) ?></div>
    </div>
  </div>

  <!-- Paid / Unpaid counters -->
  <div style="display:flex;gap:12px;margin-bottom:<?= !empty($ov['unpaid_list']) ? '16px' : '0' ?>;">
    <div style="flex:1;text-align:center;background:#f0fdf4;border-radius:10px;padding:12px;">
      <div style="font-size:22px;font-weight:800;color:#16a34a;"><?= $ov['paid_count'] ?></div>
      <div style="font-size:12px;color:#16a34a;">Paid</div>
    </div>
    <div style="flex:1;text-align:center;background:#fef2f2;border-radius:10px;padding:12px;">
      <div style="font-size:22px;font-weight:800;color:#dc2626;"><?= $ov['unpaid_count'] ?></div>
      <div style="font-size:12px;color:#dc2626;">Unpaid</div>
    </div>
  </div>

  <!-- Outstanding balances list -->
  <?php if(!empty($ov['unpaid_list'])):?>
  <div style="border-top:1px dashed #e5e7eb;padding-top:14px;">
    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Outstanding Balances</div>
    <?php foreach($ov['unpaid_list'] as $item):?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin-bottom:8px;">
      <div>
        <div style="font-weight:600;color:#0f2342;font-size:14px;"><?= htmlspecialchars($item['category']) ?></div>
        <?php if($item['paid']>0):?>
        <div style="font-size:12px;color:#6b7280;">Partially paid: ₱<?= number_format($item['paid'],2) ?> of ₱<?= number_format($item['required'],2) ?></div>
        <?php else:?>
        <div style="font-size:12px;color:#6b7280;">Required: ₱<?= number_format($item['required'],2) ?></div>
        <?php endif;?>
      </div>
      <div style="text-align:right;">
        <div style="font-size:16px;font-weight:800;color:#dc2626;">₱<?= number_format($item['balance'],2) ?></div>
        <div style="font-size:11px;color:#9ca3af;">Balance due</div>
      </div>
    </div>
    <?php endforeach;?>
    <p style="font-size:12px;color:#9ca3af;margin-top:8px;">💡 Please approach your school's <?= $ov['unpaid_list'][0]['managed_by']==='spta_officer' ? 'SPTA Officer' : 'Staff' ?> to settle your balance.</p>
  </div>
  <?php endif;?>
</div>
<?php endforeach;?>

<!-- Recent Payments -->
<div class="card">
  <div class="card-title">Recent Payments <a href="/spta-system/parent/payments.php" class="btn btn-outline btn-sm">View All</a></div>
  <div class="table-wrap"><table><thead><tr><th>Student</th><th>Category</th><th>Amount</th><th>Date</th><th>Status</th><th>Receipt</th></tr></thead>
  <tbody>
    <?php if(empty($payments)):?><tr><td colspan="6"><div class="empty-state"><p>No payments yet.</p></div></td></tr>
    <?php else: foreach($payments as $p): ?>
    <tr>
      <td><strong><?= htmlspecialchars($p['sname']) ?></strong></td>
      <td style="font-size:13px;"><?= htmlspecialchars($p['category_name']) ?></td>
      <td>&#8369;<?= number_format($p['amount_paid'],2) ?></td>
      <td style="font-size:13px;"><?= date('M d, Y',strtotime($p['payment_date'])) ?></td>
      <td><span class="badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
      <td style="font-size:13px;font-weight:600;color:#0f2342;"><?= htmlspecialchars($p['receipt_no']??'—') ?></td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody></table></div>
</div>
<?php endif;?>
</div>
<?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
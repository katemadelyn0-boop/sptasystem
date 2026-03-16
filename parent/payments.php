<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('parent');
$db=$db=getDB();$uid=$_SESSION['user_id'];

// Get children with sy_id
$s=$db->prepare("SELECT ps.student_id, s.first_name, s.last_name, g.grade_name, sy.sy_label, sy.sy_id FROM parent_student ps JOIN students s ON ps.student_id=s.student_id JOIN grade_levels g ON s.grade_id=g.grade_id JOIN school_years sy ON s.sy_id=sy.sy_id WHERE ps.parent_id=? AND s.is_active=1");
$s->bind_param('i',$uid);$s->execute();$children=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
$ids=array_column($children,'student_id');

// Payment history
$payments=[];
if(!empty($ids)){
    $in=implode(',',array_fill(0,count($ids),'?'));
    $s=$db->prepare("SELECT p.*,CONCAT(s.first_name,' ',s.last_name) sname,g.grade_name,pc.category_name,r.receipt_id,r.receipt_no,sy.sy_label FROM payments p JOIN students s ON p.student_id=s.student_id JOIN grade_levels g ON s.grade_id=g.grade_id JOIN school_years sy ON s.sy_id=sy.sy_id JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id LEFT JOIN receipts r ON p.payment_id=r.payment_id WHERE p.student_id IN($in) ORDER BY p.payment_date DESC");
    $s->bind_param(str_repeat('i',count($ids)),...$ids);$s->execute();$payments=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
}

// Outstanding balances per child
$outstanding = [];
foreach($children as $c){
    $sid = $c['student_id'];
    $sy_id = $c['sy_id'];
    $s=$db->prepare("
        SELECT pr.requirement_id, pr.amount AS amount_required,
               pc.category_name, pc.managed_by,
               COALESCE(SUM(p.amount_paid),0) AS total_paid
        FROM payment_requirements pr
        JOIN payment_categories pc ON pr.category_id=pc.category_id
        LEFT JOIN payments p ON p.requirement_id=pr.requirement_id AND p.student_id=?
        WHERE pr.sy_id=?
        GROUP BY pr.requirement_id, pr.amount, pc.category_name, pc.managed_by
        HAVING COALESCE(SUM(p.amount_paid),0) < pr.amount
    ");
    if(!$s){ error_log("DB prepare failed: ".$db->error); $rows=[]; }
    else { $s->bind_param('ii',$sid,$sy_id);$s->execute(); $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close(); }
    if(!empty($rows)){
        $outstanding[] = [
            'name'     => $c['first_name'].' '.$c['last_name'],
            'grade'    => $c['grade_name'],
            'sy_label' => $c['sy_label'],
            'balances' => $rows,
        ];
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Payments — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php';?>
<div class="main-content"><?php include '../includes/header.php';?>
<div class="page-body">
<div class="page-header"><h1>My Payments</h1><p>Complete payment history for your child/children.</p></div>

<!-- Outstanding Balances Section -->
<?php if(!empty($outstanding)):?>
<div class="card" style="border-left:4px solid #f59e0b;margin-bottom:24px;">
  <div style="font-size:13px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">⚠️ Outstanding Balances</div>
  <?php foreach($outstanding as $oc):?>
  <div style="margin-bottom:14px;">
    <div style="font-weight:600;color:#0f2342;font-size:14px;margin-bottom:8px;">
      <?= htmlspecialchars($oc['name']) ?> — <span style="font-weight:400;color:#6b7280;"><?= htmlspecialchars($oc['grade']) ?>, <?= htmlspecialchars($oc['sy_label']) ?></span>
    </div>
    <?php foreach($oc['balances'] as $b):
      $balance = $b['amount_required'] - $b['total_paid'];
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin-bottom:6px;">
      <div>
        <div style="font-weight:600;font-size:14px;color:#0f2342;"><?= htmlspecialchars($b['category_name']) ?></div>
        <?php if($b['total_paid']>0):?>
        <div style="font-size:12px;color:#6b7280;">Partially paid: ₱<?= number_format($b['total_paid'],2) ?> of ₱<?= number_format($b['amount_required'],2) ?></div>
        <?php else:?>
        <div style="font-size:12px;color:#6b7280;">Required: ₱<?= number_format($b['amount_required'],2) ?></div>
        <?php endif;?>
      </div>
      <div style="text-align:right;">
        <div style="font-size:16px;font-weight:800;color:#dc2626;">₱<?= number_format($balance,2) ?></div>
        <div style="font-size:11px;color:#9ca3af;">Balance due</div>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <?php endforeach;?>
  <p style="font-size:12px;color:#9ca3af;margin-top:4px;">💡 Please approach your school's Staff or SPTA Officer to settle your balance.</p>
</div>
<?php endif;?>

<!-- Payment History -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:20px 24px 0;"><div class="card-title">Payment History</div></div>
  <div class="table-wrap"><table>
  <thead><tr><th>Student</th><th>School Year</th><th>Category</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Receipt</th><th></th></tr></thead>
  <tbody>
  <?php if(empty($payments)):?><tr><td colspan="9"><div class="empty-state"><p>No payment records found.</p></div></td></tr>
  <?php else:foreach($payments as $p):?>
  <tr>
    <td><strong><?=htmlspecialchars($p['sname'])?></strong><br/><small style="color:#9ca3af;"><?=htmlspecialchars($p['grade_name'])?></small></td>
    <td style="font-size:13px;"><?=htmlspecialchars($p['sy_label'])?></td>
    <td style="font-size:13px;"><?=htmlspecialchars($p['category_name'])?></td>
    <td><strong>&#8369;<?=number_format($p['amount_paid'],2)?></strong></td>
    <td style="font-size:13px;"><?=ucfirst(str_replace('_',' ',$p['payment_method']))?></td>
    <td style="font-size:13px;"><?=date('M d, Y',strtotime($p['payment_date']))?></td>
    <td><span class="badge <?=$p['status']?>"><?=ucfirst($p['status'])?></span></td>
    <td style="font-size:13px;font-weight:600;color:#0f2342;"><?=htmlspecialchars($p['receipt_no']??'—')?></td>
    <td><?php if($p['receipt_id']):?><a href="/spta-system/receipt.php?id=<?=$p['receipt_id']?>" target="_blank" class="btn btn-outline btn-sm" style="white-space:nowrap;">🖨️ View</a><?php else:?>—<?php endif;?></td>
  </tr>
  <?php endforeach;endif;?>
  </tbody></table></div>
</div>

</div><?php include '../includes/footer.php';?></div></div>
</body></html>
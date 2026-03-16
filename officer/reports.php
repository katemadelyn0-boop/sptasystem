<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('admin','spta_officer');
$db=getDB();
$month=$_GET['month']??date('m');$year=$_GET['year']??date('Y');$sf=$_GET['status']??'';
$where="WHERE MONTH(p.payment_date)=? AND YEAR(p.payment_date)=?";
$p=[$month,$year];$t='ii';
if($sf){$where.=" AND p.status=?";$p[]=$sf;$t.='s';}
$s=$db->prepare("SELECT COUNT(*) c,COALESCE(SUM(p.amount_paid),0) total FROM payments p $where AND p.status='paid'");
$s->bind_param($t,...$p);$s->execute();$sum=$s->get_result()->fetch_assoc();$s->close();
$s=$db->prepare("SELECT p.*,CONCAT(s.first_name,' ',s.last_name) sname,g.grade_name,pc.category_name,r.receipt_no,CONCAT(u.first_name,' ',u.last_name) rby FROM payments p JOIN students s ON p.student_id=s.student_id JOIN grade_levels g ON s.grade_id=g.grade_id JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id JOIN users u ON p.recorded_by=u.user_id LEFT JOIN receipts r ON p.payment_id=r.payment_id $where ORDER BY p.payment_date DESC");
$s->bind_param($t,...$p);$s->execute();$payments=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
$yrs=$db->query("SELECT DISTINCT YEAR(payment_date) y FROM payments ORDER BY y DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Reports — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php';?>
<div class="main-content">
<?php include '../includes/header.php';?>
<div class="page-body">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>Financial Reports</h1><p>Payment records by month, year, and status.</p></div>
  <button class="btn btn-gold" onclick="window.print()">
    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg> Print
  </button>
</div>
<div class="card" style="margin-bottom:24px;">
<form method="GET" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
  <div class="form-group" style="margin:0;flex:1;min-width:120px;"><label class="form-label">Month</label><select name="month" class="form-control"><?php for($m=1;$m<=12;$m++):?><option value="<?=$m?>" <?=$month==$m?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option><?php endfor;?></select></div>
  <div class="form-group" style="margin:0;flex:1;min-width:100px;"><label class="form-label">Year</label><select name="year" class="form-control"><?php $yl=array_column($yrs,'y');if(!in_array(date('Y'),$yl))$yl[]=date('Y');rsort($yl);foreach($yl as $y):?><option value="<?=$y?>" <?=$year==$y?'selected':''?>><?=$y?></option><?php endforeach;?></select></div>
  <div class="form-group" style="margin:0;flex:1;min-width:120px;"><label class="form-label">Status</label><select name="status" class="form-control"><option value="">All</option><option value="paid" <?=$sf==='paid'?'selected':''?>>Paid</option><option value="unpaid" <?=$sf==='unpaid'?'selected':''?>>Unpaid</option><option value="overdue" <?=$sf==='overdue'?'selected':''?>>Overdue</option></select></div>
  <div style="margin:0;"><button type="submit" class="btn btn-primary">Filter</button></div>
</form></div>
<div class="stats-grid" style="margin-bottom:24px;">
  <div class="stat-card"><div class="stat-icon green"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div><div class="stat-info"><div class="num">&#8369;<?=number_format($sum['total'],2)?></div><div class="label">Collected (<?=date('F',mktime(0,0,0,$month,1)).' '.$year?>)</div></div></div>
  <div class="stat-card"><div class="stat-icon gold"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div><div class="stat-info"><div class="num"><?=$sum['c']?></div><div class="label">Paid Transactions</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg></div><div class="stat-info"><div class="num"><?=count($payments)?></div><div class="label">Total Records</div></div></div>
</div>
<div class="card" style="padding:0;overflow:hidden;"><div class="table-wrap"><table>
<thead><tr><th>#</th><th>Date</th><th>Student</th><th>Grade</th><th>Category</th><th>Method</th><th>Amount</th><th>Status</th><th>Receipt</th><th>Recorded By</th></tr></thead>
<tbody>
<?php if(empty($payments)):?><tr><td colspan="10"><div class="empty-state"><p>No records found.</p></div></td></tr>
<?php else:foreach($payments as $i=>$p):?>
<tr>
  <td style="color:#9ca3af;font-size:13px;"><?=$i+1?></td>
  <td style="font-size:13px;"><?=date('M d, Y',strtotime($p['payment_date']))?></td>
  <td><strong><?=htmlspecialchars($p['sname'])?></strong></td>
  <td style="font-size:13px;"><?=htmlspecialchars($p['grade_name'])?></td>
  <td style="font-size:13px;"><?=htmlspecialchars($p['category_name'])?></td>
  <td style="font-size:13px;"><?=ucfirst(str_replace('_',' ',$p['payment_method']))?></td>
  <td><strong>&#8369;<?=number_format($p['amount_paid'],2)?></strong></td>
  <td><span class="badge <?=$p['status']?>"><?=ucfirst($p['status'])?></span></td>
  <td style="font-size:13px;font-weight:600;color:#0f2342;"><?=htmlspecialchars($p['receipt_no']??'—')?></td>
  <td style="font-size:12px;color:#6b7280;"><?=htmlspecialchars($p['rby'])?></td>
</tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
</div>
<?php include '../includes/footer.php';?>
</div></div>
</body></html>

<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('spta_officer','admin');
$db=getDB();
$search=trim($_GET['search']??'');
$gf=(int)($_GET['grade']??0);
$grades=$db->query("SELECT * FROM grade_levels ORDER BY grade_id")->fetch_all(MYSQLI_ASSOC);
$sql="SELECT s.*,g.grade_name,sy.sy_label,
      (SELECT COUNT(*) FROM payments p JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id WHERE p.student_id=s.student_id AND p.status='paid' AND pc.managed_by='spta_officer') paid_count,
      (SELECT COUNT(*) FROM payments p JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id WHERE p.student_id=s.student_id AND p.status IN('unpaid','overdue') AND pc.managed_by='spta_officer') unpaid_count,
      (SELECT GROUP_CONCAT(CONCAT(u.first_name,' ',u.last_name) SEPARATOR ', ') FROM parent_student ps JOIN users u ON ps.parent_id=u.user_id WHERE ps.student_id=s.student_id) parent_names
      FROM students s JOIN grade_levels g ON s.grade_id=g.grade_id JOIN school_years sy ON s.sy_id=sy.sy_id WHERE s.is_active=1";
$p=[];$t='';
if($search){$sql.=" AND(s.first_name LIKE? OR s.last_name LIKE? OR s.lrn LIKE?)";$lk="%$search%";$p=[$lk,$lk,$lk];$t='sss';}
if($gf){$sql.=" AND s.grade_id=?";$p[]=$gf;$t.='i';}
$sql.=" ORDER BY g.grade_id,s.last_name";
$s=$db->prepare($sql);if($p)$s->bind_param($t,...$p);$s->execute();
$students=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Student & Parent Details — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php';?>
<div class="main-content">
<?php include '../includes/header.php';?>
<div class="page-body">
<div class="page-header"><h1>Student & Parent Details</h1><p>View student records and linked parent information for SPTA collection.</p></div>
<div class="toolbar">
  <div class="search-wrap">
    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    <input class="search-input" placeholder="Search name or LRN..." value="<?=htmlspecialchars($search)?>" onkeyup="filterTable(this.value)"/>
  </div>
  <select class="form-control" style="width:auto;padding:9px 14px;" onchange="window.location='?grade='+this.value">
    <option value="">All Grades</option>
    <?php foreach($grades as $g):?><option value="<?=$g['grade_id']?>" <?=$gf==$g['grade_id']?'selected':''?>><?=htmlspecialchars($g['grade_name'])?></option><?php endforeach;?>
  </select>
</div>
<div class="card" style="padding:0;overflow:hidden;">
<div class="table-wrap"><table id="tbl">
<thead><tr><th>#</th><th>LRN</th><th>Student Name</th><th>Grade</th><th>School Year</th><th>Parent(s)</th><th>SPTA Status</th></tr></thead>
<tbody>
<?php if(empty($students)):?>
<tr><td colspan="7"><div class="empty-state"><p>No students found.</p></div></td></tr>
<?php else:foreach($students as $i=>$st):?>
<tr>
  <td style="color:#9ca3af;font-size:13px;"><?=$i+1?></td>
  <td style="font-size:13px;color:#6b7280;"><?=htmlspecialchars($st['lrn']??'—')?></td>
  <td><strong><?=htmlspecialchars($st['last_name'].', '.$st['first_name'].($st['middle_name']?' '.$st['middle_name']:''))?></strong></td>
  <td><span class="badge staff"><?=htmlspecialchars($st['grade_name'])?></span></td>
  <td style="font-size:13px;"><?=htmlspecialchars($st['sy_label'])?></td>
  <td style="font-size:13px;color:#374151;"><?=htmlspecialchars($st['parent_names']??'— No parent linked')?></td>
  <td>
    <?php if($st['paid_count']>0&&$st['unpaid_count']==0):?>
      <span class="badge paid">✓ Paid</span>
    <?php elseif($st['unpaid_count']>0):?>
      <span class="badge overdue">⚠ Unpaid</span>
    <?php else:?>
      <span style="color:#9ca3af;font-size:13px;">No requirements</span>
    <?php endif;?>
  </td>
</tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
</div>
<?php include '../includes/footer.php';?>
</div></div>
<script>function filterTable(q){q=q.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}</script>
</body></html>

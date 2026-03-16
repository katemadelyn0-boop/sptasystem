<?php
    require_once '../config/db.php';
    require_once '../config/auth.php';
    requireRole('admin','staff');
$db=getDB();
$msg=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_student'])){
    $fn=trim($_POST['first_name']??'');$mn=trim($_POST['middle_name']??'');
    $ln=trim($_POST['last_name']??'');$lrn=trim($_POST['lrn']??'')?:null;
    $gender=$_POST['gender']??'';$gid=(int)($_POST['grade_id']??0);$sy=(int)($_POST['sy_id']??0);
    if($fn&&$ln&&$gender&&$gid&&$sy){
        $s=$db->prepare("INSERT INTO students(first_name,middle_name,last_name,lrn,gender,grade_id,sy_id)VALUES(?,?,?,?,?,?,?)");
        $s->bind_param('sssssii',$fn,$mn,$ln,$lrn,$gender,$gid,$sy);
        if($s->execute()){logAudit('ADD_STUDENT','students',$s->insert_id);$msg='Student added!';}
        else $err='Failed to add student.';$s->close();
    } else $err='Fill in all required fields.';
}
$grades=$db->query("SELECT * FROM grade_levels ORDER BY grade_id")->fetch_all(MYSQLI_ASSOC);
$syears=$db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetch_all(MYSQLI_ASSOC);
$search=trim($_GET['search']??'');$gf=(int)($_GET['grade']??0);
$sql="SELECT s.*,g.grade_name,sy.sy_label FROM students s JOIN grade_levels g ON s.grade_id=g.grade_id JOIN school_years sy ON s.sy_id=sy.sy_id WHERE s.is_active=1";
$p=[];$t='';
if($search){$sql.=" AND(s.first_name LIKE? OR s.last_name LIKE? OR s.lrn LIKE?)";$lk="%$search%";$p=[$lk,$lk,$lk];$t='sss';}
if($gf){$sql.=" AND s.grade_id=?";$p[]=$gf;$t.='i';}
$sql.=" ORDER BY g.grade_id,s.last_name";
$s=$db->prepare($sql);if($p)$s->bind_param($t,...$p);$s->execute();
$students=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Students — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php';?>
<div class="main-content">
<?php include '../includes/header.php';?>
<div class="page-body">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>Students</h1><p>Manage student enrollment records.</p></div>
  <button class="btn btn-primary" onclick="document.getElementById('addM').classList.add('open')">
    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Student
  </button>
</div>
<?php if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert alert-error"><?=htmlspecialchars($err)?></div><?php endif;?>
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
<thead><tr><th>#</th><th>LRN</th><th>Name</th><th>Gender</th><th>Grade</th><th>School Year</th></tr></thead>
<tbody>
<?php if(empty($students)):?><tr><td colspan="6"><div class="empty-state"><p>No students found.</p></div></td></tr>
<?php else:foreach($students as $i=>$s):?>
<tr>
  <td style="color:#9ca3af;font-size:13px;"><?=$i+1?></td>
  <td style="font-size:13px;color:#6b7280;"><?=htmlspecialchars($s['lrn']??'—')?></td>
  <td><strong><?=htmlspecialchars($s['last_name'].', '.$s['first_name'].($s['middle_name']?' '.$s['middle_name']:''))?></strong></td>
  <td><?=ucfirst($s['gender'])?></td>
  <td><span class="badge staff"><?=htmlspecialchars($s['grade_name'])?></span></td>
  <td style="font-size:13px;"><?=htmlspecialchars($s['sy_label'])?></td>
</tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
</div>
<?php include '../includes/footer.php';?>
</div></div>
<div class="modal-overlay" id="addM">
<div class="modal"><div class="modal-header"><h3>Add New Student</h3>
<button class="modal-close" onclick="document.getElementById('addM').classList.remove('open')">
<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button></div>
<div class="modal-body"><form method="POST">
<input type="hidden" name="add_student" value="1"/>
<div class="form-row">
  <div class="form-group"><label class="form-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" required/></div>
  <div class="form-group"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control"/></div>
</div>
<div class="form-group"><label class="form-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" required/></div>
<div class="form-row">
  <div class="form-group"><label class="form-label">LRN</label><input type="text" name="lrn" class="form-control" maxlength="12"/></div>
  <div class="form-group"><label class="form-label">Gender <span class="req">*</span></label><select name="gender" class="form-control" required><option value="">Select</option><option value="male">Male</option><option value="female">Female</option></select></div>
</div>
<div class="form-row">
  <div class="form-group"><label class="form-label">Grade Level <span class="req">*</span></label><select name="grade_id" class="form-control" required><option value="">Select</option><?php foreach($grades as $g):?><option value="<?=$g['grade_id']?>"><?=htmlspecialchars($g['grade_name'])?></option><?php endforeach;?></select></div>
  <div class="form-group"><label class="form-label">School Year <span class="req">*</span></label><select name="sy_id" class="form-control" required><option value="">Select</option><?php foreach($syears as $sy):?><option value="<?=$sy['sy_id']?>" <?=$sy['is_active']?'selected':''?>><?=htmlspecialchars($sy['sy_label'])?></option><?php endforeach;?></select></div>
</div>
<div class="modal-footer" style="padding:0;margin-top:8px;">
  <button type="button" class="btn btn-outline" onclick="document.getElementById('addM').classList.remove('open')">Cancel</button>
  <button type="submit" class="btn btn-primary">Add Student</button>
</div></form></div></div></div>
<script>function filterTable(q){q=q.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}</script>
</body></html>

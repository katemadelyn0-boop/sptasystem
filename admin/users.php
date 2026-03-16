<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('admin');
$db = getDB();
$msg = $err = '';

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid=$_GET['toggle'];
    $s=$db->prepare("UPDATE users SET is_active=!is_active WHERE user_id=? AND role!='admin'");
    $s->bind_param('i',$uid);$s->execute();$s->close();
    logAudit('TOGGLE_USER','users',$uid);
    $msg='User status updated.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_role'])) {
    $uid=(int)$_POST['user_id']; $role=$_POST['role'];
    if (in_array($role,['staff','spta_officer','parent'])) {
        $s=$db->prepare("UPDATE users SET role=? WHERE user_id=? AND role!='admin'");
        $s->bind_param('si',$role,$uid);$s->execute();$s->close();
        $msg='Role updated.';
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_user'])) {
    $fn=trim($_POST['first_name']??''); $ln=trim($_POST['last_name']??'');
    $em=trim($_POST['email']??''); $pw=trim($_POST['password']??'');
    $rl=$_POST['role']??'';
    if($fn && $ln && $em && $pw && in_array($rl,['staff','spta_officer','parent'])) {
        $chk=$db->prepare("SELECT user_id FROM users WHERE email=?");
        $chk->bind_param('s',$em);$chk->execute();$chk->store_result();
        if($chk->num_rows>0) $err='Email already exists.';
        else {
            $hash=password_hash($pw,PASSWORD_BCRYPT);
            $s=$db->prepare("INSERT INTO users(first_name,last_name,email,password,role,is_verified,is_active)VALUES(?,?,?,?,?,1,1)");
            $s->bind_param('sssss',$fn,$ln,$em,$hash,$rl);
            if($s->execute()){logAudit('ADD_USER','users',$s->insert_id);$msg='User added successfully!';}
            else $err='Failed to add user.';
            $s->close();
        }
        $chk->close();
    } else $err='Fill in all required fields.';
}
$search=trim($_GET['search']??''); $filter=$_GET['role']??'';
$sql="SELECT * FROM users WHERE 1=1";
$params=[];$types='';
if($search){$sql.=" AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";$like="%$search%";$params=[$like,$like,$like];$types.='sss';}
if($filter){$sql.=" AND role=?";$params[]=$filter;$types.='s';}
$sql.=" ORDER BY created_at DESC";
$s=$db->prepare($sql);
if($params)$s->bind_param($types,...$params);
$s->execute();$users=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>User Accounts — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/header.php'; ?>
<div class="page-body">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>User Accounts</h1><p>Manage all registered users and their roles.</p></div>
  <button class="btn btn-primary" onclick="document.getElementById('addUserM').classList.add('open')">
    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add User
  </button>
</div>
<?php if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="toolbar">
  <div class="search-wrap">
    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    <input class="search-input" placeholder="Search name or email..." value="<?=htmlspecialchars($search)?>" onkeyup="filterTable(this.value)"/>
  </div>
  <select class="form-control" style="width:auto;padding:9px 14px;" onchange="window.location='?role='+this.value">
    <option value="">All Roles</option>
    <option value="staff" <?=$filter==='staff'?'selected':''?>>Staff</option>
    <option value="spta_officer" <?=$filter==='spta_officer'?'selected':''?>>SPTA Officer</option>
    <option value="parent" <?=$filter==='parent'?'selected':''?>>Parent</option>
    <option value="admin" <?=$filter==='admin'?'selected':''?>>Admin</option>
  </select>
</div>
<div class="card" style="padding:0;overflow:hidden;">
<div class="table-wrap"><table id="tbl">
<thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead>
<tbody>
<?php if(empty($users)):?><tr><td colspan="7"><div class="empty-state"><p>No users found.</p></div></td></tr>
<?php else: foreach($users as $u):?>
<tr>
  <td><div style="display:flex;align-items:center;gap:10px;"><div style="width:34px;height:34px;border-radius:50%;background:#0f2342;display:flex;align-items:center;justify-content:center;color:#e8a020;font-weight:800;font-size:13px;"><?=strtoupper(substr($u['first_name'],0,1))?></div><strong><?=htmlspecialchars($u['first_name'].' '.$u['last_name'])?></strong></div></td>
  <td style="font-size:13px;color:#6b7280;"><?=htmlspecialchars($u['email'])?></td>
  <td><?php if($u['role']!=='admin'):?><form method="POST" style="display:inline;"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"/><input type="hidden" name="update_role" value="1"/><select name="role" class="form-control" style="padding:5px 10px;font-size:13px;width:auto;" onchange="this.form.submit()"><option value="staff" <?=$u['role']==='staff'?'selected':''?>>Staff</option><option value="spta_officer" <?=$u['role']==='spta_officer'?'selected':''?>>SPTA Officer</option><option value="parent" <?=$u['role']==='parent'?'selected':''?>>Parent</option></select></form><?php else:?><span class="badge admin">Admin</span><?php endif;?></td>
  <td><?=$u['is_verified']?'<span style="color:#16a34a;font-size:13px;font-weight:600;">✓ Verified</span>':'<span style="color:#d97706;font-size:13px;font-weight:600;">⏳ Pending</span>'?></td>
  <td><span class="badge <?=$u['is_active']?'active':'inactive'?>"><?=$u['is_active']?'Active':'Inactive'?></span></td>
  <td style="font-size:13px;color:#6b7280;"><?=date('M d, Y',strtotime($u['created_at']))?></td>
  <td><?php if($u['role']!=='admin'):?><a href="?toggle=<?=$u['user_id']?>" class="btn btn-sm <?=$u['is_active']?'btn-danger':'btn-outline'?>" onclick="return confirm('<?=$u['is_active']?'Deactivate':'Activate'?> this user?')"><?=$u['is_active']?'Deactivate':'Activate'?></a><?php endif;?></td>
</tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
</div>
<?php include '../includes/footer.php'; ?>
</div></div>
<!-- Add User Modal -->
<div class="modal-overlay" id="addUserM">
<div class="modal" style="max-width:480px;">
<div class="modal-header"><h3>Add New User</h3>
<button class="modal-close" onclick="document.getElementById('addUserM').classList.remove('open')">
<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button></div>
<div class="modal-body"><form method="POST">
<input type="hidden" name="add_user" value="1"/>
<div class="form-row">
  <div class="form-group"><label class="form-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" required/></div>
  <div class="form-group"><label class="form-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" required/></div>
</div>
<div class="form-group"><label class="form-label">Email <span class="req">*</span></label><input type="email" name="email" class="form-control" required/></div>
<div class="form-group"><label class="form-label">Password <span class="req">*</span></label><input type="password" name="password" class="form-control" placeholder="Min. 8 characters" required/></div>
<div class="form-group"><label class="form-label">Role <span class="req">*</span></label>
  <select name="role" class="form-control" required>
    <option value="">-- Select Role --</option>
    <option value="staff">Staff</option>
    <option value="spta_officer">SPTA Officer</option>
    <option value="parent">Parent</option>
  </select>
</div>
<div class="modal-footer" style="padding:0;margin-top:16px;">
  <button type="button" class="btn btn-outline" onclick="document.getElementById('addUserM').classList.remove('open')">Cancel</button>
  <button type="submit" class="btn btn-primary">Add User</button>
</div>
</form></div></div></div>
<script>function filterTable(q){q=q.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}</script>
</body></html>
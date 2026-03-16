<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('admin');
$db  = getDB();
$msg = $err = '';

// ── Handle POST actions ────────────────────────────────────

// Add School Year
if (isset($_POST['add_sy'])) {
    $label = trim($_POST['sy_label'] ?? '');
    if ($label) {
        $s = $db->prepare("INSERT INTO school_years (sy_label, is_active) VALUES (?, 0)");
        $s->bind_param('s', $label); $s->execute(); $s->close();
        $msg = 'School year added!';
    } else $err = 'School year label is required.';
}

// Set Active School Year
if (isset($_POST['set_active_sy'])) {
    $syid = (int)$_POST['sy_id'];
    $db->query("UPDATE school_years SET is_active=0");
    $s = $db->prepare("UPDATE school_years SET is_active=1 WHERE sy_id=?");
    $s->bind_param('i', $syid); $s->execute(); $s->close();
    $msg = 'Active school year updated!';
}

// Delete School Year
if (isset($_GET['delete_sy'])) {
    $syid = (int)$_GET['delete_sy'];
    $chk = $db->prepare("SELECT COUNT(*) c FROM payment_requirements WHERE sy_id=?");
    $chk->bind_param('i', $syid); $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['c']; $chk->close();
    if ($cnt > 0) $err = 'Cannot delete: school year has payment requirements.';
    else {
        $s = $db->prepare("DELETE FROM school_years WHERE sy_id=? AND is_active=0");
        $s->bind_param('i', $syid); $s->execute(); $s->close();
        $msg = 'School year deleted.';
    }
}

// Add Payment Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description']   ?? '');
    $mgby = $_POST['managed_by'] ?? 'staff';
    if ($name && in_array($mgby, ['staff','spta_officer'])) {
        $s = $db->prepare("INSERT INTO payment_categories (category_name, description, managed_by) VALUES (?,?,?)");
        $s->bind_param('sss', $name, $desc, $mgby); $s->execute(); $s->close();
        $msg = 'Category added!';
    } else $err = 'Category name is required.';
}

// Delete Payment Category
if (isset($_GET['delete_cat'])) {
    $cid = (int)$_GET['delete_cat'];
    $chk = $db->prepare("SELECT COUNT(*) c FROM payment_requirements WHERE category_id=?");
    $chk->bind_param('i', $cid); $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['c']; $chk->close();
    if ($cnt > 0) $err = 'Cannot delete: category has payment requirements.';
    else {
        $s = $db->prepare("DELETE FROM payment_categories WHERE category_id=?");
        $s->bind_param('i', $cid); $s->execute(); $s->close();
        $msg = 'Category deleted.';
    }
}

// Add Payment Requirement
if (isset($_POST['add_requirement'])) {
    $cid  = (int)$_POST['category_id'];
    $syid = (int)$_POST['sy_id'];
    $amt  = (float)$_POST['amount'];
    $desc = trim($_POST['req_description'] ?? '');
    if ($cid && $syid && $amt > 0) {
        // Check duplicate
        $chk = $db->prepare("SELECT COUNT(*) c FROM payment_requirements WHERE category_id=? AND sy_id=?");
        $chk->bind_param('ii', $cid, $syid); $chk->execute();
        $cnt = $chk->get_result()->fetch_assoc()['c']; $chk->close();
        if ($cnt > 0) $err = 'A requirement for this category and school year already exists.';
        else {
            $s = $db->prepare("INSERT INTO payment_requirements (category_id, sy_id, amount, description) VALUES (?,?,?,?)");
            $s->bind_param('iids', $cid, $syid, $amt, $desc); $s->execute(); $s->close();
            $msg = 'Payment requirement added!';
        }
    } else $err = 'Fill in all required fields with a valid amount.';
}

// Delete Payment Requirement
if (isset($_GET['delete_req'])) {
    $rid = (int)$_GET['delete_req'];
    $chk = $db->prepare("SELECT COUNT(*) c FROM payments WHERE requirement_id=?");
    $chk->bind_param('i', $rid); $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['c']; $chk->close();
    if ($cnt > 0) $err = 'Cannot delete: requirement has existing payment records.';
    else {
        $s = $db->prepare("DELETE FROM payment_requirements WHERE requirement_id=?");
        $s->bind_param('i', $rid); $s->execute(); $s->close();
        $msg = 'Requirement deleted.';
    }
}

// ── Fetch data ─────────────────────────────────────────────
$school_years = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetch_all(MYSQLI_ASSOC);
$categories   = $db->query("SELECT * FROM payment_categories ORDER BY managed_by, category_name")->fetch_all(MYSQLI_ASSOC);
$requirements = $db->query("
    SELECT pr.*, pc.category_name, pc.managed_by, sy.sy_label
    FROM payment_requirements pr
    JOIN payment_categories pc ON pr.category_id = pc.category_id
    JOIN school_years sy ON pr.sy_id = sy.sy_id
    ORDER BY sy.sy_id DESC, pc.category_name
")->fetch_all(MYSQLI_ASSOC);
$active_sy = $db->query("SELECT * FROM school_years WHERE is_active=1 LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Settings — SPTA System</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
  <style>
    .tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid #e5e7eb;padding-bottom:0;}
    .tab-btn{padding:10px 20px;border:none;background:none;font-family:inherit;font-size:14px;font-weight:600;color:#6b7280;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all 0.15s;}
    .tab-btn.active{color:#0f2342;border-bottom-color:#0f2342;}
    .tab-btn:hover{color:#0f2342;}
    .tab-pane{display:none;}.tab-pane.active{display:block;}
    .section-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 8px rgba(0,0,0,0.06);margin-bottom:24px;}
    .section-card h3{font-size:16px;font-weight:700;color:#0f2342;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .badge-managed{font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;}
    .badge-managed.staff{background:#dbeafe;color:#1d4ed8;}
    .badge-managed.spta_officer{background:#fef3c7;color:#d97706;}
    .active-badge{background:#dcfce7;color:#16a34a;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:700;}
    .delete-btn{color:#dc2626;background:none;border:none;cursor:pointer;font-size:12px;font-weight:600;padding:4px 8px;border-radius:6px;transition:background 0.15s;}
    .delete-btn:hover{background:#fef2f2;}
    .req-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;}
    .req-card{background:#f8faff;border:1px solid #dbeafe;border-radius:12px;padding:16px;}
    .req-card .req-title{font-size:14px;font-weight:700;color:#0f2342;margin-bottom:4px;}
    .req-card .req-meta{font-size:12px;color:#6b7280;margin-bottom:8px;}
    .req-card .req-amount{font-size:20px;font-weight:800;color:#0f2342;}
  </style>
</head>
<body>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/header.php'; ?>
<div class="page-body">

<div class="page-header">
  <h1>System Settings</h1>
  <p>Manage school years, payment categories, and requirements.</p>
</div>

<?php if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert alert-error"><?=htmlspecialchars($err)?></div><?php endif;?>

<!-- Tabs -->
<div class="tabs">
  <button class="tab-btn active" onclick="switchTab('sy')">🗓️ School Years</button>
  <button class="tab-btn" onclick="switchTab('cat')">📂 Payment Categories</button>
  <button class="tab-btn" onclick="switchTab('req')">💰 Payment Requirements</button>
</div>

<!-- ── TAB 1: School Years ── -->
<div class="tab-pane active" id="tab-sy">
  <div class="section-card">
    <h3>
      <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
      School Years
      <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="document.getElementById('addSyM').classList.add('open')">+ Add</button>
    </h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>School Year</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($school_years)):?>
          <tr><td colspan="3"><div class="empty-state"><p>No school years found.</p></div></td></tr>
        <?php else: foreach($school_years as $sy):?>
        <tr>
          <td><strong><?=htmlspecialchars($sy['sy_label'])?></strong></td>
          <td><?=$sy['is_active']?'<span class="active-badge">✓ Active</span>':'<span style="color:#9ca3af;font-size:13px;">Inactive</span>'?></td>
          <td style="display:flex;gap:8px;align-items:center;">
            <?php if(!$sy['is_active']):?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="sy_id" value="<?=$sy['sy_id']?>"/>
              <button type="submit" name="set_active_sy" class="btn btn-outline btn-sm">Set Active</button>
            </form>
            <a href="?delete_sy=<?=$sy['sy_id']?>" class="delete-btn" onclick="return confirm('Delete this school year?')">Delete</a>
            <?php else:?>
            <span style="font-size:12px;color:#9ca3af;">Current</span>
            <?php endif;?>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── TAB 2: Payment Categories ── -->
<div class="tab-pane" id="tab-cat">
  <div class="section-card">
    <h3>
      <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2l-5.5 9h11L12 2zm0 3.84L13.93 9h-3.87L12 5.84zM17.5 13c-2.49 0-4.5 2.01-4.5 4.5s2.01 4.5 4.5 4.5 4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5zm0 7c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zM3 21.5h8v-8H3v8zm2-6h4v4H5v-4z"/></svg>
      Payment Categories
      <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="document.getElementById('addCatM').classList.add('open')">+ Add</button>
    </h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Category Name</th><th>Description</th><th>Managed By</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($categories)):?>
          <tr><td colspan="4"><div class="empty-state"><p>No categories yet.</p></div></td></tr>
        <?php else: foreach($categories as $cat):?>
        <tr>
          <td><strong><?=htmlspecialchars($cat['category_name'])?></strong></td>
          <td style="font-size:13px;color:#6b7280;"><?=htmlspecialchars($cat['description']??'—')?></td>
          <td><span class="badge-managed <?=$cat['managed_by']?>"><?=$cat['managed_by']==='spta_officer'?'SPTA Officer':'Staff'?></span></td>
          <td><a href="?delete_cat=<?=$cat['category_id']?>" class="delete-btn" onclick="return confirm('Delete this category?')">Delete</a></td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── TAB 3: Payment Requirements ── -->
<div class="tab-pane" id="tab-req">
  <div class="section-card">
    <h3>
      <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
      Payment Requirements
      <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="document.getElementById('addReqM').classList.add('open')">+ Add</button>
    </h3>
    <?php if(!$active_sy):?>
    <div class="alert alert-warning" style="margin-bottom:16px;">⚠️ No active school year set! Please set one in the School Years tab first.</div>
    <?php endif;?>
    <?php if(empty($requirements)):?>
    <div class="empty-state"><p>No payment requirements yet. Add one to enable payment recording.</p></div>
    <?php else:?>
    <div class="req-grid">
      <?php foreach($requirements as $req):?>
      <div class="req-card">
        <div class="req-title"><?=htmlspecialchars($req['category_name'])?></div>
        <div class="req-meta">
          <?=htmlspecialchars($req['sy_label'])?> &bull;
          <span class="badge-managed <?=$req['managed_by']?>"><?=$req['managed_by']==='spta_officer'?'SPTA Officer':'Staff'?></span>
        </div>
        <?php if($req['description']):?>
        <div style="font-size:12px;color:#6b7280;margin-bottom:8px;"><?=htmlspecialchars($req['description'])?></div>
        <?php endif;?>
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <div class="req-amount">&#8369;<?=number_format($req['amount'],2)?></div>
          <a href="?delete_req=<?=$req['requirement_id']?>&tab=req" class="delete-btn" onclick="return confirm('Delete this requirement?')">Delete</a>
        </div>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<!-- Modal: Add School Year -->
<div class="modal-overlay" id="addSyM">
<div class="modal" style="max-width:400px;">
<div class="modal-header"><h3>Add School Year</h3>
<button class="modal-close" onclick="document.getElementById('addSyM').classList.remove('open')">
<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button></div>
<div class="modal-body"><form method="POST">
<div class="form-group">
  <label class="form-label">School Year Label <span class="req">*</span></label>
  <input type="text" name="sy_label" class="form-control" placeholder="e.g. 2025-2026" required/>
  <small style="color:#9ca3af;font-size:12px;">Format: YYYY-YYYY</small>
</div>
<div class="modal-footer" style="padding:0;margin-top:16px;">
  <button type="button" class="btn btn-outline" onclick="document.getElementById('addSyM').classList.remove('open')">Cancel</button>
  <button type="submit" name="add_sy" class="btn btn-primary">Add School Year</button>
</div>
</form></div></div></div>

<!-- Modal: Add Category -->
<div class="modal-overlay" id="addCatM">
<div class="modal" style="max-width:460px;">
<div class="modal-header"><h3>Add Payment Category</h3>
<button class="modal-close" onclick="document.getElementById('addCatM').classList.remove('open')">
<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button></div>
<div class="modal-body"><form method="POST">
<div class="form-group">
  <label class="form-label">Category Name <span class="req">*</span></label>
  <input type="text" name="category_name" class="form-control" placeholder="e.g. SPTA Fee, Miscellaneous" required/>
</div>
<div class="form-group">
  <label class="form-label">Description</label>
  <input type="text" name="description" class="form-control" placeholder="Optional description"/>
</div>
<div class="form-group">
  <label class="form-label">Managed By <span class="req">*</span></label>
  <select name="managed_by" class="form-control" required>
    <option value="staff">Staff (School Fees)</option>
    <option value="spta_officer">SPTA Officer (SPTA Fees)</option>
  </select>
</div>
<div class="modal-footer" style="padding:0;margin-top:16px;">
  <button type="button" class="btn btn-outline" onclick="document.getElementById('addCatM').classList.remove('open')">Cancel</button>
  <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
</div>
</form></div></div></div>

<!-- Modal: Add Requirement -->
<div class="modal-overlay" id="addReqM">
<div class="modal" style="max-width:460px;">
<div class="modal-header"><h3>Add Payment Requirement</h3>
<button class="modal-close" onclick="document.getElementById('addReqM').classList.remove('open')">
<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button></div>
<div class="modal-body"><form method="POST">
<div class="form-group">
  <label class="form-label">Payment Category <span class="req">*</span></label>
  <select name="category_id" class="form-control" required>
    <option value="">-- Select Category --</option>
    <?php foreach($categories as $cat):?>
    <option value="<?=$cat['category_id']?>"><?=htmlspecialchars($cat['category_name'])?> (<?=$cat['managed_by']==='spta_officer'?'SPTA Officer':'Staff'?>)</option>
    <?php endforeach;?>
  </select>
</div>
<div class="form-group">
  <label class="form-label">School Year <span class="req">*</span></label>
  <select name="sy_id" class="form-control" required>
    <option value="">-- Select School Year --</option>
    <?php foreach($school_years as $sy):?>
    <option value="<?=$sy['sy_id']?>" <?=$sy['is_active']?'selected':''?>><?=htmlspecialchars($sy['sy_label'])?><?=$sy['is_active']?' (Active)':''?></option>
    <?php endforeach;?>
  </select>
</div>
<div class="form-group">
  <label class="form-label">Amount (₱) <span class="req">*</span></label>
  <input type="number" name="amount" class="form-control" step="0.01" min="1" placeholder="e.g. 150.00" required/>
</div>
<div class="form-group">
  <label class="form-label">Description <span style="font-weight:400;color:#9ca3af;">(optional)</span></label>
  <input type="text" name="req_description" class="form-control" placeholder="e.g. Annual SPTA membership fee"/>
</div>
<div class="modal-footer" style="padding:0;margin-top:16px;">
  <button type="button" class="btn btn-outline" onclick="document.getElementById('addReqM').classList.remove('open')">Cancel</button>
  <button type="submit" name="add_requirement" class="btn btn-primary">Add Requirement</button>
</div>
</form></div></div></div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  event.target.classList.add('active');
}
// Auto-open tab from URL
<?php
$tab = $_GET['tab'] ?? 'sy';
if (!in_array($tab, ['sy','cat','req'])) $tab = 'sy';
$tabMap = ['sy' => 0, 'cat' => 1, 'req' => 2];
$tabIdx = $tabMap[$tab];
?>
(function(){
  var btns = document.querySelectorAll('.tab-btn');
  var panes = document.querySelectorAll('.tab-pane');
  btns.forEach(b => b.classList.remove('active'));
  panes.forEach(p => p.classList.remove('active'));
  btns[<?=$tabIdx?>].classList.add('active');
  panes[<?=$tabIdx?>].classList.add('active');
})();
</script>
</body>
</html>
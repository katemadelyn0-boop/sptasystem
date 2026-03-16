<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('staff','spta_officer','admin');
$db   = getDB();
$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'];
$msg  = $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['record_payment'])) {
    $sid  = (int)$_POST['student_id'];
    $rid  = (int)$_POST['requirement_id'];
    $amt  = (float)$_POST['amount_paid'];
    $date = $_POST['payment_date'];
    $meth = $_POST['payment_method'];
    $ref  = trim($_POST['reference_no']??'')?:null;
    $rem  = trim($_POST['remarks']??'')?:null;
    $stat = $_POST['status'];
    $proof= null;
    if (!empty($_FILES['proof_image']['name'])) {
        $ext=$_FILES['proof_image']['name']; $ext=pathinfo($ext,PATHINFO_EXTENSION);
        $fname='proof_'.time().'_'.rand(100,999).'.'.$ext;
        $dest=$_SERVER['DOCUMENT_ROOT'].'/spta-system/assets/uploads/'.$fname;
        if(move_uploaded_file($_FILES['proof_image']['tmp_name'],$dest)) $proof=$fname;
    }
    $s=$db->prepare("INSERT INTO payments(student_id,requirement_id,amount_paid,payment_method,payment_date,status,reference_no,proof_image,remarks,recorded_by)VALUES(?,?,?,?,?,?,?,?,?,?)");
    $s->bind_param('iidssssssi',$sid,$rid,$amt,$meth,$date,$stat,$ref,$proof,$rem,$uid);
    if($s->execute()){
        $pid=$s->insert_id;
        if(in_array($stat,['paid','partial'])){
            $rno='SPTA-'.date('Y').'-'.str_pad($pid,5,'0',STR_PAD_LEFT);
            $sr=$db->prepare("SELECT CONCAT(first_name,' ',last_name) n FROM students WHERE student_id=?");
            $sr->bind_param('i',$sid);$sr->execute();$it=$sr->get_result()->fetch_assoc()['n'];$sr->close();
            $ri=$db->prepare("INSERT INTO receipts(payment_id,receipt_no,issued_to,issued_by)VALUES(?,?,?,?)");
            $ri->bind_param('issi',$pid,$rno,$it,$uid);$ri->execute();$ri->close();
        }
        logAudit('RECORD_PAYMENT','payments',$pid);
        // Send email confirmation
        try {
            require_once '../config/mailer.php';
            $pq=$db->prepare("SELECT u.email,CONCAT(u.first_name,' ',u.last_name) pname FROM parent_student ps JOIN users u ON ps.parent_id=u.user_id WHERE ps.student_id=? AND u.is_active=1");
            $pq->bind_param('i',$sid);$pq->execute();$parents=$pq->get_result()->fetch_all(MYSQLI_ASSOC);$pq->close();
            if(!empty($parents)){
                $pdq=$db->prepare("SELECT p.*,r.receipt_id,r.receipt_no,CONCAT(s.first_name,' ',s.last_name) student_name,pc.category_name FROM payments p JOIN students s ON p.student_id=s.student_id JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id LEFT JOIN receipts r ON p.payment_id=r.payment_id WHERE p.payment_id=?");
                $pdq->bind_param('i',$pid);$pdq->execute();$pd=$pdq->get_result()->fetch_assoc();$pdq->close();
                foreach($parents as $par){ sendPaymentConfirmationEmail($par['email'],$par['pname'],$pd); }
            }
        } catch(Exception $e){ error_log('Email err:'.$e->getMessage()); }
        $msg='Payment recorded successfully!';
    } else $err='Failed: '.$db->error;
    $s->close();
}

$students=$db->query("SELECT student_id,first_name,last_name FROM students WHERE is_active=1 ORDER BY last_name")->fetch_all(MYSQLI_ASSOC);
$cat_filter=($role==='spta_officer')?"AND pc.managed_by='spta_officer'":"AND pc.managed_by='staff'";
$reqs=$db->query("SELECT pr.requirement_id,pr.amount,pc.category_name,sy.sy_label FROM payment_requirements pr JOIN payment_categories pc ON pr.category_id=pc.category_id JOIN school_years sy ON pr.sy_id=sy.sy_id WHERE sy.is_active=1 $cat_filter ORDER BY pc.category_name")->fetch_all(MYSQLI_ASSOC);

$sf=$_GET['status']??'';$search=trim($_GET['search']??'');
$sql="SELECT p.*,CONCAT(s.first_name,' ',s.last_name) sname,g.grade_name,pc.category_name,r.receipt_id,r.receipt_no,CONCAT(u.first_name,' ',u.last_name) rby FROM payments p JOIN students s ON p.student_id=s.student_id JOIN grade_levels g ON s.grade_id=g.grade_id JOIN payment_requirements pr ON p.requirement_id=pr.requirement_id JOIN payment_categories pc ON pr.category_id=pc.category_id JOIN users u ON p.recorded_by=u.user_id LEFT JOIN receipts r ON p.payment_id=r.payment_id WHERE 1=1 $cat_filter";
$p=[];$t='';
if($search){$sql.=" AND(s.first_name LIKE? OR s.last_name LIKE?)";$lk="%$search%";$p=[$lk,$lk];$t='ss';}
if($sf){$sql.=" AND p.status=?";$p[]=$sf;$t.='s';}
$sql.=" ORDER BY p.created_at DESC LIMIT 50";
$s=$db->prepare($sql);if($p)$s->bind_param($t,...$p);$s->execute();
$payments=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
$title=($role==='spta_officer')?'SPTA Payments':'School Fee Payments';
$backlink=($role==='spta_officer')?'/spta-system/officer/payments.php':'/spta-system/staff/payments.php';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $title ?> — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/header.php'; ?>
<div class="page-body">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1><?= $title ?></h1><p>Record and monitor payment transactions.</p></div>
  <button class="btn btn-primary" onclick="document.getElementById('payM').classList.add('open')">
    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Record Payment
  </button>
</div>
<?php if($msg):?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif;?>
<?php if($err):?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif;?>
<div class="toolbar">
  <div class="search-wrap">
    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    <input class="search-input" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable(this.value)"/>
  </div>
  <select class="form-control" style="width:auto;padding:9px 14px;" onchange="window.location='?status='+this.value">
    <option value="">All Status</option>
    <option value="paid" <?=$sf==='paid'?'selected':''?>>Paid</option>
    <option value="unpaid" <?=$sf==='unpaid'?'selected':''?>>Unpaid</option>
    <option value="partial" <?=$sf==='partial'?'selected':''?>>Partial</option>
    <option value="overdue" <?=$sf==='overdue'?'selected':''?>>Overdue</option>
  </select>
</div>
<div class="card" style="padding:0;overflow:hidden;"><div class="table-wrap"><table id="tbl">
<thead><tr><th>Student</th><th>Grade</th><th>Category</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Receipt</th><th>By</th><th></th></tr></thead>
<tbody>
<?php if(empty($payments)):?><tr><td colspan="9"><div class="empty-state"><p>No payments yet.</p></div></td></tr>
<?php else:foreach($payments as $p):?>
<tr>
  <td><strong><?= htmlspecialchars($p['sname']) ?></strong></td>
  <td style="font-size:13px;"><?= htmlspecialchars($p['grade_name']) ?></td>
  <td style="font-size:13px;"><?= htmlspecialchars($p['category_name']) ?></td>
  <td><strong>&#8369;<?= number_format($p['amount_paid'],2) ?></strong></td>
  <td style="font-size:13px;"><?= ucfirst(str_replace('_',' ',$p['payment_method'])) ?></td>
  <td style="font-size:13px;"><?= date('M d, Y',strtotime($p['payment_date'])) ?></td>
  <td><span class="badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
  <td style="font-size:13px;font-weight:600;color:#0f2342;"><?= htmlspecialchars($p['receipt_no']??'—') ?></td>
  <td style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($p['rby']) ?></td>
  <td><?php if($p['receipt_id']):?><a href="/spta-system/receipt.php?id=<?= $p['receipt_id'] ?>" target="_blank" class="btn btn-outline btn-sm" style="white-space:nowrap;">🖨️ View</a><?php else:?>—<?php endif;?></td>
</tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
</div>
<?php include '../includes/footer.php'; ?>
</div></div>

<!-- Record Payment Modal -->
<div class="modal-overlay" id="payM">
<div class="modal" style="max-width:560px;">
<div class="modal-header"><h3>Record Payment</h3>
<button class="modal-close" onclick="document.getElementById('payM').classList.remove('open')">
<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button></div>
<div class="modal-body"><form method="POST" enctype="multipart/form-data">
<input type="hidden" name="record_payment" value="1"/>
<div class="form-group"><label class="form-label">Student <span class="req">*</span></label>
<select name="student_id" class="form-control" required><option value="">-- Select Student --</option>
<?php foreach($students as $s):?><option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['last_name'].', '.$s['first_name']) ?></option><?php endforeach;?>
</select></div>
<div class="form-group"><label class="form-label">Payment For <span class="req">*</span></label>
<select name="requirement_id" id="reqSel" class="form-control" required><option value="">-- Select Requirement --</option>
<?php foreach($reqs as $r):?><option value="<?= $r['requirement_id'] ?>" data-amount="<?= $r['amount'] ?>"><?= htmlspecialchars($r['category_name'].' — '.$r['sy_label'].' (₱'.number_format($r['amount'],2).')') ?></option><?php endforeach;?>
</select></div>
<div class="form-row">
  <div class="form-group"><label class="form-label">Amount Paid <span class="req">*</span></label><input type="number" name="amount_paid" id="amtField" class="form-control" step="0.01" min="0" required/></div>
  <div class="form-group"><label class="form-label">Payment Date <span class="req">*</span></label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required/></div>
</div>
<div class="form-row">
  <div class="form-group"><label class="form-label">Method <span class="req">*</span></label><select name="payment_method" class="form-control" onchange="toggleProof(this.value)" required><option value="cash">Cash</option><option value="gcash">GCash</option><option value="maya">Maya</option><option value="bank_transfer">Bank Transfer</option></select></div>
  <div class="form-group"><label class="form-label">Status <span class="req">*</span></label><select name="status" class="form-control" required><option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option></select></div>
</div>
<div class="form-group" id="refField"><label class="form-label">Reference No.</label><input type="text" name="reference_no" class="form-control" placeholder="e.g. GCash reference number"/></div>
<div class="form-group" id="proofField" style="display:none;"><label class="form-label">Proof of Payment</label><input type="file" name="proof_image" class="form-control" accept="image/*"/></div>
<div class="form-group"><label class="form-label">Remarks</label><input type="text" name="remarks" class="form-control" placeholder="Optional notes..."/></div>
<div class="modal-footer" style="padding:0;margin-top:8px;">
  <button type="button" class="btn btn-outline" onclick="document.getElementById('payM').classList.remove('open')">Cancel</button>
  <button type="submit" class="btn btn-primary">Record Payment</button>
</div></form></div></div></div>

<script>
function filterTable(q){q=q.toLowerCase();document.querySelectorAll('#tbl tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
function toggleProof(m){var o=['gcash','maya','bank_transfer'];document.getElementById('proofField').style.display=o.includes(m)?'block':'none';document.getElementById('refField').style.display=o.includes(m)?'block':'none';}
document.getElementById('reqSel').addEventListener('change',function(){var o=this.options[this.selectedIndex];if(o.dataset.amount)document.getElementById('amtField').value=o.dataset.amount;});
</script>
</body></html>
<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole('parent');
$db=$db=getDB();$uid=$_SESSION['user_id'];
if(isset($_GET['mark_read'])){
    $s=$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $s->bind_param('i',$uid);$s->execute();$s->close();
    header('Location: /spta-system/parent/notifications.php');exit;
}
$s=$db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY sent_at DESC");
$s->bind_param('i',$uid);$s->execute();$notifs=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
$unread=count(array_filter($notifs,fn($n)=>!$n['is_read']));
$icons=['reminder'=>'🔔','overdue'=>'⚠️','confirmation'=>'✅','announcement'=>'📢'];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Notifications — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/spta-system/assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar.php';?>
<div class="main-content"><?php include '../includes/header.php';?>
<div class="page-body">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>Notifications <?php if($unread>0):?><span style="background:#dc2626;color:#fff;font-size:14px;padding:2px 10px;border-radius:100px;margin-left:8px;"><?=$unread?></span><?php endif;?></h1><p>Payment reminders and announcements.</p></div>
  <?php if($unread>0):?><a href="?mark_read=1" class="btn btn-outline btn-sm">Mark All as Read</a><?php endif;?>
</div>
<?php if(empty($notifs)):?>
<div class="card" style="text-align:center;padding:60px;"><div style="font-size:48px;margin-bottom:12px;">🔔</div><h3 style="color:#0f2342;margin-bottom:8px;">No notifications yet</h3><p style="color:#6b7280;font-size:14px;">You'll receive reminders here when payments are due or confirmed.</p></div>
<?php else:?>
<div style="display:flex;flex-direction:column;gap:10px;">
<?php foreach($notifs as $n):?>
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;display:flex;gap:14px;<?=!$n['is_read']?'border-left:4px solid #0f2342;background:#f8faff;':''?>">
  <div style="font-size:28px;line-height:1;flex-shrink:0;"><?=$icons[$n['type']]??'🔔'?></div>
  <div style="flex:1;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <div><div style="font-weight:700;color:#0f2342;font-size:15px;"><?=htmlspecialchars($n['title'])?></div><div style="font-size:13px;color:#6b7280;margin-top:2px;"><?=date('M d, Y — g:i A',strtotime($n['sent_at']))?></div></div>
      <span class="badge staff"><?=ucfirst($n['type'])?></span>
    </div>
    <p style="margin-top:10px;font-size:14px;color:#374151;line-height:1.6;"><?=nl2br(htmlspecialchars($n['message']))?></p>
  </div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
</div><?php include '../includes/footer.php';?></div></div>
</body></html>

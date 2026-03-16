<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/mailer.php';
if(isLoggedIn())redirectToDashboard();
$msg=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=strtolower(trim($_POST['email']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){$err='Please enter a valid email.';}
    else{
        $db=getDB();$s=$db->prepare("SELECT user_id,first_name FROM users WHERE email=? AND is_active=1");
        $s->bind_param('s',$email);$s->execute();$user=$s->get_result()->fetch_assoc();$s->close();
        if($user){
            $token=bin2hex(random_bytes(32));$exp=date('Y-m-d H:i:s',strtotime('+1 hour'));
            $u=$db->prepare("UPDATE users SET reset_token=?,reset_expires=? WHERE user_id=?");
            $u->bind_param('ssi',$token,$exp,$user['user_id']);$u->execute();$u->close();
            sendPasswordResetEmail($email,$user['first_name'],$token);
        }
        $msg='If that email is registered, a reset link has been sent.';
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Forgot Password — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#fdf8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
.card{background:#fff;border-radius:20px;box-shadow:0 4px 40px rgba(15,35,66,.1);padding:48px 44px;max-width:420px;width:100%;}
.ico{width:60px;height:60px;background:#0f2342;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;}
.ico svg{width:28px;height:28px;fill:#e8a020;}
h2{font-size:22px;font-weight:700;color:#0f2342;text-align:center;margin-bottom:6px;}
p.sub{text-align:center;font-size:14px;color:#6b7280;margin-bottom:28px;}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px;}
.wrap{position:relative;}
.wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;width:15px;height:15px;}
input{width:100%;padding:11px 13px 11px 40px;border:1.5px solid #d1d5db;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f3f4f6;}
input:focus{border-color:#0f2342;background:#fff;box-shadow:0 0 0 3px rgba(15,35,66,.07);}
.btn{display:block;width:100%;padding:13px;background:#0f2342;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:inherit;cursor:pointer;margin-top:20px;}
.btn:hover{background:#1a3560;}
.alert-s{background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;color:#16a34a;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;}
.alert-e{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;color:#dc2626;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;}
.back{display:block;text-align:center;margin-top:20px;font-size:14px;color:#0f2342;font-weight:600;text-decoration:none;}
.back:hover{text-decoration:underline;}
</style></head><body>
<div class="card">
  <div class="ico"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></div>
  <h2>Forgot Password?</h2>
  <p class="sub">Enter your email to receive a reset link.</p>
  <?php if($msg):?><div class="alert-s"><?=htmlspecialchars($msg)?></div><?php endif;?>
  <?php if($err):?><div class="alert-e"><?=htmlspecialchars($err)?></div><?php endif;?>
  <?php if(!$msg):?>
  <form method="POST">
    <label>Email Address</label>
    <div class="wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
      <input type="email" name="email" placeholder="your@email.com" required/>
    </div>
    <button type="submit" class="btn">Send Reset Link</button>
  </form>
  <?php endif;?>
  <a href="/spta-system/login.php" class="back">← Back to Login</a>
</div>
</body></html>

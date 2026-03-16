<?php
require_once 'config/db.php';
require_once 'config/auth.php';
if (isLoggedIn()) redirectToDashboard();

$err = $msg = '';
$token = trim($_GET['token'] ?? '');

if (!$token) { header('Location: /spta-system/forgot_password.php'); exit; }

$db   = getDB();
$stmt = $db->prepare("SELECT user_id, first_name FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1 LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) $err = 'This reset link is invalid or has expired. Please request a new one.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (strlen($pass) < 8) {
        $err = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $err = 'Passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
        $u = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $u->bind_param('si', $hash, $user['user_id']);
        $u->execute(); $u->close();
        $msg = 'Password reset successfully! You can now log in.';
        $user = null;
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Reset Password — SPTA System</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#fdf8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
.card{background:#fff;border-radius:20px;box-shadow:0 4px 40px rgba(15,35,66,.1);padding:48px 44px;max-width:420px;width:100%;}
.ico{width:60px;height:60px;background:#0f2342;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;}
.ico svg{width:28px;height:28px;fill:#e8a020;}
h2{font-size:22px;font-weight:700;color:#0f2342;text-align:center;margin-bottom:6px;}
p.sub{text-align:center;font-size:14px;color:#6b7280;margin-bottom:28px;}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px;margin-top:16px;}
.wrap{position:relative;}
.wrap svg.icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;width:15px;height:15px;}
input[type=password],input[type=text]{width:100%;padding:11px 42px;border:1.5px solid #d1d5db;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f3f4f6;}
input:focus{border-color:#0f2342;background:#fff;box-shadow:0 0 0 3px rgba(15,35,66,.07);}
.toggle-pass{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;}
.btn{display:block;width:100%;padding:13px;background:#0f2342;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:inherit;cursor:pointer;margin-top:24px;text-align:center;text-decoration:none;}
.btn:hover{background:#1a3560;}
.alert-s{background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;color:#16a34a;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;}
.alert-e{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;color:#dc2626;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;}
.back{display:block;text-align:center;margin-top:20px;font-size:14px;color:#0f2342;font-weight:600;text-decoration:none;}
.back:hover{text-decoration:underline;}
</style></head><body>
<div class="card">
  <div class="ico"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></div>
  <h2>Reset Password</h2>
  <p class="sub">Enter your new password below.</p>
  <?php if($msg): ?>
    <div class="alert-s"><?=htmlspecialchars($msg)?></div>
    <a href="/spta-system/login.php" class="btn">Go to Login →</a>
  <?php elseif($err && !$user): ?>
    <div class="alert-e"><?=htmlspecialchars($err)?></div>
    <a href="/spta-system/forgot_password.php" class="back">← Request a new reset link</a>
  <?php else: ?>
    <?php if($err):?><div class="alert-e"><?=htmlspecialchars($err)?></div><?php endif;?>
    <form method="POST">
      <label>New Password</label>
      <div class="wrap">
        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
        <input type="password" name="password" id="p1" placeholder="Minimum 8 characters" required/>
        <button type="button" class="toggle-pass" onclick="tp('p1')">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        </button>
      </div>
      <label>Confirm New Password</label>
      <div class="wrap">
        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
        <input type="password" name="password2" id="p2" placeholder="Re-enter password" required/>
        <button type="button" class="toggle-pass" onclick="tp('p2')">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        </button>
      </div>
      <button type="submit" class="btn">Reset Password</button>
    </form>
    <a href="/spta-system/login.php" class="back">← Back to Login</a>
  <?php endif; ?>
</div>
<script>function tp(id){var f=document.getElementById(id);f.type=f.type==='text'?'password':'text';}</script>
</body></html>

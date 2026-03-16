<?php
require_once 'config/db.php';
require_once 'config/auth.php';
if (isLoggedIn()) redirectToDashboard();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $db    = getDB();
    $stmt  = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($pass, $user['password'])) {
        if (!$user['is_verified']) {
            $err = 'Please verify your email before logging in.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            logAudit('LOGIN', 'users', $user['user_id']);
            redirectToDashboard();
        }
    } else {
        $err = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — SPTA System</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;display:flex;background:#f3f4f6;}
    .left{width:380px;background:#0f2342;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 40px;flex-shrink:0;}
    .left-logo{width:72px;height:72px;background:#e8a020;border-radius:20px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;}
    .left-logo svg{width:36px;height:36px;fill:#0f2342;}
    .left h1{color:#fff;font-size:22px;font-weight:800;text-align:center;margin-bottom:8px;}
    .left p{color:rgba(255,255,255,0.5);font-size:13px;text-align:center;margin-bottom:40px;}
    .left ul{list-style:none;display:flex;flex-direction:column;gap:14px;width:100%;}
    .left ul li{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.7);font-size:14px;}
    .left ul li::before{content:'✓';background:rgba(232,160,32,0.2);color:#e8a020;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;}
    .left-footer{margin-top:auto;color:rgba(255,255,255,0.3);font-size:12px;text-align:center;}
    .right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
    .card{background:#fff;border-radius:24px;padding:48px 44px;width:100%;max-width:440px;box-shadow:0 4px 40px rgba(0,0,0,0.08);}
    h2{font-size:26px;font-weight:800;color:#0f2342;margin-bottom:6px;}
    .sub{color:#6b7280;font-size:14px;margin-bottom:28px;}
    .alert{padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;color:#dc2626;font-size:14px;margin-bottom:20px;display:flex;gap:8px;align-items:center;}
    .success-alert{background:#f0fdf4;border-color:#bbf7d0;border-left-color:#16a34a;color:#16a34a;}
    label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px;}
    .input-wrap{position:relative;margin-bottom:18px;}
    .input-wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;width:16px;height:16px;}
    .input-wrap input{width:100%;padding:11px 42px;border:1.5px solid #d1d5db;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9fafb;color:#374151;transition:border-color 0.2s,box-shadow 0.2s;}
    .input-wrap input:focus{border-color:#0f2342;background:#fff;box-shadow:0 0 0 3px rgba(15,35,66,0.07);}
    .toggle-pass{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;}
    .toggle-pass:hover{color:#374151;}
    .forgot{display:block;text-align:right;font-size:13px;color:#0f2342;font-weight:600;text-decoration:none;margin-top:-10px;margin-bottom:20px;}
    .forgot:hover{text-decoration:underline;}
    .btn{display:block;width:100%;padding:13px;background:#0f2342;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:background 0.2s;}
    .btn:hover{background:#1a3560;}
    .register{text-align:center;margin-top:20px;font-size:14px;color:#6b7280;}
    .register a{color:#0f2342;font-weight:700;text-decoration:none;}
    .register a:hover{text-decoration:underline;}
    @media(max-width:700px){.left{display:none;}.card{padding:32px 24px;}}
  </style>
</head>
<body>
<div class="left">
  <div class="left-logo"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
  <h1>SPTA Payment System</h1>
  <p>Pawing Central School</p>
  <ul>
    <li>Secure role-based access</li>
    <li>Real-time payment tracking</li>
    <li>Automated parent notifications</li>
    <li>Financial reports &amp; receipts</li>
    <li>Transparent audit trail</li>
  </ul>
  <div class="left-footer">&copy; <?= date('Y') ?> Pawing Central School</div>
</div>
<div class="right">
  <div class="card">
    <h2>Welcome back 👋</h2>
    <p class="sub">Sign in to your account to continue.</p>
    <?php if ($err): ?>
    <div class="alert">
      <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      <?= htmlspecialchars($err) ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['verified'])): ?>
    <div class="alert success-alert">Email verified! You can now log in.</div>
    <?php endif; ?>
    <form method="POST">
      <label>Email Address</label>
      <div class="input-wrap">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        <input type="email" name="email" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>"/>
      </div>
      <label>Password</label>
      <div class="input-wrap" style="margin-bottom:10px;">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
        <input type="password" name="password" id="passField" placeholder="Enter your password" required/>
        <button type="button" class="toggle-pass" id="toggleBtn" onclick="togglePassword()">
          <svg id="eyeIcon" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        </button>
      </div>
      <a href="/spta-system/forgot_password.php" class="forgot">Forgot password?</a>
      <button type="submit" class="btn">Sign In</button>
    </form>
    <p class="register">Don't have an account? <a href="/spta-system/register.php">Register here</a></p>
  </div>
</div>
<script>
function togglePassword() {
  var f = document.getElementById('passField');
  var showing = f.type === 'text';
  f.type = showing ? 'password' : 'text';
  document.getElementById('eyeIcon').outerHTML = showing
    ? '<svg id="eyeIcon" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>'
    : '<svg id="eyeIcon" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
}
</script>
</body>
</html>

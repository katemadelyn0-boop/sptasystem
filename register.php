<?php
require_once 'config/db.php';
require_once 'config/auth.php';
if (isLoggedIn()) redirectToDashboard();

$db  = getDB();
$err = [];
$msg = '';
$data = [];
$step = $_POST['step'] ?? 'form'; // form | verify

// ── STEP 2: Verify OTP ──────────────────────────────────────
if ($step === 'verify') {
    $pending_id = (int)($_POST['pending_id'] ?? 0);
    $otp_input  = trim($_POST['otp_code'] ?? '');

    $s = $db->prepare("SELECT * FROM pending_registrations WHERE id=? AND expires_at > NOW()");
    $s->bind_param('i', $pending_id);
    $s->execute();
    $pending = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$pending) {
        $err['otp'] = 'Verification session expired. Please register again.';
        $step = 'expired';
    } elseif ($otp_input !== $pending['otp_code']) {
        $err['otp'] = 'Invalid verification code. Please try again.';
    } else {
        // Insert user
        $hash = $pending['password_hash'];
        $s = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role, is_verified, is_active) VALUES (?,?,?,?,?,1,1)");
        $s->bind_param('sssss', $pending['first_name'], $pending['last_name'], $pending['email'], $hash, $pending['role']);
        if ($s->execute()) {
            $new_uid = $db->insert_id;
            // Link student if parent
            if ($pending['role'] === 'parent' && $pending['student_id']) {
                $l = $db->prepare("INSERT IGNORE INTO parent_student (parent_id, student_id) VALUES (?,?)");
                $l->bind_param('ii', $new_uid, $pending['student_id']);
                $l->execute(); $l->close();
            }
            // Delete pending
            $db->query("DELETE FROM pending_registrations WHERE id=$pending_id");
            $msg = 'success';
        } else {
            $err['otp'] = 'Registration failed. Please try again.';
        }
        $s->close();
    }
}

// ── STEP 1: Submit Registration Form ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'form') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name']  ?? ''),
        'email'      => strtolower(trim($_POST['email'] ?? '')),
        'role'       => $_POST['role'] ?? '',
        'password'   => $_POST['password'] ?? '',
        'confirm'    => $_POST['confirm']  ?? '',
        // Parent-only fields
        'child_first' => trim($_POST['child_first'] ?? ''),
        'child_last'  => trim($_POST['child_last']  ?? ''),
        'grade_id'    => (int)($_POST['grade_id']   ?? 0),
        'section'     => trim($_POST['section']     ?? ''),
    ];

    // Basic validation
    if (!$data['first_name']) $err['first_name'] = 'First name is required.';
    if (!$data['last_name'])  $err['last_name']  = 'Last name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Please enter a valid email address.';
    if (!in_array($data['role'], ['staff','spta_officer','parent'])) $err['role'] = 'Please select a role.';

    if (in_array($data['role'], ['staff','spta_officer'])) {
        if (!str_ends_with($data['email'], '@deped.gov.ph'))
            $err['email'] = 'Staff/Officers must use a @deped.gov.ph email.';
    }

    if (strlen($data['password']) < 8)           $err['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $data['password'])) $err['password'] = 'Must contain at least one uppercase letter.';
    elseif (!preg_match('/[0-9]/', $data['password'])) $err['password'] = 'Must contain at least one number.';
    if ($data['password'] !== $data['confirm'])  $err['confirm']  = 'Passwords do not match.';

    // Check duplicate email
    if (empty($err['email'])) {
        $chk = $db->prepare("SELECT user_id FROM users WHERE email=?");
        $chk->bind_param('s', $data['email']); $chk->execute();
        if ($chk->get_result()->num_rows > 0) $err['email'] = 'This email is already registered.';
        $chk->close();
    }

    // ── Parent: validate child exists ──────────────────────
    $student_id = null;
    if ($data['role'] === 'parent' && empty($err)) {
        if (!$data['child_first'] || !$data['child_last']) {
            $err['child'] = "Please enter your child's first and last name.";
        } elseif (!$data['grade_id']) {
            $err['grade_id'] = "Please select your child's grade level.";
        } else {
            // Search student
            $sq = $db->prepare("SELECT student_id FROM students WHERE LOWER(first_name)=LOWER(?) AND LOWER(last_name)=LOWER(?) AND grade_id=? AND is_active=1" . ($data["section"] ? " AND LOWER(section)=LOWER(?)" : ""));
            if ($data['section']) {
                $sq->bind_param('ssis', $data['child_first'], $data['child_last'], $data['grade_id'], $data['section']);
            } else {
                $sq->bind_param('ssi', $data['child_first'], $data['child_last'], $data['grade_id']);
            }
            $sq->execute();
            $found = $sq->get_result()->fetch_assoc();
            $sq->close();
            if (!$found) {
                $err['child'] = "We could not find a student matching that name and grade. Please check the details or contact the school.";
            } else {
                $student_id = $found['student_id'];
            }
        }
    }

    // ── If no errors: create OTP and pending registration ──
    if (empty($err)) {
        require_once 'config/mailer.php';
        $otp  = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost'=>10]);
        $exp  = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Delete old pending for this email
        $del = $db->prepare("DELETE FROM pending_registrations WHERE email=?");
        $del->bind_param('s', $data['email']); $del->execute(); $del->close();

        $ins = $db->prepare("INSERT INTO pending_registrations (first_name, last_name, email, password_hash, role, student_id, otp_code, expires_at) VALUES (?,?,?,?,?,?,?,?)");
        $ins->bind_param('sssssiss', $data['first_name'], $data['last_name'], $data['email'], $hash, $data['role'], $student_id, $otp, $exp);

        if ($ins->execute()) {
            $pending_id = $db->insert_id;
            $sent = sendVerificationEmail($data['email'], $data['first_name'], $otp);
            $step = 'otp';
        } else {
            $err['general'] = 'Could not process registration. Please try again.';
        }
        $ins->close();
    }
}

// Load grades for the form
$grades = $db->query("SELECT grade_id, grade_name FROM grade_levels ORDER BY grade_id")->fetch_all(MYSQLI_ASSOC);

function fe($k) { global $data; return htmlspecialchars($data[$k] ?? ''); }
function hasErr($k) { global $err; return isset($err[$k]); }
function showErr($k) { global $err; return isset($err[$k]) ? '<div class="field-err"><svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>'.$err[$k].'</div>' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Register — SPTA System</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;background:#f3f4f6;display:flex;align-items:center;justify-content:center;padding:32px 16px;}
    .card{background:#fff;border-radius:24px;padding:44px;width:100%;max-width:560px;box-shadow:0 4px 40px rgba(0,0,0,0.08);}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
    .brand-ico{width:44px;height:44px;background:#0f2342;border-radius:12px;display:flex;align-items:center;justify-content:center;}
    .brand-ico svg{width:22px;height:22px;fill:#e8a020;}
    .brand-text strong{display:block;font-size:15px;font-weight:800;color:#0f2342;}
    .brand-text span{font-size:12px;color:#9ca3af;}
    h2{font-size:24px;font-weight:800;color:#0f2342;margin-bottom:4px;}
    .sub{font-size:14px;color:#6b7280;margin-bottom:28px;}
    .alert-success{background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;border-radius:12px;padding:28px;text-align:center;}
    .alert-success h3{font-size:20px;font-weight:800;color:#15803d;margin-bottom:8px;}
    .alert-success p{font-size:14px;color:#166534;line-height:1.7;}
    .alert-err{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;color:#dc2626;border-radius:10px;padding:12px 16px;font-size:14px;margin-bottom:20px;display:flex;gap:8px;align-items:center;}
    .section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin:20px 0 12px;padding-bottom:8px;border-bottom:1px solid #e5e7eb;}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .form-group{margin-bottom:16px;}
    label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
    label .req{color:#dc2626;}
    .input-wrap{position:relative;}
    .input-wrap .ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;display:flex;}
    .input-wrap .ico svg{width:16px;height:16px;}
    .input-wrap input,.input-wrap select{width:100%;padding:11px 14px 11px 40px;border:1.5px solid #d1d5db;border-radius:10px;font-size:14px;font-family:inherit;color:#374151;background:#f9fafb;outline:none;transition:border-color 0.2s,box-shadow 0.2s;appearance:none;}
    .input-wrap input:focus,.input-wrap select:focus{border-color:#0f2342;background:#fff;box-shadow:0 0 0 3px rgba(15,35,66,0.07);}
    .input-wrap.has-error input,.input-wrap.has-error select{border-color:#dc2626;background:#fff9f9;}
    .toggle-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#9ca3af;display:flex;align-items:center;border-radius:6px;transition:color 0.15s;}
    .toggle-btn:hover{color:#0f2342;}
    .field-err{font-size:12px;color:#dc2626;margin-top:5px;display:flex;align-items:center;gap:4px;}
    .pass-hints{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
    .hint{font-size:11px;padding:3px 8px;border-radius:100px;background:#f3f4f6;color:#9ca3af;font-weight:500;transition:all 0.2s;}
    .hint.ok{background:#dcfce7;color:#16a34a;}
    .role-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:6px;}
    .role-opt{position:relative;}
    .role-opt input[type=radio]{position:absolute;opacity:0;pointer-events:none;}
    .role-opt label{display:block;padding:12px 10px;border:1.5px solid #e5e7eb;border-radius:12px;text-align:center;cursor:pointer;transition:all 0.15s;margin:0;font-size:13px;font-weight:600;color:#6b7280;}
    .role-opt label .role-ico{display:block;font-size:22px;margin-bottom:4px;}
    .role-opt input:checked+label{border-color:#0f2342;background:#0f2342;color:#fff;}
    .role-note{display:block;font-size:10px;font-weight:400;color:#9ca3af;margin-top:2px;}
    .role-opt input:checked+label .role-note{color:rgba(255,255,255,0.6);}
    .deped-note{display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:13px;color:#2563eb;margin-top:10px;gap:8px;}
    .deped-note.show{display:flex;align-items:center;}
    /* Parent child section */
    #childSection{display:none;background:#f8faff;border:1px solid #dbeafe;border-radius:14px;padding:20px;margin-bottom:16px;}
    #childSection.show{display:block;}
    #childSection .sec-title{font-size:13px;font-weight:700;color:#1d4ed8;margin-bottom:14px;display:flex;align-items:center;gap:6px;}
    /* OTP step */
    .otp-wrap{text-align:center;padding:10px 0;}
    .otp-icon{font-size:52px;margin-bottom:16px;}
    .otp-wrap h3{font-size:20px;font-weight:800;color:#0f2342;margin-bottom:8px;}
    .otp-wrap p{font-size:14px;color:#6b7280;line-height:1.7;margin-bottom:24px;}
    .otp-input-wrap{display:flex;justify-content:center;gap:10px;margin:24px 0;}
    .otp-digit{width:52px;height:62px;text-align:center;font-size:24px;font-weight:800;color:#0f2342;border:2px solid #d1d5db;border-radius:12px;font-family:inherit;outline:none;transition:border-color 0.2s,box-shadow 0.2s;background:#f9fafb;}
    .otp-digit:focus{border-color:#0f2342;background:#fff;box-shadow:0 0 0 3px rgba(15,35,66,0.1);}
    .otp-digit.has-error{border-color:#dc2626;}
    .btn-submit{display:block;width:100%;padding:13px;background:#0f2342;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;margin-top:8px;transition:background 0.2s;}
    .btn-submit:hover{background:#1a3560;}
    .login-link{text-align:center;margin-top:18px;font-size:14px;color:#6b7280;}
    .login-link a{color:#0f2342;font-weight:700;text-decoration:none;}
    @media(max-width:540px){.card{padding:28px 18px;}.form-row,.role-grid{grid-template-columns:1fr;}.otp-digit{width:42px;height:52px;font-size:20px;gap:6px;}}
  </style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-ico"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
    <div class="brand-text"><strong>SPTA Payment System</strong><span>Pawing Central School</span></div>
  </div>

<?php if ($msg === 'success'): ?>
  <div class="alert-success">
    <div style="font-size:48px;margin-bottom:12px;">🎉</div>
    <h3>Account Created!</h3>
    <p>Your account has been verified and activated.<br/>You can now sign in to your account.</p>
    <a href="/spta-system/login.php" style="display:inline-block;margin-top:20px;background:#0f2342;color:#fff;padding:12px 32px;border-radius:10px;font-weight:700;text-decoration:none;font-size:15px;">Go to Login →</a>
  </div>

<?php elseif ($step === 'otp' && empty($err)): ?>
  <!-- OTP VERIFICATION STEP -->
  <div class="otp-wrap">
    <div class="otp-icon">📧</div>
    <h3>Check your email!</h3>
    <p>We sent a <strong>6-digit verification code</strong> to<br/><strong><?= htmlspecialchars($data['email']) ?></strong></p>
    <?php if (isset($err['otp'])): ?><div class="alert-err"><svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg><?= $err['otp'] ?></div><?php endif; ?>
    <form method="POST" id="otpForm">
      <input type="hidden" name="step" value="verify"/>
      <input type="hidden" name="pending_id" value="<?= (int)($pending_id ?? 0) ?>"/>
      <div class="otp-input-wrap" id="otpBoxes">
        <?php for($i=0;$i<6;$i++): ?>
        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp<?=$i?>" autocomplete="off"/>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="otp_code" id="otpHidden"/>
      <button type="submit" class="btn-submit">Verify & Create Account</button>
    </form>
    <p style="margin-top:16px;font-size:13px;color:#9ca3af;">Didn't receive the code? <a href="/spta-system/register.php" style="color:#0f2342;font-weight:600;">Start over</a></p>
  </div>

<?php elseif ($step === 'expired'): ?>
  <div class="alert-err"><?= $err['otp'] ?? 'Session expired.' ?></div>
  <p class="login-link"><a href="/spta-system/register.php">← Register again</a></p>

<?php else: ?>
  <h2>Create Account</h2>
  <p class="sub">Fill in the form below to register.</p>

  <?php if (isset($err['general'])): ?><div class="alert-err"><?= $err['general'] ?></div><?php endif; ?>

  <form method="POST" id="regForm" novalidate>
    <input type="hidden" name="step" value="form"/>

    <!-- Role selection -->
    <div class="form-group">
      <label>I am registering as <span class="req">*</span></label>
      <div class="role-grid">
        <div class="role-opt">
          <input type="radio" name="role" id="r_staff" value="staff" <?= fe('role')==='staff'?'checked':'' ?> onchange="onRoleChange(this.value)"/>
          <label for="r_staff"><span class="role-ico">👩‍💼</span>Staff<span class="role-note">@deped.gov.ph</span></label>
        </div>
        <div class="role-opt">
          <input type="radio" name="role" id="r_officer" value="spta_officer" <?= fe('role')==='spta_officer'?'checked':'' ?> onchange="onRoleChange(this.value)"/>
          <label for="r_officer"><span class="role-ico">🏫</span>SPTA Officer<span class="role-note">@deped.gov.ph</span></label>
        </div>
        <div class="role-opt">
          <input type="radio" name="role" id="r_parent" value="parent" <?= fe('role')==='parent'?'checked':'' ?> onchange="onRoleChange(this.value)"/>
          <label for="r_parent"><span class="role-ico">👨‍👩‍👧</span>Parent<span class="role-note">Any email</span></label>
        </div>
      </div>
      <?= showErr('role') ?>
      <div class="deped-note" id="depedNote">
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" style="flex-shrink:0"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        &nbsp;Staff and SPTA Officers must use a <strong>&nbsp;@deped.gov.ph&nbsp;</strong> email.
      </div>
    </div>

    <!-- Name -->
    <div class="form-row">
      <div class="form-group">
        <label>First Name <span class="req">*</span></label>
        <div class="input-wrap <?= hasErr('first_name')?'has-error':'' ?>">
          <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21.6h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></span>
          <input type="text" name="first_name" value="<?= fe('first_name') ?>" placeholder="Juan" required/>
        </div><?= showErr('first_name') ?>
      </div>
      <div class="form-group">
        <label>Last Name <span class="req">*</span></label>
        <div class="input-wrap <?= hasErr('last_name')?'has-error':'' ?>">
          <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21.6h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></span>
          <input type="text" name="last_name" value="<?= fe('last_name') ?>" placeholder="Dela Cruz" required/>
        </div><?= showErr('last_name') ?>
      </div>
    </div>

    <!-- Email -->
    <div class="form-group">
      <label>Email Address <span class="req">*</span></label>
      <div class="input-wrap <?= hasErr('email')?'has-error':'' ?>">
        <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
        <input type="email" name="email" id="emailField" value="<?= fe('email') ?>" placeholder="your@email.com" required/>
      </div><?= showErr('email') ?>
    </div>

    <!-- Child info (parents only) -->
    <div id="childSection" class="<?= (fe('role')==='parent'||hasErr('child')||hasErr('grade_id'))?'show':'' ?>">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
        Verify your child's enrollment
      </div>
      <p style="font-size:13px;color:#374151;margin-bottom:14px;line-height:1.6;">Enter your child's name and grade level exactly as enrolled in the school system for verification.</p>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label>Child's First Name <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21.6h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></span>
            <input type="text" name="child_first" value="<?= fe('child_first') ?>" placeholder="Maria"/>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Child's Last Name <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21.6h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></span>
            <input type="text" name="child_last" value="<?= fe('child_last') ?>" placeholder="Santos"/>
          </div>
        </div>
      </div>
      <div style="margin-top:12px;">
        <?= showErr('child') ?>
      </div>
      <div class="form-row" style="margin-top:12px;">
        <div class="form-group" style="margin-bottom:0;">
          <label>Grade Level <span class="req">*</span></label>
          <div class="input-wrap <?= hasErr('grade_id')?'has-error':'' ?>">
            <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg></span>
            <select name="grade_id">
              <option value="">Select grade...</option>
              <?php foreach($grades as $g): ?>
              <option value="<?=$g['grade_id']?>" <?= fe('grade_id')==$g['grade_id']?'selected':'' ?>><?= htmlspecialchars($g['grade_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div><?= showErr('grade_id') ?>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Section <span style="font-weight:400;color:#9ca3af;">(optional)</span></label>
          <div class="input-wrap">
            <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg></span>
            <input type="text" name="section" value="<?= fe('section') ?>" placeholder="e.g. Sampaguita"/>
          </div>
        </div>
      </div>
    </div>

    <!-- Password -->
    <div class="form-group">
      <label>Password <span class="req">*</span></label>
      <div class="input-wrap <?= hasErr('password')?'has-error':'' ?>">
        <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></span>
        <input type="password" name="password" id="passField" placeholder="Min. 8 characters" oninput="checkPass()" required/>
        <button type="button" class="toggle-btn" onclick="togglePass('passField',this)">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        </button>
      </div>
      <div class="pass-hints"><span class="hint" id="h8">8+ chars</span><span class="hint" id="hUp">Uppercase</span><span class="hint" id="hNum">Number</span></div>
      <?= showErr('password') ?>
    </div>
    <div class="form-group">
      <label>Confirm Password <span class="req">*</span></label>
      <div class="input-wrap <?= hasErr('confirm')?'has-error':'' ?>">
        <span class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></span>
        <input type="password" name="confirm" id="confirmField" placeholder="Re-enter password" required/>
        <button type="button" class="toggle-btn" onclick="togglePass('confirmField',this)">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        </button>
      </div><?= showErr('confirm') ?>
    </div>

    <button type="submit" class="btn-submit">Send Verification Code →</button>
  </form>
  <p class="login-link">Already have an account? <a href="/spta-system/login.php">Sign in here</a></p>
<?php endif; ?>
</div>

<script>
function togglePass(id,btn){var f=document.getElementById(id);var s=f.type==='text';f.type=s?'password':'text';btn.querySelector('svg').style.opacity=s?'1':'0.5';}
function checkPass(){var p=document.getElementById('passField').value;document.getElementById('h8').className='hint'+(p.length>=8?' ok':'');document.getElementById('hUp').className='hint'+(/[A-Z]/.test(p)?' ok':'');document.getElementById('hNum').className='hint'+(/[0-9]/.test(p)?' ok':'');}
function onRoleChange(role){
  document.getElementById('depedNote').className='deped-note'+((['staff','spta_officer'].includes(role))?' show':'');
  document.getElementById('emailField').placeholder=(['staff','spta_officer'].includes(role))?'your@deped.gov.ph':'your@email.com';
  var cs=document.getElementById('childSection');
  cs.className=(role==='parent')?'show':'';
  cs.querySelectorAll('input,select').forEach(function(el){el.required=(role==='parent');});
}
window.addEventListener('DOMContentLoaded',function(){
  var ch=document.querySelector('input[name="role"]:checked');
  if(ch)onRoleChange(ch.value);
  checkPass();
});

// OTP boxes: auto-advance
document.querySelectorAll('.otp-digit').forEach(function(el,i,arr){
  el.addEventListener('input',function(){
    this.value=this.value.replace(/[^0-9]/g,'');
    if(this.value&&i<arr.length-1)arr[i+1].focus();
    collectOtp();
  });
  el.addEventListener('keydown',function(e){
    if(e.key==='Backspace'&&!this.value&&i>0)arr[i-1].focus();
  });
  el.addEventListener('paste',function(e){
    e.preventDefault();
    var pasted=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
    pasted.split('').forEach(function(c,j){if(arr[j])arr[j].value=c;});
    if(arr[Math.min(pasted.length,5)])arr[Math.min(pasted.length,5)].focus();
    collectOtp();
  });
});
function collectOtp(){
  var v=Array.from(document.querySelectorAll('.otp-digit')).map(function(el){return el.value;}).join('');
  var h=document.getElementById('otpHidden');
  if(h)h.value=v;
}
</script>
</body>
</html>
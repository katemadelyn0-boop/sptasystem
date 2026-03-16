<?php
require_once 'config/auth.php';
if (isLoggedIn()) redirectToDashboard();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SPTA Payment System — Pawing Central School</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy:#0f2342; --navy2:#1a3560; --gold:#e8a020; --gold2:#f5b940;
      --white:#ffffff; --gray:#f3f4f6; --text:#374151; --muted:#6b7280;
    }
    body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--white); color:var(--text); overflow-x:hidden; }

    /* NAV */
    nav {
      position:fixed; top:0; left:0; right:0; z-index:100;
      display:flex; align-items:center; justify-content:space-between;
      padding:20px 60px;
      background:rgba(15,35,66,0.97); backdrop-filter:blur(12px);
      animation:slideDown 0.6s ease both;
    }
    @keyframes slideDown { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }
    .nav-brand { display:flex; align-items:center; gap:12px; }
    .nav-brand-icon { width:38px; height:38px; background:var(--gold); border-radius:10px; display:flex; align-items:center; justify-content:center; }
    .nav-brand-icon svg { width:20px; height:20px; fill:var(--navy); }
    .nav-brand-text strong { color:var(--white); font-size:16px; font-weight:700; display:block; }
    .nav-brand-text span { color:rgba(255,255,255,0.5); font-size:11px; }
    .nav-links { display:flex; align-items:center; gap:12px; }
    .btn-nav-outline { padding:9px 22px; border:1.5px solid rgba(255,255,255,0.25); border-radius:8px; color:var(--white); font-size:14px; font-weight:600; text-decoration:none; transition:all 0.2s; }
    .btn-nav-outline:hover { border-color:var(--gold); color:var(--gold); }
    .btn-nav-solid { padding:9px 22px; background:var(--gold); border:none; border-radius:8px; color:var(--navy); font-size:14px; font-weight:700; text-decoration:none; transition:all 0.2s; }
    .btn-nav-solid:hover { background:var(--gold2); }

    /* HERO */
    .hero { min-height:100vh; background:var(--navy); display:flex; align-items:center; padding:120px 60px 80px; position:relative; overflow:hidden; }
    .hero::before { content:''; position:absolute; top:-100px; right:-100px; width:600px; height:600px; background:radial-gradient(circle,rgba(232,160,32,0.12) 0%,transparent 70%); border-radius:50%; }
    .hero::after  { content:''; position:absolute; bottom:-150px; left:-100px; width:500px; height:500px; background:radial-gradient(circle,rgba(232,160,32,0.07) 0%,transparent 70%); border-radius:50%; }
    .hero-grid { display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:center; max-width:1200px; margin:0 auto; width:100%; position:relative; z-index:1; }
    .hero-badge { display:inline-flex; align-items:center; gap:8px; background:rgba(232,160,32,0.15); border:1px solid rgba(232,160,32,0.3); border-radius:100px; padding:6px 16px; color:var(--gold); font-size:13px; font-weight:600; margin-bottom:24px; animation:fadeUp 0.7s 0.2s ease both; }
    .hero-badge span { width:6px; height:6px; background:var(--gold); border-radius:50%; }
    .hero-title { font-family:'Playfair Display',serif; font-size:clamp(38px,5vw,58px); font-weight:900; color:var(--white); line-height:1.1; margin-bottom:20px; animation:fadeUp 0.7s 0.3s ease both; }
    .hero-title .accent { color:var(--gold); }
    .hero-sub { font-size:17px; color:rgba(255,255,255,0.65); line-height:1.7; margin-bottom:36px; animation:fadeUp 0.7s 0.4s ease both; }
    .hero-btns { display:flex; gap:14px; flex-wrap:wrap; animation:fadeUp 0.7s 0.5s ease both; }
    .btn-primary { padding:14px 32px; background:var(--gold); border:none; border-radius:10px; color:var(--navy); font-size:15px; font-weight:700; text-decoration:none; font-family:inherit; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-primary:hover { background:var(--gold2); transform:translateY(-2px); box-shadow:0 8px 24px rgba(232,160,32,0.3); }
    .btn-secondary { padding:14px 32px; background:transparent; border:2px solid rgba(255,255,255,0.25); border-radius:10px; color:var(--white); font-size:15px; font-weight:600; text-decoration:none; font-family:inherit; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-secondary:hover { border-color:var(--white); background:rgba(255,255,255,0.05); }
    @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

    /* Hero right card */
    .hero-card-wrap { animation:fadeUp 0.8s 0.4s ease both; }
    .hero-card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:24px; padding:32px; backdrop-filter:blur(10px); }
    .hero-card-title { color:rgba(255,255,255,0.5); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:1px; margin-bottom:20px; }
    .stats-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
    .stat-box { background:rgba(255,255,255,0.05); border-radius:14px; padding:20px; border:1px solid rgba(255,255,255,0.08); }
    .stat-box .num { font-size:28px; font-weight:800; color:var(--white); }
    .stat-box .lbl { font-size:12px; color:rgba(255,255,255,0.45); margin-top:4px; }
    .stat-box.gold .num { color:var(--gold); }
    .feature-list { display:flex; flex-direction:column; gap:12px; }
    .feature-item { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,0.7); font-size:14px; }
    .feature-item .dot { width:8px; height:8px; background:var(--gold); border-radius:50%; flex-shrink:0; }

    /* FEATURES */
    .features { padding:100px 60px; background:var(--gray); }
    .section-label { text-align:center; color:var(--gold); font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-bottom:12px; }
    .section-title { text-align:center; font-family:'Playfair Display',serif; font-size:clamp(28px,4vw,42px); font-weight:700; color:var(--navy); margin-bottom:16px; }
    .section-sub { text-align:center; color:var(--muted); font-size:16px; max-width:520px; margin:0 auto 60px; line-height:1.7; }
    .features-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; max-width:1100px; margin:0 auto; }
    .feat-card { background:var(--white); border-radius:20px; padding:32px; border:1px solid #e5e7eb; transition:all 0.3s; }
    .feat-card:hover { transform:translateY(-6px); box-shadow:0 20px 50px rgba(15,35,66,0.1); border-color:var(--navy); }
    .feat-icon { width:52px; height:52px; background:var(--navy); border-radius:14px; display:flex; align-items:center; justify-content:center; margin-bottom:20px; }
    .feat-icon svg { width:26px; height:26px; fill:var(--gold); }
    .feat-card h3 { font-size:17px; font-weight:700; color:var(--navy); margin-bottom:10px; }
    .feat-card p  { font-size:14px; color:var(--muted); line-height:1.7; }

    /* ROLES */
    .roles { padding:100px 60px; background:var(--navy); }
    .roles .section-title { color:var(--white); }
    .roles .section-sub   { color:rgba(255,255,255,0.55); }
    .roles-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; max-width:1100px; margin:0 auto; }
    .role-card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:20px; padding:28px; text-align:center; transition:all 0.3s; }
    .role-card:hover { background:rgba(232,160,32,0.1); border-color:var(--gold); }
    .role-emoji { font-size:36px; margin-bottom:14px; }
    .role-card h3 { color:var(--white); font-size:16px; font-weight:700; margin-bottom:8px; }
    .role-card p  { color:rgba(255,255,255,0.5); font-size:13px; line-height:1.6; }

    /* CTA */
    .cta { padding:100px 60px; text-align:center; background:linear-gradient(135deg,#fdf8f0 0%,#fff8ec 100%); }
    .cta h2 { font-family:'Playfair Display',serif; font-size:clamp(28px,4vw,44px); font-weight:900; color:var(--navy); margin-bottom:16px; }
    .cta p { color:var(--muted); font-size:16px; margin-bottom:36px; }
    .cta-btns { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
    .btn-cta-primary { padding:16px 40px; background:var(--navy); color:var(--white); border-radius:12px; font-size:16px; font-weight:700; text-decoration:none; font-family:inherit; transition:all 0.2s; display:inline-flex; align-items:center; gap:10px; }
    .btn-cta-primary:hover { background:var(--navy2); transform:translateY(-2px); box-shadow:0 12px 32px rgba(15,35,66,0.2); }
    .btn-cta-outline { padding:16px 40px; background:transparent; border:2px solid var(--navy); color:var(--navy); border-radius:12px; font-size:16px; font-weight:700; text-decoration:none; font-family:inherit; transition:all 0.2s; }
    .btn-cta-outline:hover { background:var(--navy); color:var(--white); }

    /* FOOTER */
    footer { background:var(--navy); padding:32px 60px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    footer p { color:rgba(255,255,255,0.35); font-size:13px; }

    /* RESPONSIVE */
    @media(max-width:900px){
      nav{padding:16px 24px;}
      .hero{padding:100px 24px 60px;}
      .hero-grid{grid-template-columns:1fr;}
      .hero-card-wrap{display:none;}
      .features,.roles,.cta{padding:70px 24px;}
      .features-grid{grid-template-columns:1fr 1fr;}
      .roles-grid{grid-template-columns:1fr 1fr;}
      footer{padding:24px;}
    }
    @media(max-width:560px){
      .features-grid{grid-template-columns:1fr;}
      .hero-btns{flex-direction:column;}
    }
  </style>
</head>
<body>

<nav>
  <div class="nav-brand">
    <div class="nav-brand-icon">
      <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
    </div>
    <div class="nav-brand-text">
      <strong>SPTA System</strong>
      <span>Pawing Central School</span>
    </div>
  </div>
  <div class="nav-links">
    <a href="/spta-system/login.php" class="btn-nav-outline">Sign In</a>
    <a href="/spta-system/register.php" class="btn-nav-solid">Register</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-grid">
    <div class="hero-content">
      <div class="hero-badge"><span></span> Official SPTA Payment Portal</div>
      <h1 class="hero-title">Smarter Payments for <span class="accent">Pawing Central School</span></h1>
      <p class="hero-sub">A secure and transparent platform for managing SPTA fees, school payments, and financial records — designed for parents, staff, and officers.</p>
      <div class="hero-btns">
        <a href="/spta-system/register.php" class="btn-primary">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          Create Account
        </a>
        <a href="/spta-system/login.php" class="btn-secondary">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
          Sign In
        </a>
      </div>
    </div>
    <div class="hero-card-wrap">
      <div class="hero-card">
        <div class="hero-card-title">System Overview</div>
        <div class="stats-row">
          <div class="stat-box gold"><div class="num">4</div><div class="lbl">User Roles</div></div>
          <div class="stat-box"><div class="num">100%</div><div class="lbl">Secure</div></div>
          <div class="stat-box"><div class="num">Real-time</div><div class="lbl">Tracking</div></div>
          <div class="stat-box gold"><div class="num">Auto</div><div class="lbl">Receipts</div></div>
        </div>
        <div class="feature-list">
          <div class="feature-item"><span class="dot"></span> Secure role-based access control</div>
          <div class="feature-item"><span class="dot"></span> Real-time payment tracking</div>
          <div class="feature-item"><span class="dot"></span> Automated receipt generation</div>
          <div class="feature-item"><span class="dot"></span> Parent notifications & reminders</div>
          <div class="feature-item"><span class="dot"></span> Complete audit trail</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="features">
  <div class="section-label">Features</div>
  <h2 class="section-title">Everything You Need</h2>
  <p class="section-sub">A complete payment management solution built specifically for Pawing Central School.</p>
  <div class="features-grid">
    <div class="feat-card">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></div>
      <h3>Secure Access</h3>
      <p>Role-based authentication ensures each user only sees what they need — admin, staff, officer, or parent.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
      <h3>Payment Tracking</h3>
      <p>Record cash, GCash, Maya, and bank transfer payments with automatic receipt generation and status tracking.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg></div>
      <h3>Financial Reports</h3>
      <p>Generate detailed monthly and annual financial reports with print support for school records and auditing.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg></div>
      <h3>Parent Notifications</h3>
      <p>Parents receive real-time reminders for upcoming payments and confirmation when fees are settled.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
      <h3>Student Records</h3>
      <p>Manage complete student enrollment records by grade level and school year with easy search and filtering.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
      <h3>Audit Trail</h3>
      <p>Every action is logged — full transparency and accountability for all payment transactions and user activities.</p>
    </div>
  </div>
</section>

<section class="roles">
  <div class="section-label">Who It's For</div>
  <h2 class="section-title">Built for Everyone</h2>
  <p class="section-sub">Four dedicated portals tailored to each user's specific role and responsibilities.</p>
  <div class="roles-grid">
    <div class="role-card"><div class="role-emoji">🛡️</div><h3>Admin</h3><p>Full system control — manage users, students, reports, and system settings.</p></div>
    <div class="role-card"><div class="role-emoji">👩‍💼</div><h3>Staff</h3><p>Record school fee payments and manage student enrollment records.</p></div>
    <div class="role-card"><div class="role-emoji">🏫</div><h3>SPTA Officer</h3><p>Manage SPTA fee collections, view reports, and monitor payment status.</p></div>
    <div class="role-card"><div class="role-emoji">👨‍👩‍👧</div><h3>Parent</h3><p>View your child's payment history, receipts, and receive payment notifications.</p></div>
  </div>
</section>

<section class="cta">
  <h2>Ready to Get Started?</h2>
  <p>Join Pawing Central School's payment management system today.</p>
  <div class="cta-btns">
    <a href="/spta-system/register.php" class="btn-cta-primary">
      <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
      Create an Account
    </a>
    <a href="/spta-system/login.php" class="btn-cta-outline">Sign In Instead</a>
  </div>
</section>

<footer>
  <p>&copy; <?= date('Y') ?> Pawing Central School — SPTA Payment Management System</p>
  <p>All rights reserved.</p>
</footer>

</body>
</html>
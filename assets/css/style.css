@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:       #0f2342;
  --navy-mid:   #1a3560;
  --gold:       #e8a020;
  --gold-light: #f5c347;
  --gold-pale:  #fef3dc;
  --white:      #ffffff;
  --gray-50:    #f9fafb;
  --gray-100:   #f3f4f6;
  --gray-200:   #e5e7eb;
  --gray-300:   #d1d5db;
  --gray-400:   #9ca3af;
  --gray-500:   #6b7280;
  --gray-600:   #4b5563;
  --gray-700:   #374151;
  --error:      #dc2626;
  --error-bg:   #fef2f2;
  --success:    #16a34a;
  --success-bg: #f0fdf4;
  --warning:    #d97706;
  --warning-bg: #fffbeb;
  --info:       #2563eb;
  --info-bg:    #eff6ff;
  --sidebar-w:  260px;
}

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--gray-100);
  color: var(--gray-700);
  min-height: 100vh;
}

/* ── Layout ── */
.app-layout { display: flex; min-height: 100vh; }

.main-content {
  flex: 1;
  margin-left: var(--sidebar-w);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.page-body { flex: 1; padding: 32px; }

/* ── Sidebar ── */
.sidebar {
  width: var(--sidebar-w);
  background: var(--navy);
  height: 100vh;
  position: fixed;
  top: 0; left: 0;
  display: flex;
  flex-direction: column;
  z-index: 50;
  overflow-y: auto;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 22px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}

.brand-logo {
  width: 36px; height: 36px;
  background: var(--gold);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.brand-logo svg { width: 18px; height: 18px; fill: var(--navy); }
.brand-text strong { display: block; color: #fff; font-size: 13px; font-weight: 700; }
.brand-text span   { display: block; color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 1px; }

.sidebar-user {
  display: flex; align-items: center; gap: 10px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.user-avatar {
  width: 36px; height: 36px;
  background: var(--gold);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 15px; color: var(--navy); flex-shrink: 0;
}
.user-info strong { display: block; color: #fff; font-size: 13px; font-weight: 600; }
.role-pill {
  display: inline-block; font-size: 11px; font-weight: 600;
  padding: 2px 8px; border-radius: 100px; margin-top: 3px;
}
.role-pill.admin        { background: #ede9fe; color: #7c3aed; }
.role-pill.staff        { background: #dbeafe; color: #1d4ed8; }
.role-pill.spta_officer { background: var(--gold-pale); color: var(--gold); }
.role-pill.parent       { background: #dcfce7; color: #16a34a; }

.sidebar-nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 2px; }

.nav-section-label {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 1px; color: rgba(255,255,255,0.3);
  padding: 12px 8px 6px; margin-top: 4px;
}

.nav-link {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px;
  text-decoration: none; color: rgba(255,255,255,0.65);
  font-size: 14px; font-weight: 500;
  transition: background 0.15s, color 0.15s;
}
.nav-link svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
.nav-link.active { background: rgba(232,160,32,0.15); color: var(--gold-light); font-weight: 600; }
.nav-link.active svg { fill: var(--gold); }

.sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.07); }
.btn-logout {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px;
  text-decoration: none; color: rgba(255,255,255,0.5);
  font-size: 14px; font-weight: 500; transition: all 0.15s; width: 100%;
}
.btn-logout:hover { background: rgba(220,38,38,0.15); color: #f87171; }

/* ── Header ── */
.app-header {
  height: 64px; background: var(--white);
  border-bottom: 1px solid var(--gray-200);
  display: flex; align-items: center; padding: 0 28px; gap: 16px;
  position: sticky; top: 0; z-index: 40;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.header-title { flex: 1; font-size: 15px; font-weight: 600; color: var(--gray-600); }
.header-right { display: flex; align-items: center; gap: 8px; }
.header-user {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 10px; border-radius: 10px; cursor: pointer;
  transition: background 0.15s; position: relative; user-select: none;
}
.header-user:hover { background: var(--gray-100); }
.header-avatar {
  width: 32px; height: 32px; background: var(--navy);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: var(--gold);
}
.header-name { font-size: 14px; font-weight: 600; color: var(--gray-700); }

.dropdown-menu {
  position: absolute; top: calc(100% + 8px); right: 0;
  background: var(--white); border: 1px solid var(--gray-200);
  border-radius: 14px; min-width: 220px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
  opacity: 0; pointer-events: none; transform: translateY(-8px);
  transition: opacity 0.15s, transform 0.15s; z-index: 100; overflow: hidden;
}
.dropdown-menu.open { opacity: 1; pointer-events: all; transform: translateY(0); }
.dropdown-header { padding: 14px 16px; background: var(--gray-50); }
.dropdown-header strong { display: block; font-size: 14px; font-weight: 700; color: var(--navy); }
.dropdown-header span   { display: block; font-size: 12px; color: var(--gray-500); margin-top: 2px; }
.dropdown-divider { height: 1px; background: var(--gray-200); }
.dropdown-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; text-decoration: none; font-size: 14px;
  font-weight: 500; color: var(--gray-700); transition: background 0.15s;
}
.dropdown-item:hover { background: var(--gray-50); }
.dropdown-item.danger { color: var(--error); }
.dropdown-item.danger:hover { background: var(--error-bg); }

/* ── Footer ── */
.app-footer {
  padding: 18px 32px; border-top: 1px solid var(--gray-200);
  background: var(--white); text-align: center;
}
.app-footer p { font-size: 13px; color: var(--gray-400); }

/* ── Page Header ── */
.page-header { margin-bottom: 28px; }
.page-header h1 { font-size: 24px; font-weight: 800; color: var(--navy); margin-bottom: 4px; }
.page-header p  { font-size: 14px; color: var(--gray-500); }

/* ── Stats Grid ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px; margin-bottom: 28px;
}
.stat-card {
  background: var(--white); border-radius: 16px;
  border: 1px solid var(--gray-200); padding: 22px 24px;
  display: flex; align-items: center; gap: 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  transition: box-shadow 0.2s, transform 0.2s;
}
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
.stat-icon {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-icon svg { width: 24px; height: 24px; }
.stat-icon.blue  { background: var(--info-bg);    color: var(--info); }
.stat-icon.gold  { background: var(--gold-pale);  color: var(--gold); }
.stat-icon.green { background: var(--success-bg); color: var(--success); }
.stat-icon.red   { background: var(--error-bg);   color: var(--error); }
.stat-icon.navy  { background: #e8edf5;           color: var(--navy); }
.stat-info .num   { font-size: 26px; font-weight: 800; color: var(--navy); line-height: 1; }
.stat-info .label { font-size: 13px; color: var(--gray-500); margin-top: 4px; }

/* ── Cards ── */
.card {
  background: var(--white); border-radius: 16px;
  border: 1px solid var(--gray-200); padding: 24px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.card-title {
  font-size: 16px; font-weight: 700; color: var(--navy);
  margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;
}

/* ── Tables ── */
.table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid var(--gray-200); }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
thead th {
  background: var(--gray-50); padding: 12px 16px; text-align: left;
  font-size: 12px; font-weight: 700; color: var(--gray-500);
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 1px solid var(--gray-200); white-space: nowrap;
}
tbody td { padding: 14px 16px; border-bottom: 1px solid var(--gray-100); color: var(--gray-700); vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--gray-50); }

/* ── Badges ── */
.badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 600;
}
.badge.paid     { background: var(--success-bg); color: var(--success); }
.badge.unpaid   { background: var(--error-bg);   color: var(--error); }
.badge.partial  { background: var(--warning-bg); color: var(--warning); }
.badge.overdue  { background: #fff1f2;           color: #e11d48; }
.badge.admin    { background: #ede9fe;           color: #7c3aed; }
.badge.staff    { background: var(--info-bg);    color: var(--info); }
.badge.officer  { background: var(--gold-pale);  color: var(--gold); }
.badge.parent   { background: var(--success-bg); color: var(--success); }
.badge.active   { background: var(--success-bg); color: var(--success); }
.badge.inactive { background: var(--gray-100);   color: var(--gray-500); }

/* ── Buttons ── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 10px; font-size: 14px; font-weight: 600;
  font-family: inherit; cursor: pointer; border: none; text-decoration: none;
  transition: background 0.2s, transform 0.1s; white-space: nowrap;
}
.btn:active { transform: scale(0.98); }
.btn-primary { background: var(--navy); color: #fff; }
.btn-primary:hover { background: var(--navy-mid); }
.btn-gold { background: var(--gold); color: var(--navy); font-weight: 700; }
.btn-gold:hover { background: var(--gold-light); }
.btn-outline { background: transparent; border: 1.5px solid var(--gray-300); color: var(--gray-700); }
.btn-outline:hover { border-color: var(--navy); color: var(--navy); background: var(--gray-50); }
.btn-danger { background: var(--error-bg); color: var(--error); border: 1px solid #fecaca; }
.btn-danger:hover { background: #fee2e2; }
.btn-sm { padding: 6px 12px; font-size: 13px; border-radius: 8px; }

/* ── Forms ── */
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 7px; }
.form-label .req { color: var(--error); margin-left: 2px; }
.form-control {
  width: 100%; padding: 10px 14px; border: 1.5px solid var(--gray-300);
  border-radius: 10px; font-size: 14px; font-family: inherit; color: var(--gray-700);
  background: var(--gray-50); outline: none;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}
.form-control:focus { border-color: var(--navy); background: #fff; box-shadow: 0 0 0 3px rgba(15,35,66,0.07); }
select.form-control { cursor: pointer; }
.form-hint { font-size: 12px; color: var(--gray-400); margin-top: 5px; }
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

/* ── Alerts ── */
.alert {
  padding: 12px 16px; border-radius: 10px; font-size: 14px;
  margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
}
.alert-success { background: var(--success-bg); border: 1px solid #bbf7d0; color: var(--success); border-left: 4px solid var(--success); }
.alert-error   { background: var(--error-bg);   border: 1px solid #fecaca; color: var(--error);   border-left: 4px solid var(--error); }
.alert-warning { background: var(--warning-bg); border: 1px solid #fde68a; color: var(--warning); border-left: 4px solid var(--warning); }
.alert-info    { background: var(--info-bg);    border: 1px solid #bfdbfe; color: var(--info);    border-left: 4px solid var(--info); }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200;
  display: flex; align-items: center; justify-content: center; padding: 20px;
  opacity: 0; pointer-events: none; transition: opacity 0.2s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal {
  background: #fff; border-radius: 20px; width: 100%; max-width: 520px;
  max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  transform: translateY(20px); transition: transform 0.2s;
}
.modal-overlay.open .modal { transform: translateY(0); }
.modal-header { padding: 24px 28px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.modal-header h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
.modal-close { background: none; border: none; cursor: pointer; color: var(--gray-400); padding: 4px; border-radius: 6px; }
.modal-close:hover { color: var(--navy); background: var(--gray-100); }
.modal-body { padding: 0 28px; }
.modal-footer { padding: 20px 28px 28px; display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

/* ── Toolbar ── */
.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400); pointer-events: none; }
.search-input {
  width: 100%; padding: 9px 14px 9px 38px; border: 1.5px solid var(--gray-200);
  border-radius: 10px; font-size: 14px; font-family: inherit; outline: none;
  background: #fff; transition: border-color 0.2s;
}
.search-input:focus { border-color: var(--navy); }

/* ── Empty State ── */
.empty-state { text-align: center; padding: 60px 20px; color: var(--gray-400); }
.empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.4; }
.empty-state p { font-size: 14px; }

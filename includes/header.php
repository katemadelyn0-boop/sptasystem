<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header class="app-header">
  <div class="header-title"></div>
  <div class="header-right">
    <div class="header-user" onclick="document.getElementById('userDrop').classList.toggle('open')">
      <div class="header-avatar"><?= strtoupper(substr($_SESSION['name']??'U',0,1)) ?></div>
      <span class="header-name"><?= htmlspecialchars($_SESSION['name']??'') ?></span>
      <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M7 10l5 5 5-5z"/></svg>
      <div class="dropdown-menu" id="userDrop">
        <div class="dropdown-header">
          <strong><?= htmlspecialchars($_SESSION['name']??'') ?></strong>
          <span><?= htmlspecialchars($_SESSION['email']??'') ?></span>
        </div>
        <div class="dropdown-divider"></div>
        <a href="/spta-system/logout.php" class="dropdown-item danger">
          <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
          Sign Out
        </a>
      </div>
    </div>
  </div>
</header>
<script>
document.addEventListener('click', function(e) {
  var u = document.querySelector('.header-user');
  if (u && !u.contains(e.target)) {
    var d = document.getElementById('userDrop');
    if (d) d.classList.remove('open');
  }
});
</script>

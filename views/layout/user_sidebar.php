<?php
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
function user_nav_active(string $file, string $script): string { return $script === $file ? 'active' : ''; }
?>
<aside class="app-sidebar d-flex flex-column">
  <a class="app-brand" href="dashboard.php">
    <span class="app-dot"></span>
    <span>Fesmic</span>
  </a>

  <div class="app-nav d-flex flex-column gap-1">
    <a class="<?= e(user_nav_active('dashboard.php', $script)) ?>" href="dashboard.php">Dashboard</a>
    <a class="<?= e(user_nav_active('history.php', $script)) ?>" href="history.php">Order History</a>
    <a class="<?= e(user_nav_active('history.php', $script)) ?>" href="history.php">E-Ticket</a>
  </div>

  <div class="app-sidebar-footer">
    <a class="app-logout" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
  </div>
</aside>

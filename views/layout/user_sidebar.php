<?php
$WEB = BASE_URL . '/public';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

function u_active(string $uri, string $end): string {
  return str_ends_with($uri, $end) ? 'active' : '';
}
?>
<aside class="app-sidebar d-flex flex-column">
  <a class="app-brand" href="<?= e($WEB . '/dashboard') ?>">
    <span class="app-dot"></span>
    <span>Fesmic</span>
  </a>

  <div class="app-nav d-flex flex-column gap-1">
    <a class="<?= e(u_active($uri, '/dashboard')) ?>" href="<?= e($WEB . '/dashboard') ?>">Dashboard</a>
    <a class="<?= e(u_active($uri, '/history')) ?>" href="<?= e($WEB . '/history') ?>">Order History</a>
    <a class="<?= e(u_active($uri, '/eticket')) ?>" href="<?= e($WEB . '/eticket') ?>">E-Ticket</a>
  </div>

  <div class="app-sidebar-footer">
    <a class="app-logout" href="<?= e($WEB . '/logout') ?>">Logout</a>
  </div>
</aside>

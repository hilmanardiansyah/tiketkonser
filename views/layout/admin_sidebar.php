<?php
$u = current_user();

$WEB = BASE_URL . '/public';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

function a_active(string $uri, string $end): string {
  return str_ends_with($uri, $end) ? 'active' : '';
}
?>
<aside class="app-sidebar d-flex flex-column">
  <a class="app-brand" href="<?= e($WEB . '/dashboard') ?>">
    <span class="app-dot"></span>
    <span>Fesmic</span>
  </a>

  <nav class="app-nav">
    <a class="<?= e(a_active($uri, '/dashboard')) ?>" href="<?= e($WEB . '/dashboard') ?>">Dashboard</a>
    <a class="<?= e(a_active($uri, '/admin/events')) ?>" href="<?= e($WEB . '/admin/events') ?>">Event Management</a>
    <a class="<?= e(a_active($uri, '/admin/tickets')) ?>" href="<?= e($WEB . '/admin/tickets') ?>">Ticket Management</a>
    <a class="<?= e(a_active($uri, '/admin/orders')) ?>" href="<?= e($WEB . '/admin/orders') ?>">Order Management</a>
  </nav>

  <div class="app-sidebar-footer">
    <a class="text-danger d-flex align-items-center gap-2 px-2 py-2 text-decoration-none"
       href="<?= e($WEB . '/logout') ?>">
      Logout
    </a>
  </div>
</aside>

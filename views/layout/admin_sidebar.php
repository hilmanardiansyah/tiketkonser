<?php
$u = current_user();
$current = basename($_SERVER['PHP_SELF']);
function acls(string $file, string $current): string {
  return $file === $current ? 'active' : '';
}
?>
<aside class="app-sidebar d-flex flex-column">
  <a class="app-brand" href="<?= e(BASE_URL . '/views/admin/dashboard.php') ?>">
    <span class="app-dot"></span>
    <span>Fesmic</span>
  </a>

  <nav class="app-nav">
    <a class="<?= e(acls('dashboard.php', $current)) ?>" href="<?= e(BASE_URL . '/views/admin/dashboard.php') ?>">Dashboard</a>
    <a class="<?= e(acls('events.php', $current)) ?>" href="<?= e(BASE_URL . '/views/admin/events.php') ?>">Event Management</a>
    <a class="<?= e(acls('tickets.php', $current)) ?>" href="<?= e(BASE_URL . '/views/admin/tickets.php') ?>">Ticket Management</a>
    <a class="<?= e(acls('orders.php', $current)) ?>" href="<?= e(BASE_URL . '/views/admin/orders.php') ?>">Order Management</a>
  </nav>

  <div class="app-sidebar-footer">
    <a class="text-danger d-flex align-items-center gap-2 px-2 py-2 text-decoration-none" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">
      Logout
    </a>
  </div>
</aside>

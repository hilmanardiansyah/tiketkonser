<?php
$u = current_user();
$isAdmin = $u && (($u['role'] ?? '') === 'ADMIN');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand"
       href="<?= BASE_URL ?>/views/<?= $isAdmin ? 'admin/dashboard.php' : 'user/dashboard.php' ?>">
      TiketKonser
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topnav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($u): ?>
          <?php if ($isAdmin): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/views/admin/dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/views/admin/events.php">Events</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/views/admin/tickets.php">Tickets</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/views/admin/orders.php">Orders</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/views/user/dashboard.php">Dashboard</a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <div class="d-flex gap-2">
        <?php if ($u): ?>
          <span class="navbar-text text-white-50">
            <?= e($u['name']) ?> (<?= e($u['role']) ?>)
          </span>
          <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/views/auth/logout.php">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

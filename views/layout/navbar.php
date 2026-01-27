<?php
$u = current_user();
$isAdmin = $u && (strtoupper($u['role'] ?? '') === 'ADMIN');

if (!empty($hideNavbar)) return;

$WEB = BASE_URL . '/public';
?>
<nav class="navbar navbar-expand-lg navbar-dark nav-float">
  <div class="nav-card">
    <div class="d-flex align-items-center justify-content-between">
      <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="<?= $WEB ?>/">
        <span class="app-dot"></span>
        TiketKonser
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="topnav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= $WEB ?>/events">All Event</a>
        </li>

        <?php if ($u): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $WEB ?>/dashboard">Dashboard</a>
          </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <?php if ($u): ?>
          <span class="navbar-text text-white-50 d-none d-lg-inline">
            <?= e($u['name']) ?> (<?= e($u['role']) ?>)
          </span>
          <a class="btn btn-outline-light btn-sm" href="<?= $WEB ?>/logout">Logout</a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="<?= $WEB ?>/login">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

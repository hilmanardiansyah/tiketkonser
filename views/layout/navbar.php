<?php $u = current_user(); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/views/<?= ($u && $u['role']==='ADMIN') ? 'admin/dashboard.php' : 'user/dashboard.php' ?>">
      TiketKonser
    </a>

    <div class="d-flex gap-2">
      <?php if ($u): ?>
        <span class="navbar-text text-white-50"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</span>
        <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/views/auth/logout.php">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

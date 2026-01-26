<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();

$pdo = db();

$totalEvents = (int)($pdo->query("SELECT COUNT(*) FROM events")->fetchColumn() ?: 0);
$totalUsers  = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0);
$totalOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0);
$revenue     = (float)($pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='PAID'")->fetchColumn() ?: 0);

$orders = $pdo->query("
  SELECT o.order_code, o.status, o.total_amount, o.order_date, u.name AS customer
  FROM orders o
  JOIN users u ON u.id = o.user_id
  ORDER BY o.order_date DESC
  LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$title = 'Dashboard Admin';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <h1 class="app-title">Dashboard Admin</h1>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="panel stat-card">
            <div class="stat-label">Total Event</div>
            <div class="stat-value"><?= $totalEvents ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="panel stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= $totalUsers ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="panel stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $totalOrders ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="panel stat-card">
            <div class="stat-label">Revenue (PAID)</div>
            <div class="stat-value">Rp <?= number_format($revenue, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <div class="panel p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="fw-semibold text-white">Order Terbaru</div>
        </div>

        <div class="table-responsive">
          <table class="table table-dark table-hover table-borderless align-middle mb-0">
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tanggal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td class="fw-semibold"><?= e($o['order_code']) ?></td>
                  <td><?= e($o['customer']) ?></td>
                  <td>Rp <?= number_format((float)$o['total_amount'], 0, ',', '.') ?></td>
                  <td>
                    <span class="badge <?= $o['status']==='PAID' ? 'bg-success' : 'bg-warning text-dark' ?>">
                      <?= e($o['status']) ?>
                    </span>
                  </td>
                  <td><?= e($o['order_date']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$orders): ?>
                <tr><td colspan="5" class="text-muted">Belum ada order.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

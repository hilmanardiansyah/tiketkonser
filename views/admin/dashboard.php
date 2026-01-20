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
require __DIR__ . '/../layout/navbar.php';
?>

<div class="container py-4">
  <h4 class="mb-3">Dashboard Admin</h4>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Total Event</div><div class="fs-4 fw-bold"><?= $totalEvents ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Total Users</div><div class="fs-4 fw-bold"><?= $totalUsers ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Total Orders</div><div class="fs-4 fw-bold"><?= $totalOrders ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Revenue (PAID)</div><div class="fs-4 fw-bold">Rp <?= number_format($revenue, 0, ',', '.') ?></div>
    </div></div></div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h6 class="mb-3">Order Terbaru</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
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
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

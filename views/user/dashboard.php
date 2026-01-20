<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();

$pdo = db();

$st = $pdo->prepare("
  SELECT
    COUNT(*) AS total_orders,
    SUM(CASE WHEN status='PAID' THEN 1 ELSE 0 END) AS paid_orders,
    SUM(CASE WHEN status='PENDING' THEN 1 ELSE 0 END) AS pending_orders
  FROM orders
  WHERE user_id = ?
");
$st->execute([$u['id']]);
$sum = $st->fetch(PDO::FETCH_ASSOC) ?: ['total_orders'=>0,'paid_orders'=>0,'pending_orders'=>0];

$st = $pdo->prepare("
  SELECT
    o.order_code, o.status, o.total_amount, o.order_date
  FROM orders o
  WHERE o.user_id = ?
  ORDER BY o.order_date DESC
  LIMIT 10
");
$st->execute([$u['id']]);
$orders = $st->fetchAll(PDO::FETCH_ASSOC);

$title = 'Dashboard User';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/navbar.php';
?>

<div class="container py-4">
  <h4 class="mb-3">Dashboard User</h4>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Total Pesanan</div>
        <div class="fs-4 fw-bold"><?= (int)$sum['total_orders'] ?></div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Pesanan PAID</div>
        <div class="fs-4 fw-bold"><?= (int)$sum['paid_orders'] ?></div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Pesanan PENDING</div>
        <div class="fs-4 fw-bold"><?= (int)$sum['pending_orders'] ?></div>
      </div></div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h6 class="mb-3">Pesanan Terakhir</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Order Code</th>
              <th>Total</th>
              <th>Status</th>
              <th>Tanggal</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td class="fw-semibold"><?= e($o['order_code']) ?></td>
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
            <tr><td colspan="4" class="text-muted">Belum ada pesanan.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();
$pdo = db();

$st = $pdo->prepare("
  SELECT o.id, o.order_code, o.status, o.order_date, e.name as event_name, e.image, e.location
  FROM orders o
  JOIN events e ON o.event_id = e.id
  WHERE o.user_id = ?
  ORDER BY o.order_date DESC LIMIT 3
");
$st->execute([$u['id']]);
$last_orders = $st->fetchAll();

$recommended = $pdo->query("SELECT * FROM events ORDER BY date ASC LIMIT 3")->fetchAll();

$title = 'Dashboard User - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <nav class="col-md-2 d-none d-md-block bg-dark sidebar vh-100 p-3">
      <h5 class="text-white fw-bold mb-4">FESMIC</h5>
      <ul class="nav flex-column">
        <li class="nav-item mb-2">
          <a class="nav-link text-white active bg-primary rounded" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-muted" href="history.php">Riwayat Pesanan</a>
        </li>
      </ul>
    </nav>

    <main class="col-md-10 ms-sm-auto px-md-4 py-4" style="background-color: #0F0F0F; color: white; min-height: 100vh;">
      <h2 class="fw-bold">Hello, <?= e($u['name']) ?>!</h2>
      <p class="text-muted">Cek tiket dan konser terbaru kamu di sini.</p>

      <div class="mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h4 class="fw-bold">Last Orders</h4>
          <a href="history.php" class="text-primary text-decoration-none small">Lihat Semua</a>
        </div>
        <div class="row g-4">
          <?php foreach ($last_orders as $o): ?>
          <div class="col-md-4">
            <div class="card bg-dark text-white border-secondary rounded-4 overflow-hidden">
              <img src="/public/img/<?= e($o['image']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
              <div class="card-body">
                <h5 class="fw-bold mb-1"><?= e($o['event_name']) ?></h5>
                <p class="text-muted small mb-3"><?= e($o['location']) ?></p>
                <a href="eticket.php?id=<?= $o['id'] ?>" class="btn btn-outline-light btn-sm w-100 rounded-pill">Lihat E-Ticket</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mt-5">
        <h4 class="fw-bold mb-4">Recommended Event</h4>
        <div class="row g-4">
          <?php foreach ($recommended as $ev): ?>
          <div class="col-md-4">
            <div class="p-3 bg-dark border border-secondary rounded-4 text-center">
              <img src="/public/img/<?= e($ev['image']) ?>" class="rounded-3 w-100 mb-3" style="height: 120px; object-fit: cover;">
              <h6 class="fw-bold"><?= e($ev['name']) ?></h6>
              <a href="buy_process.php?event_id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm w-100 mt-2">Beli Tiket</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

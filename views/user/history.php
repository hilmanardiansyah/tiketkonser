
<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();
$pdo = db();

// Query tetap sama karena ini inti fiturnya
$st = $pdo->prepare("
  SELECT o.*, e.name as event_name, e.image, e.location, e.date as event_date
  FROM orders o
  JOIN events e ON o.event_id = e.id
  WHERE o.user_id = ?
  ORDER BY o.order_date DESC
");
$st->execute([$u['id']]);
$all_orders = $st->fetchAll();

$title = 'Riwayat Pesanan - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12"> <div class="d-flex justify-content-between align-items-center mb-4 text-white">
                <h2 class="fw-bold">Riwayat Pesanan</h2>
                <a href="dashboard.php" class="btn btn-primary btn-sm rounded-pill">Kembali</a>
            </div>

            <div class="table-responsive bg-dark p-4 rounded-4 shadow">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Tanggal</th>
                            <th>Kode Order</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_orders as $o): ?>
                        <tr class="align-middle">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../public/img/<?= e($o['image']) ?>" class="rounded me-3" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/50'">
                                    <?= e($o['event_name']) ?>
                                </div>
                            </td>
                            <td><?= date('d M Y', strtotime($o['event_date'])) ?></td>
                            <td class="text-primary small"><?= e($o['order_code']) ?></td>
                            <td>
                                <a href="eticket.php?id=<?= $o['id'] ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">E-Ticket</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

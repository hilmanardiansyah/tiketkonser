<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();
$pdo = db();


function poster_src(string $posterUrl): string {
  if ($posterUrl === '') return 'https://via.placeholder.com/50';
  if (preg_match('#^https?://#i', $posterUrl)) return $posterUrl;
  if (str_starts_with($posterUrl, '/')) return $posterUrl;
  return BASE_URL . '/public/img/' . $posterUrl;
}

$st = $pdo->prepare("
  SELECT
    o.id,
    o.order_code,
    o.status,
    o.order_date,
    MIN(e.title)      AS event_name,
    MIN(e.poster_url) AS poster_url,
    MIN(e.event_date) AS event_date
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  JOIN ticket_types tt ON tt.id = oi.ticket_type_id
  JOIN events e ON e.id = tt.event_id
  WHERE o.user_id = ?
  GROUP BY o.id, o.order_code, o.status, o.order_date
  ORDER BY o.order_date DESC
");
$st->execute([$u['id']]);
$all_orders = $st->fetchAll();

$title = 'Riwayat Pesanan - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="container py-5">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4 text-white">
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
                    <img
                      src="<?= e(poster_src($o['poster_url'] ?? '')) ?>"
                      class="rounded me-3"
                      style="width:45px;height:45px;object-fit:cover;"
                      onerror="this.src='https://via.placeholder.com/50'"
                      alt=""
                    >
                    <?= e($o['event_name'] ?? '-') ?>
                  </div>
                </td>

                <td>
                  <?php
                    $d = $o['event_date'] ?? null;
                    echo $d ? date('d M Y', strtotime($d)) : '-';
                  ?>
                </td>

                <td class="text-primary small"><?= e($o['order_code']) ?></td>

                <td>
                  <a href="eticket.php?id=<?= (int)$o['id'] ?>"
                     class="btn btn-outline-light btn-sm rounded-pill px-3">
                    E-Ticket
                  </a>
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

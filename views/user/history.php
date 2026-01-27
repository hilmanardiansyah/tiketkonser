<?php

$u = require_login();
$pdo = db();

function img_src(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') return 'https://via.placeholder.com/200x200?text=Poster';
  if (preg_match('#^https?://#i', $url)) return $url;
  if (str_starts_with($url, '/')) return $url;
  return BASE_URL . '/public/img/' . $url;
}

$st = $pdo->prepare("
  SELECT
    o.id,
    o.order_code,
    o.status,
    o.order_date,
    MIN(e.title) AS event_name,
    MIN(e.poster_url) AS poster_url,
    MIN(e.event_date) AS event_date
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  JOIN ticket_types tt ON tt.id = oi.ticket_type_id
  JOIN events e ON e.id = tt.event_id
  WHERE o.user_id = ?
  GROUP BY o.id, o.order_code, o.status, o.order_date
  ORDER BY o.order_date DESC, o.id DESC
");
$st->execute([$u['id']]);
$all_orders = $st->fetchAll(PDO::FETCH_ASSOC);

$title = 'Order History - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/user_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">

      <div class="app-topbar">
        <h1 class="app-title m-0">Order History</h1>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (USER)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <div class="panel p-3">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width:360px;">Event</th>
                <th style="width:140px;">Tanggal</th>
                <th>Kode Order</th>
                <th style="width:120px;">Status</th>
                <th class="text-end" style="width:170px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_orders as $o): ?>
                <?php
                  $d = $o['event_date'] ?? null;
                  $dateTxt = $d ? date('d M Y', strtotime($d)) : '-';
                  $stt = $o['status'] ?? '';
                  $badge = $stt === 'PAID' ? 'bg-success' : ($stt === 'PENDING' ? 'bg-warning text-dark' : 'bg-secondary');
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <img src="<?= e(img_src($o['poster_url'] ?? '')) ?>" class="rounded" style="width:46px;height:46px;object-fit:cover;" alt="">
                      <div class="fw-semibold"><?= e($o['event_name'] ?? '-') ?></div>
                    </div>
                  </td>
                  <td><?= e($dateTxt) ?></td>
                  <td class="text-primary small"><?= e($o['order_code'] ?? '-') ?></td>
                  <td><span class="badge <?= e($badge) ?>"><?= e($stt) ?></span></td>
                  <td class="text-end">
                    <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="eticket.php?id=<?= (int)$o['id'] ?>">E-Ticket</a>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$all_orders): ?>
                <tr><td colspan="5" class="text-white-50">Belum ada pesanan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

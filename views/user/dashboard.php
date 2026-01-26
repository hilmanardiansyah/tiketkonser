<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();
$pdo = db();

function img_src(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') return 'https://via.placeholder.com/800x500?text=Poster';
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
    MIN(e.event_date) AS event_date,
    MIN(e.venue) AS venue,
    MIN(e.city) AS city
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  JOIN ticket_types tt ON tt.id = oi.ticket_type_id
  JOIN events e ON e.id = tt.event_id
  WHERE o.user_id = ?
  GROUP BY o.id, o.order_code, o.status, o.order_date
  ORDER BY o.order_date DESC
  LIMIT 3
");
$st->execute([$u['id']]);
$last_orders = $st->fetchAll(PDO::FETCH_ASSOC);

$recommended = $pdo->query("
  SELECT id, title, poster_url, event_date, venue, city
  FROM events
  WHERE status = 'ACTIVE'
  ORDER BY event_date ASC, id ASC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$title = 'Dashboard User - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/user_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">

      <div class="app-topbar">
        <div>
          <h1 class="app-title m-0">Dashboard</h1>
          <div class="text-white-50 small">Hello, <span class="text-white fw-semibold"><?= e($u['name']) ?></span>!</div>
        </div>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (USER)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <div class="panel p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="fw-semibold text-white">Last Orders</div>
          <a class="link-primary text-decoration-none small" href="events.php">Lihat Semua</a>
        </div>

        <?php if (!$last_orders): ?>
          <div class="text-white-50">Belum ada pesanan.</div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($last_orders as $o): ?>
              <?php
                $loc = trim((string)($o['venue'] ?? ''));
                $city = trim((string)($o['city'] ?? ''));
                $location = trim($loc . ($city !== '' ? ', ' . $city : ''));
                $location = $location !== '' ? $location : '-';
              ?>
              <div class="col-md-4">
                <div class="event-card">
                  <div class="event-card__img">
                    <img src="<?= e(img_src($o['poster_url'] ?? '')) ?>" alt="">
                  </div>
                  <div class="event-card__body">
                    <div class="event-card__title"><?= e($o['event_name'] ?? '-') ?></div>
                    <div class="event-card__meta"><?= e($location) ?></div>
                    <a class="btn btn-outline-light btn-sm w-100 rounded-pill mt-3" href="eticket.php?id=<?= (int)$o['id'] ?>">Lihat E-Ticket</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="panel p-3">
        <div class="fw-semibold text-white mb-3">Recommended Event</div>

        <?php if (!$recommended): ?>
          <div class="text-white-50">Belum ada event aktif.</div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($recommended as $ev): ?>
              <?php
                $loc = trim((string)($ev['venue'] ?? ''));
                $city = trim((string)($ev['city'] ?? ''));
                $location = trim($loc . ($city !== '' ? ', ' . $city : ''));
                $location = $location !== '' ? $location : '-';
                $d = $ev['event_date'] ?? null;
                $dateTxt = $d ? date('d M Y', strtotime($d)) : '-';
              ?>
              <div class="col-md-4">
                <div class="event-card">
                  <div class="event-card__img">
                    <img src="<?= e(img_src($ev['poster_url'] ?? '')) ?>" alt="">
                  </div>
                  <div class="event-card__body">
                    <div class="event-card__title"><?= e($ev['title'] ?? '-') ?></div>
                    <div class="event-card__meta"><?= e($dateTxt) ?> • <?= e($location) ?></div>
                    <a class="btn btn-primary btn-sm w-100 rounded-pill mt-3" href="buy_process.php?event_id=<?= (int)$ev['id'] ?>">Beli Tiket</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

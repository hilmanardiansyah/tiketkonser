<?php

$u = require_login();
$pdo = db();

$events = $pdo->query("
  SELECT
    e.*,
    COALESCE(MIN(tt.price), 0) AS min_price,
    COALESCE(SUM(tt.quota - tt.sold), 0) AS remaining_qty
  FROM events e
  LEFT JOIN ticket_types tt ON tt.event_id = e.id
  WHERE (e.status IS NULL OR e.status = 'ACTIVE')
  GROUP BY e.id
  ORDER BY e.event_date ASC, e.start_time ASC, e.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

function poster_src(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') return 'https://via.placeholder.com/900x600?text=Poster';
  if (preg_match('#^https?://#i', $url)) return $url;
  if (str_starts_with($url, '/')) return $url;
  return BASE_URL . '/public/img/' . $url;
}

$title = 'Explore Events - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/user_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">

      <div class="app-topbar">
        <div>
          <h1 class="app-title m-0">Explore Events</h1>
          <div class="text-white-50 small">Temukan tiket konser favoritmu di sini.</div>
        </div>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <div class="panel p-3">
        <div class="row g-4">
          <?php foreach ($events as $ev): ?>
            <?php
              $location = trim((string)($ev['venue'] ?? ''));
              $city = trim((string)($ev['city'] ?? ''));
              $locTxt = trim($location . ($city !== '' ? ', ' . $city : ''));
              $locTxt = $locTxt !== '' ? $locTxt : '-';

              $dateTxt = ($ev['event_date'] ?? null) ? date('d M Y', strtotime($ev['event_date'])) : '-';
              $start = trim((string)($ev['start_time'] ?? ''));
              $end   = trim((string)($ev['end_time'] ?? ''));
              $timeTxt = $start ? ($start . ($end ? ' - ' . $end : '')) : '-';

              $remain = (int)($ev['remaining_qty'] ?? 0);
              $price  = (float)($ev['min_price'] ?? 0);
              $canBuy = $remain > 0 && $price > 0;
            ?>

            <div class="col-md-6 col-lg-4">
              <div class="ev-card">
                <div class="ev-poster">
                  <img src="<?= e(poster_src($ev['poster_url'] ?? '')) ?>" alt=""
                       onerror="this.src='https://via.placeholder.com/900x600?text=Poster'">
                  <div class="ev-badge">
                    <?= $remain > 0 ? 'Available' : 'Sold Out' ?>
                  </div>
                </div>

                <div class="ev-body">
                  <div class="ev-title"><?= e($ev['title'] ?? '-') ?></div>
                  <div class="ev-loc text-white-50 small"><?= e($locTxt) ?></div>

                  <div class="ev-meta">
                    <div class="ev-meta__item">
                      <div class="ev-meta__label">Date</div>
                      <div class="ev-meta__value"><?= e($dateTxt) ?></div>
                    </div>
                    <div class="ev-meta__item">
                      <div class="ev-meta__label">Time</div>
                      <div class="ev-meta__value"><?= e($timeTxt) ?></div>
                    </div>
                    <div class="ev-meta__item">
                      <div class="ev-meta__label">Remaining</div>
                      <div class="ev-meta__value"><?= (int)$remain ?></div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between mt-2">
                    <div class="ev-price">
                      <?php if ($price > 0): ?>
                        Rp <?= number_format($price, 0, ',', '.') ?>
                      <?php else: ?>
                        <span class="text-white-50 small">Belum ada ticket type</span>
                      <?php endif; ?>
                    </div>

                    <?php if ($canBuy): ?>
                      <a class="btn btn-primary btn-sm rounded-pill px-3"
                         href="buy_process.php?event_id=<?= (int)$ev['id'] ?>">
                        Beli Tiket
                      </a>
                    <?php else: ?>
                      <button class="btn btn-secondary btn-sm rounded-pill px-3" disabled>
                        Tidak tersedia
                      </button>
                    <?php endif; ?>
                  </div>

                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (!$events): ?>
            <div class="col-12">
              <div class="text-white-50">Belum ada event.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <style>
        .ev-card{border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04)}
        .ev-poster{position:relative;height:190px;background:#000}
        .ev-poster img{width:100%;height:100%;object-fit:cover;display:block}
        .ev-badge{position:absolute;top:12px;left:12px;padding:6px 10px;border-radius:999px;
          background:rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.16);color:#fff;font-size:12px;font-weight:700}
        .ev-body{padding:14px}
        .ev-title{color:#fff;font-weight:900;font-size:16px;line-height:1.2}
        .ev-meta{margin-top:10px;display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
        .ev-meta__item{padding:10px 10px;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:rgba(0,0,0,.20)}
        .ev-meta__label{color:rgba(229,231,235,.65);font-size:11px}
        .ev-meta__value{color:#fff;font-weight:800;font-size:12px;margin-top:2px}
        .ev-price{color:#fff;font-weight:900}
      </style>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

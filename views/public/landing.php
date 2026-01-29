<?php
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/navbar.php';

$BASE = defined('BASE_URL') ? BASE_URL : '';
$WEB  = $BASE . '/public';

$pdoConn = null;
if (isset($pdo) && $pdo instanceof PDO) $pdoConn = $pdo;
if (!$pdoConn && function_exists('db')) $pdoConn = db();
if (!$pdoConn && function_exists('get_db')) $pdoConn = get_db();

$events = [];
$featured = null;

if ($pdoConn instanceof PDO) {
  $stmt = $pdoConn->prepare("
    SELECT 
      e.*,
      MIN(tt.price) AS min_price
    FROM events e
    LEFT JOIN ticket_types tt ON tt.event_id = e.id
    GROUP BY e.id
    ORDER BY e.event_date ASC, e.start_time ASC, e.id DESC
    LIMIT 9
  ");
  $stmt->execute();
  $events = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $featured = $events[0] ?? null;
}

function rp($n) {
  if ($n === null || $n === '') return '';
  return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function dt_text($date, $time = null) {
  $d = trim((string)$date);
  if ($d === '') return '';
  $t = trim((string)$time);
  $out = $d;
  if ($t !== '') $out .= ' ' . substr($t, 0, 5);
  return $out;
}

$featTitle = $featured['title'] ?? 'Featured Event';
$featCity  = $featured['city'] ?? '';
$featVenue = $featured['venue'] ?? '';
$featDate  = dt_text($featured['event_date'] ?? '', $featured['start_time'] ?? '');
$featPoster = $featured['poster_file'] ?? $featured['poster_url'] ?? '';
$featMin = $featured['min_price'] ?? null;
$featId  = (int)($featured['id'] ?? 0);
$featHref = $featId ? ($WEB . '/events/' . $featId) : ($WEB . '/events');
?>

<link rel="stylesheet" href="<?= $WEB ?>/assets/landing.css">

<main class="site-wrap lp">

  <section class="panel lp-hero">
    <div class="lp-hero__grid">
      <div>
        <div class="lp-badge">
          <span class="app-dot"></span>
          <span>Fesmic</span>
        </div>

        <h1 class="lp-title">Cari & beli tiket konser dengan mudah</h1>
        <p class="lp-sub">
          Jelajahi event, pilih kategori tiket, bayar, dan unduh e-ticket + QR Code.
          Dashboard Admin/EO tersedia untuk kelola event, tiket, dan pesanan.
        </p>

        <div class="lp-cta">
          <a href="<?= $WEB ?>/events" class="btn btn-primary">Lihat Event</a>
          <a href="<?= $WEB ?>/login" class="btn btn-outline-light">Login</a>
          <a href="#how" class="btn btn-outline-light">Cara Kerja</a>
        </div>

        <div class="lp-stats">
          <div class="lp-stat">
            <div class="lp-stat__v">E-ticket + QR</div>
            <div class="lp-stat__l">Tiket otomatis tersedia setelah pembayaran terkonfirmasi</div>
          </div>
          <div class="lp-stat">
            <div class="lp-stat__v">Validasi Kuota</div>
            <div class="lp-stat__l">Cek sisa tiket sebelum order dibuat</div>
          </div>
          <div class="lp-stat">
            <div class="lp-stat__v">Dashboard</div>
            <div class="lp-stat__l">Admin & user terpisah, rapi, dan aman</div>
          </div>
        </div>
      </div>

      <a href="<?= e($featHref) ?>" class="lp-feature">
        <div class="lp-feature__poster">
          <?php if ($featPoster): ?>
            <img src="<?= e($featPoster) ?>" alt="<?= e($featTitle) ?>">
          <?php else: ?>
            <div class="lp-feature__noposter">No poster</div>
          <?php endif; ?>
        </div>

        <div class="lp-feature__body">
          <div class="lp-feature__kicker">Featured</div>
          <div class="lp-feature__title"><?= e($featTitle) ?></div>
          <div class="lp-feature__meta">
            <?= e(trim($featCity)) ?><?= $featCity && $featVenue ? ' • ' : '' ?><?= e(trim($featVenue)) ?><?= ($featCity || $featVenue) && $featDate ? ' • ' : '' ?><?= e($featDate) ?>
          </div>

          <div class="lp-feature__cta">
            <?php if ($featMin !== null): ?>
              <span class="lp-feature__price">Mulai <?= e(rp($featMin)) ?></span>
            <?php else: ?>
              <span class="lp-feature__price">Lihat detail event</span>
            <?php endif; ?>
            <span class="lp-feature__btn">Detail</span>
          </div>
        </div>
      </a>
    </div>
  </section>

  <section id="how" class="lp-sec">
    <div class="lp-sec__head">
      <div>
        <h2 class="lp-sec__title">Cara Kerja</h2>
        <p class="lp-sec__desc">Flow singkat dari pilih event sampai e-ticket jadi.</p>
      </div>
    </div>

    <div class="lp-steps">
      <div class="lp-step">
        <div class="lp-step__n">1</div>
        <div class="lp-step__t">Pilih Event</div>
        <div class="lp-step__d">
          Buka daftar event, lihat detail, lalu pilih kategori tiket dan jumlahnya.
        </div>
      </div>

      <div class="lp-step">
        <div class="lp-step__n">2</div>
        <div class="lp-step__t">Buat Pesanan</div>
        <div class="lp-step__d">
          Sistem membuat order dengan nomor unik dan status awal PENDING.
        </div>
      </div>

      <div class="lp-step">
        <div class="lp-step__n">3</div>
        <div class="lp-step__t">Bayar & Unduh</div>
        <div class="lp-step__d">
          Setelah pembayaran terkonfirmasi, status PAID dan e-ticket + QR tersedia.
        </div>
      </div>
    </div>
  </section>

  <section class="lp-sec">
    <div class="panel p-3 p-md-4">
      <div class="lp-search">
        <div>
          <h2 class="lp-sec__title mb-1">All Event</h2>
          <p class="lp-sec__desc">Cari event berdasarkan judul / kota / venue.</p>
        </div>

        <div>
          <input id="q" type="text" class="form-control" placeholder="Cari event..." autocomplete="off" style="border-radius:14px;">
        </div>
      </div>
    </div>
  </section>

  <section class="lp-sec">
    <div id="eventsGrid" class="row g-3">
      <?php if (!$events): ?>
        <div class="col-12">
          <div class="panel p-4" style="color: rgba(229,231,235,.75);">
            Belum ada event.
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($events as $ev): ?>
          <?php
            $id = (int)($ev['id'] ?? 0);
            $title = $ev['title'] ?? 'Untitled Event';
            $city = $ev['city'] ?? '';
            $venue = $ev['venue'] ?? '';
            $date = dt_text($ev['event_date'] ?? '', $ev['start_time'] ?? '');
            $poster = $ev['poster_file'] ?? $ev['poster_url'] ?? '';
            $minp = $ev['min_price'] ?? null;
            $href = $WEB . '/events/' . $id;
            $searchBlob = mb_strtolower(trim($title . ' ' . $city . ' ' . $venue));
          ?>
          <div class="col-12 col-sm-6 col-lg-4 lp-card" data-search="<?= e($searchBlob) ?>">
            <a class="event-card d-block text-decoration-none" href="<?= e($href) ?>">
              <div class="event-card__img">
                <?php if ($poster): ?>
                  <img src="<?= e($poster) ?>" alt="<?= e($title) ?>">
                <?php else: ?>
                  <div style="height:170px; display:flex; align-items:center; justify-content:center; color:rgba(229,231,235,.6); background:rgba(255,255,255,.04);">No poster</div>
                <?php endif; ?>
              </div>
              <div class="event-card__body">
                <div class="event-card__title"><?= e($title) ?></div>
                <div class="event-card__meta">
                  <?= e(trim($city)) ?><?= $city && $venue ? ' • ' : '' ?><?= e(trim($venue)) ?><?= ($city || $venue) && $date ? ' • ' : '' ?><?= e($date ?: '—') ?>
                </div>
                <div class="mt-2" style="color: rgba(229,231,235,.78); font-weight:800; font-size:13px;">
                  <?= $minp !== null ? e('Mulai ' . rp($minp)) : e('Lihat harga tiket') ?>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="text-center mt-4">
      <a href="<?= $WEB ?>/events" class="btn btn-outline-light" style="border-radius:999px; font-weight:800;">
        Lihat Semua Event
      </a>
    </div>
  </section>

  <div class="lp-foot">
    <div>© <?= date('Y') ?> Fesmic</div>
    <div>TiketKonser</div>
  </div>

</main>

<script>
  const q = document.getElementById('q');
  const cards = Array.from(document.querySelectorAll('.lp-card'));

  function filterCards() {
    const v = (q.value || '').trim().toLowerCase();
    if (!v) {
      cards.forEach(c => c.style.display = '');
      return;
    }
    cards.forEach(c => {
      const s = (c.getAttribute('data-search') || '');
      c.style.display = s.includes(v) ? '' : 'none';
    });
  }

  q.addEventListener('input', filterCards);
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

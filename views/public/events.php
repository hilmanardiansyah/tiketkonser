<?php
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/navbar.php';

$BASE = defined('BASE_URL') ? BASE_URL : '';
$WEB  = $BASE . '/public';

$pdoConn = null;
if (isset($pdo) && $pdo instanceof PDO) $pdoConn = $pdo;
if (!$pdoConn && function_exists('db')) $pdoConn = db();
if (!$pdoConn && function_exists('get_db')) $pdoConn = get_db();

$q = trim($_GET['q'] ?? '');
$events = [];
$total = 0;

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

if ($pdoConn instanceof PDO) {
  $where = "";
  $params = [];
  if ($q !== '') {
    $where = "WHERE (e.title LIKE :q OR e.city LIKE :q OR e.venue LIKE :q)";
    $params[':q'] = '%' . $q . '%';
  }

  $stmtTotal = $pdoConn->prepare("SELECT COUNT(*) FROM events e $where");
  $stmtTotal->execute($params);
  $total = (int)$stmtTotal->fetchColumn();

  $stmt = $pdoConn->prepare("
    SELECT 
      e.*,
      MIN(tt.price) AS min_price
    FROM events e
    LEFT JOIN ticket_types tt ON tt.event_id = e.id
    $where
    GROUP BY e.id
    ORDER BY e.event_date ASC, e.start_time ASC, e.id DESC
    LIMIT 60
  ");
  $stmt->execute($params);
  $events = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>

<link rel="stylesheet" href="<?= $WEB ?>/assets/landing.css">

<main class="site-wrap lp">

  <section class="panel p-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <div class="d-inline-flex align-items-center gap-2 mb-2">
          <span class="app-dot"></span>
          <span class="text-white-50" style="font-weight:800; letter-spacing:.2px;">All Event</span>
        </div>
        <h1 class="m-0" style="font-size:30px; font-weight:900; line-height:1.15; color:#fff;">
          Temukan event favoritmu
        </h1>
        <div class="mt-2" style="color: rgba(229,231,235,.70); font-size:13px;">
          <?= $total ? e($total) . ' event tersedia' : 'Daftar event' ?>
        </div>
      </div>

      <form method="GET" action="" class="d-flex gap-2 flex-wrap" style="width: min(520px, 100%);">
        <input
          type="text"
          name="q"
          value="<?= e($q) ?>"
          class="form-control"
          placeholder="Cari judul / kota / venue..."
          style="border-radius:14px; flex: 1 1 280px;"
        >
        <button class="btn btn-primary" type="submit" style="border-radius:999px; font-weight:800; padding:10px 14px;">
          Cari
        </button>
        <?php if ($q !== ''): ?>
          <a class="btn btn-outline-light" href="<?= $WEB ?>/events" style="border-radius:999px; font-weight:800; padding:10px 14px;">
            Reset
          </a>
        <?php endif; ?>
      </form>
    </div>
  </section>

  <section class="lp-sec">
    <div class="row g-3">
      <?php if (!$events): ?>
        <div class="col-12">
          <div class="panel p-4" style="color: rgba(229,231,235,.75);">
            Event tidak ditemukan.
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
          ?>
          <div class="col-12 col-sm-6 col-lg-4">
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
                <div class="d-flex align-items-center justify-content-between mt-2">
                  <div style="color: rgba(229,231,235,.78); font-weight:900; font-size:13px;">
                    <?= $minp !== null ? e('Mulai ' . rp($minp)) : e('Lihat harga tiket') ?>
                  </div>
                  <span style="color: rgba(229,231,235,.60); font-weight:800; font-size:12px;">
                    Detail →
                  </span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="lp-foot">
    <div>© <?= date('Y') ?> Fesmic</div>
    <div>TiketKonser</div>
  </div>

</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>

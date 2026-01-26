<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();
$pdo = db();

function img_src(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') return 'https://via.placeholder.com/900x600?text=Poster';
  if (preg_match('#^https?://#i', $url)) return $url;
  if (str_starts_with($url, '/')) return $url;
  return BASE_URL . '/public/img/' . $url;
}

$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("
  SELECT
    o.id,
    o.user_id,
    o.order_code,
    o.status,
    o.order_date,
    o.total_amount,
    MIN(e.title) AS event_title,
    MIN(e.poster_url) AS poster_url,
    MIN(e.event_date) AS event_date,
    MIN(e.venue) AS venue,
    MIN(e.city) AS city
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  JOIN ticket_types tt ON tt.id = oi.ticket_type_id
  JOIN events e ON e.id = tt.event_id
  WHERE o.id = ? AND o.user_id = ?
  GROUP BY o.id, o.user_id, o.order_code, o.status, o.order_date, o.total_amount
  LIMIT 1
");
$st->execute([$id, (int)$u['id']]);
$t = $st->fetch(PDO::FETCH_ASSOC);

if (!$t) {
  die("Tiket tidak ditemukan.");
}

$st = $pdo->prepare("
  SELECT t.ticket_code, t.status
  FROM tickets t
  JOIN order_items oi ON oi.id = t.order_item_id
  WHERE oi.order_id = ?
  ORDER BY t.id ASC
");
$st->execute([(int)$t['id']]);
$tickets = $st->fetchAll(PDO::FETCH_ASSOC);

$venue = trim((string)($t['venue'] ?? ''));
$city  = trim((string)($t['city'] ?? ''));
$location = trim($venue . ($city !== '' ? ', ' . $city : ''));
$location = $location !== '' ? $location : '-';

$eventDate = $t['event_date'] ?? null;
$dateTxt = $eventDate ? date('d M Y', strtotime($eventDate)) : '-';

$qrData = $tickets[0]['ticket_code'] ?? ($t['order_code'] ?? '');
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qrData);

$title = 'E-Ticket - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/user_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">

      <div class="app-topbar">
        <div>
          <h1 class="app-title m-0">E-Ticket</h1>
          <div class="text-white-50 small">Order: <span class="text-white fw-semibold"><?= e($t['order_code']) ?></span></div>
        </div>
        <div class="app-user">
          <a class="btn btn-outline-light btn-sm rounded-pill" href="history.php">Back</a>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <div class="panel p-3">
        <div class="ticket-wrap">
          <div class="ticket-card">
            <div class="ticket-left">
              <img class="ticket-poster" src="<?= e(img_src($t['poster_url'] ?? '')) ?>" alt="">
              <div class="ticket-brand">
                <div class="ticket-dot"></div>
                <div class="ticket-brand__name">Fesmic</div>
              </div>
            </div>

            <div class="ticket-right">
              <div class="ticket-title"><?= e($t['event_title'] ?? '-') ?></div>
              <div class="ticket-meta"><?= e($dateTxt) ?> • <?= e($location) ?></div>

              <div class="ticket-qr">
                <img src="<?= e($qrUrl) ?>" alt="">
              </div>

              <div class="ticket-row">
                <div class="ticket-code">CODE: <?= e($t['order_code']) ?></div>
                <?php
                  $stt = strtoupper((string)($t['status'] ?? ''));
                  $badge = $stt === 'PAID' ? 'bg-success' : ($stt === 'PENDING' ? 'bg-warning text-dark' : 'bg-secondary');
                ?>
                <span class="badge <?= e($badge) ?>"><?= e($stt) ?></span>
              </div>

              <div class="ticket-subtitle">Tickets</div>
              <div class="ticket-list">
                <?php if (!$tickets): ?>
                  <div class="text-white-50 small">Belum ada ticket yang tergenerate.</div>
                <?php else: ?>
                  <?php foreach ($tickets as $idx => $tk): ?>
                    <div class="ticket-item">
                      <div class="ticket-item__n">#<?= (int)($idx + 1) ?></div>
                      <div class="ticket-item__code"><?= e($tk['ticket_code'] ?? '-') ?></div>
                      <div class="ticket-item__st"><?= e(strtoupper((string)($tk['status'] ?? ''))) ?></div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary rounded-pill w-100" onclick="window.print()">Download Ticket</button>
                <a class="btn btn-outline-light rounded-pill w-100" href="history.php">Order History</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <style>
        .ticket-wrap{display:flex;justify-content:center;padding:14px 0}
        .ticket-card{width:100%;max-width:920px;display:grid;grid-template-columns:340px 1fr;gap:16px}
        .ticket-left{position:relative;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.12);background:rgba(0,0,0,.25)}
        .ticket-poster{width:100%;height:100%;min-height:520px;object-fit:cover;display:block;filter:saturate(1.05) contrast(1.02)}
        .ticket-brand{position:absolute;left:14px;top:14px;display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:999px;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(10px)}
        .ticket-dot{width:10px;height:10px;border-radius:3px;background:#2563eb}
        .ticket-brand__name{color:#fff;font-weight:800;letter-spacing:.2px}
        .ticket-right{border-radius:18px;border:1px solid rgba(255,255,255,.12);background:rgba(0,0,0,.22);padding:18px}
        .ticket-title{color:#fff;font-weight:900;font-size:22px;line-height:1.2}
        .ticket-meta{margin-top:6px;color:rgba(229,231,235,.72);font-size:13px}
        .ticket-qr{margin-top:16px;display:flex;justify-content:center}
        .ticket-qr img{width:220px;height:220px;border-radius:16px;background:#fff;padding:10px}
        .ticket-row{margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:10px}
        .ticket-code{color:#fff;font-weight:700;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-size:12px}
        .ticket-subtitle{margin-top:14px;color:#fff;font-weight:800;font-size:14px}
        .ticket-list{margin-top:10px;display:flex;flex-direction:column;gap:8px}
        .ticket-item{display:grid;grid-template-columns:64px 1fr 90px;gap:10px;align-items:center;padding:10px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.04)}
        .ticket-item__n{color:rgba(229,231,235,.70);font-size:12px}
        .ticket-item__code{color:#fff;font-weight:800;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .ticket-item__st{color:rgba(229,231,235,.70);font-size:12px;text-align:right}
        @media (max-width: 900px){.ticket-card{grid-template-columns:1fr}.ticket-poster{min-height:260px}}
        @media print{
          .app-sidebar,.app-topbar,.btn{display:none!important}
          .app-main{padding:0!important}
          .panel{border:none!important;box-shadow:none!important}
          .ticket-right{border:none!important;background:transparent!important}
          .ticket-left{border:none!important}
        }
      </style>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

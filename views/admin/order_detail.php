<?php
require_once __DIR__ . '/../_init.php';
$admin = require_admin();
$pdo = db();

$WEB = BASE_URL . '/public';

$order_code = trim($_GET['order_code'] ?? '');
if ($order_code === '') {
  http_response_code(404);
  require __DIR__ . '/../layout/header.php';
  ?>
  <div class="app-shell">
    <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>
    <main class="app-main">
      <div class="app-inner">
        <div class="panel p-3">
          <div class="text-white fw-semibold mb-1">Not Found</div>
          <div class="text-white-50">order_code tidak ada.</div>
          <div class="mt-3">
            <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/admin/orders') ?>">Back</a>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php
  require __DIR__ . '/../layout/footer.php';
  exit;
}

$st = $pdo->prepare("
  SELECT
    o.*,
    u.name AS customer_name,
    u.email AS customer_email
  FROM orders o
  JOIN users u ON u.id = o.user_id
  WHERE o.order_code = ?
  LIMIT 1
");
$st->execute([$order_code]);
$order = $st->fetch(PDO::FETCH_ASSOC);

if (!$order) {
  http_response_code(404);
  require __DIR__ . '/../layout/header.php';
  ?>
  <div class="app-shell">
    <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>
    <main class="app-main">
      <div class="app-inner">
        <div class="panel p-3">
          <div class="text-white fw-semibold mb-1">Not Found</div>
          <div class="text-white-50">
            Order dengan kode <span class="text-white"><?= e($order_code) ?></span> tidak ditemukan.
          </div>
          <div class="mt-3">
            <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/admin/orders') ?>">Back</a>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php
  require __DIR__ . '/../layout/footer.php';
  exit;
}

$st = $pdo->prepare("
  SELECT
    oi.*,
    tt.name AS ticket_type_name,
    tt.price AS ticket_type_price,
    e.title AS event_title,
    e.event_date,
    e.start_time,
    e.end_time,
    e.venue,
    e.city
  FROM order_items oi
  JOIN ticket_types tt ON tt.id = oi.ticket_type_id
  JOIN events e ON e.id = tt.event_id
  WHERE oi.order_id = ?
  ORDER BY oi.id ASC
");
$st->execute([(int)$order['id']]);
$items = $st->fetchAll(PDO::FETCH_ASSOC);

$st = $pdo->prepare("
  SELECT
    t.id,
    t.ticket_code,
    t.status,
    t.attendee_name,
    t.checked_in_at,
    oi.id AS order_item_id,
    tt.name AS ticket_type_name,
    e.title AS event_title
  FROM tickets t
  JOIN order_items oi ON oi.id = t.order_item_id
  JOIN ticket_types tt ON tt.id = oi.ticket_type_id
  JOIN events e ON e.id = tt.event_id
  WHERE oi.order_id = ?
  ORDER BY t.id ASC
");
$st->execute([(int)$order['id']]);
$tickets = $st->fetchAll(PDO::FETCH_ASSOC);

$st = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$st->execute([(int)$order['id']]);
$payment = $st->fetch(PDO::FETCH_ASSOC);

$total_qty = 0;
foreach ($items as $it) $total_qty += (int)($it['qty'] ?? 0);

$badge = ($order['status'] ?? '') === 'PAID'
  ? 'bg-success'
  : (($order['status'] ?? '') === 'PENDING' ? 'bg-warning text-dark' : 'bg-secondary');

$title = 'Order Detail';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <h1 class="app-title">Order Detail</h1>
        <div class="app-user">
          <div class="app-pill"><?= e($admin['name']) ?> (<?= e($admin['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/logout') ?>">Logout</a>
        </div>
      </div>

      <div class="panel p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-3">
          <div>
            <div class="text-white fw-semibold"><?= e($order['order_code']) ?></div>
            <div class="text-white-50 small">
              <?= e($order['order_date'] ?: $order['created_at']) ?>
            </div>
          </div>

          <div class="text-end">
            <div class="mb-1">
              <span class="badge <?= e($badge) ?>"><?= e($order['status']) ?></span>
            </div>
            <div class="text-white fw-semibold">
              Rp <?= number_format((float)$order['total_amount'], 0, ',', '.') ?>
            </div>
            <div class="text-white-50 small">Qty: <?= (int)$total_qty ?></div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="panel p-3 h-100">
            <div class="text-white fw-semibold mb-2">Customer</div>
            <div class="text-white"><?= e($order['customer_name']) ?></div>
            <div class="text-white-50 small"><?= e($order['customer_email']) ?></div>

            <hr class="border-secondary my-3">

            <div class="text-white fw-semibold mb-2">Payment</div>
            <?php if ($payment): ?>
              <div class="d-flex justify-content-between">
                <div class="text-white-50">Method</div>
                <div class="text-white"><?= e($payment['method'] ?? '-') ?></div>
              </div>
              <div class="d-flex justify-content-between">
                <div class="text-white-50">Ref</div>
                <div class="text-white"><?= e($payment['payment_ref'] ?? '-') ?></div>
              </div>
              <div class="d-flex justify-content-between">
                <div class="text-white-50">Status</div>
                <div class="text-white"><?= e($payment['status'] ?? '-') ?></div>
              </div>
              <div class="d-flex justify-content-between">
                <div class="text-white-50">Paid At</div>
                <div class="text-white"><?= e($payment['paid_at'] ?? '-') ?></div>
              </div>
            <?php else: ?>
              <div class="text-white-50">Belum ada payment record.</div>
            <?php endif; ?>

            <div class="mt-3">
              <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/admin/orders') ?>">Back</a>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="panel p-3 h-100">
            <div class="text-white fw-semibold mb-2">Event</div>
            <?php
              $first = $items[0] ?? null;
              $loc = $first ? trim(($first['venue'] ?? '') . (isset($first['city']) && $first['city'] !== '' ? ', ' . $first['city'] : '')) : '-';
              $dt  = $first ? ($first['event_date'] ?? '-') : '-';
              $tm  = $first ? trim(($first['start_time'] ?? '') . (($first['end_time'] ?? '') ? ' - ' . $first['end_time'] : '')) : '';
            ?>
            <div class="text-white"><?= e($first['event_title'] ?? '-') ?></div>
            <div class="text-white-50 small"><?= e($loc ?: '-') ?></div>
            <div class="text-white-50 small"><?= e($dt) ?> <?= $tm ? '• ' . e($tm) : '' ?></div>
          </div>
        </div>
      </div>

      <div class="panel p-3 mt-3">
        <div class="text-white fw-semibold mb-2">Items</div>
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Ticket Type</th>
                <th class="text-end" style="width:120px;">Qty</th>
                <th class="text-end" style="width:160px;">Unit</th>
                <th class="text-end" style="width:180px;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td class="fw-semibold"><?= e($it['ticket_type_name'] ?? '-') ?></td>
                  <td class="text-end"><?= (int)($it['qty'] ?? 0) ?></td>
                  <td class="text-end">Rp <?= number_format((float)($it['unit_price'] ?? 0), 0, ',', '.') ?></td>
                  <td class="text-end">Rp <?= number_format((float)($it['subtotal'] ?? 0), 0, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$items): ?>
                <tr><td colspan="4" class="text-muted">Order item kosong.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel p-3 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="text-white fw-semibold">Tickets</div>
          <div class="text-white-50 small">Total: <?= count($tickets) ?></div>
        </div>

        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width:90px;">ID</th>
                <th>Ticket Code</th>
                <th>Type</th>
                <th style="width:120px;">Status</th>
                <th>Attendee</th>
                <th style="width:200px;">Checked In</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
                <tr>
                  <td><?= (int)$t['id'] ?></td>
                  <td class="fw-semibold"><?= e($t['ticket_code'] ?? '-') ?></td>
                  <td><?= e($t['ticket_type_name'] ?? '-') ?></td>
                  <td>
                    <?php
                      $ts = $t['status'] ?? '';
                      $tb = $ts==='ACTIVE' ? 'bg-success' : ($ts==='USED' ? 'bg-secondary' : 'bg-warning text-dark');
                    ?>
                    <span class="badge <?= e($tb) ?>"><?= e($ts ?: '-') ?></span>
                  </td>
                  <td><?= e($t['attendee_name'] ?? '-') ?></td>
                  <td><?= e($t['checked_in_at'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$tickets): ?>
                <tr><td colspan="6" class="text-muted">Belum ada tiket (order belum dikonfirmasi / belum generate).</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

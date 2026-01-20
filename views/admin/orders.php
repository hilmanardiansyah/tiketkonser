<?php
require_once __DIR__ . '/../_init.php';
$admin = require_admin();
$pdo = db();

if (!isset($_SESSION['flash'])) $_SESSION['flash'] = null;
$flash = $_SESSION['flash'];
$_SESSION['flash'] = null;

function gen_code(string $prefix): string {
  return $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
}

// ====== HANDLE ACTIONS (POST) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // EXPIRE / CANCEL
  if ($action === 'expire') {
    $order_id = (int)($_POST['order_id'] ?? 0);

    try {
      $st = $pdo->prepare("UPDATE orders SET status='EXPIRED', updated_at=NOW() WHERE id=? AND status='PENDING'");
      $st->execute([$order_id]);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Order berhasil diubah ke EXPIRED.'];
    } catch (Throwable $e) {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal expire: ' . $e->getMessage()];
    }

    header('Location: orders.php');
    exit;
  }

  // CONFIRM PAYMENT (generate payment + tickets + update sold + update order status)
  if ($action === 'confirm') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $method = trim($_POST['method'] ?? 'transfer');

    try {
      $pdo->beginTransaction();

      // Lock order
      $st = $pdo->prepare("SELECT * FROM orders WHERE id=? FOR UPDATE");
      $st->execute([$order_id]);
      $order = $st->fetch(PDO::FETCH_ASSOC);

      if (!$order) throw new Exception("Order tidak ditemukan.");
      if (($order['status'] ?? '') !== 'PENDING') throw new Exception("Order bukan PENDING (status: {$order['status']}).");

      // Items + lock ticket_types
      $st = $pdo->prepare("
        SELECT
          oi.*,
          tt.name AS ticket_name,
          tt.quota, tt.sold,
          tt.price AS current_price,
          e.title AS event_title
        FROM order_items oi
        JOIN ticket_types tt ON tt.id = oi.ticket_type_id
        JOIN events e ON e.id = tt.event_id
        WHERE oi.order_id = ?
        FOR UPDATE
      ");
      $st->execute([$order_id]);
      $items = $st->fetchAll(PDO::FETCH_ASSOC);

      if (!$items) throw new Exception("Order item kosong.");

      // Re-check quota before confirm
      foreach ($items as $it) {
        $quota = (int)($it['quota'] ?? 0);
        $sold  = (int)($it['sold'] ?? 0);
        $qty   = (int)($it['qty'] ?? 0);
        if ($sold + $qty > $quota) {
          throw new Exception("Kuota tidak cukup untuk {$it['ticket_name']} (sisa: " . ($quota - $sold) . ", minta: {$qty}).");
        }
      }

      // Create payment (if not exists)
      $st = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE order_id=?");
      $st->execute([$order_id]);
      $payCount = (int)$st->fetchColumn();

      $payment_ref = gen_code('PAY');
      if ($payCount === 0) {
        $st = $pdo->prepare("
          INSERT INTO payments (order_id, method, payment_ref, amount, status, paid_at)
          VALUES (?, ?, ?, ?, 'PAID', NOW())
        ");
        $st->execute([$order_id, $method, $payment_ref, $order['total_amount']]);
      } else {
        // kalau sudah ada payment record, kita tetap lanjut generate ticket + update status
        $payment_ref = 'EXISTING';
      }

      // Update order status
      $st = $pdo->prepare("UPDATE orders SET status='PAID', updated_at=NOW() WHERE id=?");
      $st->execute([$order_id]);

      // Update sold + generate tickets
      foreach ($items as $it) {
        $ticket_type_id = (int)$it['ticket_type_id'];
        $qty = (int)$it['qty'];
        $order_item_id = (int)$it['id'];

        // update sold
        $st = $pdo->prepare("UPDATE ticket_types SET sold = sold + ?, updated_at=NOW() WHERE id=?");
        $st->execute([$qty, $ticket_type_id]);

        // generate tickets rows
        for ($i=0; $i < $qty; $i++) {
          $ticket_code = gen_code('TIX');

          $qr_payload = json_encode([
            'ticket_code' => $ticket_code,
            'order_code'  => $order['order_code'],
            'order_item_id' => $order_item_id,
          ], JSON_UNESCAPED_SLASHES);

          $st = $pdo->prepare("
            INSERT INTO tickets (order_item_id, ticket_code, qr_payload, attendee_name, status, checked_in_at)
            VALUES (?, ?, ?, NULL, 'ACTIVE', NULL)
          ");
          $st->execute([$order_item_id, $ticket_code, $qr_payload]);
        }
      }

      $pdo->commit();
      $_SESSION['flash'] = ['type' => 'success', 'msg' => "Pembayaran dikonfirmasi. Order jadi PAID & ticket ter-generate."];
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal confirm: ' . $e->getMessage()];
    }

    header('Location: orders.php');
    exit;
  }
}

// ====== FILTERS (GET) ======
$status = trim($_GET['status'] ?? 'ALL');   // ALL | PENDING | PAID | EXPIRED
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($status !== 'ALL') {
  $where[] = "o.status = ?";
  $params[] = $status;
}

if ($q !== '') {
  $where[] = "(o.order_code LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
  $like = "%$q%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$orders = [];
$st = $pdo->prepare("
  SELECT
    o.id, o.order_code, o.status, o.total_amount, o.order_date, o.created_at,
    u.name AS customer_name, u.email AS customer_email,
    (SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi WHERE oi.order_id=o.id) AS total_qty,
    (SELECT COUNT(*) FROM tickets t
      JOIN order_items oi2 ON oi2.id=t.order_item_id
      WHERE oi2.order_id=o.id) AS tickets_count
  FROM orders o
  JOIN users u ON u.id=o.user_id
  $whereSql
  ORDER BY COALESCE(o.order_date, o.created_at) DESC, o.id DESC
  LIMIT 100
");
$st->execute($params);
$orders = $st->fetchAll(PDO::FETCH_ASSOC);

$title = 'Order Management';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/navbar.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Admin - Order Management</h3>
    <a class="btn btn-outline-secondary" href="dashboard.php">Kembali</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-2" method="get">
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <?php foreach (['ALL','PENDING','PAID','EXPIRED'] as $opt): ?>
              <option value="<?= $opt ?>" <?= $status===$opt?'selected':'' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-7">
          <label class="form-label">Search</label>
          <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="order_code / nama / email">
        </div>
        <div class="col-md-2 d-grid">
          <label class="form-label">&nbsp;</label>
          <button class="btn btn-primary">Filter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th class="text-end">Total</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Tickets</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="fw-semibold"><?= e($o['order_code']) ?></td>
            <td>
              <div><?= e($o['customer_name']) ?></div>
              <div class="text-muted small"><?= e($o['customer_email']) ?></div>
            </td>
            <td class="text-end">Rp <?= number_format((float)$o['total_amount'], 0, ',', '.') ?></td>
            <td class="text-end"><?= (int)$o['total_qty'] ?></td>
            <td class="text-end"><?= (int)$o['tickets_count'] ?></td>
            <td>
              <span class="badge
                <?php
                  $st = $o['status'];
                  echo $st==='PAID' ? 'bg-success' : ($st==='PENDING' ? 'bg-warning text-dark' : 'bg-secondary');
                ?>">
                <?= e($o['status']) ?>
              </span>
            </td>
            <td><?= e($o['order_date'] ?: $o['created_at']) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary"
                 href="order_detail.php?order_code=<?= urlencode($o['order_code']) ?>">
                Detail
              </a>

              <?php if (($o['status'] ?? '') === 'PENDING'): ?>
                <form class="d-inline" method="post" onsubmit="return confirm('Konfirmasi pembayaran order ini?');">
                  <input type="hidden" name="action" value="confirm">
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <input type="hidden" name="method" value="transfer">
                  <button class="btn btn-sm btn-outline-success">Confirm</button>
                </form>

                <form class="d-inline" method="post" onsubmit="return confirm('Ubah order ini menjadi EXPIRED?');">
                  <input type="hidden" name="action" value="expire">
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Expire</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$orders): ?>
          <tr><td colspan="8" class="text-muted">Tidak ada data.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

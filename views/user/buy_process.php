<?php


$u   = require_login();
$pdo = db();

// input
$event_id       = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$ticket_type_id = isset($_GET['ticket_type_id']) ? (int)$_GET['ticket_type_id'] : 0;
$qty            = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;

if ($event_id <= 0 && $ticket_type_id <= 0) {
  header("Location: events.php");
  exit;
}

try {
  $pdo->beginTransaction();

  // 1) Tentukan ticket_type_id jika user cuma kirim event_id
  if ($ticket_type_id <= 0) {
    $stmtPick = $pdo->prepare("
      SELECT id
      FROM ticket_types
      WHERE event_id = ?
      ORDER BY price ASC, id ASC
      LIMIT 1
      FOR UPDATE
    ");
    $stmtPick->execute([$event_id]);
    $picked = $stmtPick->fetch(PDO::FETCH_ASSOC);

    if (!$picked) {
      throw new Exception("Tiket untuk event ini belum tersedia.");
    }
    $ticket_type_id = (int)$picked['id'];
  }

  // 2) Lock & cek kuota ticket type
  $stmtTT = $pdo->prepare("
    SELECT id, event_id, name, price, quota, sold, sales_start, sales_end
    FROM ticket_types
    WHERE id = ?
    FOR UPDATE
  ");
  $stmtTT->execute([$ticket_type_id]);
  $tt = $stmtTT->fetch(PDO::FETCH_ASSOC);

  if (!$tt) {
    throw new Exception("Jenis tiket tidak ditemukan.");
  }

  // kalau event_id dikirim, pastikan cocok
  if ($event_id > 0 && (int)$tt['event_id'] !== $event_id) {
    throw new Exception("Jenis tiket tidak cocok dengan event.");
  }
  $event_id = (int)$tt['event_id'];

  // opsional: validasi masa penjualan
  $now = date('Y-m-d H:i:s');
  if (!empty($tt['sales_start']) && $now < $tt['sales_start']) {
    throw new Exception("Penjualan tiket belum dibuka.");
  }
  if (!empty($tt['sales_end']) && $now > $tt['sales_end']) {
    throw new Exception("Penjualan tiket sudah ditutup.");
  }

  $quota = (int)$tt['quota'];
  $sold  = (int)$tt['sold'];
  $avail = $quota - $sold;

  if ($avail < $qty) {
    throw new Exception("Maaf, kuota tiket tidak cukup. Sisa: {$avail}");
  }

  // 3) Generate order_code
  $order_code = "FES-" . strtoupper(bin2hex(random_bytes(3))); // contoh: FES-A1B2C3

  $unit_price   = (float)$tt['price'];
  $total_amount = $unit_price * $qty;

  // 4) Insert orders (tanpa event_id)
  $stmtOrder = $pdo->prepare("
    INSERT INTO orders (user_id, order_code, order_date, status, total_amount, created_at, updated_at)
    VALUES (?, ?, NOW(), 'PAID', ?, NOW(), NOW())
  ");
  $stmtOrder->execute([$u['id'], $order_code, $total_amount]);
  $order_id = (int)$pdo->lastInsertId();

  // 5) Insert order_items
  $stmtItem = $pdo->prepare("
    INSERT INTO order_items (order_id, ticket_type_id, qty, unit_price, subtotal)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmtItem->execute([$order_id, $ticket_type_id, $qty, $unit_price, $total_amount]);
  $order_item_id = (int)$pdo->lastInsertId();

  // 6) Update sold
  $stmtUpd = $pdo->prepare("
    UPDATE ticket_types
    SET sold = sold + ?
    WHERE id = ?
  ");
  $stmtUpd->execute([$qty, $ticket_type_id]);

  // 7) Generate tickets
  $stmtTicket = $pdo->prepare("
    INSERT INTO tickets (order_item_id, ticket_code, qr_payload, attendee_name, status, checked_in_at)
    VALUES (?, ?, ?, ?, 'UNUSED', NULL)
  ");

  for ($i = 1; $i <= $qty; $i++) {
    $ticket_code = $order_code . '-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
    $qr_payload  = json_encode([
      'order_code'  => $order_code,
      'ticket_code' => $ticket_code,
      'event_id'    => $event_id,
      'ticket_type' => $tt['name'],
    ], JSON_UNESCAPED_SLASHES);

    $stmtTicket->execute([$order_item_id, $ticket_code, $qr_payload, $u['name'] ?? '']);
  }

  $pdo->commit();


  header("Location: eticket.php?id=" . $order_id);
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  die("Gagal membeli tiket: " . $e->getMessage());
}

<?php
require_once __DIR__ . '/../_init.php';
$u = require_login();
$pdo = db();

$id = $_GET['id'] ?? 0;
$st = $pdo->prepare("
  SELECT o.*, e.name as event_name, e.date as event_date, e.location
  FROM orders o
  JOIN events e ON o.event_id = e.id
  WHERE o.id = ? AND o.user_id = ?
");
$st->execute([$id, $u['id']]);
$t = $st->fetch();

if (!$t) die("Tiket tidak ditemukan.");
?>

<div class="d-flex justify-content-center align-items-center vh-100" style="background: #000;">
  <div class="bg-white p-5 rounded-5 text-center" style="width: 350px; color: black;">
    <h2 class="fw-black mb-4">FESMIC.</h2>
    <div class="mb-4">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= e($t['order_code']) ?>" class="img-fluid">
    </div>
    <h4 class="fw-bold mb-1"><?= e($t['event_name']) ?></h4>
    <p class="text-muted small"><?= date('d M Y', strtotime($t['event_date'])) ?> | <?= e($t['location']) ?></p>
    <hr class="border-dashed">
    <div class="d-flex justify-content-between small font-monospace">
      <span>CODE: <?= e($t['order_code']) ?></span>
      <span class="text-success fw-bold">PAID</span>
    </div>
    <button onclick="window.print()" class="btn btn-dark w-100 mt-4 rounded-pill">Download Ticket</button>
  </div>
</div>

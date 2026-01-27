<?php
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// TODO: ambil detail event dari DB/API berdasarkan $id
$event = null;

if (!$event) {
  echo "<main class='container'>Event tidak ditemukan.</main>";
  include __DIR__ . '/../layout/footer.php';
  exit;
}
?>

<main class="container">
  <h1><?= htmlspecialchars($event['title']) ?></h1>
  <p><?= htmlspecialchars($event['description']) ?></p>

  <?php if (!isset($_SESSION['user'])): ?>
    <a class="btn" href="index.php?page=login">Login untuk beli tiket</a>
  <?php else: ?>
    <a class="btn" href="index.php?page=buy&event_id=<?= (int)$event['id'] ?>">Beli Tiket</a>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>

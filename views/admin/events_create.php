<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();

function dt_local_to_mysql(?string $s): ?string {
  if (!$s) return null;
  $s = str_replace('T', ' ', $s);
  if (strlen($s) === 16) $s .= ':00';
  return $s;
}

function mysql_to_dt_local(?string $s): string {
  if (!$s) return '';
  return str_replace(' ', 'T', substr($s, 0, 16));
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$eventId = (int)($_GET['event_id'] ?? ($_POST['event_id'] ?? 0));
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

if ($eventId <= 0 || $id <= 0) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Parameter tidak valid.'];
  header('Location: tickets.php');
  exit;
}

$stmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Event tidak ditemukan.'];
  header('Location: tickets.php');
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE id = ? AND event_id = ?");
$stmt->execute([$id, $eventId]);
$edit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edit) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Ticket type tidak ditemukan.'];
  header('Location: tickets.php?event_id=' . $eventId);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $quota = (int)($_POST['quota'] ?? 0);
  $sales_start = dt_local_to_mysql($_POST['sales_start'] ?? null);
  $sales_end   = dt_local_to_mysql($_POST['sales_end'] ?? null);

  if ($name === '' || $price <= 0 || $quota <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Name, price, quota wajib valid.'];
    header('Location: tickets_edit.php?event_id=' . $eventId . '&id=' . $id);
    exit;
  }

  try {
    $stmt = $pdo->prepare("
      UPDATE ticket_types
      SET name = ?, price = ?, quota = ?, sales_start = ?, sales_end = ?, updated_at = NOW()
      WHERE id = ? AND event_id = ?
    ");
    $stmt->execute([$name, $price, $quota, $sales_start, $sales_end, $id, $eventId]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket type berhasil diupdate.'];
    header('Location: tickets.php?event_id=' . $eventId);
    exit;
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal update ticket type: ' . $e->getMessage()];
    header('Location: tickets_edit.php?event_id=' . $eventId . '&id=' . $id);
    exit;
  }
}

$title = 'Edit Ticket Type';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <h1 class="app-title m-0">Edit Ticket Type</h1>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="panel p-3 mb-3">
        <div class="text-white-50 small">Event: <span class="text-white fw-semibold"><?= e($event['title']) ?></span> (<?= e($event['event_date']) ?>)</div>
      </div>

      <div class="panel p-3">
        <form method="post" action="tickets_edit.php?event_id=<?= (int)$eventId ?>&id=<?= (int)$id ?>">
          <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Name *</label>
              <input class="form-control" name="name" value="<?= e($edit['name'] ?? '') ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Price *</label>
              <input type="number" step="0.01" class="form-control" name="price" value="<?= e((string)($edit['price'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Quota *</label>
              <input type="number" class="form-control" name="quota" value="<?= e((string)($edit['quota'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Sales Start</label>
              <input type="datetime-local" class="form-control" name="sales_start" value="<?= e(mysql_to_dt_local($edit['sales_start'] ?? null)) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Sales End</label>
              <input type="datetime-local" class="form-control" name="sales_end" value="<?= e(mysql_to_dt_local($edit['sales_end'] ?? null)) ?>">
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary rounded-pill px-4" type="submit">Update</button>
              <a class="btn btn-outline-light rounded-pill px-4" href="tickets.php?event_id=<?= (int)$eventId ?>">Back</a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function dt_local_to_mysql(?string $s): ?string {
  if (!$s) return null;
  $s = str_replace('T', ' ', $s);
  if (strlen($s) === 16) $s .= ':00';
  return $s;
}

$events = $pdo->query("SELECT id, title, event_date FROM events ORDER BY event_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0 && !empty($events)) $eventId = (int)$events[0]['id'];

if ($eventId <= 0) {
  $title = 'Ticket Management';
  require __DIR__ . '/../layout/header.php';
  ?>
  <div class="app-shell">
    <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>
    <main class="app-main">
      <div class="app-inner">
        <div class="app-topbar">
          <h1 class="app-title m-0">Ticket Management</h1>
          <div class="app-user">
            <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
            <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
          </div>
        </div>
        <div class="alert alert-warning">Belum ada event. Buat event dulu di Event Management.</div>
      </div>
    </main>
  </div>
  <?php
  require __DIR__ . '/../layout/footer.php';
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
  $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Event tidak ditemukan.'];
  header('Location: tickets.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $quota = (int)($_POST['quota'] ?? 0);
  $sales_start = dt_local_to_mysql($_POST['sales_start'] ?? null);
  $sales_end   = dt_local_to_mysql($_POST['sales_end'] ?? null);

  if ($name === '' || $price <= 0 || $quota <= 0) {
    $flash = ['type' => 'danger', 'msg' => 'Name, price, quota wajib valid.'];
  } else {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO ticket_types (event_id, name, price, quota, sold, sales_start, sales_end, created_at, updated_at)
        VALUES (?, ?, ?, ?, 0, ?, ?, NOW(), NOW())
      ");
      $stmt->execute([$eventId, $name, $price, $quota, $sales_start, $sales_end]);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket type berhasil ditambahkan.'];
      header('Location: tickets.php?event_id=' . $eventId);
      exit;
    } catch (Throwable $e) {
      $flash = ['type' => 'danger', 'msg' => 'Gagal simpan ticket type: ' . $e->getMessage()];
    }
  }
}

$title = 'Create Ticket Type';
require __DIR__ . '/../layout/header.php';
?>

<style>
.form-control.bg-dark::placeholder,.form-select.bg-dark::placeholder{color:rgba(255,255,255,.45)}
</style>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <div class="d-flex align-items-center gap-2">
          <h1 class="app-title m-0">Create Ticket Type</h1>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="tickets.php?event_id=<?= (int)$eventId ?>">Back</a>
        </div>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="panel p-3 mb-3">
        <div class="text-white-50 small">
          Event: <span class="text-white fw-semibold"><?= e($event['title'] ?? '-') ?></span>
          <span class="ms-2">(<?= e($event['event_date'] ?? '-') ?>)</span>
        </div>
      </div>

      <div class="panel p-3">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label text-white">Name *</label>
              <input class="form-control bg-dark text-white border-secondary" name="name" required>
            </div>

            <div class="col-md-4">
              <label class="form-label text-white">Price *</label>
              <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" name="price" required>
            </div>

            <div class="col-md-4">
              <label class="form-label text-white">Quota *</label>
              <input type="number" class="form-control bg-dark text-white border-secondary" name="quota" required>
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">Sales Start (opsional)</label>
              <input type="datetime-local" class="form-control bg-dark text-white border-secondary" name="sales_start">
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">Sales End (opsional)</label>
              <input type="datetime-local" class="form-control bg-dark text-white border-secondary" name="sales_end">
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary rounded-pill px-4" type="submit">Create</button>
              <a class="btn btn-outline-light rounded-pill px-4" href="tickets.php?event_id=<?= (int)$eventId ?>">Cancel</a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

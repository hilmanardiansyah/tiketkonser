<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title       = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $venue       = trim($_POST['venue'] ?? '');
  $city        = trim($_POST['city'] ?? '');
  $event_date  = $_POST['event_date'] ?? null;
  $start_time  = $_POST['start_time'] ?? null;
  $end_time    = $_POST['end_time'] ?? null;
  $poster_url  = trim($_POST['poster_url'] ?? '');
  $status      = $_POST['status'] ?? 'ACTIVE';

  if ($title === '' || !$event_date) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Title dan Event Date wajib diisi.'];
    header('Location: events_create.php');
    exit;
  }

  try {
    $created_by = (int)($u['id'] ?? 1);
    $stmt = $pdo->prepare("
      INSERT INTO events (created_by, title, description, venue, city, event_date, start_time, end_time, poster_url, status, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$created_by, $title, $description, $venue, $city, $event_date, $start_time, $end_time, $poster_url, $status]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil ditambahkan.'];
    header('Location: events.php');
    exit;
  } catch (Throwable $e) {
    $flash = ['type' => 'danger', 'msg' => 'Gagal simpan event: ' . $e->getMessage()];
  }
}

$title = 'Create Event';
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
          <h1 class="app-title m-0">Create Event</h1>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="events.php">Back</a>
        </div>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="panel p-3">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-white">Title *</label>
              <input class="form-control bg-dark text-white border-secondary" name="title" required>
            </div>

            <div class="col-md-3">
              <label class="form-label text-white">Event Date *</label>
              <input type="date" class="form-control bg-dark text-white border-secondary" name="event_date" required>
            </div>

            <div class="col-md-3">
              <label class="form-label text-white">Status</label>
              <select class="form-select bg-dark text-white border-secondary" name="status">
                <option value="ACTIVE" selected>ACTIVE</option>
                <option value="INACTIVE">INACTIVE</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">Venue</label>
              <input class="form-control bg-dark text-white border-secondary" name="venue">
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">City</label>
              <input class="form-control bg-dark text-white border-secondary" name="city">
            </div>

            <div class="col-md-3">
              <label class="form-label text-white">Start Time</label>
              <input type="time" class="form-control bg-dark text-white border-secondary" name="start_time">
            </div>

            <div class="col-md-3">
              <label class="form-label text-white">End Time</label>
              <input type="time" class="form-control bg-dark text-white border-secondary" name="end_time">
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">Poster URL</label>
              <input class="form-control bg-dark text-white border-secondary" name="poster_url">
            </div>

            <div class="col-12">
              <label class="form-label text-white">Description</label>
              <textarea class="form-control bg-dark text-white border-secondary" name="description" rows="3"></textarea>
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary rounded-pill px-4" type="submit">Create</button>
              <a class="btn btn-outline-light rounded-pill px-4" href="events.php">Cancel</a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

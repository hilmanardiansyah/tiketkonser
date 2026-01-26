<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID event tidak valid.'];
  header('Location: events.php');
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$edit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edit) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Event tidak ditemukan.'];
  header('Location: events.php');
  exit;
}

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
    header('Location: events_edit.php?id=' . $id);
    exit;
  }

  try {
    $stmt = $pdo->prepare("
      UPDATE events
      SET title = ?, description = ?, venue = ?, city = ?, event_date = ?, start_time = ?, end_time = ?, poster_url = ?, status = ?, updated_at = NOW()
      WHERE id = ?
    ");
    $stmt->execute([$title, $description, $venue, $city, $event_date, $start_time, $end_time, $poster_url, $status, $id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil diupdate.'];
    header('Location: events.php');
    exit;
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal update event: ' . $e->getMessage()];
    header('Location: events_edit.php?id=' . $id);
    exit;
  }
}

$title = 'Edit Event';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <h1 class="app-title m-0">Edit Event</h1>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e(BASE_URL . '/views/auth/logout.php') ?>">Logout</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="panel p-3">
        <form method="post" action="events_edit.php?id=<?= (int)$id ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Title *</label>
              <input class="form-control" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Event Date *</label>
              <input type="date" class="form-control" name="event_date" value="<?= e($edit['event_date'] ?? '') ?>" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <?php $st = $edit['status'] ?? 'ACTIVE'; ?>
                <option value="ACTIVE" <?= $st==='ACTIVE'?'selected':'' ?>>ACTIVE</option>
                <option value="INACTIVE" <?= $st==='INACTIVE'?'selected':'' ?>>INACTIVE</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Venue</label>
              <input class="form-control" name="venue" value="<?= e($edit['venue'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">City</label>
              <input class="form-control" name="city" value="<?= e($edit['city'] ?? '') ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Start Time</label>
              <input type="time" class="form-control" name="start_time" value="<?= e($edit['start_time'] ?? '') ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">End Time</label>
              <input type="time" class="form-control" name="end_time" value="<?= e($edit['end_time'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Poster URL</label>
              <input class="form-control" name="poster_url" value="<?= e($edit['poster_url'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary rounded-pill px-4" type="submit">Update</button>
              <a class="btn btn-outline-light rounded-pill px-4" href="events.php">Back</a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

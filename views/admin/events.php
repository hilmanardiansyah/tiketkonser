<?php
session_start();
require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'ADMIN') {
  header('Location: ../auth/login.php');
  exit;
}

$pdo = $pdo ?? (function_exists('db') ? db() : null);
if (!$pdo) {
  die('Koneksi DB tidak ditemukan. Pastikan src/db.php menyediakan $pdo atau fungsi db().');
}

function mysql_dt_from_datetime_local(?string $s): ?string {
  if (!$s) return null; // boleh kosong
  // input: 2026-01-20T12:34
  $s = str_replace('T', ' ', $s);
  if (strlen($s) === 16) $s .= ':00'; // tambah detik
  return $s;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // CREATE / UPDATE
  if ($action === 'save') {
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $event_date  = $_POST['event_date'] ?? null;    // YYYY-MM-DD
    $start_time  = $_POST['start_time'] ?? null;    // HH:MM
    $end_time    = $_POST['end_time'] ?? null;      // HH:MM
    $poster_url  = trim($_POST['poster_url'] ?? '');
    $status      = $_POST['status'] ?? 'ACTIVE';

    if ($title === '' || !$event_date) {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Title dan Event Date wajib diisi.'];
      header('Location: events.php');
      exit;
    }

    try {
      if ($id > 0) {
        $stmt = $pdo->prepare("
          UPDATE events
          SET title = ?, description = ?, venue = ?, city = ?, event_date = ?, start_time = ?, end_time = ?, poster_url = ?, status = ?, updated_at = NOW()
          WHERE id = ?
        ");
        $stmt->execute([$title, $description, $venue, $city, $event_date, $start_time, $end_time, $poster_url, $status, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil diupdate.'];
      } else {
        $created_by = (int)($user['id'] ?? 1);
        $stmt = $pdo->prepare("
          INSERT INTO events (created_by, title, description, venue, city, event_date, start_time, end_time, poster_url, status, created_at, updated_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$created_by, $title, $description, $venue, $city, $event_date, $start_time, $end_time, $poster_url, $status]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil ditambahkan.'];
      }
    } catch (Throwable $e) {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal simpan event: ' . $e->getMessage()];
    }

    header('Location: events.php');
    exit;
  }

  // DELETE (hati-hati bisa kena FK kalau event sudah punya ticket/order)
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    try {
      $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
      $stmt->execute([$id]);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil dihapus.'];
    } catch (Throwable $e) {
      // biasanya kena FK constraint, jadi saran: nonaktifkan status saja
      $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Tidak bisa hapus event (mungkin sudah punya tiket/pesanan). Coba ubah status jadi INACTIVE. Detail: ' . $e->getMessage()];
    }

    header('Location: events.php');
    exit;
  }
}

// mode edit
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
  $stmt->execute([$editId]);
  $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$events = $pdo->query("
  SELECT
    e.*,
    (SELECT COUNT(*) FROM ticket_types tt WHERE tt.event_id = e.id) AS ticket_types_count,
    (SELECT COALESCE(SUM(tt.sold),0) FROM ticket_types tt WHERE tt.event_id = e.id) AS sold_total
  FROM events e
  ORDER BY e.event_date DESC, e.start_time DESC, e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/navbar.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Admin - Event Management</h3>
    <a class="btn btn-outline-secondary" href="dashboard.php">Kembali ke Dashboard</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header"><?= $edit ? 'Edit Event' : 'Tambah Event' ?></div>
    <div class="card-body">
      <form method="post" action="events.php">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Title *</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Event Date *</label>
            <input type="date" class="form-control" name="event_date" value="<?= htmlspecialchars($edit['event_date'] ?? '') ?>" required>
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
            <input class="form-control" name="venue" value="<?= htmlspecialchars($edit['venue'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">City</label>
            <input class="form-control" name="city" value="<?= htmlspecialchars($edit['city'] ?? '') ?>">
          </div>

          <div class="col-md-3">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" value="<?= htmlspecialchars($edit['start_time'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" value="<?= htmlspecialchars($edit['end_time'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Poster URL</label>
            <input class="form-control" name="poster_url" value="<?= htmlspecialchars($edit['poster_url'] ?? '') ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= $edit ? 'Update' : 'Create' ?></button>
            <?php if ($edit): ?>
              <a class="btn btn-outline-secondary" href="events.php">Batal Edit</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Daftar Event</div>
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Tanggal</th>
            <th>Kota</th>
            <th>Status</th>
            <th>Ticket Types</th>
            <th>Sold</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $e): ?>
          <tr>
            <td><?= (int)$e['id'] ?></td>
            <td><?= htmlspecialchars($e['title']) ?></td>
            <td><?= htmlspecialchars($e['event_date']) ?></td>
            <td><?= htmlspecialchars($e['city'] ?? '-') ?></td>
            <td>
              <span class="badge <?= ($e['status'] ?? '') === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                <?= htmlspecialchars($e['status'] ?? '-') ?>
              </span>
            </td>
            <td><?= (int)($e['ticket_types_count'] ?? 0) ?></td>
            <td><?= (int)($e['sold_total'] ?? 0) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="events.php?edit=<?= (int)$e['id'] ?>">Edit</a>
              <a class="btn btn-sm btn-outline-success" href="tickets.php?event_id=<?= (int)$e['id'] ?>">Kelola Tiket</a>
              <form method="post" action="events.php" class="d-inline" onsubmit="return confirm('Yakin hapus event ini? Kalau event sudah punya ticket/order, bisa gagal.');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

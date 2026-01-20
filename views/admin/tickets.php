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
if (!$pdo) die('Koneksi DB tidak ditemukan.');

function dt_local_to_mysql(?string $s): ?string {
  if (!$s) return null;
  $s = str_replace('T', ' ', $s);
  if (strlen($s) === 16) $s .= ':00';
  return $s;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$events = $pdo->query("SELECT id, title, event_date FROM events ORDER BY event_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0 && !empty($events)) $eventId = (int)$events[0]['id'];

if ($eventId <= 0) {
  include __DIR__ . '/../layout/header.php';
  include __DIR__ . '/../layout/navbar.php';
  echo '<div class="container py-4"><div class="alert alert-warning">Belum ada event. Buat event dulu di menu Event Management.</div></div>';
  include __DIR__ . '/../layout/footer.php';
  exit;
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $event_id = (int)($_POST['event_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $quota = (int)($_POST['quota'] ?? 0);
    $sales_start = dt_local_to_mysql($_POST['sales_start'] ?? null);
    $sales_end   = dt_local_to_mysql($_POST['sales_end'] ?? null);

    if ($event_id <= 0 || $name === '' || $price <= 0 || $quota <= 0) {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Event, name, price, quota wajib valid.'];
      header('Location: tickets.php?event_id=' . $event_id);
      exit;
    }

    try {
      if ($id > 0) {
        $stmt = $pdo->prepare("
          UPDATE ticket_types
          SET name = ?, price = ?, quota = ?, sales_start = ?, sales_end = ?, updated_at = NOW()
          WHERE id = ? AND event_id = ?
        ");
        $stmt->execute([$name, $price, $quota, $sales_start, $sales_end, $id, $event_id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket type berhasil diupdate.'];
      } else {
        $stmt = $pdo->prepare("
          INSERT INTO ticket_types (event_id, name, price, quota, sold, sales_start, sales_end, created_at, updated_at)
          VALUES (?, ?, ?, ?, 0, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$event_id, $name, $price, $quota, $sales_start, $sales_end]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket type berhasil ditambahkan.'];
      }
    } catch (Throwable $e) {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal simpan ticket type: ' . $e->getMessage()];
    }

    header('Location: tickets.php?event_id=' . $event_id);
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $event_id = (int)($_POST['event_id'] ?? 0);

    try {
      $stmt = $pdo->prepare("DELETE FROM ticket_types WHERE id = ? AND event_id = ?");
      $stmt->execute([$id, $event_id]);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket type berhasil dihapus.'];
    } catch (Throwable $e) {
      $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Tidak bisa hapus ticket type (mungkin sudah dipakai order). Detail: ' . $e->getMessage()];
    }

    header('Location: tickets.php?event_id=' . $event_id);
    exit;
  }
}

// edit mode
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE id = ? AND event_id = ?");
  $stmt->execute([$editId, $eventId]);
  $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY id DESC");
$stmt->execute([$eventId]);
$ticketTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/navbar.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Admin - Ticket Management</h3>
    <a class="btn btn-outline-secondary" href="dashboard.php">Kembali ke Dashboard</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">Pilih Event</div>
    <div class="card-body">
      <form method="get" action="tickets.php" class="row g-2 align-items-end">
        <div class="col-md-8">
          <label class="form-label">Event</label>
          <select class="form-select" name="event_id" onchange="this.form.submit()">
            <?php foreach ($events as $ev): ?>
              <option value="<?= (int)$ev['id'] ?>" <?= (int)$ev['id'] === $eventId ? 'selected' : '' ?>>
                <?= htmlspecialchars($ev['title']) ?> (<?= htmlspecialchars($ev['event_date']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <div class="small text-muted">
            Event terpilih: <b><?= htmlspecialchars($event['title'] ?? '-') ?></b>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><?= $edit ? 'Edit Ticket Type' : 'Tambah Ticket Type' ?></div>
    <div class="card-body">
      <form method="post" action="tickets.php?event_id=<?= (int)$eventId ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name *</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price *</label>
            <input type="number" step="0.01" class="form-control" name="price" value="<?= htmlspecialchars($edit['price'] ?? '') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Quota *</label>
            <input type="number" class="form-control" name="quota" value="<?= htmlspecialchars($edit['quota'] ?? '') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Sales Start (opsional)</label>
            <?php
              $ss = $edit['sales_start'] ?? '';
              $ssVal = $ss ? str_replace(' ', 'T', substr($ss, 0, 16)) : '';
            ?>
            <input type="datetime-local" class="form-control" name="sales_start" value="<?= htmlspecialchars($ssVal) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Sales End (opsional)</label>
            <?php
              $se = $edit['sales_end'] ?? '';
              $seVal = $se ? str_replace(' ', 'T', substr($se, 0, 16)) : '';
            ?>
            <input type="datetime-local" class="form-control" name="sales_end" value="<?= htmlspecialchars($seVal) ?>">
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= $edit ? 'Update' : 'Create' ?></button>
            <?php if ($edit): ?>
              <a class="btn btn-outline-secondary" href="tickets.php?event_id=<?= (int)$eventId ?>">Batal Edit</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Daftar Ticket Types</div>
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th class="text-end">Price</th>
            <th class="text-end">Quota</th>
            <th class="text-end">Sold</th>
            <th>Sales Start</th>
            <th>Sales End</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($ticketTypes as $t): ?>
          <tr>
            <td><?= (int)$t['id'] ?></td>
            <td><?= htmlspecialchars($t['name']) ?></td>
            <td class="text-end"><?= number_format((float)$t['price'], 0, ',', '.') ?></td>
            <td class="text-end"><?= (int)$t['quota'] ?></td>
            <td class="text-end"><?= (int)($t['sold'] ?? 0) ?></td>
            <td><?= htmlspecialchars($t['sales_start'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['sales_end'] ?? '-') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="tickets.php?event_id=<?= (int)$eventId ?>&edit=<?= (int)$t['id'] ?>">Edit</a>
              <form method="post" action="tickets.php?event_id=<?= (int)$eventId ?>" class="d-inline" onsubmit="return confirm('Yakin hapus ticket type ini?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
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

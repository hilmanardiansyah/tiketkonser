<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();
$WEB = BASE_URL . '/public';


$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $event_id = (int)($_POST['event_id'] ?? 0);
    try {
      $stmt = $pdo->prepare("DELETE FROM ticket_types WHERE id = ? AND event_id = ?");
      $stmt->execute([$id, $event_id]);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket type berhasil dihapus.'];
    } catch (Throwable $e) {
      $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Tidak bisa hapus ticket type. Detail: ' . $e->getMessage()];
    }
    header('Location: ' . $WEB . '/admin/tickets?event_id=' . $event_id);    exit;
  }
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY id DESC");
$stmt->execute([$eventId]);
$ticketTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Ticket Management';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <div class="d-flex align-items-center gap-2">
          <h1 class="app-title m-0">Ticket Management</h1>
          <a class="btn btn-primary btn-sm rounded-pill" href="<?= e($WEB . '/admin/tickets/create?event_id=' . (int)$eventId) ?>">Create</a>
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
        <form method="get" action="<?= e($WEB . '/admin/tickets') ?>" class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id" onchange="this.form.submit()">
              <?php foreach ($events as $ev): ?>
                <option value="<?= (int)$ev['id'] ?>" <?= (int)$ev['id'] === $eventId ? 'selected' : '' ?>>
                  <?= e($ev['title']) ?> (<?= e($ev['event_date']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <div class="text-white-50 small">Event terpilih: <span class="text-white fw-semibold"><?= e($event['title'] ?? '-') ?></span></div>
          </div>
        </form>
      </div>

      <div class="panel p-3">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width:72px;">ID</th>
                <th>Name</th>
                <th class="text-end" style="width:140px;">Price</th>
                <th class="text-end" style="width:110px;">Quota</th>
                <th class="text-end" style="width:90px;">Sold</th>
                <th style="width:200px;">Sales Start</th>
                <th style="width:200px;">Sales End</th>
                <th class="text-end" style="width:260px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ticketTypes as $t): ?>
                <tr>
                  <td><?= (int)$t['id'] ?></td>
                  <td class="fw-semibold"><?= e($t['name'] ?? '') ?></td>
                  <td class="text-end">Rp <?= number_format((float)$t['price'], 0, ',', '.') ?></td>
                  <td class="text-end"><?= (int)$t['quota'] ?></td>
                  <td class="text-end"><?= (int)($t['sold'] ?? 0) ?></td>
                  <td><?= e($t['sales_start'] ?? '-') ?></td>
                  <td><?= e($t['sales_end'] ?? '-') ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-light rounded-pill" href="<?= e($WEB . '/admin/tickets/edit?event_id=' . (int)$eventId . '&id=' . (int)$t['id']) ?>">Edit</a>
                    <form method="post" action="<?= e($WEB . '/admin/tickets?event_id=' . (int)$eventId) ?>" class="d-inline" onsubmit="return confirm('Yakin hapus ticket type ini?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                      <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$ticketTypes): ?>
                <tr><td colspan="8" class="text-muted">Belum ada ticket type.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

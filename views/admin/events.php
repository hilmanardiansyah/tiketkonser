<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();
$WEB = BASE_URL . '/public';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    try {
      $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
      $stmt->execute([$id]);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil dihapus.'];
    } catch (Throwable $e) {
      $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Tidak bisa hapus event. Coba ubah status jadi INACTIVE. Detail: ' . $e->getMessage()];
    }
    header('Location: ' . $WEB . '/admin/events');
    exit;
  }
}

$events = $pdo->query("
  SELECT
    e.*,
    (SELECT COUNT(*) FROM ticket_types tt WHERE tt.event_id = e.id) AS ticket_types_count,
    (SELECT COALESCE(SUM(tt.sold),0) FROM ticket_types tt WHERE tt.event_id = e.id) AS sold_total
  FROM events e
  ORDER BY e.event_date DESC, e.start_time DESC, e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$title = 'Event Management';
require __DIR__ . '/../layout/header.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <div class="d-flex align-items-center gap-2">
          <h1 class="app-title m-0">Event Management</h1>
          <a class="btn btn-primary btn-sm rounded-pill" href="<?= e($WEB . '/admin/events/create') ?>"
>Create</a>
        </div>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/logout') ?>"
>Logout</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="panel p-3">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width:72px;">ID</th>
                <th>Title</th>
                <th style="width:130px;">Tanggal</th>
                <th style="width:160px;">Lokasi</th>
                <th style="width:110px;">Status</th>
                <th class="text-end" style="width:120px;">Ticket Types</th>
                <th class="text-end" style="width:90px;">Sold</th>
                <th class="text-end" style="width:320px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $e): ?>
                <tr>
                  <td><?= (int)$e['id'] ?></td>
                  <td class="fw-semibold"><?= e($e['title'] ?? '') ?></td>
                  <td><?= e($e['event_date'] ?? '') ?></td>
                  <td><?= e(trim(($e['venue'] ?? '') . (isset($e['city']) && $e['city'] !== '' ? ', ' . $e['city'] : '')) ?: '-') ?></td>
                  <td>
                    <span class="badge <?= ($e['status'] ?? '') === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                      <?= e($e['status'] ?? '-') ?>
                    </span>
                  </td>
                  <td class="text-end"><?= (int)($e['ticket_types_count'] ?? 0) ?></td>
                  <td class="text-end"><?= (int)($e['sold_total'] ?? 0) ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-light rounded-pill" href="<?= e($WEB . '/admin/events/edit?id=' . (int)$e['id']) ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($WEB . '/admin/tickets?event_id=' . (int)$e['id']) ?>">Tickets</a>
                    <form method="post" action="<?= e($WEB . '/admin/events') ?>" class="d-inline" onsubmit="return confirm('Yakin hapus event ini?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$events): ?>
                <tr><td colspan="8" class="text-muted">Belum ada event.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

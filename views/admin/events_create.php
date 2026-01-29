<?php
require_once __DIR__ . '/../_init.php';
$u = require_admin();
$pdo = db();

$WEB = BASE_URL . '/public';
$UPLOAD_DIR = __DIR__ . '/../../public/uploads/events/';
$UPLOAD_URL = BASE_URL . '/public/uploads/events/';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $venue = trim($_POST['venue'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $event_date = trim($_POST['event_date'] ?? '');
  $start_time = trim($_POST['start_time'] ?? '') ?: null;
  $end_time = trim($_POST['end_time'] ?? '') ?: null;
  $status = ($_POST['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'ACTIVE' : 'INACTIVE';
  $poster_url = trim($_POST['poster_url'] ?? '');
  $poster_file = null;

  // Handle file upload
  if (isset($_FILES['poster_upload']) && $_FILES['poster_upload']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['poster_upload'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowed)) {
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $destination = $UPLOAD_DIR . $filename;
      
      if (!is_dir($UPLOAD_DIR)) {
        mkdir($UPLOAD_DIR, 0755, true);
      }
      
      if (move_uploaded_file($file['tmp_name'], $destination)) {
        $poster_file = $UPLOAD_URL . $filename;
      } else {
        $flash = ['type' => 'warning', 'msg' => 'Gagal upload file, tapi event tetap bisa disimpan.'];
      }
    } else {
      $flash = ['type' => 'warning', 'msg' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'];
    }
  }

  if ($title === '' || $event_date === '') {
    $flash = ['type' => 'danger', 'msg' => 'Title dan Event Date wajib diisi.'];
  } else {
    try {
      $created_by = (int)($u['id'] ?? 0);
      $stmt = $pdo->prepare("
        INSERT INTO events (title, description, venue, city, event_date, start_time, end_time, status, poster_url, poster_file, created_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
      ");
      $stmt->execute([$title, $description, $venue, $city, $event_date, $start_time, $end_time, $status, $poster_url, $poster_file, $created_by]);

      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Event berhasil dibuat.'];
      header('Location: ' . $WEB . '/admin/events');
      exit;
    } catch (Throwable $e) {
      $flash = ['type' => 'danger', 'msg' => 'Gagal membuat event: ' . $e->getMessage()];
    }
  }
}

$title_page = 'Create Event';
require __DIR__ . '/../layout/header.php';
?>

<style>
.form-control.bg-dark::placeholder, .form-select.bg-dark::placeholder { color: rgba(255,255,255,.45); }
.upload-preview { max-width: 200px; max-height: 150px; border-radius: 8px; margin-top: 8px; display: none; }
.upload-box { border: 2px dashed rgba(255,255,255,.2); border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .2s; }
.upload-box:hover { border-color: rgba(255,255,255,.4); }
.upload-box input[type="file"] { display: none; }
.or-divider { display: flex; align-items: center; gap: 12px; margin: 12px 0; color: rgba(255,255,255,.5); font-size: 12px; }
.or-divider::before, .or-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.15); }
</style>

<div class="app-shell">
  <?php require __DIR__ . '/../layout/admin_sidebar.php'; ?>

  <main class="app-main">
    <div class="app-inner">
      <div class="app-topbar">
        <div class="d-flex align-items-center gap-2">
          <h1 class="app-title m-0">Create Event</h1>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/admin/events') ?>">Back</a>
        </div>
        <div class="app-user">
          <div class="app-pill"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</div>
          <a class="btn btn-outline-light btn-sm rounded-pill" href="<?= e($WEB . '/logout') ?>">Logout</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="panel p-3">
        <form method="post" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label text-white">Title *</label>
              <input class="form-control bg-dark text-white border-secondary" name="title" value="<?= e($_POST['title'] ?? '') ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label text-white">Status *</label>
              <select class="form-select bg-dark text-white border-secondary" name="status">
                <option value="ACTIVE" <?= ($_POST['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                <option value="INACTIVE" <?= ($_POST['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label text-white">Description</label>
              <textarea class="form-control bg-dark text-white border-secondary" name="description" rows="4"><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">Venue</label>
              <input class="form-control bg-dark text-white border-secondary" name="venue" value="<?= e($_POST['venue'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label text-white">City</label>
              <input class="form-control bg-dark text-white border-secondary" name="city" value="<?= e($_POST['city'] ?? '') ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label text-white">Event Date *</label>
              <input type="date" class="form-control bg-dark text-white border-secondary" name="event_date" value="<?= e($_POST['event_date'] ?? '') ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label text-white">Start Time</label>
              <input type="time" class="form-control bg-dark text-white border-secondary" name="start_time" value="<?= e($_POST['start_time'] ?? '') ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label text-white">End Time</label>
              <input type="time" class="form-control bg-dark text-white border-secondary" name="end_time" value="<?= e($_POST['end_time'] ?? '') ?>">
            </div>

            <!-- Poster Section -->
            <div class="col-12">
              <label class="form-label text-white">Poster Event</label>
              
              <!-- Upload Option -->
              <div class="upload-box" id="uploadBox">
                <input type="file" name="poster_upload" id="posterUpload" accept="image/*">
                <div class="text-white-50">
                  <i class="bi bi-cloud-upload" style="font-size: 24px;"></i>
                  <div class="mt-2">Klik untuk upload gambar</div>
                  <div class="small">JPG, PNG, GIF, WebP (Max 5MB)</div>
                </div>
                <img id="uploadPreview" class="upload-preview" alt="Preview">
              </div>
              
              <div class="or-divider">ATAU</div>
              
              <!-- URL Option -->
              <input class="form-control bg-dark text-white border-secondary" name="poster_url" placeholder="https://example.com/poster.jpg" value="<?= e($_POST['poster_url'] ?? '') ?>">
              <div class="form-text text-white-50">Masukkan URL gambar poster jika tidak upload file</div>
            </div>

            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary rounded-pill px-4" type="submit">Create Event</button>
              <a class="btn btn-outline-light rounded-pill px-4" href="<?= e($WEB . '/admin/events') ?>">Cancel</a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<script>
const uploadBox = document.getElementById('uploadBox');
const posterUpload = document.getElementById('posterUpload');
const uploadPreview = document.getElementById('uploadPreview');

uploadBox.addEventListener('click', () => posterUpload.click());

posterUpload.addEventListener('change', function() {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      uploadPreview.src = e.target.result;
      uploadPreview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>

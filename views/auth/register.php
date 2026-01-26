<?php
require_once __DIR__ . '/../_init.php';

if (current_user()) {
  $u = current_user();
  header('Location: ' . BASE_URL . '/views/' . (($u['role'] ?? '') === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php'));
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $confirm = (string)($_POST['confirm_password'] ?? '');

  if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    $error = 'Nama, email, password wajib diisi';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter';
  } elseif ($password !== $confirm) {
    $error = 'Konfirmasi password tidak sama';
  } else {
    $pdo = db();
    $st = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $exists = $st->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
      $error = 'Email sudah terdaftar';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $role = 'USER';

      $ins = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, phone, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
      ");
      $ins->execute([$name, $email, $hash, $phone, $role]);

      $userId = (int)$pdo->lastInsertId();

      $_SESSION['user'] = [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'role' => $role,
      ];

      header('Location: ' . BASE_URL . '/views/user/dashboard.php');
      exit;
    }
  }
}

$title = 'Register';
require __DIR__ . '/../layout/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <h4 class="auth-brand mb-0">Fesmic</h4>
      <div class="auth-sub">Buat akun untuk mulai beli tiket</div>
    </div>

    <div class="auth-body">
      <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="on">
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input class="form-control" name="name" type="text" required placeholder="Nama lengkap" value="<?= e($_POST['name'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">No. HP</label>
          <input class="form-control" name="phone" type="text" placeholder="08xxxx" value="<?= e($_POST['phone'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" required placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" name="password" type="password" required placeholder="Minimal 6 karakter">
        </div>

        <div class="mb-3">
          <label class="form-label">Konfirmasi Password</label>
          <input class="form-control" name="confirm_password" type="password" required placeholder="Ulangi password">
        </div>

        <button class="btn btn-primary w-100">Daftar</button>
      </form>

      <div class="auth-footer">
        Sudah punya akun?
        <a class="auth-link" href="<?= e(BASE_URL . '/views/auth/login.php') ?>">Login</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

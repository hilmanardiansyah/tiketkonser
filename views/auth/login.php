<?php
require_once __DIR__ . '/../_init.php';

if (current_user()) {
  header('Location: ' . BASE_URL . '/public/dashboard');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $error = 'Email & password wajib diisi';
  } else {
    $pdo = db();
    $st = $pdo->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
      $error = 'Email atau password salah';
    } else {
      $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
      ];

      header('Location: ' . BASE_URL . '/public/dashboard');
      exit;
    }
  }
}

$title = 'Login';
require __DIR__ . '/../layout/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <h4 class="auth-brand mb-0">Fesmic</h4>
      <div class="auth-sub">Masuk untuk lanjut beli tiket & akses e-ticket</div>
    </div>

    <div class="auth-body">
      <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="on" action="<?= e(BASE_URL . '/public/login') ?>">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" required placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" name="password" type="password" required placeholder="Password">
        </div>

        <button class="btn btn-primary w-100">Login</button>
      </form>

      <div class="auth-footer">
        Belum punya akun?
        <a class="auth-link" href="<?= e(BASE_URL . '/views/auth/register.php') ?>">Daftar</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

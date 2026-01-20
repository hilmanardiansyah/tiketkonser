<?php
require_once __DIR__ . '/../_init.php';

if (current_user()) {
  $u = current_user();
  header('Location: ' . BASE_URL . '/views/' . (($u['role'] ?? '') === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php'));
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

      header('Location: ' . BASE_URL . '/views/' . ($user['role'] === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php'));
      exit;
    }
  }
}

$title = 'Login';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/navbar.php';
?>

<div class="container py-5" style="max-width:520px;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="mb-3">Login</h4>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" name="password" type="password" required>
        </div>
        <button class="btn btn-primary w-100">Masuk</button>
      </form>

      <div class="text-muted small mt-3">
        Contoh: admin@test.com / 123456
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

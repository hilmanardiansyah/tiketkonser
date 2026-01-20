<?php
namespace Controllers;

class AuthController {
  public function login(): void {
    $body = \get_json();

    $email = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($email === '' || $password === '') {
      \json_response(['error' => 'email dan password wajib diisi'], 422);
      return;
    }

    $pdo = \db();
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
      \json_response(['error' => 'Email atau password salah'], 401);
      return;
    }

    if (!password_verify($password, $user['password_hash'])) {
      \json_response(['error' => 'Email atau password salah'], 401);
      return;
    }

    $token = bin2hex(random_bytes(32));

    $upd = $pdo->prepare("UPDATE users SET api_token = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$token, (int)$user['id']]);

    \json_response([
      'token' => $token,
      'user' => [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
      ]
    ]);
  }
}

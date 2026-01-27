<?php

function require_auth(): array {
  $auth = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '');

  if (!preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
    json_response(['error' => 'Unauthorized (no token)'], 401);
    exit;
  }

  $token = trim($m[1]);

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE api_token = ? LIMIT 1");
  $stmt->execute([$token]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    json_response(['error' => 'Unauthorized (invalid token)'], 401);
    exit;
  }

  return $user;
}

function require_login(): array {
  if (!isset($_SESSION)) {
    return [];
  }

  $u = $_SESSION['user'] ?? null;

  if (!$u) {
    header('Location: ' . BASE_URL . '/public/login');
    exit;
  }

  return $u;
}

function require_admin(): array {
  $u = require_login();
  $role = strtoupper((string)($u['role'] ?? 'USER'));

  if ($role !== 'ADMIN') {
    http_response_code(403);
    echo "403 - Forbidden";
    exit;
  }

  return $u;
}

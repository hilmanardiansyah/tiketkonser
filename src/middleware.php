<?php

function require_auth(): array {
  // Ambil header Authorization (XAMPP kadang taro di HTTP_AUTHORIZATION / REDIRECT_HTTP_AUTHORIZATION)
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

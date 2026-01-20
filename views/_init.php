<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../src/db.php'; // pakai db() kamu

// ganti kalau nama folder project bukan tiketkonser
define('BASE_URL', '/tiketkonser');

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): array {
  $u = current_user();
  if (!$u) {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit;
  }
  return $u;
}

function require_admin(): array {
  $u = require_login();
  if (($u['role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    echo "Forbidden (Admin only)";
    exit;
  }
  return $u;
}

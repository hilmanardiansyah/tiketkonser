<?php

// =========================
// CONFIG BASIC
// =========================
if (!defined('BASE_URL')) {
  // SESUAIKAN dengan nama folder project kamu di htdocs
  // contoh kamu: http://localhost/tiketkonser/...
  define('BASE_URL', '/tiketkonser');
}

// =========================
// WEB HELPERS
// =========================
function e($str): string {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
  return isset($_SESSION['user']);
}

function redirect(string $path): void {
  // $path contoh: '/public/login' atau '/public/'
  header('Location: ' . BASE_URL . $path);
  exit;
}

// =========================
// API HELPERS (punyamu)
// =========================
function json_response($data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function get_json(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/middleware.php';

require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/EventController.php';
require_once __DIR__ . '/../src/controllers/OrderController.php';
require_once __DIR__ . '/../src/controllers/PaymentController.php';

use Controllers\AuthController;
use Controllers\EventController;
use Controllers\OrderController;
use Controllers\PaymentController;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

/**
 * Hitung path request relatif terhadap /public
 * Contoh:
 *   REQUEST_URI: /tiketkonser/public/history
 *   SCRIPT_NAME: /tiketkonser/public/index.php
 *   => path: /history
 */
if (strpos($uriPath, $scriptName) === 0) {
  $path = substr($uriPath, strlen($scriptName));
} else {
  $baseDir = rtrim(dirname($scriptName), '/');
  if ($baseDir !== '' && strpos($uriPath, $baseDir) === 0) {
    $path = substr($uriPath, strlen($baseDir));
  } else {
    $path = $uriPath;
  }
}

if ($path === '') $path = '/';
if ($path !== '/') $path = rtrim($path, '/');

function render(string $view, array $data = []): void {
  extract($data);
  require __DIR__ . '/../views/' . $view . '.php';
  exit;
}

function redirect_to(string $to): void {
  header('Location: ' . $to);
  exit;
}

$isApi = (strpos($path, '/api') === 0);

try {
  // =========================
  // API ROUTES
  // =========================
  if ($isApi) {
    header('Content-Type: application/json; charset=utf-8');

    if ($method === 'POST' && $path === '/api/auth/login') {
      (new AuthController())->login();
      exit;
    }

    if ($method === 'GET' && $path === '/api/events') {
      (new EventController())->index();
      exit;
    }

    if ($method === 'GET' && preg_match('#^/api/events/(\d+)$#', $path, $m)) {
      (new EventController())->show((int)$m[1]);
      exit;
    }

    if ($method === 'GET' && preg_match('#^/api/events/(\d+)/ticket-types$#', $path, $m)) {
      (new EventController())->ticketTypes((int)$m[1]);
      exit;
    }

    if ($method === 'POST' && $path === '/api/orders') {
      require_auth();
      (new OrderController())->create();
      exit;
    }

    if ($method === 'POST' && $path === '/api/payments/confirm') {
      require_auth();
      (new PaymentController())->confirm();
      exit;
    }

    if ($method === 'GET' && preg_match('#^/api/orders/([A-Za-z0-9\-]+)$#', $path, $m)) {
      require_auth();
      (new OrderController())->showByCode($m[1]);
      exit;
    }

    if ($method === 'GET' && preg_match('#^/api/orders/([A-Za-z0-9\-]+)/tickets$#', $path, $m)) {
      require_auth();
      (new OrderController())->ticketsByCode($m[1]);
      exit;
    }

    json_response(['error' => 'Not Found'], 404);
    exit;
  }

  // =========================
  // WEB ROUTES
  // =========================
  header('Content-Type: text/html; charset=utf-8');

  // --- Compatibility: kalau masih ada link user lama
  if ($method === 'GET' && $path === '/history.php') {
    redirect_to(BASE_URL . '/public/history');
  }
  if ($method === 'GET' && $path === '/eticket.php') {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    redirect_to(BASE_URL . '/public/eticket' . ($qs ? ('?' . $qs) : ''));
  }
  if ($method === 'GET' && $path === '/events.php') {
    redirect_to(BASE_URL . '/public/events');
  }

  // =========================
  // PUBLIC PAGES
  // =========================
  if ($method === 'GET' && $path === '/') {
    render('public/landing');
  }

  if ($method === 'GET' && $path === '/events') {
    render('public/events');
  }

  if ($method === 'GET' && preg_match('#^/events/(\d+)$#', $path, $m)) {
    render('public/event_detail', ['event_id' => (int)$m[1]]);
  }

  // =========================
  // AUTH
  // =========================
  if ($method === 'GET' && $path === '/login') {
    render('auth/login');
  }

  if ($method === 'POST' && $path === '/login') {
    render('auth/login');
  }

  if ($method === 'GET' && $path === '/logout') {
    session_destroy();
    redirect_to(BASE_URL . '/public/');
  }

  // =========================
  // DASHBOARD (ADMIN/USER)
  // =========================
  if ($method === 'GET' && $path === '/dashboard') {
    $u = require_login();
    $role = strtoupper((string)($u['role'] ?? 'USER'));
    if ($role === 'ADMIN') render('admin/dashboard');
    render('user/dashboard');
  }

  // =========================
  // USER ROUTES
  // =========================
  if ($method === 'GET' && $path === '/history') {
    require_login();
    render('user/history');
  }

  if ($method === 'GET' && $path === '/eticket') {
    require_login();
    $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    render('user/eticket', ['order_id' => $orderId]);
  }

  // =========================
  // ADMIN ROUTES (NEW + COMPAT .php)
  // =========================
  // LIST pages (support POST because there are forms)
  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/events' || $path === '/admin/events.php')) {
    require_admin();
    render('admin/events'); // views/admin/events.php
  }

  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/tickets' || $path === '/admin/tickets.php')) {
    require_admin();
    render('admin/tickets'); // views/admin/tickets.php
  }

  // Compatibility: /admin/events/tickets.php -> /admin/tickets
  if (($method === 'GET' || $method === 'POST') && $path === '/admin/events/tickets.php') {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    redirect_to(BASE_URL . '/public/admin/tickets' . ($qs ? ('?' . $qs) : ''));
  }

  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/orders' || $path === '/admin/orders.php')) {
    require_admin();
    render('admin/orders'); // views/admin/orders.php
  }

  // CREATE/EDIT pages (support POST too)
  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/events/create' || $path === '/admin/events_create.php')) {
    require_admin();
    render('admin/events_create'); // views/admin/events_create.php
  }

  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/events/edit' || $path === '/admin/events_edit.php')) {
    require_admin();
    render('admin/events_edit'); // views/admin/events_edit.php
  }

  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/tickets/create' || $path === '/admin/tickets_create.php')) {
    require_admin();
    render('admin/tickets_create'); // views/admin/tickets_create.php
  }

  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/tickets/edit' || $path === '/admin/tickets_edit.php')) {
    require_admin();
    render('admin/tickets_edit'); // views/admin/tickets_edit.php
  }

  // ORDER DETAIL (biasanya GET, tapi amanin kalau POST juga)
  if (($method === 'GET' || $method === 'POST') && ($path === '/admin/orders/detail' || $path === '/admin/order_detail.php')) {
    require_admin();
    render('admin/order_detail'); // views/admin/order_detail.php
  }

  // =========================
  // 404
  // =========================
  http_response_code(404);
  echo "404 - Page not found";
} catch (Throwable $e) {
  if ($isApi) {
    header('Content-Type: application/json; charset=utf-8');
    json_response(['error' => $e->getMessage()], 500);
  } else {
    http_response_code(500);
    echo "500 - " . htmlspecialchars($e->getMessage());
  }
}

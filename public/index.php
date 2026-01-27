<?php
session_start();

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

$method = $_SERVER['REQUEST_METHOD'];

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];

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

// helper render view
function render($view, $data = []) {
  extract($data);
  require __DIR__ . '/../views/' . $view . '.php';
  exit;
}

$isApi = (strpos($path, '/api') === 0);

try {
  // =========================
  // 1) ROUTER API (JSON)
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
      require_auth(); // wajib login (API)
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
  // 2) ROUTER WEB (HTML)
  // =========================
  header('Content-Type: text/html; charset=utf-8');

  // landing page default
  if ($method === 'GET' && $path === '/') {
    render('public/landing'); // buat: views/public/landing.php
  }

  // all events
  if ($method === 'GET' && $path === '/events') {
    render('public/events'); // buat: views/public/events.php
  }

  // event detail
  if ($method === 'GET' && preg_match('#^/events/(\d+)$#', $path, $m)) {
    render('public/event_detail', ['event_id' => (int)$m[1]]);
  }

  // login page (UI)
  if ($method === 'GET' && $path === '/login') {
    render('auth/login'); // kamu sudah punya: views/auth/login.php
  }

  // logout (opsional)
  if ($method === 'GET' && $path === '/logout') {
    session_destroy();
    header("Location: " . BASE_URL . "/public/");
    exit;
  }

  // dashboard (sudah kamu buat)
  if ($method === 'GET' && $path === '/dashboard') {
    // sesuaikan kunci sessionmu ya:
    $role = $_SESSION['user']['role'] ?? 'user';

    if ($role === 'admin') render('admin/dashboard');
    render('user/dashboard');
  }

  // 404 web
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

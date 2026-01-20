<?php
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
// contoh: /tiketkonser/public/index.php/api/auth/login

$scriptName = $_SERVER['SCRIPT_NAME'];
// contoh: /tiketkonser/public/index.php

if (strpos($uriPath, $scriptName) === 0) {
  $path = substr($uriPath, strlen($scriptName));
} else {
  $baseDir = rtrim(dirname($scriptName), '/'); // /tiketkonser/public
  if ($baseDir !== '' && strpos($uriPath, $baseDir) === 0) {
    $path = substr($uriPath, strlen($baseDir));
  } else {
    $path = $uriPath;
  }
}

if ($path === '') $path = '/';
if ($path !== '/') $path = rtrim($path, '/');

header('Content-Type: application/json; charset=utf-8');

try {
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
    require_auth(); // wajib login
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
} catch (Throwable $e) {
  json_response(['error' => $e->getMessage()], 500);
}

<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/middleware.php';

if (!defined('BASE_URL')) {
  define('BASE_URL', '/tiketkonser');
}

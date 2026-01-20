<?php
require_once __DIR__ . '/../_init.php';
session_destroy();
header('Location: ' . BASE_URL . '/views/auth/login.php');
exit;

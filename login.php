<?php
require __DIR__ . '/vendor/autoload.php';

use App\Controllers\LoginController;

$controller = new LoginController();
$controller->handleRequest();

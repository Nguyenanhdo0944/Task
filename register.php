<?php
require __DIR__ . '/vendor/autoload.php';

use App\Controllers\RegisterController;

$controller = new RegisterController();
$controller->handleRequest();

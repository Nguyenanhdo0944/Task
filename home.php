<?php
require __DIR__ . '/vendor/autoload.php';


use App\Controllers\HomeController;

session_start();

$controller = new HomeController();
$controller->handleRequest();

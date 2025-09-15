<?php
require 'vendor/autoload.php';

use App\Controllers\UserController;

session_start();

$controller = $_GET['controller'] ?? 'User';
$action     = $_GET['action'] ?? 'handleRequest';

switch ($controller) {
    case 'User':
        $ctrl = new UserController();
        if ($action === 'edit' && isset($_GET['id'])) {
            $ctrl->edit($_GET['id']);
        } elseif ($action === 'add') {
            $ctrl->add();
        } elseif ($action === 'remove' && isset($_GET['id'])) {
            $ctrl->remove($_GET['id']);
        } else {
            $ctrl->handleRequest();
        }
        break;

    default:
        echo "Controller not found!";
        break;
}

<?php

session_start();

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../controllers/UserController.php';

/** @var UserService $userService */
$controller = new UserController($userService);

$action = $_GET['action'] ?? '';

match($action) {
    'register' => $controller->register(),
    'login'    => $controller->login(),
    'logout'   => $controller->logout(),
    'me'       => $controller->me(),
    default    => (function() {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Acțiune inexistentă.']);
    })()
};
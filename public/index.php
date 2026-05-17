<?php
// public/index.php  — Front controller

session_start();

define('BASE_PATH', dirname(__DIR__));

$page   = $_GET['page']   ?? 'books';
$action = $_GET['action'] ?? 'index';

$routes = [
    'books' => [
        'controller' => 'BookController',
        'actions'    => ['index', 'add', 'edit', 'delete'],
    ],
    'members' => [
        'controller' => 'MemberController',
        'actions'    => ['index', 'add', 'edit', 'delete'],
    ],
    'borrows' => [
        'controller' => 'BorrowController',
        'actions'    => ['index', 'borrow', 'return'],
    ],
    'fines' => [
        'controller' => 'FineController',
        'actions'    => ['index', 'show', 'pay'],
    ],
];

// Flash helper — disponible dans toutes les vues
function flash(): array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return [];
}

if (!isset($routes[$page]) || !in_array($action, $routes[$page]['actions'])) {
    $page   = 'books';
    $action = 'index';
}

$controllerFile  = BASE_PATH . '/controllers/' . $routes[$page]['controller'] . '.php';
$controllerClass = $routes[$page]['controller'];

require_once $controllerFile;

$controller = new $controllerClass();
$controller->$action();
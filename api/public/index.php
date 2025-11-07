<?php
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// mapa de rotas → arquivo PHP
$routes = [
    "POST /api/usuarios" => __DIR__."/../routes/usuarios_post.php",
    "GET /api/usuarios" => __DIR__."/../routes/usuarios.php",
    'GET /api/ping' => __DIR__.'/../routes/ping.php',
];

$key = $method.' '.$uri;

if (isset($routes[$key])) {
    require $routes[$key];
    exit;
}

// 404 padrão JSON
require __DIR__.'/../lib/http.php';
cors();
json(['error' => 'Not Found', 'path' => $uri], 404);

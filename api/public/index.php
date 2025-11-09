<?php
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

/** rotas exatas (sem parâmetros) **/
$routes = [
    'GET /api/ping'      => __DIR__.'/../routes/ping.php',
    'GET /api/usuarios'  => __DIR__.'/../routes/usuarios.php',
    'POST /api/usuarios' => __DIR__.'/../routes/usuarios_post.php',
];

$key = $method.' '.$uri;
if (isset($routes[$key])) {
    require $routes[$key];
    exit;
}

/** rotas dinâmicas: /api/usuarios/{id} **/
if (preg_match('#^/api/usuarios/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/usuarios_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/usuarios_delete.php';
        exit;
    }
}

/** 404 padrão JSON **/
require __DIR__.'/../lib/http.php';
cors();
json(['error' => 'Not Found', 'path' => $uri], 404);

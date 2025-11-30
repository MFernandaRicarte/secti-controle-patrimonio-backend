<?php
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET /api/ping'      => __DIR__.'/../routes/ping.php',
    'GET /api/usuarios'  => __DIR__.'/../routes/usuarios.php',
    'POST /api/usuarios' => __DIR__.'/../routes/usuarios_post.php',
    'DELETE /api/usuarios/:id' => __DIR__.'/../routes/usuarios_delete.php',

    'GET /api/setores'   => __DIR__.'/../routes/setores.php',

    'GET /api/bens'      => __DIR__.'/../routes/bens.php',
    'POST /api/bens'     => __DIR__.'/../routes/bens_post.php',

    'GET /api/materiais' => __DIR__.'/../routes/materiais.php',
    'POST /api/materiais'=> __DIR__.'/../routes/materiais_post.php',
    'GET /api/materiais'  => __DIR__.'/../routes/materiais.php',
    'POST /api/materiais' => __DIR__.'/../routes/materiais_post.php',

    'POST /api/login'    => __DIR__.'/../routes/login.php',
];

$key = $method.' '.$uri;
if (isset($routes[$key])) {
    require $routes[$key];
    exit;
}

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

if (preg_match('#^/api/bens/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/bens_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/bens_delete.php';
        exit;
    }
}

if (preg_match('#^/api/materiais/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/materiais_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/materiais_delete.php';
        exit;
    }
}

require __DIR__.'/../lib/http.php';
cors();
json(['error' => 'Not Found', 'path' => $uri], 404);
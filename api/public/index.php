<?php
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    // Healthcheck
    'GET /api/ping'      => __DIR__.'/../routes/ping.php',

    // Usuários
    'GET /api/usuarios'        => __DIR__.'/../routes/usuarios.php',
    'POST /api/usuarios'       => __DIR__.'/../routes/usuarios_post.php',
    'DELETE /api/usuarios/:id' => __DIR__.'/../routes/usuarios_delete.php',

    // Setores e salas
    'GET /api/setores'   => __DIR__.'/../routes/setores.php',
    'POST /api/setores'  => __DIR__.'/../routes/setores_post.php',

    'GET /api/salas'     => __DIR__.'/../routes/salas.php',
    'POST /api/salas'    => __DIR__.'/../routes/salas_post.php',

    // Bens permanentes
    'GET /api/bens'      => __DIR__.'/../routes/bens.php',
    'POST /api/bens'     => __DIR__.'/../routes/bens_post.php',
    'POST /api/bens/upload' => __DIR__.'/../routes/bens_upload.php',
    'GET /api/bens-detalhes' => __DIR__.'/../routes/bens_detalhes.php',

    // Materiais de consumo
    'GET /api/materiais'        => __DIR__.'/../routes/materiais.php',
    'POST /api/materiais'       => __DIR__.'/../routes/materiais_post.php',

    // Tipos básicos
    'GET /api/tipos-materiais'   => __DIR__.'/../routes/tipos_materiais.php',
    'POST /api/tipos-materiais'  => __DIR__.'/../routes/tipos_materiais_post.php',

    'GET /api/tipos-eletronicos'   => __DIR__.'/../routes/tipos_eletronicos.php',
    'POST /api/tipos-eletronicos'  => __DIR__.'/../routes/tipos_eletronicos_post.php',

    'GET /api/transferencias'    => __DIR__.'/../routes/transferencias.php',
    'POST /api/transferencias'   => __DIR__.'/../routes/transferencias_post.php',

    // Login
    'POST /api/login'    => __DIR__.'/../routes/login.php',

    // Licitações
    'GET /api/licitacoes'      => __DIR__.'/../routes/licitacoes.php',
    'POST /api/licitacoes/cadastro'     => __DIR__.'/../routes/licitacoes_post.php',
    'PUT /api/licitacoes/:id/alterar'     => __DIR__.'/../routes/licitacoes_put.php',
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

if (preg_match('#^/api/tipos-materiais/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/tipos_materiais_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/tipos_materiais_delete.php';
        exit;
    }
}

if (preg_match('#^/api/tipos-eletronicos/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/tipos_eletronicos_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/tipos_eletronicos_delete.php';
        exit;
    }
}

if (preg_match('#^/api/setores/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/setores_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/setores_delete.php';
        exit;
    }
}

if (preg_match('#^/api/salas/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/salas_put.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__.'/../routes/salas_delete.php';
        exit;
    }
}

//Rota para alterar licitação
if (preg_match('#^/api/licitacoes/(\d+)/alterar$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__.'/../routes/licitacoes_put.php';
        exit;
    }
}

//Rota para deletar licitação
if (preg_match('#^/api/licitacoes/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $GLOBALS['routeParams'] = ['id' => $id];

    if ($method === 'DELETE') {
        require __DIR__.'/../routes/licitacoes_delete.php';
        exit;
    }
}

require __DIR__.'/../lib/http.php';
cors();
json(['error' => 'Not Found', 'path' => $uri], 404);
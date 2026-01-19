<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// =============================================================================
// ROTAS ESTÁTICAS (sem parâmetros dinâmicos)
// =============================================================================

$routes = [
    // Healthcheck
    'GET /api/ping' => __DIR__ . '/../routes/ping.php',

    // Auth
    'POST /api/login' => __DIR__ . '/../routes/auth/login.php',

    // Usuários
    'GET /api/usuarios' => __DIR__ . '/../routes/usuarios/list.php',
    'POST /api/usuarios' => __DIR__ . '/../routes/usuarios/create.php',

    // Setores
    'GET /api/setores' => __DIR__ . '/../routes/setores/list.php',
    'POST /api/setores' => __DIR__ . '/../routes/setores/create.php',

    // Salas
    'GET /api/salas' => __DIR__ . '/../routes/salas/list.php',
    'POST /api/salas' => __DIR__ . '/../routes/salas/create.php',

    // Bens
    'GET /api/bens' => __DIR__ . '/../routes/bens/list.php',
    'POST /api/bens' => __DIR__ . '/../routes/bens/create.php',
    'POST /api/bens/upload' => __DIR__ . '/../routes/bens/upload.php',
    'GET /api/bens-detalhes' => __DIR__ . '/../routes/bens/details.php',
    'GET /api/bens-excluidos' => __DIR__ . '/../routes/bens/excluidos.php',

    // Materiais de consumo
    'GET /api/materiais' => __DIR__ . '/../routes/materiais/list.php',
    'POST /api/materiais' => __DIR__ . '/../routes/materiais/create.php',

    // Tipos básicos
    'GET /api/tipos-materiais' => __DIR__ . '/../routes/tipos/materiais/list.php',
    'POST /api/tipos-materiais' => __DIR__ . '/../routes/tipos/materiais/create.php',

    'GET /api/tipos-eletronicos' => __DIR__ . '/../routes/tipos/eletronicos/list.php',
    'POST /api/tipos-eletronicos' => __DIR__ . '/../routes/tipos/eletronicos/create.php',

    // Transferências
    'GET /api/transferencias' => __DIR__ . '/../routes/transferencias/list.php',
    'POST /api/transferencias' => __DIR__ . '/../routes/transferencias/create.php',

    // Licitações
    'GET /api/licitacoes' => __DIR__ . '/../routes/licitacoes/list.php',
    'POST /api/licitacoes/cadastro' => __DIR__ . '/../routes/licitacoes/create.php',

    // LHS - Cursos
    'GET /api/lhs/cursos' => __DIR__ . '/../routes/lhs/cursos/list.php',
    'POST /api/lhs/cursos' => __DIR__ . '/../routes/lhs/cursos/create.php',

    // Healthcheck
    'GET /api/ping'    => __DIR__ . '/../routes/ping.php',
    'GET /api/ping_db' => __DIR__ . '/../routes/ping_db.php',

    // Notificações
    'GET /api/notificacoes' => __DIR__ . '/../routes/notificacoes/list.php',
    'POST /api/notificacoes' => __DIR__ . '/../routes/notificacoes/create.php', // superadmin
    'POST /api/notificacoes/marcar-lida' => __DIR__ . '/../routes/notificacoes/marcar_lida.php',


];

$key = $method . ' ' . $uri;
if (isset($routes[$key])) {
    require $routes[$key];
    exit;
}

// =============================================================================
// ROTAS DINÂMICAS (com parâmetros de ID)
// =============================================================================

// --- Usuários ---
if (preg_match('#^/api/usuarios/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/usuarios/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/usuarios/delete.php';
        exit;
    }
}

// --- Bens ---
if (preg_match('#^/api/bens/(\d+)/restaurar$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'POST') {
        require __DIR__ . '/../routes/bens/restaurar.php';
        exit;
    }
}

if (preg_match('#^/api/bens/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/bens/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/bens/delete.php';
        exit;
    }
}

// --- Materiais ---
if (preg_match('#^/api/materiais/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/materiais/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/materiais/delete.php';
        exit;
    }
}

// --- Tipos Materiais ---
if (preg_match('#^/api/tipos-materiais/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/tipos/materiais/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/tipos/materiais/delete.php';
        exit;
    }
}

// --- Tipos Eletrônicos ---
if (preg_match('#^/api/tipos-eletronicos/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/tipos/eletronicos/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/tipos/eletronicos/delete.php';
        exit;
    }
}

// --- Setores ---
if (preg_match('#^/api/setores/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/setores/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/setores/delete.php';
        exit;
    }
}

// --- Salas ---
if (preg_match('#^/api/salas/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/salas/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/salas/delete.php';
        exit;
    }
}

// --- Licitações ---
if (preg_match('#^/api/licitacoes/(\d+)/alterar$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/licitacoes/update.php';
        exit;
    }
}

if (preg_match('#^/api/licitacoes/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/licitacoes/delete.php';
        exit;
    }
}

if (preg_match('#^/api/licitacoes/(\d+)/detalhes$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/licitacoes/details.php';
        exit;
    }
}

if (preg_match('#^/api/licitacoes/(\d+)/documentos$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'POST') {
        require __DIR__ . '/../routes/licitacoes/documentos.php';
        exit;
    }
}

// --- LHS Cursos ---
if (preg_match('#^/api/lhs/cursos/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/lhs/cursos/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/lhs/cursos/delete.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/cursos/(\d+)/detalhes$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/lhs/cursos/details.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/cursos/(\d+)/materiais$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'POST') {
        require __DIR__ . '/../routes/lhs/cursos/materiais/upload.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/cursos/(\d+)/materiais/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1], 'material_id' => (int) $m[2]];
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/lhs/cursos/materiais/delete.php';
        exit;
    }
}

// =============================================================================
// FALLBACK - 404
// =============================================================================

require __DIR__ . '/../lib/http.php';
cors();
json(['error' => 'Not Found', 'path' => $uri], 404);
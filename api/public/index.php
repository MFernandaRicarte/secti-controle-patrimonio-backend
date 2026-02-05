<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// =============================================================================
// ROTAS ESTÁTICAS (sem parâmetros dinâmicos)
// =============================================================================

$routes = [
    // Healthcheck
    'GET /api/ping'    => __DIR__ . '/../routes/ping.php',
    'GET /api/ping_db' => __DIR__ . '/../routes/ping_db.php',

    // Auth
    'POST /api/login' => __DIR__ . '/../routes/auth/login.php',

    // Usuários
    'GET /api/usuarios'  => __DIR__ . '/../routes/usuarios/list.php',
    'POST /api/usuarios' => __DIR__ . '/../routes/usuarios/create.php',

    // Fornecedores
    'GET /api/fornecedores' => __DIR__ . '/../routes/fornecedores/list.php',
    'POST /api/fornecedores' => __DIR__ . '/../routes/fornecedores/create.php',
    'POST /api/fornecedores/create' => __DIR__ . '/../routes/fornecedores/create.php',

    // Setores
    'GET /api/setores'  => __DIR__ . '/../routes/setores/list.php',
    'POST /api/setores' => __DIR__ . '/../routes/setores/create.php',

    // Salas
    'GET /api/salas'  => __DIR__ . '/../routes/salas/list.php',
    'POST /api/salas' => __DIR__ . '/../routes/salas/create.php',

    // Bens
    'GET /api/bens'          => __DIR__ . '/../routes/bens/list.php',
    'POST /api/bens'         => __DIR__ . '/../routes/bens/create.php',
    'POST /api/bens/upload'  => __DIR__ . '/../routes/bens/upload.php',
    'GET /api/bens-detalhes' => __DIR__ . '/../routes/bens/details.php',
    'GET /api/bens-excluidos'=> __DIR__ . '/../routes/bens/excluidos.php',

    // Materiais de consumo
    'GET /api/materiais'  => __DIR__ . '/../routes/materiais/list.php',
    'POST /api/materiais' => __DIR__ . '/../routes/materiais/create.php',

    // Tipos básicos
    'GET /api/tipos-materiais'  => __DIR__ . '/../routes/tipos/materiais/list.php',
    'POST /api/tipos-materiais' => __DIR__ . '/../routes/tipos/materiais/create.php',

    'GET /api/tipos-eletronicos'  => __DIR__ . '/../routes/tipos/eletronicos/list.php',
    'POST /api/tipos-eletronicos' => __DIR__ . '/../routes/tipos/eletronicos/create.php',

    // Transferências
    'GET /api/transferencias'  => __DIR__ . '/../routes/transferencias/list.php',
    'POST /api/transferencias' => __DIR__ . '/../routes/transferencias/create.php',

    // Fases (tramitações)
    'GET /api/fases' => __DIR__ . '/../routes/fases/list.php',
    'POST /api/fases' => __DIR__ . '/../routes/fases/create.php',
    'PUT /api/fases' => __DIR__ . '/../routes/fases/update.php',
    'DELETE /api/fases' => __DIR__ . '/../routes/fases/delete.php',

    // Tramitações de bens
    'GET /api/bens/tramitacoes' => __DIR__ . '/../routes/bens/tramitacoes_list.php',
    'POST /api/bens/tramitacoes' => __DIR__ . '/../routes/bens/tramitacoes_create.php',

    // Licitações
    'GET /api/licitacoes'           => __DIR__ . '/../routes/licitacoes/list.php',
    'POST /api/licitacoes/cadastro' => __DIR__ . '/../routes/licitacoes/create.php',
    'GET /api/licitacoes/tramitacoes' => __DIR__ . '/../routes/licitacoes/tramitacoes_list.php',
    'POST /api/licitacoes/tramitacoes' => __DIR__ . '/../routes/licitacoes/tramitacoes_create.php',
    'POST /api/licitacoes/fases' => __DIR__ . '/../routes/licitacoes/fases_create.php',
    'DELETE /api/licitacoes/fases' => __DIR__ . '/../routes/licitacoes/fases_delete.php',

    // Contratos
    'GET /api/contratos' => __DIR__ . '/../routes/contratos/list.php',
    'POST /api/contratos' => __DIR__ . '/../routes/contratos/create.php',

    // Empenhos
    'GET /api/empenhos' => __DIR__ . '/../routes/empenhos/list.php',
    'POST /api/empenhos' => __DIR__ . '/../routes/empenhos/create.php',

    // Dispensas
    'GET /api/dispensas' => __DIR__ . '/../routes/dispensas/list.php',
    'POST /api/dispensas' => __DIR__ . '/../routes/dispensas/create.php',

    // Notificações
    'GET /api/notificacoes'                      => __DIR__ . '/../routes/notificacoes/list.php',
    'POST /api/notificacoes'                     => __DIR__ . '/../routes/notificacoes/create.php',
    'POST /api/notificacoes/marcar-lida'         => __DIR__ . '/../routes/notificacoes/marcar_lida.php',
    'POST /api/notificacoes/solicitacao-material'=> __DIR__ . '/../routes/notificacoes/solicitacao_material.php',

    // LHS - Cursos
    'GET /api/lhs/cursos'  => __DIR__ . '/../routes/lhs/cursos/list.php',
    'POST /api/lhs/cursos' => __DIR__ . '/../routes/lhs/cursos/create.php',

    // LHS - Alunos
    'GET /api/lhs/alunos'  => __DIR__ . '/../routes/lhs/alunos/list.php',
    'POST /api/lhs/alunos' => __DIR__ . '/../routes/lhs/alunos/create.php',

    // LHS - Turmas
    'GET /api/lhs/turmas'  => __DIR__ . '/../routes/lhs/turmas/list.php',
    'POST /api/lhs/turmas' => __DIR__ . '/../routes/lhs/turmas/create.php',

    // LHS - Aulas
    'GET /api/lhs/aulas'  => __DIR__ . '/../routes/lhs/aulas/list.php',
    'POST /api/lhs/aulas' => __DIR__ . '/../routes/lhs/aulas/create.php',

    // LHS - Inscrições
    'GET /api/lhs/inscricoes'  => __DIR__ . '/../routes/lhs/inscricoes/list.php',
    'POST /api/lhs/inscricoes' => __DIR__ . '/../routes/lhs/inscricoes/create.php',
    'GET /api/lhs/inscricoes/consulta' => __DIR__ . '/../routes/lhs/inscricoes/consulta.php',
    'GET /api/lhs/inscricoes/cursos-disponiveis' => __DIR__ . '/../routes/lhs/inscricoes/cursos_disponiveis.php',

    // LHS - Dashboard
    'GET /api/lhs/dashboard/stats' => __DIR__ . '/../routes/lhs/dashboard/stats.php',

    // LHS - Certificados
    'GET /api/lhs/certificados' => __DIR__ . '/../routes/lhs/certificados/list.php',
    'POST /api/lhs/certificados/emitir' => __DIR__ . '/../routes/lhs/certificados/emitir.php',
    'GET /api/lhs/certificados/validar' => __DIR__ . '/../routes/lhs/certificados/validar.php',

    // LHS - Notificações
    'GET /api/lhs/notificacoes' => __DIR__ . '/../routes/lhs/notificacoes/list.php',

    // LHS - Professores
    'GET /api/lhs/professores' => __DIR__ . '/../routes/lhs/professores/list.php',

    // LHS - Cursos (público)
    'GET /api/lhs/cursos/ativos' => __DIR__ . '/../routes/lhs/cursos/list_ativos.php',
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

// --- Fornecedores ---
if (preg_match('#^/api/fornecedores/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/fornecedores/detail.php';
        exit;
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/fornecedores/detail.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/fornecedores/detail.php';
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

// --- Contratos ---
if (preg_match('#^/api/contratos/(\d+)/detalhes$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/contratos/details.php';
        exit;
    }
}

if (preg_match('#^/api/contratos/(\d+)/documentos$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET' || $method === 'POST') {
        require __DIR__ . '/../routes/contratos/documentos.php';
        exit;
    }
}

if (preg_match('#^/api/contratos/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/contratos/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/contratos/delete.php';
        exit;
    }
}

if (preg_match('#^/api/contratos/(\d+)/fiscais$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'POST') {
        require __DIR__ . '/../routes/contratos/fiscais_create.php';
        exit;
    }
}

if (preg_match('#^/api/contratos/(\d+)/gestores$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'POST') {
        require __DIR__ . '/../routes/contratos/gestores_create.php';
        exit;
    }
}

if (preg_match('#^/api/contratos/(\d+)/fiscais/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1], 'fiscal_id' => (int) $m[2]];
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/contratos/fiscais_remove.php';
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

// --- LHS Alunos ---
if (preg_match('#^/api/lhs/alunos/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/lhs/alunos/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/lhs/alunos/delete.php';
        exit;
    }
}

// --- LHS Turmas ---
if (preg_match('#^/api/lhs/turmas/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/lhs/turmas/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/lhs/turmas/delete.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/turmas/(\d+)/detalhes$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/lhs/turmas/details.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/turmas/(\d+)/alunos-risco$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/lhs/turmas/alunos_risco.php';
        exit;
    }
}

// --- LHS Turmas - Matrículas ---
if (preg_match('#^/api/lhs/turmas/(\d+)/alunos$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/lhs/turmas/alunos/list.php';
        exit;
    }
    if ($method === 'POST') {
        require __DIR__ . '/../routes/lhs/turmas/alunos/matricular.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/turmas/(\d+)/alunos/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1], 'aluno_id' => (int) $m[2]];
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/lhs/turmas/alunos/remover.php';
        exit;
    }
}

// --- LHS Inscrições ---
if (preg_match('#^/api/lhs/inscricoes/(\d+)/aprovar$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'POST') {
        require __DIR__ . '/../routes/lhs/inscricoes/aprovar.php';
        exit;
    }
}

if (preg_match('#^/api/lhs/inscricoes/(\d+)/rejeitar$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'PUT' || $method === 'POST') {
        require __DIR__ . '/../routes/lhs/inscricoes/rejeitar.php';
        exit;
    }
}

// --- LHS Aulas ---
if (preg_match('#^/api/lhs/aulas/(\d+)$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/lhs/aulas/details.php';
        exit;
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        require __DIR__ . '/../routes/lhs/aulas/update.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/../routes/lhs/aulas/delete.php';
        exit;
    }
}

// --- LHS Certificados ---
if (preg_match('#^/api/lhs/certificados/turma/(\d+)/elegiveis$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'GET') {
        require __DIR__ . '/../routes/lhs/certificados/elegiveis.php';
        exit;
    }
}

// --- LHS Notificações ---
if (preg_match('#^/api/lhs/notificacoes/(\d+)/marcar-lida$#', $uri, $m)) {
    $GLOBALS['routeParams'] = ['id' => (int) $m[1]];
    if ($method === 'POST') {
        require __DIR__ . '/../routes/lhs/notificacoes/marcar_lida.php';
        exit;
    }
}

// =============================================================================
// FALLBACK - 404
// =============================================================================

require __DIR__ . '/../lib/http.php';
cors();
json(['error' => 'Not Found', 'path' => $uri], 404);
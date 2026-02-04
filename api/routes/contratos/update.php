<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();
$usuarioId = (int) $usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido. Use PUT ou PATCH.'], 405);
    exit;
}

$id = isset($GLOBALS['routeParams']['id']) ? (int) $GLOBALS['routeParams']['id'] : 0;

if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM contratos WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Contrato não encontrado'], 404);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json(['error' => 'JSON inválido'], 400);
    exit;
}

$camposPermitidos = [
    'numero',
    'licitacao_id',
    'fornecedor_id',
    'objeto',
    'data_inicio',
    'data_fim',
    'valor_contratado',
    'valor_executado',
    'valor_saldo',
    'status',
    'observacoes',
];

$statusValidos = ['planejamento', 'andamento', 'concluido', 'rescindido', 'suspenso'];

if (isset($input['status']) && !in_array($input['status'], $statusValidos)) {
    json(['error' => 'Status inválido'], 400);
    exit;
}

if (isset($input['fornecedor_id'])) {
    $stmt = $pdo->prepare('SELECT id FROM fornecedores WHERE id = ?');
    $stmt->execute([$input['fornecedor_id']]);
    if (!$stmt->fetch()) {
        json(['error' => 'Fornecedor não encontrado'], 400);
        exit;
    }
}

if (isset($input['licitacao_id']) && $input['licitacao_id'] !== null) {
    $stmt = $pdo->prepare('SELECT id FROM licitacoes WHERE id = ?');
    $stmt->execute([$input['licitacao_id']]);
    if (!$stmt->fetch()) {
        json(['error' => 'Licitação não encontrada'], 400);
        exit;
    }
}

$sets = [];
$params = [];

foreach ($camposPermitidos as $campo) {
    if (array_key_exists($campo, $input)) {
        $sets[] = "$campo = :$campo";
        $params[":$campo"] = $input[$campo] === '' ? null : $input[$campo];
    }
}

if (!$sets) {
    json(['error' => 'Nenhum campo para atualizar'], 400);
    exit;
}

$sets[] = "atualizado_por = :atualizado_por";
$sets[] = "atualizado_em = NOW()";
$params[':atualizado_por'] = $usuarioId;
$params[':id'] = $id;

$sql = "UPDATE contratos SET " . implode(', ', $sets) . " WHERE id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Buscar contrato atualizado
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.numero,
        c.ano_contrato,
        c.licitacao_id,
        c.fornecedor_id,
        f.nome AS fornecedor_nome,
        c.objeto,
        c.data_inicio,
        c.data_fim,
        c.valor_contratado,
        c.valor_executado,
        c.valor_saldo,
        c.status,
        c.criado_em,
        c.atualizado_em
    FROM contratos c
    LEFT JOIN fornecedores f ON f.id = c.fornecedor_id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$contrato = [
    'id' => (int) $row['id'],
    'numero' => $row['numero'],
    'ano_contrato' => (int) $row['ano_contrato'],
    'licitacao_id' => $row['licitacao_id'] ? (int) $row['licitacao_id'] : null,
    'fornecedor_id' => (int) $row['fornecedor_id'],
    'fornecedor_nome' => $row['fornecedor_nome'],
    'objeto' => $row['objeto'],
    'data_inicio' => $row['data_inicio'],
    'data_fim' => $row['data_fim'],
    'valor_contratado' => (float) $row['valor_contratado'],
    'valor_executado' => (float) $row['valor_executado'],
    'valor_saldo' => (float) $row['valor_saldo'],
    'status' => $row['status'],
    'criado_em' => $row['criado_em'],
    'atualizado_em' => $row['atualizado_em'],
];

json($contrato);

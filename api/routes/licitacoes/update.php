<?php
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Method Not Allowed'], 405);
}

$usuarioId = (int) $usuario['id'];

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int)$id;
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json(['error' => 'JSON inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id FROM licitacoes WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Licitação não encontrada'], 404);
}

$camposPermitidos = [
    'numero',
    'modalidade',
    'objeto',
    'secretaria_id',
    'data_abertura',
    'valor_estimado',
    'status',
];

$modalidadesValidas = [
    'CONCORRENCIA',
    'TOMADA_DE_PRECOS',
    'CONVITE',
    'LEILAO',
    'PREGAO',
    'DIARIO_OFICIAL'
];

$statusValidos = [
    'planejamento',
    'publicacao',
    'julgamento',
    'homologacao',
    'adjudicacao',
    'encerrada'
];

if (isset($input['modalidade']) && !in_array($input['modalidade'], $modalidadesValidas)) {
    json(['error' => 'Modalidade inválida'], 400);
}

if (isset($input['status']) && !in_array($input['status'], $statusValidos)) {
    json(['error' => 'Status inválido'], 400);
}

if (isset($input['secretaria_id'])) {
    $stmt = $pdo->prepare('SELECT id FROM setores WHERE id = ?');
    $stmt->execute([$input['secretaria_id']]);
    if (!$stmt->fetch()) {
        json(['error' => 'Secretaria não encontrada'], 400);
    }
}

$sets   = [];
$params = [];

foreach ($camposPermitidos as $campo) {
    if (array_key_exists($campo, $input)) {
        $sets[] = "$campo = :$campo";
        $params[":$campo"] = $input[$campo] === '' ? null : $input[$campo];
    }
}

if (!$sets) {
    json(['error' => 'Nenhum campo para atualizar'], 400);
}

// Adicionar atualizado_por e atualizado_em
$sets[] = "atualizado_por = :atualizado_por";
$sets[] = "atualizado_em = NOW()";
$params[':atualizado_por'] = $usuarioId;

$sql = "UPDATE licitacoes SET " . implode(', ', $sets) . " WHERE id = :id";
$params[':id'] = $id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Buscar licitação atualizada com informações dos usuários
$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.numero,
        l.modalidade,
        l.objeto,
        l.secretaria_id,
        l.data_abertura,
        l.valor_estimado,
        l.status,
        l.criado_por,
        u_criado.nome AS criado_por_nome,
        l.atualizado_por,
        u_atualizado.nome AS atualizado_por_nome,
        l.criado_em,
        l.atualizado_em,
        s.nome AS secretaria_nome
    FROM licitacoes l
    LEFT JOIN setores s ON s.id = l.secretaria_id
    LEFT JOIN usuarios u_criado ON u_criado.id = l.criado_por
    LEFT JOIN usuarios u_atualizado ON u_atualizado.id = l.atualizado_por
    WHERE l.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$licitacao = [
    'id'               => (int)$row['id'],
    'numero'           => $row['numero'],
    'modalidade'       => $row['modalidade'],
    'objeto'           => $row['objeto'],
    'secretaria_id'    => (int)$row['secretaria_id'],
    'secretaria_nome'  => $row['secretaria_nome'],
    'data_abertura'    => $row['data_abertura'],
    'valor_estimado'   => $row['valor_estimado'] !== null
                          ? (float)$row['valor_estimado']
                          : null,
    'status'           => $row['status'],
    'criado_por'       => $row['criado_por'] ? (int)$row['criado_por'] : null,
    'criado_por_nome'  => $row['criado_por_nome'],
    'atualizado_por'   => $row['atualizado_por'] ? (int)$row['atualizado_por'] : null,
    'atualizado_por_nome' => $row['atualizado_por_nome'],
    'criado_em'        => $row['criado_em'],
    'atualizado_em'    => $row['atualizado_em'],
];

json($licitacao);
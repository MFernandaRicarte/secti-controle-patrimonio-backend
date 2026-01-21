<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/cors.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

// Obter usuário autenticado
$usuario = requireAuth();
$usuarioId = (int) $usuario['id'];

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$numero = trim($input['numero'] ?? '');
$modalidade = trim($input['modalidade'] ?? '');
$objeto = trim($input['objeto'] ?? '');
$secretariaId = !empty($input['secretaria_id']) ? (int) $input['secretaria_id'] : null;
$dataAbertura = trim($input['data_abertura'] ?? '');
$valorEstimado = isset($input['valor_estimado']) && $input['valor_estimado'] !== ''
    ? (float) $input['valor_estimado']
    : null;
$status = trim($input['status'] ?? 'planejamento');

$erros = [];

if ($numero === '') {
    $erros[] = 'numero é obrigatório.';
}

$modalidadesValidas = ['CONCORRENCIA', 'TOMADA_DE_PRECOS', 'CONVITE', 'LEILAO', 'PREGAO', 'DIARIO_OFICIAL'];
if (!in_array($modalidade, $modalidadesValidas, true)) {
    $erros[] = 'modalidade inválida.';
}

if ($objeto === '') {
    $erros[] = 'objeto é obrigatório.';
}

if ($secretariaId === null) {
    $erros[] = 'secretaria_id é obrigatório.';
}

if ($dataAbertura === '') {
    $erros[] = 'data_abertura é obrigatória.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAbertura)) {
    $erros[] = 'data_abertura deve estar no formato YYYY-MM-DD.';
}

if ($valorEstimado === null || $valorEstimado <= 0) {
    $erros[] = 'valor_estimado é obrigatório e deve ser maior que zero.';
}

$statusValidos = ['planejamento', 'publicacao', 'julgamento', 'homologacao', 'adjudicacao', 'encerrada'];
if (!in_array($status, $statusValidos, true)) {
    $erros[] = 'status inválido.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $sqlInsert = "
        INSERT INTO licitacoes (
            numero,
            modalidade,
            objeto,
            secretaria_id,
            data_abertura,
            valor_estimado,
            status,
            criado_por,
            atualizado_por
        ) VALUES (
            :numero,
            :modalidade,
            :objeto,
            :secretaria_id,
            :data_abertura,
            :valor_estimado,
            :status,
            :criado_por,
            :atualizado_por
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':numero' => $numero,
        ':modalidade' => $modalidade,
        ':objeto' => $objeto,
        ':secretaria_id' => $secretariaId,
        ':data_abertura' => $dataAbertura,
        ':valor_estimado' => $valorEstimado,
        ':status' => $status,
        ':criado_por' => $usuarioId,
        ':atualizado_por' => $usuarioId,
    ]);

    $novoId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        json(['error' => 'Já existe uma licitação com este número.'], 409);
        exit;
    }
    json(['error' => 'Erro ao salvar a licitação.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

try {
    $sqlSelect = "
        SELECT
            l.id,
            l.numero,
            l.modalidade,
            l.objeto,
            l.secretaria_id,
            s.nome AS secretaria,
            l.data_abertura,
            l.valor_estimado,
            l.status,
            l.criado_por,
            u_criado.nome AS criado_por_nome,
            l.atualizado_por,
            u_atualizado.nome AS atualizado_por_nome,
            l.criado_em,
            l.atualizado_em
        FROM licitacoes l
        LEFT JOIN setores s ON s.id = l.secretaria_id
        LEFT JOIN usuarios u_criado ON u_criado.id = l.criado_por
        LEFT JOIN usuarios u_atualizado ON u_atualizado.id = l.atualizado_por
        WHERE l.id = :id
    ";

    $stmt2 = $pdo->prepare($sqlSelect);
    $stmt2->execute([':id' => $novoId]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json(['error' => 'Erro ao buscar licitação criada.'], 500);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar licitação criada.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

$licitacao = [
    'id' => (int) $row['id'],
    'numero' => $row['numero'],
    'modalidade' => $row['modalidade'],
    'objeto' => $row['objeto'],
    'secretaria_id' => $row['secretaria_id'] ? (int) $row['secretaria_id'] : null,
    'secretaria' => $row['secretaria'],
    'data_abertura' => $row['data_abertura'],
    'valor_estimado' => $row['valor_estimado'],
    'status' => $row['status'],
    'criado_por' => $row['criado_por'] ? (int) $row['criado_por'] : null,
    'criado_por_nome' => $row['criado_por_nome'],
    'atualizado_por' => $row['atualizado_por'] ? (int) $row['atualizado_por'] : null,
    'atualizado_por_nome' => $row['atualizado_por_nome'],
    'criado_em' => $row['criado_em'],
    'atualizado_em' => $row['atualizado_em'],
];

json($licitacao, 201);
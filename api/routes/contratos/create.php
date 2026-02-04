<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$usuario = requireAuth();
$usuarioId = (int) $usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$numero = trim($input['numero'] ?? '');
$anoContrato = !empty($input['ano_contrato']) ? (int) $input['ano_contrato'] : date('Y');
$liciacaoId = !empty($input['licitacao_id']) ? (int) $input['licitacao_id'] : null;
$fornecedorId = !empty($input['fornecedor_id']) ? (int) $input['fornecedor_id'] : null;
$objeto = trim($input['objeto'] ?? '');
$dataInicio = trim($input['data_inicio'] ?? '');
$dataFim = trim($input['data_fim'] ?? '');
$valorContratado = isset($input['valor_contratado']) && $input['valor_contratado'] !== ''
    ? (float) $input['valor_contratado']
    : null;
$status = trim($input['status'] ?? 'planejamento');
$observacoes = trim($input['observacoes'] ?? '') ?: null;

$erros = [];

if ($numero === '') {
    $erros[] = 'numero é obrigatório.';
}

if ($fornecedorId === null) {
    $erros[] = 'fornecedor_id é obrigatório.';
}

if ($objeto === '') {
    $erros[] = 'objeto é obrigatório.';
}

if ($dataInicio === '') {
    $erros[] = 'data_inicio é obrigatória.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) {
    $erros[] = 'data_inicio deve estar no formato YYYY-MM-DD.';
}

if ($dataFim === '') {
    $erros[] = 'data_fim é obrigatória.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
    $erros[] = 'data_fim deve estar no formato YYYY-MM-DD.';
}

if ($dataInicio !== '' && $dataFim !== '' && $dataInicio > $dataFim) {
    $erros[] = 'data_inicio deve ser anterior a data_fim.';
}

if ($valorContratado === null || $valorContratado <= 0) {
    $erros[] = 'valor_contratado é obrigatório e deve ser maior que zero.';
}

$statusValidos = ['planejamento', 'andamento', 'concluido', 'rescindido', 'suspenso'];
if (!in_array($status, $statusValidos, true)) {
    $erros[] = 'status inválido.';
}

// Validar fornecedor
if ($fornecedorId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM fornecedores WHERE id = ?');
    $stmt->execute([$fornecedorId]);
    if (!$stmt->fetch()) {
        $erros[] = 'Fornecedor não encontrado.';
    }
}

// Validar licitação se informada
if ($liciacaoId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM licitacoes WHERE id = ?');
    $stmt->execute([$liciacaoId]);
    if (!$stmt->fetch()) {
        $erros[] = 'Licitação não encontrada.';
    }
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $sqlInsert = "
        INSERT INTO contratos (
            numero,
            ano_contrato,
            licitacao_id,
            fornecedor_id,
            objeto,
            data_inicio,
            data_fim,
            valor_contratado,
            valor_saldo,
            status,
            observacoes,
            criado_por,
            atualizado_por
        ) VALUES (
            :numero,
            :ano_contrato,
            :licitacao_id,
            :fornecedor_id,
            :objeto,
            :data_inicio,
            :data_fim,
            :valor_contratado,
            :valor_saldo,
            :status,
            :observacoes,
            :criado_por,
            :atualizado_por
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':numero' => $numero,
        ':ano_contrato' => $anoContrato,
        ':licitacao_id' => $liciacaoId,
        ':fornecedor_id' => $fornecedorId,
        ':objeto' => $objeto,
        ':data_inicio' => $dataInicio,
        ':data_fim' => $dataFim,
        ':valor_contratado' => $valorContratado,
        ':valor_saldo' => $valorContratado,
        ':status' => $status,
        ':observacoes' => $observacoes,
        ':criado_por' => $usuarioId,
        ':atualizado_por' => $usuarioId,
    ]);

    $id = (int) $pdo->lastInsertId();

    // Buscar contrato inserido
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

    json($contrato, 201);

} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        json(['error' => 'Número de contrato já existe.'], 400);
    } else {
        error_log('Erro ao criar contrato: ' . $e->getMessage());
        json(['error' => 'Erro ao criar contrato.'], 500);
    }
}

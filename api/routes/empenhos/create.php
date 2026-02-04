<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

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
$anoEmpenho = !empty($input['ano_empenho']) ? (int) $input['ano_empenho'] : date('Y');
$contratoId = !empty($input['contrato_id']) ? (int) $input['contrato_id'] : null;
$licitacaoId = !empty($input['licitacao_id']) ? (int) $input['licitacao_id'] : null;
$valorEmpenhado = isset($input['valor_empenhado']) && $input['valor_empenhado'] !== ''
    ? (float) $input['valor_empenhado']
    : null;
$descricao = trim($input['descricao'] ?? '') ?: null;
$dataEmpenho = trim($input['data_empenho'] ?? '');

$erros = [];

if ($numero === '') {
    $erros[] = 'numero é obrigatório.';
}

if ($valorEmpenhado === null || $valorEmpenhado <= 0) {
    $erros[] = 'valor_empenhado é obrigatório e deve ser maior que zero.';
}

if ($dataEmpenho === '') {
    $erros[] = 'data_empenho é obrigatória.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataEmpenho)) {
    $erros[] = 'data_empenho deve estar no formato YYYY-MM-DD.';
}

// Validar contrato se informado
if ($contratoId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM contratos WHERE id = ?');
    $stmt->execute([$contratoId]);
    if (!$stmt->fetch()) {
        $erros[] = 'Contrato não encontrado.';
    }
}

// Validar licitação se informada
if ($licitacaoId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM licitacoes WHERE id = ?');
    $stmt->execute([$licitacaoId]);
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
        INSERT INTO empenhos (
            numero,
            ano_empenho,
            contrato_id,
            licitacao_id,
            valor_empenhado,
            saldo,
            descricao,
            data_empenho,
            status,
            criado_por,
            atualizado_por
        ) VALUES (
            :numero,
            :ano_empenho,
            :contrato_id,
            :licitacao_id,
            :valor_empenhado,
            :valor_empenhado,
            :descricao,
            :data_empenho,
            'empenho',
            :criado_por,
            :atualizado_por
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':numero' => $numero,
        ':ano_empenho' => $anoEmpenho,
        ':contrato_id' => $contratoId,
        ':licitacao_id' => $licitacaoId,
        ':valor_empenhado' => $valorEmpenhado,
        ':descricao' => $descricao,
        ':data_empenho' => $dataEmpenho,
        ':criado_por' => $usuarioId,
        ':atualizado_por' => $usuarioId,
    ]);

    $id = (int) $pdo->lastInsertId();

    // Buscar empenho inserido
    $stmt = $pdo->prepare("
        SELECT 
            id, numero, ano_empenho, contrato_id, licitacao_id,
            valor_empenhado, valor_liquidado, valor_pago, saldo,
            status, descricao, data_empenho, criado_em, atualizado_em
        FROM empenhos
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $empenho = [
        'id' => (int) $row['id'],
        'numero' => $row['numero'],
        'ano_empenho' => (int) $row['ano_empenho'],
        'contrato_id' => $row['contrato_id'] ? (int) $row['contrato_id'] : null,
        'licitacao_id' => $row['licitacao_id'] ? (int) $row['licitacao_id'] : null,
        'valor_empenhado' => (float) $row['valor_empenhado'],
        'valor_liquidado' => (float) $row['valor_liquidado'],
        'valor_pago' => (float) $row['valor_pago'],
        'saldo' => (float) $row['saldo'],
        'status' => $row['status'],
        'descricao' => $row['descricao'],
        'data_empenho' => $row['data_empenho'],
        'criado_em' => $row['criado_em'],
        'atualizado_em' => $row['atualizado_em'],
    ];

    json($empenho, 201);

} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        json(['error' => 'Número de empenho já existe.'], 400);
    } else {
        error_log('Erro ao criar empenho: ' . $e->getMessage());
        json(['error' => 'Erro ao criar empenho.'], 500);
    }
}

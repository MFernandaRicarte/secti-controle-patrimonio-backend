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
$tipo = trim($input['tipo'] ?? '');
$fornecedorId = !empty($input['fornecedor_id']) ? (int) $input['fornecedor_id'] : null;
$objeto = trim($input['objeto'] ?? '');
$valor = isset($input['valor']) && $input['valor'] !== ''
    ? (float) $input['valor']
    : null;
$justificativaLegal = trim($input['justificativa_legal'] ?? '');
$justificativaTecnica = trim($input['justificativa_tecnica'] ?? '');
$dataSolicitacao = trim($input['data_solicitacao'] ?? '');
$usuarioSolicitante = !empty($input['usuario_solicitante']) ? (int) $input['usuario_solicitante'] : $usuarioId;

$erros = [];

if ($numero === '') {
    $erros[] = 'numero é obrigatório.';
}

if ($tipo === '' || !in_array($tipo, ['dispensa', 'inexigibilidade'], true)) {
    $erros[] = 'tipo é obrigatório (dispensa ou inexigibilidade).';
}

if ($fornecedorId === null) {
    $erros[] = 'fornecedor_id é obrigatório.';
}

if ($objeto === '') {
    $erros[] = 'objeto é obrigatório.';
}

if ($valor === null || $valor <= 0) {
    $erros[] = 'valor é obrigatório e deve ser maior que zero.';
}

if ($justificativaLegal === '') {
    $erros[] = 'justificativa_legal é obrigatória.';
}

if ($justificativaTecnica === '') {
    $erros[] = 'justificativa_tecnica é obrigatória.';
}

if ($dataSolicitacao === '') {
    $erros[] = 'data_solicitacao é obrigatória.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSolicitacao)) {
    $erros[] = 'data_solicitacao deve estar no formato YYYY-MM-DD.';
}

// Validar fornecedor
if ($fornecedorId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM fornecedores WHERE id = ?');
    $stmt->execute([$fornecedorId]);
    if (!$stmt->fetch()) {
        $erros[] = 'Fornecedor não encontrado.';
    }
}

// Validar usuário solicitante
if ($usuarioSolicitante !== null) {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$usuarioSolicitante]);
    if (!$stmt->fetch()) {
        $erros[] = 'Usuário solicitante não encontrado.';
    }
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $ano = date('Y');
    
    $sqlInsert = "
        INSERT INTO dispensas_inexigibilidades (
            numero,
            ano,
            tipo,
            fornecedor_id,
            objeto,
            valor,
            justificativa_legal,
            justificativa_tecnica,
            data_solicitacao,
            usuario_solicitante,
            status,
            criado_por,
            atualizado_por
        ) VALUES (
            :numero,
            :ano,
            :tipo,
            :fornecedor_id,
            :objeto,
            :valor,
            :justificativa_legal,
            :justificativa_tecnica,
            :data_solicitacao,
            :usuario_solicitante,
            'planejamento',
            :criado_por,
            :atualizado_por
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':numero' => $numero,
        ':ano' => $ano,
        ':tipo' => $tipo,
        ':fornecedor_id' => $fornecedorId,
        ':objeto' => $objeto,
        ':valor' => $valor,
        ':justificativa_legal' => $justificativaLegal,
        ':justificativa_tecnica' => $justificativaTecnica,
        ':data_solicitacao' => $dataSolicitacao,
        ':usuario_solicitante' => $usuarioSolicitante,
        ':criado_por' => $usuarioId,
        ':atualizado_por' => $usuarioId,
    ]);

    $id = (int) $pdo->lastInsertId();

    // Buscar dispensa inserida
    $stmt = $pdo->prepare("
        SELECT 
            id, numero, ano, tipo, fornecedor_id, objeto, valor,
            status, data_solicitacao, criado_em, atualizado_em
        FROM dispensas_inexigibilidades
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $dispensa = [
        'id' => (int) $row['id'],
        'numero' => $row['numero'],
        'ano' => (int) $row['ano'],
        'tipo' => $row['tipo'],
        'fornecedor_id' => (int) $row['fornecedor_id'],
        'objeto' => $row['objeto'],
        'valor' => (float) $row['valor'],
        'status' => $row['status'],
        'data_solicitacao' => $row['data_solicitacao'],
        'criado_em' => $row['criado_em'],
        'atualizado_em' => $row['atualizado_em'],
    ];

    json($dispensa, 201);

} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        json(['error' => 'Número de dispensa/inexigibilidade já existe.'], 400);
    } else {
        error_log('Erro ao criar dispensa: ' . $e->getMessage());
        json(['error' => 'Erro ao criar dispensa/inexigibilidade.'], 500);
    }
}

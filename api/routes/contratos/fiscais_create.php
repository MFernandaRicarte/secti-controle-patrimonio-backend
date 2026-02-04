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

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$usuarioFiscalId = isset($input['usuario_id']) ? (int) $input['usuario_id'] : null;
$dataNomeacao = trim($input['data_nomeacao'] ?? '');
$portariNumero = trim($input['portaria_numero'] ?? '') ?: null;
$dataPublicacaoPortaria = trim($input['data_publicacao_portaria'] ?? '') ?: null;
$responsabilidades = trim($input['responsabilidades'] ?? '') ?: null;

$erros = [];

if ($usuarioFiscalId === null) {
    $erros[] = 'usuario_id é obrigatório.';
}

if ($dataNomeacao === '') {
    $erros[] = 'data_nomeacao é obrigatória.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNomeacao)) {
    $erros[] = 'data_nomeacao deve estar no formato YYYY-MM-DD.';
}

if ($dataPublicacaoPortaria !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataPublicacaoPortaria)) {
    $erros[] = 'data_publicacao_portaria deve estar no formato YYYY-MM-DD.';
}

// Validar usuário
if ($usuarioFiscalId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$usuarioFiscalId]);
    if (!$stmt->fetch()) {
        $erros[] = 'Usuário não encontrado.';
    }
}

// Verificar se já existe fiscal nomeado para o mesmo usuário nesta data
if ($usuarioFiscalId !== null && $dataNomeacao !== '') {
    $stmt = $pdo->prepare("
        SELECT id FROM contratos_fiscais 
        WHERE contrato_id = ? AND usuario_id = ? AND data_nomeacao = ?
    ");
    $stmt->execute([$id, $usuarioFiscalId, $dataNomeacao]);
    if ($stmt->fetch()) {
        $erros[] = 'Já existe um fiscal nomeado para este usuário nesta data.';
    }
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $sqlInsert = "
        INSERT INTO contratos_fiscais (
            contrato_id,
            usuario_id,
            data_nomeacao,
            portaria_numero,
            data_publicacao_portaria,
            responsabilidades
        ) VALUES (
            :contrato_id,
            :usuario_id,
            :data_nomeacao,
            :portaria_numero,
            :data_publicacao_portaria,
            :responsabilidades
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':contrato_id' => $id,
        ':usuario_id' => $usuarioFiscalId,
        ':data_nomeacao' => $dataNomeacao,
        ':portaria_numero' => $portariNumero,
        ':data_publicacao_portaria' => $dataPublicacaoPortaria,
        ':responsabilidades' => $responsabilidades,
    ]);

    $fiscalId = (int) $pdo->lastInsertId();

    // Buscar fiscal inserido
    $stmt = $pdo->prepare("
        SELECT cf.id, u.nome, cf.data_nomeacao, cf.data_termino, cf.portaria_numero, cf.ativo
        FROM contratos_fiscais cf
        LEFT JOIN usuarios u ON u.id = cf.usuario_id
        WHERE cf.id = ?
    ");
    $stmt->execute([$fiscalId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $fiscal = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'data_nomeacao' => $row['data_nomeacao'],
        'data_termino' => $row['data_termino'],
        'portaria_numero' => $row['portaria_numero'],
        'ativo' => (bool) $row['ativo'],
    ];

    json($fiscal, 201);

} catch (PDOException $e) {
    error_log('Erro ao criar fiscal: ' . $e->getMessage());
    json(['error' => 'Erro ao criar fiscal.'], 500);
}

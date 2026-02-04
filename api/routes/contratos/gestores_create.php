<?php

// Rotas para Gestores de Contrato
// Permita criar, visualizar e atualizar gestores

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

$usuarioGestorId = isset($input['usuario_id']) ? (int) $input['usuario_id'] : null;
$dataNomeacao = trim($input['data_nomeacao'] ?? '');
$portariNumero = trim($input['portaria_numero'] ?? '') ?: null;
$dataPublicacaoPortaria = trim($input['data_publicacao_portaria'] ?? '') ?: null;
$responsabilidades = trim($input['responsabilidades'] ?? '') ?: null;

$erros = [];

if ($usuarioGestorId === null) {
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
if ($usuarioGestorId !== null) {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$usuarioGestorId]);
    if (!$stmt->fetch()) {
        $erros[] = 'Usuário não encontrado.';
    }
}

// Verificar se já existe gestor nomeado para o mesmo usuário nesta data
if ($usuarioGestorId !== null && $dataNomeacao !== '') {
    $stmt = $pdo->prepare("
        SELECT id FROM contratos_gestores 
        WHERE contrato_id = ? AND usuario_id = ? AND data_nomeacao = ?
    ");
    $stmt->execute([$id, $usuarioGestorId, $dataNomeacao]);
    if ($stmt->fetch()) {
        $erros[] = 'Já existe um gestor nomeado para este usuário nesta data.';
    }
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $sqlInsert = "
        INSERT INTO contratos_gestores (
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
        ':usuario_id' => $usuarioGestorId,
        ':data_nomeacao' => $dataNomeacao,
        ':portaria_numero' => $portariNumero,
        ':data_publicacao_portaria' => $dataPublicacaoPortaria,
        ':responsabilidades' => $responsabilidades,
    ]);

    $gestorId = (int) $pdo->lastInsertId();

    // Buscar gestor inserido
    $stmt = $pdo->prepare("
        SELECT cg.id, u.nome, cg.data_nomeacao, cg.data_termino, cg.portaria_numero, cg.ativo
        FROM contratos_gestores cg
        LEFT JOIN usuarios u ON u.id = cg.usuario_id
        WHERE cg.id = ?
    ");
    $stmt->execute([$gestorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $gestor = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'data_nomeacao' => $row['data_nomeacao'],
        'data_termino' => $row['data_termino'],
        'portaria_numero' => $row['portaria_numero'],
        'ativo' => (bool) $row['ativo'],
    ];

    json($gestor, 201);

} catch (PDOException $e) {
    error_log('Erro ao criar gestor: ' . $e->getMessage());
    json(['error' => 'Erro ao criar gestor.'], 500);
}

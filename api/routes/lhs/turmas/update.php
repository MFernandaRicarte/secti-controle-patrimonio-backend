<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido. Use PUT ou PATCH.'], 405);
    exit;
}

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da turma não informado.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

// Verificar se turma existe
$stmt = $pdo->prepare("SELECT * FROM lhs_turmas WHERE id = :id");
$stmt->execute([':id' => $id]);
$turma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    json(['error' => 'Turma não encontrada.'], 404);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$cursoId = isset($input['curso_id']) ? (int) $input['curso_id'] : (int) $turma['curso_id'];
$professorId = array_key_exists('professor_id', $input)
    ? ($input['professor_id'] !== null && $input['professor_id'] !== '' ? (int) $input['professor_id'] : null)
    : ($turma['professor_id'] ? (int) $turma['professor_id'] : null);
$nome = isset($input['nome']) ? trim($input['nome']) : $turma['nome'];
$horarioInicio = isset($input['horario_inicio']) ? trim($input['horario_inicio']) : $turma['horario_inicio'];
$horarioFim = isset($input['horario_fim']) ? trim($input['horario_fim']) : $turma['horario_fim'];
$dataInicio = isset($input['data_inicio']) ? trim($input['data_inicio']) : $turma['data_inicio'];
$dataFim = isset($input['data_fim']) ? trim($input['data_fim']) : $turma['data_fim'];
$status = isset($input['status']) ? trim($input['status']) : $turma['status'];

$erros = [];

if ($cursoId <= 0) {
    $erros[] = 'curso_id é obrigatório.';
}

if ($nome === '') {
    $erros[] = 'nome é obrigatório.';
}

if ($horarioInicio === '') {
    $erros[] = 'horario_inicio é obrigatório.';
}

if ($horarioFim === '') {
    $erros[] = 'horario_fim é obrigatório.';
}

if ($dataInicio === '') {
    $erros[] = 'data_inicio é obrigatória.';
}

$statusValidos = ['aberta', 'em_andamento', 'concluida', 'cancelada'];
if (!in_array($status, $statusValidos, true)) {
    $erros[] = 'status inválido.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
    exit;
}

try {
    $sqlUpdate = "
        UPDATE lhs_turmas SET
            curso_id = :curso_id,
            professor_id = :professor_id,
            nome = :nome,
            horario_inicio = :horario_inicio,
            horario_fim = :horario_fim,
            data_inicio = :data_inicio,
            data_fim = :data_fim,
            status = :status
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute([
        ':curso_id' => $cursoId,
        ':professor_id' => $professorId,
        ':nome' => $nome,
        ':horario_inicio' => $horarioInicio,
        ':horario_fim' => $horarioFim,
        ':data_inicio' => $dataInicio,
        ':data_fim' => $dataFim ?: null,
        ':status' => $status,
        ':id' => $id,
    ]);

    // Buscar turma atualizada
    $sql = "
        SELECT t.*, c.nome AS curso_nome, u.nome AS professor_nome
        FROM lhs_turmas t
        LEFT JOIN lhs_cursos c ON c.id = t.curso_id
        LEFT JOIN usuarios u ON u.id = t.professor_id
        WHERE t.id = :id
    ";
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute([':id' => $id]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    $turmaResp = [
        'id' => (int) $row['id'],
        'curso_id' => (int) $row['curso_id'],
        'curso_nome' => $row['curso_nome'],
        'professor_id' => $row['professor_id'] ? (int) $row['professor_id'] : null,
        'professor_nome' => $row['professor_nome'],
        'nome' => $row['nome'],
        'horario_inicio' => $row['horario_inicio'],
        'horario_fim' => $row['horario_fim'],
        'data_inicio' => $row['data_inicio'],
        'data_fim' => $row['data_fim'],
        'status' => $row['status'],
        'criado_em' => $row['criado_em'],
    ];

    json($turmaResp);
} catch (PDOException $e) {
    json(['error' => 'Erro ao atualizar turma.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

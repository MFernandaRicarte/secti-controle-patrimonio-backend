<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

$cursoId = isset($input['curso_id']) ? (int) $input['curso_id'] : 0;
$professorId = isset($input['professor_id']) && $input['professor_id'] !== '' ? (int) $input['professor_id'] : null;
$nome = trim($input['nome'] ?? '');
$horarioInicio = trim($input['horario_inicio'] ?? '');
$horarioFim = trim($input['horario_fim'] ?? '');
$dataInicio = trim($input['data_inicio'] ?? '');
$dataFim = trim($input['data_fim'] ?? '');
$status = trim($input['status'] ?? 'aberta');

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

// Verificar se curso existe
$stmt = $pdo->prepare("SELECT id FROM lhs_cursos WHERE id = :id");
$stmt->execute([':id' => $cursoId]);
if (!$stmt->fetch()) {
    json(['error' => 'Curso não encontrado.'], 404);
    exit;
}

try {
    $sqlInsert = "
        INSERT INTO lhs_turmas (curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, status)
        VALUES (:curso_id, :professor_id, :nome, :horario_inicio, :horario_fim, :data_inicio, :data_fim, :status)
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':curso_id' => $cursoId,
        ':professor_id' => $professorId,
        ':nome' => $nome,
        ':horario_inicio' => $horarioInicio,
        ':horario_fim' => $horarioFim,
        ':data_inicio' => $dataInicio,
        ':data_fim' => $dataFim ?: null,
        ':status' => $status,
    ]);

    $novoId = (int) $pdo->lastInsertId();

    // Buscar turma criada com joins
    $sql = "
        SELECT t.*, c.nome AS curso_nome, u.nome AS professor_nome
        FROM lhs_turmas t
        LEFT JOIN lhs_cursos c ON c.id = t.curso_id
        LEFT JOIN usuarios u ON u.id = t.professor_id
        WHERE t.id = :id
    ";
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute([':id' => $novoId]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    $turma = [
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

    json($turma, 201);
} catch (PDOException $e) {
    json(['error' => 'Erro ao salvar turma.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

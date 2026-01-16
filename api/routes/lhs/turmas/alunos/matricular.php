<?php

require __DIR__ . '/../../../../lib/http.php';
require __DIR__ . '/../../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

$turmaId = $GLOBALS['routeParams']['id'] ?? 0;
if (!$turmaId) {
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
$stmt = $pdo->prepare("SELECT id FROM lhs_turmas WHERE id = :id");
$stmt->execute([':id' => $turmaId]);
if (!$stmt->fetch()) {
    json(['error' => 'Turma não encontrada.'], 404);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Aceita um único aluno_id ou array de aluno_ids
$alunoIds = [];
if (isset($input['aluno_id'])) {
    $alunoIds = [(int) $input['aluno_id']];
} elseif (isset($input['aluno_ids']) && is_array($input['aluno_ids'])) {
    $alunoIds = array_map('intval', $input['aluno_ids']);
}

if (empty($alunoIds)) {
    json(['error' => 'aluno_id ou aluno_ids é obrigatório.'], 422);
    exit;
}

$matriculados = [];
$erros = [];

foreach ($alunoIds as $alunoId) {
    // Verificar se aluno existe
    $stmt = $pdo->prepare("SELECT id, nome FROM lhs_alunos WHERE id = :id");
    $stmt->execute([':id' => $alunoId]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        $erros[] = "Aluno ID {$alunoId} não encontrado.";
        continue;
    }

    // Verificar se já está matriculado
    $stmt2 = $pdo->prepare("SELECT id FROM lhs_turma_alunos WHERE turma_id = :turma_id AND aluno_id = :aluno_id");
    $stmt2->execute([':turma_id' => $turmaId, ':aluno_id' => $alunoId]);
    if ($stmt2->fetch()) {
        $erros[] = "Aluno '{$aluno['nome']}' já está matriculado nesta turma.";
        continue;
    }

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO lhs_turma_alunos (turma_id, aluno_id, status)
            VALUES (:turma_id, :aluno_id, 'matriculado')
        ");
        $stmtInsert->execute([':turma_id' => $turmaId, ':aluno_id' => $alunoId]);
        $matriculados[] = [
            'aluno_id' => $alunoId,
            'aluno_nome' => $aluno['nome'],
        ];
    } catch (PDOException $e) {
        $erros[] = "Erro ao matricular aluno ID {$alunoId}.";
    }
}

$response = [
    'message' => count($matriculados) . ' aluno(s) matriculado(s) com sucesso.',
    'matriculados' => $matriculados,
];

if (!empty($erros)) {
    $response['avisos'] = $erros;
}

json($response, 201);

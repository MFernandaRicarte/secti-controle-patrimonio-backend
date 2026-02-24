<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$user = requireLhsAdmin();
$pdo = db();

$professorId = $GLOBALS['routeParams']['id'] ?? 0;
if (!$professorId) {
    json(['error' => 'ID do professor não informado.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$turmaIds = [];
if (isset($input['turma_id'])) {
    $turmaIds = [(int) $input['turma_id']];
} elseif (isset($input['turma_ids']) && is_array($input['turma_ids'])) {
    $turmaIds = array_map('intval', $input['turma_ids']);
}

if (empty($turmaIds)) {
    json(['error' => 'turma_id ou turma_ids é obrigatório.'], 422);
}

$stmtProf = $pdo->prepare("
    SELECT u.id, u.nome FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ? AND UPPER(p.nome) = 'PROFESSOR'
");
$stmtProf->execute([$professorId]);
$professor = $stmtProf->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    json(['error' => 'Professor não encontrado.'], 404);
}

$atribuidos = [];
$erros = [];

foreach ($turmaIds as $turmaId) {
    $stmtTurma = $pdo->prepare("SELECT id, nome FROM lhs_turmas WHERE id = ?");
    $stmtTurma->execute([$turmaId]);
    $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        $erros[] = "Turma ID {$turmaId} não encontrada.";
        continue;
    }

    $stmtCheck = $pdo->prepare("
        SELECT id FROM lhs_professor_turmas 
        WHERE professor_id = ? AND turma_id = ?
    ");
    $stmtCheck->execute([$professorId, $turmaId]);
    if ($stmtCheck->fetch()) {
        $erros[] = "Professor já atribuído à turma '{$turma['nome']}'.";
        continue;
    }

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO lhs_professor_turmas (professor_id, turma_id, atribuido_por)
            VALUES (?, ?, ?)
        ");
        $stmtInsert->execute([$professorId, $turmaId, $user['id']]);

        $stmtUpdateTurma = $pdo->prepare("
            UPDATE lhs_turmas SET professor_id = ? WHERE id = ? AND professor_id IS NULL
        ");
        $stmtUpdateTurma->execute([$professorId, $turmaId]);

        $atribuidos[] = [
            'turma_id' => (int) $turmaId,
            'turma_nome' => $turma['nome'],
        ];
    } catch (PDOException $e) {
        $erros[] = "Erro ao atribuir turma ID {$turmaId}.";
    }
}

$response = [
    'message' => count($atribuidos) . ' turma(s) atribuída(s) ao professor com sucesso.',
    'professor_id' => (int) $professorId,
    'professor_nome' => $professor['nome'],
    'atribuidos' => $atribuidos,
];

if (!empty($erros)) {
    $response['avisos'] = $erros;
}

json($response, 201);

<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$turmaId = isset($input['turma_id']) ? (int) $input['turma_id'] : 0;
$dataAula = trim($input['data_aula'] ?? '');
$conteudoMinistrado = trim($input['conteudo_ministrado'] ?? '');
$observacao = trim($input['observacao'] ?? '');
$presencas = $input['presencas'] ?? [];

$erros = [];

if ($turmaId <= 0) {
    $erros[] = 'turma_id é obrigatório.';
}

if ($dataAula === '') {
    $erros[] = 'data_aula é obrigatória.';
}

if ($conteudoMinistrado === '') {
    $erros[] = 'conteudo_ministrado é obrigatório.';
}

if (!is_array($presencas)) {
    $erros[] = 'presencas deve ser um array.';
}

if ($erros) {
    json(['error' => 'Dados inválidos.', 'detalhes' => $erros], 422);
}

if (!professorPodeTurma($user, $turmaId)) {
    json(['error' => 'Acesso negado a esta turma.'], 403);
}

$stmt = $pdo->prepare("SELECT id, status FROM lhs_turmas WHERE id = ?");
$stmt->execute([$turmaId]);
$turma = $stmt->fetch();

if (!$turma) {
    json(['error' => 'Turma não encontrada.'], 404);
}

$stmtCheck = $pdo->prepare("SELECT id FROM lhs_aulas WHERE turma_id = ? AND data_aula = ?");
$stmtCheck->execute([$turmaId, $dataAula]);
if ($stmtCheck->fetch()) {
    json(['error' => 'Já existe uma aula registrada para esta turma nesta data.'], 409);
}

$stmtAlunos = $pdo->prepare("SELECT aluno_id FROM lhs_turma_alunos WHERE turma_id = ?");
$stmtAlunos->execute([$turmaId]);
$alunosMatriculados = $stmtAlunos->fetchAll(PDO::FETCH_COLUMN);

if (empty($alunosMatriculados)) {
    json(['error' => 'Não há alunos matriculados nesta turma.'], 422);
}

$pdo->beginTransaction();

try {
    $stmtAula = $pdo->prepare("
        INSERT INTO lhs_aulas (turma_id, data_aula, conteudo_ministrado, observacao, registrado_por)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtAula->execute([
        $turmaId,
        $dataAula,
        $conteudoMinistrado,
        $observacao ?: null,
        $user['id']
    ]);
    
    $aulaId = (int) $pdo->lastInsertId();
    
    $presencasMap = [];
    foreach ($presencas as $p) {
        if (isset($p['aluno_id'])) {
            $presencasMap[(int)$p['aluno_id']] = !empty($p['presente']);
        }
    }
    
    $stmtPresenca = $pdo->prepare("
        INSERT INTO lhs_presencas (aula_id, aluno_id, presente, registrado_por)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($alunosMatriculados as $alunoId) {
        $presente = $presencasMap[(int)$alunoId] ?? false;
        $stmtPresenca->execute([
            $aulaId,
            $alunoId,
            $presente ? 1 : 0,
            $user['id']
        ]);
    }
    
    $pdo->commit();
    
    $stmtResult = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id AND presente = 1) AS total_presentes,
               (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id) AS total_alunos
        FROM lhs_aulas a WHERE a.id = ?
    ");
    $stmtResult->execute([$aulaId]);
    $aula = $stmtResult->fetch(PDO::FETCH_ASSOC);
    
    json([
        'ok' => true,
        'message' => 'Aula registrada com sucesso.',
        'aula' => [
            'id' => (int) $aula['id'],
            'turma_id' => (int) $aula['turma_id'],
            'data_aula' => $aula['data_aula'],
            'conteudo_ministrado' => $aula['conteudo_ministrado'],
            'observacao' => $aula['observacao'],
            'registrado_por' => (int) $aula['registrado_por'],
            'total_presentes' => (int) $aula['total_presentes'],
            'total_alunos' => (int) $aula['total_alunos'],
            'criado_em' => $aula['criado_em'],
        ],
    ], 201);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao registrar aula.'], 500);
}

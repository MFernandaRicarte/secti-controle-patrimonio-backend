<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$turmaId = isset($input['turma_id']) ? (int)$input['turma_id'] : 0;
$dataAula = trim($input['data_aula'] ?? '');
$conteudoMinistrado = trim($input['conteudo_ministrado'] ?? '');
$observacao = trim($input['observacao'] ?? '');
$registradoPor = isset($input['registrado_por']) ? (int)$input['registrado_por'] : null;
$presencas = $input['presencas'] ?? [];

$erros = [];

if ($turmaId <= 0) {
    $erros[] = 'turma_id é obrigatório';
}
if ($dataAula === '') {
    $erros[] = 'data_aula é obrigatória';
}
if ($conteudoMinistrado === '') {
    $erros[] = 'conteudo_ministrado é obrigatório';
}
if (empty($presencas) || !is_array($presencas)) {
    $erros[] = 'presencas é obrigatório e deve conter a lista de alunos';
}

if ($erros) {
    json(['error' => 'Dados inválidos', 'detalhes' => $erros], 422);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id, status FROM lhs_turmas WHERE id = ?");
$stmt->execute([$turmaId]);
$turma = $stmt->fetch();

if (!$turma) {
    json(['error' => 'Turma não encontrada'], 404);
}

$stmt = $pdo->prepare("SELECT id FROM lhs_aulas WHERE turma_id = ? AND data_aula = ?");
$stmt->execute([$turmaId, $dataAula]);
if ($stmt->fetch()) {
    json(['error' => 'Já existe uma aula registrada para esta turma nesta data'], 409);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO lhs_aulas (turma_id, data_aula, conteudo_ministrado, observacao, registrado_por)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$turmaId, $dataAula, $conteudoMinistrado, $observacao ?: null, $registradoPor]);
    $aulaId = (int)$pdo->lastInsertId();

    $stmtPresenca = $pdo->prepare("
        INSERT INTO lhs_presencas (aula_id, aluno_id, presente, registrado_por)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($presencas as $p) {
        $alunoId = isset($p['aluno_id']) ? (int)$p['aluno_id'] : 0;
        $presente = isset($p['presente']) ? (bool)$p['presente'] : false;
        
        if ($alunoId > 0) {
            $stmtPresenca->execute([$aulaId, $alunoId, $presente ? 1 : 0, $registradoPor]);
        }
    }

    $pdo->commit();

    $stmt = $pdo->prepare("
        SELECT a.*, t.nome AS turma_nome,
               (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id AND presente = TRUE) AS total_presentes,
               (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id) AS total_alunos
        FROM lhs_aulas a
        INNER JOIN lhs_turmas t ON t.id = a.turma_id
        WHERE a.id = ?
    ");
    $stmt->execute([$aulaId]);
    $aula = $stmt->fetch();

    json([
        'ok' => true,
        'aula' => [
            'id' => (int)$aula['id'],
            'turma_id' => (int)$aula['turma_id'],
            'turma_nome' => $aula['turma_nome'],
            'data_aula' => $aula['data_aula'],
            'conteudo_ministrado' => $aula['conteudo_ministrado'],
            'observacao' => $aula['observacao'],
            'total_presentes' => (int)$aula['total_presentes'],
            'total_alunos' => (int)$aula['total_alunos'],
            'criado_em' => $aula['criado_em'],
        ]
    ], 201);

} catch (Exception $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao registrar aula: ' . $e->getMessage()], 500);
}

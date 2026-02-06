<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido. Use PUT ou PATCH.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da aula não informado.'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM lhs_aulas WHERE id = ?");
$stmt->execute([$id]);
$aulaExistente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aulaExistente) {
    json(['error' => 'Aula não encontrada.'], 404);
}

if (!professorPodeTurma($user, (int)$aulaExistente['turma_id'])) {
    json(['error' => 'Acesso negado a esta aula.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$conteudoMinistrado = isset($input['conteudo_ministrado']) ? trim($input['conteudo_ministrado']) : $aulaExistente['conteudo_ministrado'];
$observacao = isset($input['observacao']) ? trim($input['observacao']) : $aulaExistente['observacao'];
$presencas = $input['presencas'] ?? null;

if ($conteudoMinistrado === '') {
    json(['error' => 'conteudo_ministrado é obrigatório.'], 422);
}

$pdo->beginTransaction();

try {
    $stmtUpdate = $pdo->prepare("
        UPDATE lhs_aulas 
        SET conteudo_ministrado = ?, observacao = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$conteudoMinistrado, $observacao ?: null, $id]);
    
    if (is_array($presencas)) {
        foreach ($presencas as $p) {
            if (isset($p['aluno_id'])) {
                $presente = !empty($p['presente']) ? 1 : 0;
                $stmtPresenca = $pdo->prepare("
                    UPDATE lhs_presencas 
                    SET presente = ?, registrado_por = ?
                    WHERE aula_id = ? AND aluno_id = ?
                ");
                $stmtPresenca->execute([$presente, $user['id'], $id, $p['aluno_id']]);
            }
        }
    }
    
    $pdo->commit();
    
    $stmtResult = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id AND presente = 1) AS total_presentes,
               (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id) AS total_alunos
        FROM lhs_aulas a WHERE a.id = ?
    ");
    $stmtResult->execute([$id]);
    $aula = $stmtResult->fetch(PDO::FETCH_ASSOC);
    
    json([
        'ok' => true,
        'message' => 'Aula atualizada com sucesso.',
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
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao atualizar aula.'], 500);
}

<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? 0;

if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    json(['error' => 'JSON inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM lhs_aulas WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Aula não encontrada'], 404);
}

$conteudoMinistrado = trim($input['conteudo_ministrado'] ?? '');
$observacao = trim($input['observacao'] ?? '');
$presencas = $input['presencas'] ?? null;
$registradoPor = isset($input['registrado_por']) ? (int)$input['registrado_por'] : null;

if ($conteudoMinistrado === '') {
    json(['error' => 'conteudo_ministrado é obrigatório'], 422);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE lhs_aulas 
        SET conteudo_ministrado = ?, observacao = ?, registrado_por = COALESCE(?, registrado_por)
        WHERE id = ?
    ");
    $stmt->execute([$conteudoMinistrado, $observacao ?: null, $registradoPor, $id]);

    if (is_array($presencas)) {
        foreach ($presencas as $p) {
            $alunoId = isset($p['aluno_id']) ? (int)$p['aluno_id'] : 0;
            $presente = isset($p['presente']) ? (bool)$p['presente'] : false;
            
            if ($alunoId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE lhs_presencas 
                    SET presente = ?, registrado_por = COALESCE(?, registrado_por)
                    WHERE aula_id = ? AND aluno_id = ?
                ");
                $stmt->execute([$presente ? 1 : 0, $registradoPor, $id, $alunoId]);
            }
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
    $stmt->execute([$id]);
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
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao atualizar aula: ' . $e->getMessage()], 500);
}

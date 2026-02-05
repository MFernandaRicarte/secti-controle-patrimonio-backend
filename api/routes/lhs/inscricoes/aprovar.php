<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use PUT ou POST.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da inscrição não informado.'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM lhs_inscricoes WHERE id = ?");
$stmt->execute([$id]);
$inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscricao) {
    json(['error' => 'Inscrição não encontrada.'], 404);
}

if ($inscricao['status'] !== 'pendente') {
    json(['error' => 'Apenas inscrições pendentes podem ser aprovadas.'], 422);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$turmaId = isset($input['turma_id']) ? (int) $input['turma_id'] : 0;

if ($turmaId <= 0) {
    json(['error' => 'turma_id é obrigatório para aprovação.'], 422);
}

$stmtTurma = $pdo->prepare("
    SELECT t.*, c.nome AS curso_nome 
    FROM lhs_turmas t
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    WHERE t.id = ? AND t.curso_id = ?
");
$stmtTurma->execute([$turmaId, $inscricao['curso_id']]);
$turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    json(['error' => 'Turma não encontrada ou não pertence a este curso.'], 404);
}

if ($turma['status'] !== 'aberta' && $turma['status'] !== 'em_andamento') {
    json(['error' => 'Esta turma não está aberta para matrículas.'], 422);
}

$pdo->beginTransaction();

try {
    $stmtAluno = $pdo->prepare("SELECT id FROM lhs_alunos WHERE cpf = ?");
    $stmtAluno->execute([$inscricao['cpf']]);
    $alunoExistente = $stmtAluno->fetch();
    
    if ($alunoExistente) {
        $alunoId = (int) $alunoExistente['id'];
        
        $stmtUpdate = $pdo->prepare("
            UPDATE lhs_alunos 
            SET nome = ?, telefone = ?, email = ?, endereco = ?, ativo = 1
            WHERE id = ?
        ");
        $stmtUpdate->execute([
            $inscricao['nome'],
            $inscricao['telefone'],
            $inscricao['email'],
            $inscricao['endereco'],
            $alunoId
        ]);
    } else {
        $stmtInsertAluno = $pdo->prepare("
            INSERT INTO lhs_alunos (nome, cpf, telefone, email, endereco, ativo)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmtInsertAluno->execute([
            $inscricao['nome'],
            $inscricao['cpf'],
            $inscricao['telefone'],
            $inscricao['email'],
            $inscricao['endereco']
        ]);
        $alunoId = (int) $pdo->lastInsertId();
    }
    
    $stmtCheckMatricula = $pdo->prepare("
        SELECT id FROM lhs_turma_alunos WHERE turma_id = ? AND aluno_id = ?
    ");
    $stmtCheckMatricula->execute([$turmaId, $alunoId]);
    
    if (!$stmtCheckMatricula->fetch()) {
        $stmtMatricula = $pdo->prepare("
            INSERT INTO lhs_turma_alunos (turma_id, aluno_id, status)
            VALUES (?, ?, 'matriculado')
        ");
        $stmtMatricula->execute([$turmaId, $alunoId]);
    }
    
    $stmtApprove = $pdo->prepare("
        UPDATE lhs_inscricoes 
        SET status = 'aprovado', 
            aprovado_por = ?, 
            aluno_id = ?,
            turma_preferencia_id = ?
        WHERE id = ?
    ");
    $stmtApprove->execute([$user['id'], $alunoId, $turmaId, $id]);
    
    $pdo->commit();
    
    json([
        'ok' => true,
        'message' => 'Inscrição aprovada e aluno matriculado com sucesso.',
        'aluno_id' => $alunoId,
        'turma_id' => $turmaId,
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao aprovar inscrição.'], 500);
}

<?php
/**
 * PUT /api/lhs/inscricoes/{id}/aprovar
 * Aprova uma inscrição, cria/reutiliza aluno e matricula na turma.
 * Endpoint administrativo.
 */

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

$inscricaoId = $GLOBALS['routeParams']['id'] ?? 0;

if ($inscricaoId <= 0) {
    json(['error' => 'ID da inscrição inválido.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$turmaId = isset($input['turma_id']) ? (int) $input['turma_id'] : 0;

if ($turmaId <= 0) {
    json(['error' => 'turma_id é obrigatório para aprovar a inscrição.'], 422);
    exit;
}

// Buscar inscrição
try {
    $stmt = $pdo->prepare("SELECT * FROM lhs_inscricoes WHERE id = :id");
    $stmt->execute([':id' => $inscricaoId]);
    $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inscricao) {
        json(['error' => 'Inscrição não encontrada.'], 404);
        exit;
    }

    if ($inscricao['status'] !== 'pendente') {
        json(['error' => 'Esta inscrição já foi processada.'], 409);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar inscrição.'], 500);
    exit;
}

// Verificar se turma existe e pertence ao mesmo curso
try {
    $stmt = $pdo->prepare("SELECT * FROM lhs_turmas WHERE id = :id");
    $stmt->execute([':id' => $turmaId]);
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        json(['error' => 'Turma não encontrada.'], 404);
        exit;
    }

    if ((int) $turma['curso_id'] !== (int) $inscricao['curso_id']) {
        json(['error' => 'A turma selecionada não pertence ao curso da inscrição.'], 422);
        exit;
    }
} catch (PDOException $e) {
    json(['error' => 'Erro ao verificar turma.'], 500);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar se já existe aluno com mesmo CPF
    $stmt = $pdo->prepare("SELECT id FROM lhs_alunos WHERE cpf = :cpf");
    $stmt->execute([':cpf' => $inscricao['cpf']]);
    $alunoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    $alunoId = null;

    if ($alunoExistente) {
        // Reutilizar aluno existente
        $alunoId = (int) $alunoExistente['id'];
    } else {
        // Criar novo aluno
        $stmt = $pdo->prepare("
            INSERT INTO lhs_alunos (nome, cpf, telefone, email, endereco, ativo)
            VALUES (:nome, :cpf, :telefone, :email, :endereco, 1)
        ");
        $stmt->execute([
            ':nome' => $inscricao['nome'],
            ':cpf' => $inscricao['cpf'],
            ':telefone' => $inscricao['telefone'],
            ':email' => $inscricao['email'],
            ':endereco' => $inscricao['endereco'],
        ]);
        $alunoId = (int) $pdo->lastInsertId();
    }

    // Verificar se aluno já está matriculado nesta turma
    $stmt = $pdo->prepare("
        SELECT id FROM lhs_turma_alunos 
        WHERE turma_id = :turma_id AND aluno_id = :aluno_id
    ");
    $stmt->execute([':turma_id' => $turmaId, ':aluno_id' => $alunoId]);

    if (!$stmt->fetch()) {
        // Matricular aluno na turma
        $stmt = $pdo->prepare("
            INSERT INTO lhs_turma_alunos (turma_id, aluno_id, status)
            VALUES (:turma_id, :aluno_id, 'matriculado')
        ");
        $stmt->execute([':turma_id' => $turmaId, ':aluno_id' => $alunoId]);
    }

    // Atualizar inscrição
    $stmt = $pdo->prepare("
        UPDATE lhs_inscricoes 
        SET status = 'aprovado', aluno_id = :aluno_id, turma_id = :turma_id
        WHERE id = :id
    ");
    $stmt->execute([
        ':aluno_id' => $alunoId,
        ':turma_id' => $turmaId,
        ':id' => $inscricaoId,
    ]);

    $pdo->commit();

    // Buscar inscrição atualizada com joins
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            c.nome AS curso_nome,
            t.nome AS turma_nome,
            a.nome AS aluno_nome
        FROM lhs_inscricoes i
        LEFT JOIN lhs_cursos c ON c.id = i.curso_id
        LEFT JOIN lhs_turmas t ON t.id = i.turma_id
        LEFT JOIN lhs_alunos a ON a.id = i.aluno_id
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $inscricaoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $resultado = [
        'id' => (int) $row['id'],
        'curso_id' => (int) $row['curso_id'],
        'curso_nome' => $row['curso_nome'],
        'nome' => $row['nome'],
        'cpf' => $row['cpf'],
        'status' => $row['status'],
        'aluno_id' => (int) $row['aluno_id'],
        'aluno_nome' => $row['aluno_nome'],
        'turma_id' => (int) $row['turma_id'],
        'turma_nome' => $row['turma_nome'],
        'mensagem' => 'Inscrição aprovada! Aluno matriculado com sucesso.',
    ];

    json($resultado);
} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao aprovar inscrição.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

<?php
/**
 * GET /api/lhs/inscricoes
 * Lista todas as inscrições com filtro opcional por status.
 * Endpoint administrativo.
 */

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$statusFiltro = isset($_GET['status']) ? trim($_GET['status']) : null;
$statusValidos = ['pendente', 'aprovado', 'rejeitado'];

try {
    $sql = "
        SELECT 
            i.*,
            c.nome AS curso_nome,
            t.nome AS turma_nome,
            a.nome AS aluno_nome
        FROM lhs_inscricoes i
        LEFT JOIN lhs_cursos c ON c.id = i.curso_id
        LEFT JOIN lhs_turmas t ON t.id = i.turma_id
        LEFT JOIN lhs_alunos a ON a.id = i.aluno_id
    ";

    $params = [];

    if ($statusFiltro && in_array($statusFiltro, $statusValidos, true)) {
        $sql .= " WHERE i.status = :status";
        $params[':status'] = $statusFiltro;
    }

    $sql .= " ORDER BY i.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $inscricoes = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $inscricoes[] = [
            'id' => (int) $row['id'],
            'curso_id' => (int) $row['curso_id'],
            'curso_nome' => $row['curso_nome'],
            'nome' => $row['nome'],
            'cpf' => $row['cpf'],
            'telefone' => $row['telefone'],
            'email' => $row['email'],
            'endereco' => $row['endereco'],
            'status' => $row['status'],
            'motivo_rejeicao' => $row['motivo_rejeicao'],
            'aluno_id' => $row['aluno_id'] ? (int) $row['aluno_id'] : null,
            'aluno_nome' => $row['aluno_nome'],
            'turma_id' => $row['turma_id'] ? (int) $row['turma_id'] : null,
            'turma_nome' => $row['turma_nome'],
            'criado_em' => $row['criado_em'],
            'atualizado_em' => $row['atualizado_em'],
        ];
    }

    json($inscricoes);
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar inscrições.', 'detalhes' => $e->getMessage()], 500);
    exit;
}

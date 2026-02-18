<?php
/**
 * GET /api/lhs/inscricoes
 * Lista todas as inscrições com filtro opcional por status.
 * Endpoint administrativo.
 */

require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

$where = '';
$params = [];

if ($status !== '') {
    $where .= " AND i.status = :status";
    $params[':status'] = $status;
}

if ($cursoId > 0) {
    $where .= " AND i.curso_id = :curso_id";
    $params[':curso_id'] = $cursoId;
}

$sql = "
    SELECT
        i.*,
        c.nome AS curso_nome,
        t.nome AS turma_nome,
        u.nome AS aprovado_por_nome,
        a.nome AS aluno_nome
    FROM lhs_inscricoes i
    LEFT JOIN lhs_cursos c ON c.id = i.curso_id
    LEFT JOIN lhs_turmas t ON t.id = i.turma_preferencia_id
    LEFT JOIN usuarios u ON u.id = i.aprovado_por
    LEFT JOIN lhs_alunos a ON a.id = i.aluno_id
    WHERE 1=1 {$where}
    ORDER BY 
        CASE i.status WHEN 'pendente' THEN 0 WHEN 'aprovado' THEN 1 ELSE 2 END,
        i.criado_em DESC
    LIMIT 500
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$inscricoes = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'numero_protocolo' => $row['numero_protocolo'] ?? null,
        'curso_id' => (int) $row['curso_id'],
        'curso_nome' => $row['curso_nome'],
        'turma_preferencia_id' => $row['turma_preferencia_id'] ? (int) $row['turma_preferencia_id'] : null,
        'turma_nome' => $row['turma_nome'],
        'nome' => $row['nome'],
        'cpf' => $row['cpf'],
        'telefone' => $row['telefone'],
        'email' => $row['email'],
        'endereco' => $row['endereco'],
        'data_nascimento' => $row['data_nascimento'] ?? null,
        'escolaridade' => $row['escolaridade'] ?? null,
        'como_soube' => $row['como_soube'] ?? null,
        'turma_preferencia_horario' => $row['turma_preferencia_horario'] ?? null,
        'necessidades_especiais' => $row['necessidades_especiais'] ?? null,
        'status' => $row['status'],
        'status_texto' => match($row['status']) {
            'pendente' => 'Aguardando Análise',
            'aprovado' => 'Aprovado',
            'rejeitado' => 'Não Aprovado',
            default => $row['status'],
        },
        'motivo_rejeicao' => $row['motivo_rejeicao'],
        'aprovado_por' => $row['aprovado_por'] ? (int) $row['aprovado_por'] : null,
        'aprovado_por_nome' => $row['aprovado_por_nome'],
        'aluno_id' => $row['aluno_id'] ? (int) $row['aluno_id'] : null,
        'aluno_nome' => $row['aluno_nome'],
        'criado_em' => $row['criado_em'],
        'atualizado_em' => $row['atualizado_em'],
    ];
}, $rows);

json($inscricoes);

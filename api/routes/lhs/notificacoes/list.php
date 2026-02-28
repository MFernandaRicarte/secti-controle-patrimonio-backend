<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireLhsAdmin();
$pdo = db();

$apenasNaoLidas = isset($_GET['nao_lidas']) && $_GET['nao_lidas'] === '1';
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 100) : 50;

$where = '';
$params = [];

if ($apenasNaoLidas) {
    $where .= " AND n.lida = 0";
}

if ($tipo !== '') {
    $where .= " AND n.tipo = :tipo";
    $params[':tipo'] = $tipo;
}

$sql = "
    SELECT 
        n.*,
        t.nome AS turma_nome,
        c.nome AS curso_nome,
        a.nome AS aluno_nome
    FROM lhs_notificacoes n
    LEFT JOIN lhs_turmas t ON t.id = n.turma_id
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN lhs_alunos a ON a.id = n.aluno_id
    WHERE 1=1 {$where}
    ORDER BY n.lida ASC, n.criado_em DESC
    LIMIT {$limit}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notificacoes = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'tipo' => $row['tipo'],
        'turma_id' => $row['turma_id'] ? (int) $row['turma_id'] : null,
        'turma_nome' => $row['turma_nome'],
        'curso_nome' => $row['curso_nome'],
        'aluno_id' => $row['aluno_id'] ? (int) $row['aluno_id'] : null,
        'aluno_nome' => $row['aluno_nome'],
        'data_referencia' => $row['data_referencia'],
        'mensagem' => $row['mensagem'],
        'lida' => (bool) $row['lida'],
        'criado_em' => $row['criado_em'],
    ];
}, $rows);

json($notificacoes);

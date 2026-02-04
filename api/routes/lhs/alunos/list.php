<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use GET.']);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco.']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$apenasAtivos = isset($_GET['ativos']) && $_GET['ativos'] === '1';

$where = '';
$params = [];

if ($q !== '') {
    $where .= " AND (a.nome LIKE :q OR a.cpf LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if ($apenasAtivos) {
    $where .= " AND a.ativo = 1";
}

$sql = "
    SELECT
        a.id,
        a.nome,
        a.cpf,
        a.telefone,
        a.email,
        a.endereco,
        a.ativo,
        a.criado_em,
        (SELECT COUNT(*) FROM lhs_turma_alunos ta WHERE ta.aluno_id = a.id) AS total_turmas
    FROM lhs_alunos a
    WHERE 1=1 {$where}
    ORDER BY a.nome ASC
    LIMIT 200
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $alunos = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'cpf' => $row['cpf'],
            'telefone' => $row['telefone'],
            'email' => $row['email'],
            'endereco' => $row['endereco'],
            'ativo' => (bool) $row['ativo'],
            'total_turmas' => (int) $row['total_turmas'],
            'criado_em' => $row['criado_em'],
        ];
    }, $rows);

    echo json_encode($alunos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar alunos.']);
}

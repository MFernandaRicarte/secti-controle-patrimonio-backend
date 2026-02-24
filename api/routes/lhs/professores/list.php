<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

requireLhsAdmin();
$pdo = db();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = " WHERE UPPER(p.nome) = 'PROFESSOR'";
$params = [];

if ($q !== '') {
    $where .= " AND (u.nome LIKE :q OR u.email LIKE :q OR u.matricula LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

$sql = "
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.matricula,
        (SELECT COUNT(*) FROM lhs_professor_turmas pt WHERE pt.professor_id = u.id) AS total_turmas
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    {$where}
    ORDER BY u.nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = array_map(function ($p) {
    return [
        'id' => (int) $p['id'],
        'nome' => $p['nome'],
        'email' => $p['email'],
        'matricula' => $p['matricula'],
        'total_turmas' => (int) $p['total_turmas'],
    ];
}, $professores);

json($resultado);

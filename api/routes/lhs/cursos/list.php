<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

requireProfessorOrAdmin();
$pdo = db();

$sql = "
    SELECT
        c.id,
        c.nome,
        c.carga_horaria,
        c.ementa,
        c.ativo,
        c.criado_em,
        (SELECT COUNT(*) FROM lhs_materiais_didaticos WHERE curso_id = c.id) AS total_materiais
    FROM lhs_cursos c
    ORDER BY c.nome ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cursos = array_map(function ($row) {
    return [
        'id'             => (int)$row['id'],
        'nome'           => $row['nome'],
        'carga_horaria'  => (int)$row['carga_horaria'],
        'ementa'         => $row['ementa'],
        'ativo'          => (bool)$row['ativo'],
        'criado_em'      => $row['criado_em'],
        'total_materiais'=> (int)$row['total_materiais'],
    ];
}, $rows);

json($cursos);

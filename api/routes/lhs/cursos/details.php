<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int)$id;

if ($id <= 0) {
    json(['error' => 'ID inválido.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM lhs_turmas WHERE curso_id = c.id) AS total_turmas
    FROM lhs_cursos c
    WHERE c.id = ?
");
$stmt->execute([$id]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    json(['error' => 'Curso não encontrado.'], 404);
}

$stmt = $pdo->prepare("
    SELECT m.id, m.nome_arquivo, m.path, m.criado_em, u.nome AS uploaded_por_nome
    FROM lhs_materiais_didaticos m
    LEFT JOIN usuarios u ON u.id = m.uploaded_por
    WHERE m.curso_id = ?
    ORDER BY m.criado_em DESC
");
$stmt->execute([$id]);
$materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

json([
    'id'              => (int)$curso['id'],
    'nome'            => $curso['nome'],
    'carga_horaria'   => (int)$curso['carga_horaria'],
    'ementa'          => $curso['ementa'],
    'ativo'           => (bool)$curso['ativo'],
    'criado_em'       => $curso['criado_em'],
    'total_turmas'    => (int)$curso['total_turmas'],
    'materiais'       => array_map(function ($m) {
        return [
            'id'              => (int)$m['id'],
            'nome_arquivo'    => $m['nome_arquivo'],
            'path'            => $m['path'],
            'criado_em'       => $m['criado_em'],
            'uploaded_por'    => $m['uploaded_por_nome'],
        ];
    }, $materiais),
]);

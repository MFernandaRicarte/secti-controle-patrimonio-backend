<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido.'], 405);
}

requireLhsAdmin();

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int)$id;

if ($id <= 0) {
    json(['error' => 'ID inválido.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id FROM lhs_cursos WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Curso não encontrado.'], 404);
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM lhs_turmas WHERE curso_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    json(['error' => 'Não é possível excluir curso com turmas vinculadas.'], 409);
}

$stmt = $pdo->prepare('DELETE FROM lhs_cursos WHERE id = ?');
$stmt->execute([$id]);

json(['ok' => true]);

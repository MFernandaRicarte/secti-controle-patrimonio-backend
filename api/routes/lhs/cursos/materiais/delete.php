<?php
require __DIR__ . '/../../../../lib/db.php';
require __DIR__ . '/../../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido.'], 405);
}

$cursoId    = $GLOBALS['routeParams']['id'] ?? null;
$materialId = $GLOBALS['routeParams']['material_id'] ?? null;

$cursoId    = (int)$cursoId;
$materialId = (int)$materialId;

if ($cursoId <= 0 || $materialId <= 0) {
    json(['error' => 'IDs inválidos.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT path FROM lhs_materiais_didaticos WHERE id = ? AND curso_id = ?");
$stmt->execute([$materialId, $cursoId]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    json(['error' => 'Material não encontrado.'], 404);
}

$filePath = __DIR__ . '/../public' . $material['path'];
if (file_exists($filePath)) {
    unlink($filePath);
}

$stmt = $pdo->prepare('DELETE FROM lhs_materiais_didaticos WHERE id = ?');
$stmt->execute([$materialId]);

json(['ok' => true]);

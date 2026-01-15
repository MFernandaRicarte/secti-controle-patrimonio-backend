<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
  json(['error' => 'Method Not Allowed'], 405);
}

$u = requireSuperAdmin();

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) json(['error' => 'ID inválido'], 400);

$pdo = db();

$stmt = $pdo->prepare('SELECT id, excluido_em FROM bens_patrimoniais WHERE id = ?');
$stmt->execute([$id]);
$bem = $stmt->fetch();

if (!$bem) json(['error' => 'Bem não encontrado'], 404);
if (!empty($bem['excluido_em'])) json(['error' => 'Este bem já está excluído.'], 409);

$stmt = $pdo->prepare("
  UPDATE bens_patrimoniais
     SET excluido_em = NOW(),
         excluido_por_usuario_id = ?
   WHERE id = ?
");
$stmt->execute([(int)$u['id'], $id]);

json(['ok' => true]);
<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../lib/auth.php';
require __DIR__.'/../../lib/db.php';

cors();
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  json(['error' => 'Método não permitido'], 405);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json(['error' => 'ID inválido'], 400);

$pdo = db();
$st = $pdo->prepare("
  SELECT
    u.id, u.matricula, u.email, u.nome,
    u.perfil_id, p.nome AS perfil_nome,
    u.criado_em,
    u.data_nascimento, u.celular,
    u.cep, u.cidade, u.bairro, u.numero, u.complemento
  FROM usuarios u
  LEFT JOIN perfis p ON p.id = u.perfil_id
  WHERE u.id = ?
  LIMIT 1
");
$st->execute([$id]);
$u = $st->fetch();

if (!$u) json(['error' => 'Usuário não encontrado'], 404);
json($u);
<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../lib/auth.php';
require __DIR__.'/../../lib/db.php';

cors();
$usuario = requireSuperAdmin();
$criadorId = (int)$usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) json(['error' => 'JSON inválido'], 400);

$titulo = trim((string)($data['titulo'] ?? ''));
$mensagem = trim((string)($data['mensagem'] ?? ''));
$publico = strtoupper(trim((string)($data['publico'] ?? 'TODOS'))); // TODOS|SUPERADMIN|RESTRITO

if ($titulo === '' || $mensagem === '') json(['error' => 'Título e mensagem são obrigatórios'], 422);
if (!in_array($publico, ['TODOS','SUPERADMIN','RESTRITO'], true)) json(['error' => 'Público inválido'], 422);

try {
  $pdo = db();
  $pdo->beginTransaction();

  $st = $pdo->prepare("INSERT INTO notificacoes (titulo, mensagem, publico, criado_por_usuario_id) VALUES (?,?,?,?)");
  $st->execute([$titulo, $mensagem, $publico, $criadorId]);
  $notifId = (int)$pdo->lastInsertId();

  // Seleciona usuários alvo
  if ($publico === 'TODOS') {
    $stU = $pdo->query("SELECT id, perfil_id FROM usuarios");
  } elseif ($publico === 'SUPERADMIN') {
    $stU = $pdo->prepare("SELECT id, perfil_id FROM usuarios WHERE perfil_id = 2");
    $stU->execute();
  } else {
    $stU = $pdo->prepare("SELECT id, perfil_id FROM usuarios WHERE perfil_id = 3");
    $stU->execute();
  }

  $users = $stU->fetchAll(PDO::FETCH_ASSOC);

  $ins = $pdo->prepare("INSERT IGNORE INTO notificacoes_usuario (notificacao_id, usuario_id) VALUES (?,?)");
  foreach ($users as $u) {
    $ins->execute([$notifId, (int)$u['id']]);
  }

  $pdo->commit();

  json(['ok' => true, 'id' => $notifId]);
} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  json(['error' => $e->getMessage()], 500);
}
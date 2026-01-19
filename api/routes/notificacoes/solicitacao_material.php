<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/db.php';

cors();
$usuario = requireAuth();
$criadorId = (int)$usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) json(['error' => 'JSON inválido'], 400);

$item = trim((string)($data['item'] ?? ''));
$quantidade = (int)($data['quantidade'] ?? 0);
$setor_id = (int)($data['setor_id'] ?? 0);

if ($item === '') json(['error' => 'Item é obrigatório'], 422);
if ($quantidade <= 0) json(['error' => 'Quantidade inválida'], 422);
if ($setor_id <= 0) json(['error' => 'Setor inválido'], 422);

try {
  $pdo = db();

  $stS = $pdo->prepare("SELECT id, nome FROM setores WHERE id = ? LIMIT 1");
  $stS->execute([$setor_id]);
  $setor = $stS->fetch(PDO::FETCH_ASSOC);
  if (!$setor) json(['error' => 'Setor não encontrado'], 404);

  $pdo->beginTransaction();

  $titulo = "Solicitação de material";
  $mensagem = "Item: {$item}\nQuantidade: {$quantidade}\nSetor: {$setor['nome']}\nSolicitante: {$usuario['nome']}";

  $publico = "SUPERADMIN";

  $st = $pdo->prepare("
    INSERT INTO notificacoes (titulo, mensagem, publico, criado_por_usuario_id)
    VALUES (?,?,?,?)
  ");
  $st->execute([$titulo, $mensagem, $publico, $criadorId]);

  $notifId = (int)$pdo->lastInsertId();

  $stU = $pdo->prepare("SELECT id FROM usuarios WHERE perfil_id = 2");
  $stU->execute();
  $users = $stU->fetchAll(PDO::FETCH_ASSOC);

  $ins = $pdo->prepare("
    INSERT IGNORE INTO notificacoes_usuario (notificacao_id, usuario_id)
    VALUES (?,?)
  ");
  foreach ($users as $u) {
    $ins->execute([$notifId, (int)$u['id']]);
  }

  $pdo->commit();

  json(['ok' => true, 'id' => $notifId]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  json(['error' => $e->getMessage()], 500);
}
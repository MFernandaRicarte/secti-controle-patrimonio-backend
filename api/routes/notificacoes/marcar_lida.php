<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../lib/auth.php';
require __DIR__.'/../../lib/db.php';

cors();
$usuario = requireAuth();
$userId = (int)$usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) json(['error' => 'JSON inválido'], 400);

$notifId = $data['notificacao_id'] ?? null;
if ($notifId === null || $notifId === '') json(['error' => 'notificacao_id é obrigatório'], 422);

if (is_string($notifId) && str_starts_with($notifId, 'birthday-')) {
  json(['ok' => true]);
}

try {
  $pdo = db();
  $st = $pdo->prepare("
    UPDATE notificacoes_usuario
    SET lida_em = NOW()
    WHERE notificacao_id = ? AND usuario_id = ?
  ");
  $st->execute([(int)$notifId, $userId]);

  json(['ok' => true]);
} catch (Throwable $e) {
  json(['error' => $e->getMessage()], 500);
}
<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  json(['error' => 'Método não permitido'], 405);
}

$user = requireAuth();
$perfil = strtoupper($user['perfil_nome'] ?? '');

if (!in_array($perfil, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMIN_LANHOUSE'])) {
    json(['error' => 'Acesso negado'], 403);
}

try {
  $pdo = db();
  if ($perfil === 'ADMIN_LANHOUSE') {
    $st = $pdo->prepare("SELECT id, nome, descricao FROM perfis WHERE UPPER(nome) = 'PROFESSOR' ORDER BY id ASC");
    $st->execute();
  } else {
    $st = $pdo->query("SELECT id, nome, descricao FROM perfis ORDER BY id ASC");
  }
  $rows = $st->fetchAll();
  json($rows);
} catch (Throwable $e) {
  json(['error' => $e->getMessage()], 500);
}
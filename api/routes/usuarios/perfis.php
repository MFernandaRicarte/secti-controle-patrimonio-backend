<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  json(['error' => 'Método não permitido'], 405);
}

requireSuperAdmin();

try {
  $pdo = db();
  $st = $pdo->query("SELECT id, nome, descricao FROM perfis ORDER BY id ASC");
  $rows = $st->fetchAll();
  json($rows);
} catch (Throwable $e) {
  json(['error' => $e->getMessage()], 500);
}
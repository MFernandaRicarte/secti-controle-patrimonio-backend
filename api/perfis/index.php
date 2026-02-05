<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

cors();
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  json(['error' => 'Método não permitido'], 405);
}

$pdo = db();
$st = $pdo->query("SELECT id, nome FROM perfis ORDER BY id ASC");
json($st->fetchAll());
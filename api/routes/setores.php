<?php
require __DIR__ . '/../lib/http.php';
require __DIR__ . '/../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

try {
    $pdo = db();

    $sql = "SELECT id, nome 
              FROM setores
          ORDER BY nome";

    $rows = $pdo->query($sql)->fetchAll();

    json($rows);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}

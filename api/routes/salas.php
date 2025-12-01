<?php
require __DIR__ . '/../lib/http.php';
require __DIR__ . '/../config/config.php';

cors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

try {
    $pdo = db();

    $sql = "
        SELECT id, nome, setor_id
        FROM salas
        ORDER BY nome
    ";
    $stmt = $pdo->query($sql);
    $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json($salas);
} catch (Throwable $e) {
    json(['error' => 'Erro ao buscar salas'], 500);
}
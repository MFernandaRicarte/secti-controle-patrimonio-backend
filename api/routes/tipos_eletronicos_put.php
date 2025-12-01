<?php
require __DIR__.'/../lib/http.php';
require __DIR__.'/../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido'], 405);
}

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json(['error' => 'JSON inválido'], 400);
}

$nome = trim($input['nome'] ?? '');
if ($nome === '') {
    json(['error' => 'nome é obrigatório'], 422);
}

try {
    $pdo = db();

    $st = $pdo->prepare('SELECT id FROM tipos_eletronicos WHERE id = ?');
    $st->execute([$id]);
    if (!$st->fetch()) {
        json(['error' => 'Registro não encontrado'], 404);
    }

    $st = $pdo->prepare('SELECT id FROM tipos_eletronicos WHERE nome = ? AND id <> ?');
    $st->execute([$nome, $id]);
    if ($st->fetch()) {
        json(['error' => 'Já existe outro tipo com esse nome'], 409);
    }

    $upd = $pdo->prepare('UPDATE tipos_eletronicos SET nome = ? WHERE id = ?');
    $upd->execute([$nome, $id]);

    $st = $pdo->prepare('SELECT id, nome, criado_em FROM tipos_eletronicos WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    json($row, 200);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
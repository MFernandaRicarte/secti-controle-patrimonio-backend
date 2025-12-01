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

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    json(['error' => 'JSON inválido'], 400);
}

$nome = trim($data['nome'] ?? '');
if ($nome === '') {
    json(['error' => 'Nome é obrigatório'], 400);
}

try {
    $pdo = db();
    $st = $pdo->prepare("SELECT id FROM salas WHERE nome = ? AND id <> ?");
    $st->execute([$nome, $id]);
    if ($st->fetch()) {
        json(['error' => 'Já existe sala com esse nome'], 409);
    }

    $upd = $pdo->prepare("UPDATE salas SET nome = ? WHERE id = ?");
    $upd->execute([$nome, $id]);

    $st = $pdo->prepare("SELECT id, nome, setor_id, criado_em FROM salas WHERE id = ?");
    $st->execute([$id]);
    $sala = $st->fetch(PDO::FETCH_ASSOC);

    json($sala ?: ['error' => 'Sala não encontrada'], $sala ? 200 : 404);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}